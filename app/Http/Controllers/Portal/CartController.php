<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Code;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * The portal cart, persisted server-side (one cart_items row per product per
 * customer) so it follows the customer across devices and sessions. Checkout
 * turns the cart into a SalesOrder (status 0 = "Submitted").
 */
class CartController extends Controller
{
    /** The cart page, rendered from the customer's stored cart. */
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $items = $this->activeItems($customer->id);

        return view('portal.cart', compact('customer', 'items'));
    }

    /** Add a product to the cart (increments if already present). */
    public function add(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $productId = (int) $request->input('product_id');
        $qty = max(1, (int) $request->input('quantity', 1));

        if (!Product::where('status', 1)->whereKey($productId)->exists()) {
            return response()->json(['ok' => false, 'message' => 'This product is not available.'], 422);
        }

        // Atomic upsert-increment. A plain read-modify-write would lose updates
        // from concurrent tabs and could collide on the unique
        // (customer_id, product_id) key; letting the DB do the increment is
        // race-free.
        DB::statement(
            'INSERT INTO cart_items (customer_id, product_id, quantity, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE quantity = quantity + ?, updated_at = NOW()',
            [$customer->id, $productId, $qty, $qty]
        );

        return response()->json(['ok' => true, 'count' => $this->cartCount($customer->id)]);
    }

    /** Set a product's quantity (quantity < 1 removes it). */
    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $productId = (int) $request->input('product_id');
        $qty = (int) $request->input('quantity', 1);

        $item = CartItem::where('customer_id', $customer->id)->where('product_id', $productId)->first();
        if ($item) {
            if ($qty < 1) {
                $item->delete();
            } else {
                $item->quantity = $qty;
                $item->save();
            }
        }

        return response()->json(['ok' => true, 'count' => $this->cartCount($customer->id)]);
    }

    /** Remove a product from the cart. */
    public function remove(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $productId = (int) $request->input('product_id');

        CartItem::where('customer_id', $customer->id)->where('product_id', $productId)->delete();

        return response()->json(['ok' => true, 'count' => $this->cartCount($customer->id)]);
    }

    /**
     * Turn the stored cart into a SalesOrder + line items, then empty the cart.
     * Prices are resolved server-side; inactive products are skipped.
     */
    public function submit(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        // "Off day": the customer places an order but does not want it delivered
        // today. The driver sees no order for them today; a daily job forwards it
        // to the next day as a normal order.
        $offDay = $request->boolean('off_day');

        $order = DB::transaction(function () use ($customer, $offDay) {
            // Lock this customer's cart rows for the duration of checkout. A
            // second concurrent/duplicate submit (another tab, double-click,
            // retry) blocks here, then finds an empty cart once the first one
            // commits — so a single cart can never become two orders.
            $items = CartItem::where('customer_id', $customer->id)
                ->lockForUpdate()
                ->with('product')
                ->get()
                ->filter(fn ($item) => $item->product && (int) $item->product->status === 1)
                ->values();

            if ($items->isEmpty()) {
                return null;
            }

            $order = new SalesOrder();
            $order->sono = Code::nextRunningNumber('sorunningnumber', 'SO');
            $order->date = now();
            $order->customer_id = $customer->id;
            $order->paymentterm = $customer->paymentterm;
            $order->agent_id = $customer->agent_id;
            $order->supervisor_id = $customer->supervisor_id;
            // Portal orders fall back to the customer's default driver, if set.
            $order->driver_id = $customer->so_default_driver_id;
            $order->status = 0; // New / "Submitted" in the portal
            $order->is_portal_order = true; // placed by the customer via the portal
            $order->off_day = $offDay; // deliver tomorrow, not today
            $order->save();

            foreach ($items as $item) {
                $price = Product::priceForCustomer($item->product_id, $item->product->price, $customer->id);

                $detail = new SalesOrderDetail();
                $detail->sales_order_id = $order->id;
                $detail->product_id = $item->product_id;
                $detail->quantity = $item->quantity;
                $detail->price = $price;
                $detail->totalprice = $item->quantity * $price;
                $detail->save();
            }

            CartItem::where('customer_id', $customer->id)->delete();

            return $order;
        });

        if (!$order) {
            return response()->json(['ok' => false, 'message' => 'Your cart is empty.'], 422);
        }

        return response()->json([
            'ok'       => true,
            'redirect' => route('portal.orders.show', Crypt::encrypt($order->id)),
        ]);
    }

    /** The customer's cart, limited to still-active products, with product loaded. */
    private function activeItems(int $customerId)
    {
        return CartItem::where('customer_id', $customerId)
            ->whereHas('product', fn ($q) => $q->where('status', 1))
            ->with('product')
            ->orderBy('id')
            ->get();
    }

    /** Total item quantity in the cart (for the nav badge). */
    private function cartCount(int $customerId): int
    {
        return (int) CartItem::where('customer_id', $customerId)->sum('quantity');
    }
}
