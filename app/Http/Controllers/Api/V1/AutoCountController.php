<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceCategory;
use App\Models\ProductType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Inbound endpoints for the AutoCount desktop plugin (snoodle-autocount).
 *
 * The plugin reads the AutoCount Item master + base-UOM selling price and
 * pushes it here; this controller upserts it into the OMS `products` table.
 * Sync direction is AutoCount -> OMS, triggered manually from a menu button
 * inside AutoCount.
 *
 * Protected by a shared secret: the request must carry an X-AutoCount-Token
 * header equal to services.autocount.token (AUTOCOUNT_API_TOKEN in .env,
 * matching OmsApiToken in the plugin's App.config). When that value is empty
 * the endpoints are disabled entirely.
 *
 * Every call is traced to the `autocount` log channel
 * (storage/logs/autocount-YYYY-MM-DD.log) — what was received + what was
 * returned — including rejected/token-failed attempts.
 */
class AutoCountController extends Controller
{
    // Default payment term for customers synced from AutoCount (AutoCount's
    // credit term does not map cleanly to the OMS enum). 2 = Credit.
    private const DEFAULT_PAYMENT_TERM = 2;

    // How many pending invoices the plugin may claim per 30s poll.
    private const INVOICE_PULL_LIMIT = 20;

    // A "syncing" invoice older than this is treated as stuck (plugin crashed
    // mid-sync) and re-claimed on the next poll. Generous vs a seconds-long sync.
    private const SYNC_STUCK_MINUTES = 15;

    private function authorized(Request $request): bool
    {
        $token = config('services.autocount.token');

        return !empty($token) && hash_equals($token, (string) $request->header('X-AutoCount-Token'));
    }

    private function dryRun(): bool
    {
        return filter_var(config('services.autocount.dry_run'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Upsert products + base pricing pushed from AutoCount.
     *
     * Body: { "products": [ { code, name, price, status?, classification_code?, item_group? }, ... ] }
     * Matched by `code` (AutoCount ItemCode). On update only the synced columns
     * change, so OMS-owned fields are preserved — except `type_id`, which now
     * follows AutoCount's Item Group when one is sent (image_path stays
     * OMS-owned).
     */
    public function syncProducts(Request $request)
    {
        $received = is_array($request->input('products')) ? count($request->input('products')) : 0;

        $this->log($request, 'products', 'RECV', [
            'received_count' => $received,
        ]);

        if (!$this->authorized($request)) {
            $payload = ['result' => false, 'message' => 'Unauthorized.'];
            $this->log($request, 'products', 'SENT', ['status' => 403, 'message' => 'Token rejected.'], 'warning');

            return response()->json($payload, 403);
        }

        // Dry run: log exactly what arrived and return without touching the DB,
        // so a developer can inspect the plugin's payload on a live server.
        if ($this->dryRun()) {
            $this->log($request, 'products', 'DRYRUN', [
                'received_count' => $received,
                'products' => $request->input('products'),
                'note' => 'Dry run — logged only, no products written.',
            ]);

            return response()->json([
                'result' => true,
                'dry_run' => true,
                'received' => $received,
                'created' => 0,
                'updated' => 0,
                'message' => 'Dry run: request logged, no changes applied.',
            ]);
        }

        // The plugin's pricing field was renamed product_type -> price_category.
        // Accept the legacy name as a fallback so a not-yet-rebuilt plugin keeps
        // syncing, then validate/read the canonical product_price_category key.
        $this->normalizeProductPriceCategoryKeys($request);

        $validator = Validator::make($request->all(), [
            'products' => ['required', 'array', 'min:1'],
            'products.*.code' => ['required', 'string', 'max:255'],
            'products.*.name' => ['required', 'string', 'max:255'],
            // Nullable: null = AutoCount has no base-UOM price, so the existing
            // OMS price is kept rather than overwritten.
            'products.*.price' => ['nullable', 'numeric'],
            'products.*.status' => ['nullable', 'integer'],
            'products.*.classification_code' => ['nullable', 'string', 'max:255'],
            // AutoCount Item Group -> OMS product type (classification). When
            // non-empty it drives products.type_id (created by name if missing);
            // blank/absent leaves the product's type untouched. Distinct from
            // the price categories below (customer pricing tier).
            'products.*.item_group' => ['nullable', 'string', 'max:255'],
            // Per-price-category prices. Absent = leave the product's category
            // prices untouched; present (even empty) = full-replace them. The
            // price category is matched by name and created if it does not exist.
            'products.*.prices' => ['sometimes', 'array'],
            'products.*.prices.*.product_price_category' => ['required', 'string', 'max:255'],
            'products.*.prices.*.price' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            $payload = [
                'result' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ];
            $this->log($request, 'products', 'SENT', [
                'status' => 400,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()->all(),
            ], 'warning');

            return response()->json($payload, 400);
        }

        // Legacy default category so a freshly-synced product is never
        // uncategorised (every existing product points at "Noodle").
        $defaultTypeId = ProductType::query()->orderBy('id')->value('id');

        // Price-category name -> id, filled lazily across the batch so a category
        // is resolved (or created) once, not per product.
        $categoryCache = [];

        // Item-group name -> product_types.id, resolved (or created) once per
        // batch so repeated item groups are not re-queried per product.
        $typeCache = [];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($request->input('products') as $row) {
            $code = trim($row['code']);
            if ($code === '') {
                continue;
            }

            // Inactive (discontinued) items must not be created in the OMS: they
            // cannot be invoiced, so a newly-synced inactive product could only
            // clutter the catalogue with lines that can never sync back on an
            // order. An already-existing product still falls through to upsert, so
            // a discontinuation in AutoCount is reflected (status -> 0).
            if (isset($row['status']) && (int) $row['status'] === 0
                && !Product::where('code', $code)->exists()) {
                $skipped++;
                continue;
            }

            // Guard per-item: one bad row logs a traceable reason and is skipped,
            // instead of 500-ing the whole batch.
            try {
                if ($this->upsertProduct($code, $row, $defaultTypeId, $categoryCache, $typeCache)) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::channel('autocount')->warning('[products] upsert FAILED', [
                    'code' => $code,
                    'name' => $row['name'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payload = [
            'result' => true,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => $created + $updated,
        ];
        $this->log($request, 'products', 'SENT', [
            'status' => 200,
            'received_count' => $received,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        return response()->json($payload);
    }

    /**
     * Upsert customers pushed from the AutoCount Debtor master.
     *
     * Body: { "customers": [ { code, company, phone?, address?, email?,
     *         postcode?, registration_no?, sst_registration_no?, status? }, ... ] }
     * Matched by `code` (AutoCount AccNo). Only fields AutoCount actually
     * provides are written; everything else is left empty on create or
     * untouched on update. OMS-owned fields (agent_id, supervisor_id,
     * is_do_customer, paymentterm) are never overwritten by the sync.
     */
    public function syncCustomers(Request $request)
    {
        $received = is_array($request->input('customers')) ? count($request->input('customers')) : 0;

        $this->log($request, 'customers', 'RECV', [
            'received_count' => $received,
        ]);

        if (!$this->authorized($request)) {
            $payload = ['result' => false, 'message' => 'Unauthorized.'];
            $this->log($request, 'customers', 'SENT', ['status' => 403, 'message' => 'Token rejected.'], 'warning');

            return response()->json($payload, 403);
        }

        if ($this->dryRun()) {
            $this->log($request, 'customers', 'DRYRUN', [
                'received_count' => $received,
                'customers' => $request->input('customers'),
                'note' => 'Dry run — logged only, no customers written.',
            ]);

            return response()->json([
                'result' => true,
                'dry_run' => true,
                'received' => $received,
                'created' => 0,
                'updated' => 0,
                'message' => 'Dry run: request logged, no changes applied.',
            ]);
        }

        // Accept the legacy product_type key as a fallback for the renamed
        // product_price_category field (see normalizeProductPriceCategoryKeys).
        $this->normalizeProductPriceCategoryKeys($request);

        $validator = Validator::make($request->all(), [
            'customers' => ['required', 'array', 'min:1'],
            'customers.*.code' => ['required', 'string', 'max:255'],
            'customers.*.company' => ['required', 'string', 'max:255'],
            'customers.*.phone' => ['nullable', 'string', 'max:255'],
            'customers.*.address' => ['nullable', 'string'],
            'customers.*.email' => ['nullable', 'string', 'max:255'],
            'customers.*.postcode' => ['nullable', 'string', 'max:255'],
            'customers.*.registration_no' => ['nullable', 'string', 'max:255'],
            'customers.*.sst_registration_no' => ['nullable', 'string', 'max:255'],
            'customers.*.status' => ['nullable', 'integer'],
            // The price category this debtor is assigned to. Absent = leave
            // untouched; blank = clear (falls back to base price); a new name is
            // created as a price category.
            'customers.*.product_price_category' => ['nullable', 'string', 'max:255'],
            // AutoCount Debtor Type (Debtor.DebtorType). AutoCount-owned.
            'customers.*.debtor_type' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $payload = [
                'result' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ];
            $this->log($request, 'customers', 'SENT', [
                'status' => 400,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()->all(),
            ], 'warning');

            return response()->json($payload, 400);
        }

        // Price-category name -> id, filled lazily across the batch.
        $categoryCache = [];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($request->input('customers') as $row) {
            $code = trim($row['code']);
            if ($code === '') {
                continue;
            }

            // Inactive debtors must not be created in the OMS. AutoCount itself
            // refuses to post an invoice to an inactive debtor ("already inactive,
            // save aborted"), so a newly-synced inactive customer could only ever
            // raise orders that can never sync back. An already-existing customer
            // still falls through to upsert, so a deactivation in AutoCount is
            // reflected (status -> 0) rather than left stale.
            if (isset($row['status']) && (int) $row['status'] === 0
                && !Customer::where('code', $code)->exists()) {
                $skipped++;
                continue;
            }

            // Guard per-item: one bad row logs a traceable reason and is skipped,
            // instead of 500-ing the whole batch.
            try {
                if ($this->upsertCustomer($code, $row, $categoryCache)) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::channel('autocount')->warning('[customers] upsert FAILED', [
                    'code' => $code,
                    'company' => $row['company'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payload = [
            'result' => true,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => $created + $updated,
        ];
        $this->log($request, 'customers', 'SENT', [
            'status' => 200,
            'received_count' => $received,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        return response()->json($payload);
    }

    /**
     * Pull invoices the user flagged for sync. The plugin polls this every 30s.
     *
     * Claims up to INVOICE_PULL_LIMIT pending rows (sync_status 1 -> 2) in a
     * locked transaction, so a poll that overruns the 30s interval can't hand
     * the same invoice to the next poll and double-create it in AutoCount.
     * Returns each claimed invoice with its customer code and line items.
     */
    public function pendingInvoices(Request $request)
    {
        $this->log($request, 'invoices/pending', 'RECV', []);

        if (!$this->authorized($request)) {
            $this->log($request, 'invoices/pending', 'SENT', ['status' => 403, 'message' => 'Token rejected.'], 'warning');

            return response()->json(['result' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Dry run: show what WOULD be pulled without claiming anything.
        if ($this->dryRun()) {
            $ids = Invoice::where('sync_status', Invoice::SYNC_PENDING)
                ->orderBy('id')->limit(self::INVOICE_PULL_LIMIT)->pluck('id');
            $this->log($request, 'invoices/pending', 'DRYRUN', [
                'would_claim' => $ids->all(),
                'note' => 'Dry run — nothing claimed.',
            ]);

            return response()->json([
                'result' => true,
                'dry_run' => true,
                'invoices' => $this->buildInvoicePayload($ids->all()),
            ]);
        }

        // Claim atomically so concurrent polls never grab the same invoices.
        // Includes rows stuck in "syncing" (plugin crashed mid-sync) so they
        // self-recover instead of being orphaned.
        $claimedIds = DB::transaction(function () {
            $stuckBefore = now()->subMinutes(self::SYNC_STUCK_MINUTES);

            $ids = Invoice::where(function ($q) use ($stuckBefore) {
                    $q->where('sync_status', Invoice::SYNC_PENDING)
                        ->orWhere(function ($q2) use ($stuckBefore) {
                            $q2->where('sync_status', Invoice::SYNC_SYNCING)
                                ->where('updated_at', '<', $stuckBefore);
                        });
                })
                ->orderBy('id')
                ->limit(self::INVOICE_PULL_LIMIT)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if (!empty($ids)) {
                Invoice::whereIn('id', $ids)->update(['sync_status' => Invoice::SYNC_SYNCING]);
            }

            return $ids;
        });

        $invoices = $this->buildInvoicePayload($claimedIds);

        $this->log($request, 'invoices/pending', 'SENT', [
            'status' => 200,
            'claimed' => count($invoices),
        ]);

        return response()->json([
            'result' => true,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Write back the AutoCount result for each synced invoice.
     *
     * Body: { "results": [ { id, autocount_no?, success, error? }, ... ] }
     * success -> synced (3) + api_invoice_id; otherwise -> failed (4) + error.
     */
    public function syncInvoiceResults(Request $request)
    {
        $received = is_array($request->input('results')) ? count($request->input('results')) : 0;
        $this->log($request, 'invoices/result', 'RECV', ['received_count' => $received]);

        if (!$this->authorized($request)) {
            $this->log($request, 'invoices/result', 'SENT', ['status' => 403, 'message' => 'Token rejected.'], 'warning');

            return response()->json(['result' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($this->dryRun()) {
            $this->log($request, 'invoices/result', 'DRYRUN', [
                'received_count' => $received,
                'results' => $request->input('results'),
                'note' => 'Dry run — no status written.',
            ]);

            return response()->json(['result' => true, 'dry_run' => true, 'synced' => 0, 'failed' => 0]);
        }

        $validator = Validator::make($request->all(), [
            'results' => ['required', 'array', 'min:1'],
            'results.*.id' => ['required', 'integer'],
            'results.*.success' => ['required', 'boolean'],
            'results.*.autocount_no' => ['nullable', 'string', 'max:255'],
            'results.*.error' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            $this->log($request, 'invoices/result', 'SENT', [
                'status' => 400, 'message' => 'Validation failed.', 'errors' => $validator->errors()->all(),
            ], 'warning');

            return response()->json([
                'result' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors(),
            ], 400);
        }

        $synced = 0;
        $failed = 0;

        foreach ($request->input('results') as $row) {
            $invoice = Invoice::find($row['id']);
            if (!$invoice) {
                continue;
            }

            if (filter_var($row['success'], FILTER_VALIDATE_BOOLEAN)) {
                $invoice->sync_status = Invoice::SYNC_SYNCED;
                $invoice->api_invoice_id = $row['autocount_no'] ?? null;
                $invoice->sync_error = null;
                $invoice->synced_at = now();
                $synced++;
            } else {
                $invoice->sync_status = Invoice::SYNC_FAILED;
                $invoice->sync_error = $row['error'] ?? 'Unknown error';
                $failed++;

                // Timestamped, per-invoice trace so a past failure is easy to find
                // later (storage/logs/autocount-*.log), alongside the stored reason.
                Log::channel('autocount')->warning('[invoices/result] invoice sync FAILED', [
                    'invoice_id' => $invoice->id,
                    'invoiceno' => $invoice->invoiceno,
                    'customer_id' => $invoice->customer_id,
                    'error' => $invoice->sync_error,
                ]);
            }

            $invoice->save();
        }

        $this->log($request, 'invoices/result', 'SENT', [
            'status' => 200, 'synced' => $synced, 'failed' => $failed,
        ]);

        return response()->json(['result' => true, 'synced' => $synced, 'failed' => $failed]);
    }

    /**
     * Shape claimed invoices for the plugin: customer AutoCount code + lines
     * carrying the product code (= ItemCode). UOM is resolved plugin-side from
     * AutoCount's base UOM, so it is not sent here.
     */
    private function buildInvoicePayload(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $invoices = Invoice::with(['customer:id,code,company', 'invoicedetail.product:id,code,name'])
            ->whereIn('id', $ids)
            ->get();

        // Approved payments already collected against each invoice, keyed by
        // invoice id. A cash sale carries the full amount here (the POS payment is
        // written when the CS invoice is created), which the plugin stamps on the
        // AutoCount Cash Sale so it posts fully knocked off. Hoisted to one query.
        $paidByInvoice = \App\Models\InvoicePayment::whereIn('invoice_id', $ids)
            ->where('status', 1)
            ->groupBy('invoice_id')
            ->selectRaw('invoice_id, SUM(amount) as paid')
            ->pluck('paid', 'invoice_id');

        $payload = [];

        foreach ($invoices as $invoice) {
            // Guard per-invoice: one bad row (e.g. an unparseable date) must not
            // 500 the whole pull and strand the entire claimed batch in "syncing".
            try {
                $payload[] = [
                    'id' => $invoice->id,
                    'invoiceno' => $invoice->invoiceno,
                    // date has a d-m-Y accessor; parse the raw column for a stable ISO date.
                    'date' => \Illuminate\Support\Carbon::parse($invoice->getRawOriginal('date'))->format('Y-m-d'),
                    'remark' => $invoice->remark,
                    'paymentterm' => $invoice->paymentterm,
                    // A cash sale (invoiceno CS…, already paid at point of sale) must
                    // become an AutoCount Cash Sale, not a credit Sales Invoice. The
                    // CS/IV prefix is set deterministically on creation; treat pure-cash
                    // paymentterm (1) as a fallback signal for legacy rows.
                    'is_cash_sale' => $this->isCashSale($invoice),
                    // Amount already collected (approved payments). For a cash sale this
                    // is the full total; the plugin stamps it on the Cash Sale so the
                    // document posts with zero outstanding (auto knock-off).
                    'paid_amount' => round((float) ($paidByInvoice[$invoice->id] ?? 0), 2),
                    'customer_code' => optional($invoice->customer)->code,
                    'customer_name' => optional($invoice->customer)->company,
                    // Discount (stored as a negative line on a hidden product) is sent
                    // separately so the item lines stay real AutoCount items.
                    'discount' => \App\Support\InvoiceDiscount::amount($invoice->id),
                    'details' => $invoice->invoicedetail->reject(function ($d) {
                        return \App\Support\InvoiceDiscount::isDiscountProduct($d->product_id);
                    })->map(function ($d) {
                        return [
                            'item_code' => optional($d->product)->code,
                            'description' => optional($d->product)->name ?: $d->remark,
                            'quantity' => (float) $d->quantity,
                            'unit_price' => (float) $d->price,
                            'subtotal' => (float) $d->totalprice,
                        ];
                    })->values(),
                ];
            } catch (\Throwable $e) {
                // Fail just this invoice so it leaves "syncing" and can't jam the queue.
                $invoice->sync_status = Invoice::SYNC_FAILED;
                $invoice->sync_error = 'Payload build failed: ' . $e->getMessage();
                $invoice->save();
                Log::channel('autocount')->warning('[invoices/pending] build failed', [
                    'id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $payload;
    }

    /**
     * Whether an invoice is a cash sale (paid on the spot) rather than a credit
     * invoice. Cash sales carry a `CS` invoiceno prefix (credit invoices use `IV`),
     * set deterministically when the document is created. Falls back to the pure-cash
     * payment term (1) for any legacy row with no recognisable prefix.
     */
    private function isCashSale(Invoice $invoice): bool
    {
        $no = strtoupper(trim((string) $invoice->invoiceno));

        if (\Illuminate\Support\Str::startsWith($no, 'CS')) {
            return true;
        }

        if (\Illuminate\Support\Str::startsWith($no, 'IV')) {
            return false;
        }

        return (int) $invoice->paymentterm === 1;
    }

    /**
     * Create or update one product, matched by `code`. Returns true if a new
     * product was created, false if an existing one was updated.
     *
     * - price: only overwritten when AutoCount sent a value. null means the item
     *   has no base-UOM price, so the existing OMS price is kept (new rows fall
     *   back to 0).
     * - On first creation only, stamps the legacy `type`/`type_id` defaults;
     *   updates leave OMS-owned fields (image_path/type) untouched.
     * - item_group: when sent non-empty, drives `type_id` on both create and
     *   update (AutoCount is master — overwrites the create-time default and any
     *   prior value). Blank/absent leaves the product's type as-is.
     * - Race-safe: if a concurrent sync wins the insert (unique `code`), the
     *   duplicate-key error is caught and retried as an update.
     * - prices: when the row carries a `prices` array, the product's per-category
     *   prices are reconciled against it (see syncProductPrices).
     *
     * @param array $categoryCache  price-category name => id, reused/extended across the batch
     * @param array $typeCache      item-group name => product_types.id, reused/extended across the batch
     */
    private function upsertProduct(string $code, array $row, $defaultTypeId, array &$categoryCache = [], array &$typeCache = []): bool
    {
        $attributes = [
            'name' => $row['name'],
            'status' => isset($row['status']) ? (int) $row['status'] : 1,
        ];

        if (array_key_exists('price', $row) && $row['price'] !== null) {
            $attributes['price'] = (float) $row['price'];
        }

        if (array_key_exists('classification_code', $row)) {
            $attributes['classification_code'] = $row['classification_code'];
        }

        // AutoCount's Item Group drives the OMS product type. Resolve/create the
        // type by name and assign it (overwriting the create-time default below).
        // A blank item group leaves type_id untouched.
        if (array_key_exists('item_group', $row) && trim((string) $row['item_group']) !== '') {
            $attributes['type_id'] = $this->resolveProductTypeId(trim((string) $row['item_group']), $typeCache);
        }

        $product = Product::firstOrNew(['code' => $code]);
        $isNew = !$product->exists;

        if ($isNew) {
            $product->type = 0;
            $product->type_id = $defaultTypeId;
            if (!array_key_exists('price', $attributes)) {
                $product->price = 0; // new item with no AutoCount price yet
            }
        }

        $product->fill($attributes);

        try {
            $product->save();
        } catch (QueryException $e) {
            if (!$this->isDuplicateKey($e)) {
                throw $e;
            }

            // Concurrent sync already inserted this code — update that row.
            $product = Product::where('code', $code)->firstOrFail();
            $product->fill($attributes);
            $product->save();
            $isNew = false;
        }

        if (array_key_exists('prices', $row)) {
            $this->syncProductPrices($product, is_array($row['prices']) ? $row['prices'] : [], $categoryCache);
        }

        // Keep AutoCount's item group on the product without removing extra
        // types an admin has selected.
        if (($isNew || array_key_exists('type_id', $attributes)) && !empty($product->type_id)) {
            $product->productTypes()->syncWithoutDetaching([(int) $product->type_id]);
        }

        return $isNew;
    }

    /**
     * Reconcile one product's per-category prices against the sent list. The
     * price category is matched by name and created if missing; any category not
     * sent for this product is removed (full-replace per product).
     *
     * @param array $categoryCache  price-category name => id, reused/extended across the batch
     */
    private function syncProductPrices(Product $product, array $prices, array &$categoryCache): void
    {
        $sent = []; // product_price_category_id => price
        foreach ($prices as $pr) {
            $categoryName = trim((string) ($pr['product_price_category'] ?? ''));
            if ($categoryName === '') {
                continue;
            }
            $categoryId = $this->resolvePriceCategoryId($categoryName, $categoryCache);
            $sent[$categoryId] = (float) $pr['price'];
        }

        foreach ($sent as $categoryId => $price) {
            ProductPrice::updateOrCreate(
                ['product_id' => $product->id, 'product_price_category_id' => $categoryId],
                ['price' => $price]
            );
        }

        $stale = ProductPrice::where('product_id', $product->id);
        if (!empty($sent)) {
            $stale->whereNotIn('product_price_category_id', array_keys($sent));
        }
        $stale->delete();
    }

    /**
     * Resolve a price-category name to its id, creating the category if it does
     * not exist yet (AutoCount is the source of the price-category list). Cached
     * per batch to avoid re-querying. Distinct from a product's ProductType.
     */
    private function resolvePriceCategoryId(string $name, array &$categoryCache): int
    {
        if (!array_key_exists($name, $categoryCache)) {
            $categoryCache[$name] = ProductPriceCategory::firstOrCreate(['name' => $name], ['status' => 1])->id;
        }

        return (int) $categoryCache[$name];
    }

    /**
     * Resolve an item-group name to its product_types id, creating the type if
     * it does not exist yet (AutoCount's Item Group is the source of the product
     * classification list). Cached per batch to avoid re-querying. Distinct from
     * a price category (customer pricing tier) — this is the product's type.
     */
    private function resolveProductTypeId(string $name, array &$typeCache): int
    {
        if (!array_key_exists($name, $typeCache)) {
            $typeCache[$name] = ProductType::firstOrCreate(['name' => $name], ['status' => 1])->id;
        }

        return (int) $typeCache[$name];
    }

    /**
     * Back-compat shim for the renamed wire field. The plugin used to send the
     * price category as `product_type`; it is now `product_price_category`. Copy
     * the legacy key onto the new one (on product price rows and customer rows)
     * only where the new key was not supplied, so a not-yet-rebuilt plugin keeps
     * syncing while a rebuilt one takes precedence.
     */
    private function normalizeProductPriceCategoryKeys(Request $request): void
    {
        $products = $request->input('products');
        if (is_array($products)) {
            foreach ($products as $i => $product) {
                if (!isset($product['prices']) || !is_array($product['prices'])) {
                    continue;
                }
                foreach ($product['prices'] as $j => $price) {
                    if (is_array($price)
                        && !array_key_exists('product_price_category', $price)
                        && array_key_exists('product_type', $price)) {
                        $products[$i]['prices'][$j]['product_price_category'] = $price['product_type'];
                    }
                }
            }
            $request->merge(['products' => $products]);
        }

        $customers = $request->input('customers');
        if (is_array($customers)) {
            foreach ($customers as $i => $customer) {
                if (is_array($customer)
                    && !array_key_exists('product_price_category', $customer)
                    && array_key_exists('product_type', $customer)) {
                    $customers[$i]['product_price_category'] = $customer['product_type'];
                }
            }
            $request->merge(['customers' => $customers]);
        }
    }

    /**
     * Create or update one customer, matched by `code` (AutoCount AccNo).
     * Returns true if created, false if updated.
     *
     * - Only non-empty AutoCount values are written, so a blank field never
     *   wipes an existing OMS value (status is always applied — 0/1 is definite).
     * - On first creation only, fills the required legacy columns AutoCount does
     *   not provide with safe defaults (name = company, agent_id/supervisor_id 0,
     *   paymentterm = Credit, is_do_customer = false). Updates leave those
     *   OMS-owned fields untouched.
     * - Race-safe against the unique `code` index, like upsertProduct().
     * - product_price_category: when the key is present it is resolved to a
     *   product_price_category_id, creating the category if needed (blank ->
     *   null, i.e. base-price fallback); when absent the assignment is untouched.
     *
     * @param array $categoryCache  price-category name => id, reused/extended across the batch
     */
    private function upsertCustomer(string $code, array $row, array &$categoryCache = []): bool
    {
        $attributes = [
            'company' => $row['company'],
            'status' => isset($row['status']) ? (int) $row['status'] : 1,
        ];

        // Optional fields: only overwrite when AutoCount actually sent something.
        foreach (['phone', 'address', 'email', 'postcode', 'registration_no', 'sst_registration_no'] as $field) {
            if (array_key_exists($field, $row) && trim((string) $row[$field]) !== '') {
                $attributes[$field] = $row[$field];
            }
        }

        // Debtor type is AutoCount-owned: present means reflect it (blank -> null),
        // absent means leave the stored value alone (the plugin always sends it).
        if (array_key_exists('debtor_type', $row)) {
            $attributes['debtor_type'] = trim((string) $row['debtor_type']) ?: null;
        }

        // Price category is explicit: present means set it (even to null to
        // clear); absent means leave the current assignment alone.
        if (array_key_exists('product_price_category', $row)) {
            $categoryName = trim((string) $row['product_price_category']);
            $attributes['product_price_category_id'] = $categoryName !== '' ? $this->resolvePriceCategoryId($categoryName, $categoryCache) : null;
        }

        $customer = Customer::firstOrNew(['code' => $code]);
        $isNew = !$customer->exists;

        if ($isNew) {
            // Required legacy columns with no AutoCount source — minimal defaults.
            // `name` is a legacy NOT NULL column that only exists on some
            // deployments (the live schema has drifted from migration history;
            // see snoodle-web legacy base tables). Only write it where present,
            // otherwise every insert dies with "Unknown column 'name'".
            if ($this->customersHaveNameColumn()) {
                $customer->name = $row['company'];      // not fillable
            }
            $customer->agent_id = 0;
            $customer->supervisor_id = 0;
            $customer->paymentterm = self::DEFAULT_PAYMENT_TERM;
            $customer->is_do_customer = false;
        }

        $customer->fill($attributes);

        try {
            $customer->save();
        } catch (QueryException $e) {
            if (!$this->isDuplicateKey($e)) {
                throw $e;
            }

            $customer = Customer::where('code', $code)->firstOrFail();
            $customer->fill($attributes);
            $customer->save();
            $isNew = false;
        }

        return $isNew;
    }

    /**
     * Whether the `customers` table carries the legacy `name` column. Cached
     * per request so a 20-row batch does not hit information_schema 20 times.
     */
    private function customersHaveNameColumn(): bool
    {
        static $hasName = null;

        if ($hasName === null) {
            $hasName = Schema::hasColumn('customers', 'name');
        }

        return $hasName;
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        // MySQL 1062 = duplicate entry; SQLSTATE 23000 = integrity constraint.
        return ($e->errorInfo[1] ?? null) === 1062
            || (string) ($e->errorInfo[0] ?? '') === '23000';
    }

    /**
     * Append a line to the AutoCount trace log. Never records the
     * X-AutoCount-Token header, and never breaks the sync on failure.
     *
     * @param string $stage RECV (request in) or SENT (response out)
     */
    private function log(Request $request, string $endpoint, string $stage, array $context = [], string $level = 'info'): void
    {
        try {
            Log::channel('autocount')->{$level}(
                "[{$endpoint}] {$stage}",
                array_merge([
                    'direction' => 'incoming', // AutoCount -> OMS
                    'branch_id' => $request->query('branch_id'),
                    'ip' => $request->ip(),
                ], $context)
            );
        } catch (\Throwable $e) {
            // Tracing must not break the sync.
        }
    }
}
