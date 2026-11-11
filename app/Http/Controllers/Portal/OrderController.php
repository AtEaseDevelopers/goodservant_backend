<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

/**
 * Customer-facing ordering portal: the Order catalogue plus My Orders
 * (order history + details). Cart and checkout come later.
 */
class OrderController extends Controller
{
    /** Products / orders loaded per infinite-scroll batch. */
    const PER_PAGE = 20;

    /**
     * SalesOrder.status mapped to customer-facing labels. Admin calls status 0
     * "New"; customers see it as "Submitted".
     */
    const ORDER_STATUSES = [
        0 => 'submitted',
        1 => 'completed',
        2 => 'cancelled',
    ];

    /**
     * Passwordless "fast login": the link (generated in the admin customer
     * listing) that logs the real customer into the portal. The {token} is the
     * customer's random, revocable portal_token — not an encrypted id — so a
     * leaked or rotated link can be invalidated without touching anyone else.
     */
    public function magicLogin(Request $request, $token)
    {
        $customer = Customer::where('portal_token', $token)->first();

        if (!$customer || (int) $customer->status !== 1) {
            abort(403, 'This ordering link is no longer valid.');
        }

        Auth::guard('customer')->login($customer);
        // Fresh session id on this passwordless login (prevents session
        // fixation); session data is preserved.
        $request->session()->regenerate();

        return redirect()->route('portal.order');
    }

    /**
     * The Order page. Renders the shell plus the first batch of products
     * server-side; further batches load via infinite scroll (see products()).
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $page = $this->pageOfProducts('');

        return view('portal.order', [
            'customer' => $customer,
            'products' => $page->items(),
            'hasMore'  => $page->hasMorePages(),
        ]);
    }

    /**
     * One infinite-scroll batch of products as JSON: the rendered cards plus
     * whether another page exists. Optional `q` filters by product name.
     */
    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $page = $this->pageOfProducts($q, (int) $request->query('page', 1));

        return response()->json([
            'html'     => view('portal._products', ['products' => $page->items()])->render(),
            'hasMore'  => $page->hasMorePages(),
            'nextPage' => $page->currentPage() + 1,
        ]);
    }

    /**
     * Active products in the admin-arranged order, one PER_PAGE batch,
     * optionally filtered by name.
     */
    private function pageOfProducts(string $q, int $page = 1)
    {
        $query = Product::where('status', 1);

        if ($q !== '') {
            $query->where('name', 'like', '%' . $q . '%');
        }

        return $query->orderBy('sequence')
            ->orderBy('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));
    }

    /**
     * My Orders: the logged-in customer's sales orders, newest first. Renders
     * the shell plus the first batch; more load via infinite scroll (ordersBatch).
     */
    public function orders(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));
        $date = (string) $request->query('date', '');

        $page = $this->pageOfOrders($customer->id, $status, $q, $date);

        return view('portal.orders', [
            'customer' => $customer,
            'orders'   => $page->items(),
            'hasMore'  => $page->hasMorePages(),
            'status'   => $status,
            'q'        => $q,
            'date'     => $date,
        ]);
    }

    /**
     * One infinite-scroll batch of the customer's orders as JSON. Filters:
     * `status` (submitted|completed|cancelled), `q` (order number or product
     * name), and `date` preset (7d|30d|month|3m).
     */
    public function ordersBatch(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));
        $date = (string) $request->query('date', '');

        $page = $this->pageOfOrders($customer->id, $status, $q, $date, (int) $request->query('page', 1));

        return response()->json([
            'html'     => view('portal._orders', ['orders' => $page->items()])->render(),
            'hasMore'  => $page->hasMorePages(),
            'nextPage' => $page->currentPage() + 1,
        ]);
    }

    /**
     * Order detail: one of the customer's own orders, with its line items.
     */
    public function show($token)
    {
        $order = $this->findOwnedOrder($token);
        $customer = Auth::guard('customer')->user();

        return view('portal.order_detail', compact('customer', 'order'));
    }

    /**
     * Stream/download the Sales Order PDF for one of the customer's own orders.
     * Reuses the same print layout and sizing as the admin side.
     */
    public function salesOrderPdf($token, $function)
    {
        $order = $this->findOwnedOrder($token);

        $height = (count($order->salesorderdetail) * 23) + 450;
        if (!empty($order->remark)) {
            $height += 40;
        }

        try {
            $pdf = Pdf::loadView('sales_orders.print', ['salesOrder' => $order])
                ->setPaper([0, 0, 300, $height], 'portrait')
                ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true], true);

            return $function === 'download' ? $pdf->download('sales-order.pdf') : $pdf->stream('sales-order.pdf');
        } catch (Exception $e) {
            abort(404);
        }
    }

    /**
     * One PER_PAGE batch of the customer's orders, newest first. Optionally
     * filtered by status label, a search term (order number or product name),
     * and a date preset (7d|30d|month|3m).
     */
    private function pageOfOrders(int $customerId, string $status, string $q, string $date, int $page = 1)
    {
        $query = SalesOrder::where('customer_id', $customerId)
            ->with('salesorderdetail.product:id,name');

        $code = array_search($status, self::ORDER_STATUSES, true);
        if ($code !== false) {
            $query->where('status', $code);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('sono', 'like', '%' . $q . '%')
                    ->orWhereHas('salesorderdetail.product', function ($p) use ($q) {
                        $p->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        if ($from = $this->dateFilterStart($date)) {
            $query->where('date', '>=', $from);
        }

        return $query->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));
    }

    /**
     * Translate a date-preset key into the earliest order date to include,
     * or null for "all dates".
     */
    private function dateFilterStart(string $date)
    {
        switch ($date) {
            case '7d':    return now()->subDays(7)->startOfDay();
            case '30d':   return now()->subDays(30)->startOfDay();
            case 'month': return now()->startOfMonth();
            case '3m':    return now()->subMonths(3)->startOfDay();
            default:      return null;
        }
    }

    /**
     * Load a sales order by its encrypted id, scoped to the logged-in customer
     * so one customer can never open another's order. 404 on bad/foreign id.
     */
    private function findOwnedOrder($token): SalesOrder
    {
        try {
            $id = Crypt::decrypt($token);
        } catch (DecryptException $e) {
            abort(404);
        }

        $order = SalesOrder::where('id', $id)
            ->where('customer_id', Auth::guard('customer')->id())
            ->with('salesorderdetail.product')
            ->first();

        if (!$order) {
            abort(404);
        }

        return $order;
    }

    /**
     * Sign out of the portal. Only the 'customer' guard is cleared so a
     * concurrent admin ('web') login in the same browser is left untouched.
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        return redirect()->route('login');
    }
}
