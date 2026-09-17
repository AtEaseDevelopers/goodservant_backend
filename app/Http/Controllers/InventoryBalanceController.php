<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryBalanceDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInventoryBalanceRequest;
use App\Http\Requests\UpdateInventoryBalanceRequest;
use App\Repositories\InventoryBalanceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryBalanceController extends AppBaseController
{
    /** @var InventoryBalanceRepository $inventoryBalanceRepository*/
    private $inventoryBalanceRepository;

    public function __construct(InventoryBalanceRepository $inventoryBalanceRepo)
    {
        $this->inventoryBalanceRepository = $inventoryBalanceRepo;
    }

    /**
     * Display a listing of the InventoryBalance, grouped by lorry.
     *
     * @param InventoryBalanceDataTable $inventoryBalanceDataTable
     *
     * @return Response
     */
    public function index(InventoryBalanceDataTable $inventoryBalanceDataTable)
    {
        return $inventoryBalanceDataTable->render('inventory_balances.index');
    }
	public function stockin(Request $request)
	{
		$data = $request->all();
		$lorryIds = $data['lorry_id'];  // This will be an array of selected lorry IDs

		foreach ($lorryIds as $lorryId) {
			$activeDriver = Driver::where('lorry_id', $lorryId)->whereNotNull('trip_id')->first();
			$tripId = $activeDriver->trip_id ?? null;

			$inventoryBalance = InventoryBalance::where('product_id', $data['product_id'])
				->where('lorry_id', $lorryId)
				->first();

			if (!empty($inventoryBalance)) {
				// Update the existing inventory balance
				$inventoryBalance->quantity = $inventoryBalance->quantity + $data['quantity'];
				$inventoryBalance->save();

				// Create an inventory transaction record
				$inventoryTransaction = new InventoryTransaction();
				$inventoryTransaction->type = 1;
				$inventoryTransaction->lorry_id = $lorryId;
				$inventoryTransaction->product_id = $inventoryBalance->product_id;
				$inventoryTransaction->quantity = $data['quantity'];
				$inventoryTransaction->date = date("Y-m-d H:i:s");
				$inventoryTransaction->user = Auth::user()->email . ' (' . Auth::user()->name . ')';
				$inventoryTransaction->trip_id = $tripId;
				$inventoryTransaction->save();

				Flash::success('Inventory Balance for lorry ID ' . $lorryId . ' has been updated successfully.');
			} else {
				// Insert a new inventory balance
				$newInventoryBalance = new InventoryBalance();
				$newInventoryBalance->product_id = $data['product_id'];
				$newInventoryBalance->lorry_id = $lorryId;
				$newInventoryBalance->quantity = $data['quantity'];
				$newInventoryBalance->save();

				// Create an inventory transaction record
				$inventoryTransaction = new InventoryTransaction();
				$inventoryTransaction->type = 1;
				$inventoryTransaction->lorry_id = $lorryId;
				$inventoryTransaction->product_id = $data['product_id'];
				$inventoryTransaction->quantity = $data['quantity'];
				$inventoryTransaction->date = date("Y-m-d H:i:s");
				$inventoryTransaction->user = Auth::user()->email . ' (' . Auth::user()->name . ')';
				$inventoryTransaction->trip_id = $tripId;
				$inventoryTransaction->save();

				Flash::success('Inventory Balance for lorry ID ' . $lorryId . ' has been inserted successfully.');
			}
		}

		return redirect(route('inventoryBalances.index'));
	}

    public function getstock($lorry_id,$product_id)
    {
        $inventoryBalance = InventoryBalance::where('product_id',$product_id)->where('lorry_id',$lorry_id)->first();
        if(!empty($inventoryBalance)){
            if($inventoryBalance->quantity > 0){
                return response()->json(['status' => true, 'message' => 'Stock found!', 'quantity' => $inventoryBalance->quantity]);
            }else{
                return response()->json(['status' => false,'message' => 'Stock not found!', 'quantity' => 0]);
            }
        }else{
            return response()->json(['status' => false, 'message' => 'Stock not found!', 'quantity' => 0]);
        }
    }

    public function stockout(Request $request)
    {
        $data = $request->all();
        $lorryIds = $data['lorry_id']; // This will be an array of selected lorry IDs

        foreach ($lorryIds as $lorryId) {
            $activeDriver = Driver::where('lorry_id', $lorryId)->whereNotNull('trip_id')->first();
            $tripId = $activeDriver->trip_id ?? null;

            $inventoryBalance = InventoryBalance::where('product_id', $data['product_id'])
                ->where('lorry_id', $lorryId)
                ->first();

            if (empty($inventoryBalance)) {
                // No record yet — create with negative quantity (negative stock allowed)
                $inventoryBalance = new InventoryBalance();
                $inventoryBalance->product_id = $data['product_id'];
                $inventoryBalance->lorry_id = $lorryId;
                $inventoryBalance->quantity = 0;
            }

            $newQty = $inventoryBalance->quantity - $data['quantity'];
            $inventoryBalance->quantity = $newQty;
            $inventoryBalance->save();

            $inventorytransaction = new InventoryTransaction();
            $inventorytransaction->type = 2;
            $inventorytransaction->lorry_id = $lorryId;
            $inventorytransaction->product_id = $data['product_id'];
            $inventorytransaction->quantity = $data['quantity'] * -1;
            $inventorytransaction->date = date("Y-m-d H:i:s");
            $inventorytransaction->user = Auth::user()->email . ' (' . Auth::user()->name . ')';
            $inventorytransaction->trip_id = $tripId;
            $inventorytransaction->save();

            if ($newQty < 0) {
                Flash::warning('Stock Out recorded. Lorry ' . $lorryId . ' now has negative balance (' . $newQty . ').');
            } else {
                Flash::success('Inventory Balance for lorry ID ' . $lorryId . ' has been updated successfully.');
            }
        }

        return redirect(route('inventoryBalances.index'));
    }
}
