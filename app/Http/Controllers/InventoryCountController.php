<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryCountDataTable;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Flash;

class InventoryCountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(InventoryCountDataTable $dataTable, Request $request)
    {
        $drivers = Driver::all();
        $products = Product::all();
        $statuses = InventoryCount::getStatusOptions();

        $dataTable = $dataTable
            ->with([
                'status' => $request->get('status', 'all'),
                'driver_id' => $request->get('driver_id'),
                'product_id' => $request->get('product_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ]);

        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('inventory_counts.index', compact('drivers', 'products', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), InventoryCount::$rules);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $driver = Driver::find($request->driver_id);

        if (!$driver->trip_id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver does not have an active trip.'
                ], 422);
            }
            Flash::error('Driver does not have an active trip.');
            return redirect(route('inventoryCounts.index'));
        }

        $existingCount = InventoryCount::where('driver_id', $request->driver_id)
            ->where('trip_id', $driver->trip_id)
            ->where('status', InventoryCount::STATUS_PENDING)
            ->exists();

        if ($existingCount) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A pending inventory count already exists for this driver in the current trip.'
                ], 422);
            }
            Flash::error('A pending inventory count already exists for this driver in the current trip.');
            return redirect(route('inventoryCounts.index'));
        }

        try {
            $formattedItems = [];
            foreach ($request->items as $item) {
                $formattedItems[] = [
                    'product_id' => $item['product_id'],
                    'current_quantity' => $item['quantity'],
                    'counted_quantity' => '',
                ];
            }

            $inventoryCount = InventoryCount::create([
                'driver_id' => $request->driver_id,
                'items' => $formattedItems,
                'status' => InventoryCount::STATUS_PENDING,
                'remarks' => $request->remarks,
                'trip_id' => $driver->trip_id ?? '',
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count created successfully with ' . count($request->items) . ' items.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Inventory count created successfully with ' . count($request->items) . ' items.');
            return redirect(route('inventoryCounts.index'));
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to create count: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $count = InventoryCount::with(['driver', 'approver', 'rejector'])->findOrFail($id);

        $itemsWithDetails = [];
        foreach ($count->items as $item) {
            $product = Product::find($item['product_id']);
            $itemsWithDetails[] = [
                'product_id' => $item['product_id'],
                'product_name' => $product ? $product->name : 'Unknown Product',
                'product_code' => $product ? $product->code : 'N/A',
                'quantity' => $item['quantity'] ?? null
            ];
        }

        return view('inventory_counts.show', compact('count', 'itemsWithDetails'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        if ($request->has('items') && is_array($request->items)) {
            $validator = Validator::make($request->all(), InventoryCount::$rules);
        } else {
            $validator = Validator::make($request->all(), [
                'remarks' => 'nullable|string|max:500'
            ]);
        }

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $updateData = [];

            if ($request->has('items')) {
                $updateData['items'] = json_decode($request->items, true);
            }

            if ($request->has('remarks')) {
                $updateData['remarks'] = $request->remarks;
            }

            $inventoryCount->update($updateData);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count updated successfully.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Inventory count updated successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to update count: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        if ($inventoryCount->status !== InventoryCount::STATUS_PENDING) {
            Flash::error('Only pending counts can be deleted.');
            return redirect()->back();
        }

        try {
            $inventoryCount->delete();
            Flash::success('Inventory Count deleted successfully.');
            return redirect(route('inventoryCounts.index'));
        } catch (\Exception $e) {
            Flash::error('Failed to delete count: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Approve the specified inventory count.
     */
    public function approve(Request $request, $id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        if (!$inventoryCount->canBeApproved()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This count cannot be approved. It may not be in pending status.'
                ], 422);
            }
            Flash::error('This count cannot be approved.');
            return redirect()->back();
        }

        try {
            $items = $inventoryCount->items ?? [];
            $missingCountedItems = [];

            foreach ($items as $item) {
                $countedQty = $item['counted_quantity'] ?? null;

                if (empty($countedQty) && $countedQty !== '0' && $countedQty !== 0) {
                    $productName = $item['product_name'] ?? null;

                    if (!$productName && isset($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        $productName = $product ? $product->name : 'Product ID: ' . $item['product_id'];
                    } elseif (!$productName) {
                        $productName = 'Unknown Product';
                    }

                    $missingCountedItems[] = $productName;
                }
            }

            if (!empty($missingCountedItems)) {
                $errorMessage = 'Cannot approve. Please fill in counted quantity for: ' . implode(', ', $missingCountedItems);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }
                Flash::error($errorMessage);
                return redirect()->back();
            }

            $inventoryCount->update([
                'status' => InventoryCount::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count approved successfully.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Inventory count approved successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to approve count: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Reject the specified inventory count.
     */
    public function reject(Request $request, $id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        if (!$inventoryCount->canBeRejected()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This count cannot be rejected. It may not be in pending status.'
                ], 422);
            }
            Flash::error('This count cannot be rejected.');
            return redirect()->back();
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $inventoryCount->update([
                'status' => InventoryCount::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count rejected successfully.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Inventory count rejected successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to reject count: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
