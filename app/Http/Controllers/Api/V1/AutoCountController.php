<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\SpecialPrice;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Body: { "products": [ { code, name, price, status?, classification_code? }, ... ] }
     * Matched by `code` (AutoCount ItemCode). On update only the synced
     * columns change, so OMS-owned fields (image_path, type_id) are preserved.
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

        $validator = Validator::make($request->all(), [
            'products' => ['required', 'array', 'min:1'],
            'products.*.code' => ['required', 'string', 'max:255'],
            'products.*.name' => ['required', 'string', 'max:255'],
            // Nullable: null = AutoCount has no base-UOM price, so the existing
            // OMS price is kept rather than overwritten.
            'products.*.price' => ['nullable', 'numeric'],
            'products.*.status' => ['nullable', 'integer'],
            'products.*.classification_code' => ['nullable', 'string', 'max:255'],
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

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($request->input('products') as $row) {
            $code = trim($row['code']);
            if ($code === '') {
                continue;
            }

            // Guard per-item: one bad row logs a traceable reason and is skipped,
            // instead of 500-ing the whole batch.
            try {
                if ($this->upsertProduct($code, $row, $defaultTypeId)) {
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
            'total' => $created + $updated,
        ];
        $this->log($request, 'products', 'SENT', [
            'status' => 200,
            'received_count' => $received,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
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

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($request->input('customers') as $row) {
            $code = trim($row['code']);
            if ($code === '') {
                continue;
            }

            // Guard per-item: one bad row logs a traceable reason and is skipped,
            // instead of 500-ing the whole batch.
            try {
                if ($this->upsertCustomer($code, $row)) {
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
            'total' => $created + $updated,
        ];
        $this->log($request, 'customers', 'SENT', [
            'status' => 200,
            'received_count' => $received,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
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
     * Upsert customer-specific prices pushed from AutoCount.
     *
     * AutoCount has no per-customer price rows: each item carries 6 price levels
     * (Price 1-6) and each debtor is assigned a price category that selects one
     * of them. The plugin resolves that per (customer, item) and sends the
     * already-flattened price here; this endpoint maps it to the OMS
     * `special_prices` table, which is keyed (customer_id, product_id).
     *
     * Body: {
     *   "sync_token": "<uuid, one per sync run>",
     *   "final": true|false,            // true on the last batch of the run
     *   "special_prices": [ { customer_code, item_code, price }, ... ]
     * }
     *
     * Reconciliation (runs only when final=true): every 'autocount' row NOT
     * written by this run's token is deleted, so a customer moving price
     * category — or a price that is no longer special — is removed. 'manual'
     * rows are never touched. On conflict (same customer+product exists as a
     * manual override) AutoCount wins: the row is overwritten and claimed as
     * 'autocount'.
     *
     * Depends on customers + products already being synced (rows are matched by
     * code); any (customer_code, item_code) that does not resolve is skipped.
     */
    public function syncSpecialPrices(Request $request)
    {
        $received = is_array($request->input('special_prices')) ? count($request->input('special_prices')) : 0;
        $this->log($request, 'special-prices', 'RECV', ['received_count' => $received]);

        if (!$this->authorized($request)) {
            $this->log($request, 'special-prices', 'SENT', ['status' => 403, 'message' => 'Token rejected.'], 'warning');

            return response()->json(['result' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($this->dryRun()) {
            $this->log($request, 'special-prices', 'DRYRUN', [
                'received_count' => $received,
                'special_prices' => $request->input('special_prices'),
                'note' => 'Dry run — nothing written, nothing pruned.',
            ]);

            return response()->json(['result' => true, 'dry_run' => true, 'created' => 0, 'updated' => 0, 'pruned' => 0]);
        }

        $validator = Validator::make($request->all(), [
            'sync_token' => ['required', 'string', 'max:64'],
            'final' => ['nullable', 'boolean'],
            // 'present' (not 'min:1'): the last batch may be empty and still need
            // to run the prune (e.g. every customer is back on the default level).
            'special_prices' => ['present', 'array'],
            'special_prices.*.customer_code' => ['required', 'string', 'max:255'],
            'special_prices.*.item_code' => ['required', 'string', 'max:255'],
            'special_prices.*.price' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            $this->log($request, 'special-prices', 'SENT', [
                'status' => 400, 'message' => 'Validation failed.', 'errors' => $validator->errors()->all(),
            ], 'warning');

            return response()->json([
                'result' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors(),
            ], 400);
        }

        $rows = $request->input('special_prices');
        $token = $request->input('sync_token');
        $final = filter_var($request->input('final', false), FILTER_VALIDATE_BOOLEAN);

        // Resolve codes -> ids in two queries rather than per row.
        $customerMap = Customer::whereIn('code', collect($rows)->pluck('customer_code')
            ->map(fn ($c) => trim((string) $c))->filter()->unique()->values())
            ->pluck('id', 'code');
        $productMap = Product::whereIn('code', collect($rows)->pluck('item_code')
            ->map(fn ($c) => trim((string) $c))->filter()->unique()->values())
            ->pluck('id', 'code');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $customerCode = trim((string) $row['customer_code']);
            $itemCode = trim((string) $row['item_code']);
            $customerId = $customerMap[$customerCode] ?? null;
            $productId = $productMap[$itemCode] ?? null;

            // Customer or product not synced yet — skip (and let the prune leave
            // any older row alone, since it won't carry this run's token... it
            // will be pruned, which is correct: an unresolvable code can't price).
            if (!$customerId || !$productId) {
                $skipped++;
                continue;
            }

            try {
                if ($this->upsertSpecialPrice((int) $customerId, (int) $productId, (float) $row['price'], $token)) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                Log::channel('autocount')->warning('[special-prices] upsert FAILED', [
                    'customer_code' => $customerCode,
                    'item_code' => $itemCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Full-replace reconciliation: drop AutoCount-owned rows this run didn't
        // re-affirm. Manual rows (source != 'autocount') are untouched.
        $pruned = 0;
        if ($final) {
            $pruned = SpecialPrice::where('source', 'autocount')
                ->where(function ($q) use ($token) {
                    $q->where('sync_token', '!=', $token)->orWhereNull('sync_token');
                })
                ->delete();
        }

        $this->log($request, 'special-prices', 'SENT', [
            'status' => 200,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'final' => $final,
            'pruned' => $pruned,
        ]);

        return response()->json([
            'result' => true,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'pruned' => $pruned,
        ]);
    }

    /**
     * Create or update one special price, matched by (customer_id, product_id).
     * Returns true if a new row was created, false if an existing one updated.
     * Always claims the row as 'autocount' (AutoCount wins over manual) and
     * stamps the current run's token so the prune keeps it.
     */
    private function upsertSpecialPrice(int $customerId, int $productId, float $price, string $token): bool
    {
        $row = SpecialPrice::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();
        $isNew = $row === null;

        if ($isNew) {
            $row = new SpecialPrice();
            $row->customer_id = $customerId;
            $row->product_id = $productId;
        }

        $row->price = $price;
        $row->status = 1;
        $row->source = 'autocount';
        $row->sync_token = $token;
        $row->save();

        return $isNew;
    }

    /**
     * Create or update one product, matched by `code`. Returns true if a new
     * product was created, false if an existing one was updated.
     *
     * - price: only overwritten when AutoCount sent a value. null means the item
     *   has no base-UOM price, so the existing OMS price is kept (new rows fall
     *   back to 0).
     * - On first creation only, stamps the legacy `type`/`type_id` defaults;
     *   updates leave OMS-owned fields (image_path/type_id/type) untouched.
     * - Race-safe: if a concurrent sync wins the insert (unique `code`), the
     *   duplicate-key error is caught and retried as an update.
     */
    private function upsertProduct(string $code, array $row, $defaultTypeId): bool
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

        return $isNew;
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
     */
    private function upsertCustomer(string $code, array $row): bool
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

        $customer = Customer::firstOrNew(['code' => $code]);
        $isNew = !$customer->exists;

        if ($isNew) {
            // Required legacy columns with no AutoCount source — minimal defaults.
            $customer->name = $row['company'];          // legacy NOT NULL, not fillable
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
