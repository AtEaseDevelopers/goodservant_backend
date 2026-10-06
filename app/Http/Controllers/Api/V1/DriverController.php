<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\InvoicePdf;
use Illuminate\Support\Facades\Storage;
use Datetime;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Kelindan;
use App\Models\Lorry;
use App\Models\Task;
use App\Models\TaskTransfer;
use App\Models\Assign;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SpecialPrice;
use App\Models\Customer;
use App\Models\InvoicePayment;
use App\Models\InvoiceDetail;
use App\Models\Code;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransfer;
use App\Models\TripInventoryBalance;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\foc;
use App\Models\DriverLocation;
use App\Models\Language;
use App\Models\MobileTranslationVersion;
use App\Models\MobileTranslation;
use App\Models\MobileErrorLog;
use App\Models\PaymentAttachment;
use Carbon\Carbon;

class DriverController extends Controller
{
    protected $message_separator = "|";
    //Auth
    public function login(Request $request){
        // return "000002" <=> "000002";
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'employeeid' => 'required|string',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $driver = Driver::where('employeeid', $data['employeeid'])->where('password', $data['password'])->first();
            if(!empty($driver)){
                $session = $driver->session;
                $driver->session = session_create_id();
                $driver->save();

                $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
                if(!empty($trip)){
                    if($trip->type == 2){
                        $status = false;
                    }else{
                        $status = true;
                    }
                }else{
                    $status = false;
                }

                $colorcode = Code::where('code','color_code_'.date("D"))->first()['value'] ?? '';

                if($status){
                    if($session == null){
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.login_successfully',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => true,
                                        'trip' => $trip
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }else{
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.previous_session_override',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => true,
                                        'trip' => $trip
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }
                }else{
                    if($session == null){
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.login_successfully',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => false
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }else{
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.previous_session_override',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => false
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }
                }

            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_credential',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function logout(Request $request){
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'session' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $driver = Driver::where('session', $data['session'])->first();
            if(!empty($driver)){
                $driver->session = NULL;
                $driver->save();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.login_successfully',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function session(Request $request){
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'session' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $driver = Driver::where('session', $data['session'])->first();
            $colorcode = Code::where('code','color_code_'.date("D"))->first()['value'] ?? '';
            if(!empty($driver)){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.session_found',

                    'data' => $driver,
                    'colorcode' => $colorcode
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function location(Request $request){
        $data = $request->all();
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $DriverLocation = new DriverLocation();
            $DriverLocation->date = $data['date'];
            $DriverLocation->latitude = $data['latitude'];
            $DriverLocation->longitude = $data['longitude'];
            $DriverLocation->driver_id = $trip->driver_id;
            $DriverLocation->kelindan_id = $trip->kelindan_id;
            $DriverLocation->lorry_id = $trip->lorry_id;
            $DriverLocation->save();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.driver_location_had_been_updated_successfully',
                'data' => $DriverLocation
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Client-side crash/error reporting from the mobile app - lets the
     * admin see mobile-only bugs (parsing crashes etc. that never touch a
     * backend endpoint) the same way api_logs lets them see backend ones.
     * Deliberately lenient: logs even without a valid session/driver rather
     * than rejecting, since the whole point is to capture failures.
     */
    public function errorlog(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();

            $data = $request->all();
            $log = new MobileErrorLog();
            $log->driver_id = $driver->id ?? null;
            $log->app_version = $data['app_version'] ?? null;
            $log->screen = $data['screen'] ?? null;
            $log->message = $data['message'] ?? null;
            $log->stack_trace = $data['stack_trace'] ?? null;
            $log->save();

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.error_logged',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //Trip
    public function checktrip(Request $request){
        try{
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //process
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => [
                            'status' => false
                        ]
                    ], 200);
                }else{
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_started',
                        'data' => [
                            'status' => true,
                            'trip' => $trip
                        ]
                    ], 200);
                }
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => [
                        'status' => false
                    ]
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function starttrip(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'kelindan_id' => 'nullable|numeric',
                'lorry_id' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            // $kelindan = Kelindan::where('id', $data['kelindan_id'])->first();
            // if(empty($kelindan)){
            //     return response()->json([
            //         'result' => false,
            //         'message' => __LINE__.$this->message_separator.'Invalid Kelindan',
            //         'data' => null
            //     ], 400);
            // }
            $lorry = Lorry::where('id', $data['lorry_id'])->first();
            if(empty($lorry)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_lorry',
                    'data' => null
                ], 400);
            }
            //process
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    //insert trip
                    $newtrip = new Trip();
                    $newtrip->driver_id = $driver->id;
                    $newtrip->kelindan_id = $data['kelindan_id'] ?? 0;
                    $newtrip->lorry_id = $data['lorry_id'];
                    $newtrip->type = 1;
                    $newtrip->date = date("Y-m-d H:i:s");
                    $newtrip->save();
                    //record active trip on driver
                    Driver::where('id', $driver->id)->update(['trip_id' => $newtrip->id, 'lorry_id' => $data['lorry_id']]);
                    //snapshot lorry stock at start of trip
                    $startbalances = InventoryBalance::where('lorry_id', $data['lorry_id'])->get();
                    foreach($startbalances as $startbalance){
                        TripInventoryBalance::create([
                            'trip_id' => $newtrip->id,
                            'driver_id' => $driver->id,
                            'lorry_id' => $data['lorry_id'],
                            'product_id' => $startbalance->product_id,
                            'quantity' => $startbalance->quantity,
                            'type' => TripInventoryBalance::TYPE_START,
                        ]);
                    }
                    //generate task
                    $assigns = Assign::where('driver_id', $driver->id)->orderby('sequence','asc')->get()->toarray();
                    $count = 1;
                    foreach($assigns as $assign){
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $driver->id;
                        $task->customer_id = $assign['customer_id'];
                        $task->sequence = $count;
                        $task->status = 0;
                        $task->trip_id = $newtrip->id;
                        $task->save();
                        $count = $count + 1;
                    }
                    $invoices = Invoice::where('driver_id', $driver->id)->where('status',0)->where('date',date('Y-m-d'))->get()->toarray();
                    foreach($invoices as $invoice){
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $driver->id;
                        $task->customer_id = $invoice['customer_id'];
                        $task->invoice_id = $invoice['id'];
                        $task->sequence = $count;
                        $task->status = 0;
                        $task->trip_id = $newtrip->id;
                        $task->save();
                        $count = $count + 1;
                    }
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_started_successfully',
                        'data' => $newtrip
                    ], 200);
                }else{
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_started',
                        'data' => null
                    ], 401);
                }
            }else{
                //insert trip
                $newtrip = new Trip();
                $newtrip->driver_id = $driver->id;
                $newtrip->kelindan_id = $data['kelindan_id'] ?? 0;
                $newtrip->lorry_id = $data['lorry_id'];
                $newtrip->type = 1;
                $newtrip->date = date("Y-m-d H:i:s");
                $newtrip->save();
                //record active trip on driver
                Driver::where('id', $driver->id)->update(['trip_id' => $newtrip->id, 'lorry_id' => $data['lorry_id']]);
                //snapshot lorry stock at start of trip
                $startbalances = InventoryBalance::where('lorry_id', $data['lorry_id'])->get();
                foreach($startbalances as $startbalance){
                    TripInventoryBalance::create([
                        'trip_id' => $newtrip->id,
                        'driver_id' => $driver->id,
                        'lorry_id' => $data['lorry_id'],
                        'product_id' => $startbalance->product_id,
                        'quantity' => $startbalance->quantity,
                        'type' => TripInventoryBalance::TYPE_START,
                    ]);
                }
                //generate task
                $assigns = Assign::where('driver_id', $driver->id)->orderby('sequence','asc')->get()->toarray();
                $count = 1;
                foreach($assigns as $assign){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $assign['customer_id'];
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->save();
                    $count = $count + 1;
                }
                $invoices = Invoice::where('driver_id', $driver->id)->where('status',0)->where('date',date('Y-m-d'))->get()->toarray();
                foreach($invoices as $invoice){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $invoice['customer_id'];
                    $task->invoice_id = $invoice['id'];
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->save();
                $count = $count + 1;
                }
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_started_successfully',
                    'data' => $newtrip
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function endtrip(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invalid session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'kelindan_id' => 'required|numeric',
                'lorry_id' => 'required|numeric',
                'cash' => 'required|numeric',
                'advance_amount' => 'nullable|numeric',
                'wastage' => 'present|array',
                'wastage.*.product_id' => 'required|numeric',
                'wastage.*.quantity' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            // $kelindan = Kelindan::where('id', $data['kelindan_id'])->first();
            // if(empty($kelindan)){
            //     return response()->json([
            //         'result' => false,
            //         'message' => __LINE__.$this->message_separator.'Invalid Kelindan',
            //         'data' => null
            //     ], 400);
            // }
            $lorry = Lorry::where('id', $data['lorry_id'])->first();
            if(empty($lorry)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invalid Lorry',
                    'data' => null
                ], 400);
            }
            //process
            DB::beginTransaction();
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    DB::rollback();
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'Trip had not started',
                        'data' => null
                    ], 400);
                }else{
                    $newtrip = new Trip();  
                    $newtrip->driver_id = $driver->id;   
                    $newtrip->kelindan_id = $data['kelindan_id'];
                    $newtrip->lorry_id = $data['lorry_id'];
                    $newtrip->cash = $data['cash'];
                    $newtrip->advance_amount = $data['advance_amount'] ?? 0;
                    $newtrip->type = 2;
                    $newtrip->date = date("Y-m-d H:i:s");
                    $newtrip->save();
                    //cancelled task
                    $task = Task::where('driver_id', $driver->id)->where('date',date('Y-m-d'))->whereIn('status',[0,1])->update(['trip_id'=>$newtrip->id,'status' => 9]);
                    foreach($data["wastage"] as $wastage) {
                        $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)->where('product_id',$wastage['product_id'])->first();
                        if(empty($inventorybalance)){
                            // No record yet — create with negative quantity (negative stock allowed)
                            $inventorybalance = new InventoryBalance();
                            $inventorybalance->lorry_id = $trip->lorry_id;
                            $inventorybalance->product_id = $wastage['product_id'];
                            $inventorybalance->quantity = 0 - $wastage["quantity"];
                            $inventorybalance->save();
                        }else{
                            // Decrement regardless — negative balance is allowed
                            $inventorybalance->quantity = $inventorybalance->quantity - $wastage["quantity"];
                            $inventorybalance->save();
                        }
                        $inventorytransaction = New InventoryTransaction();
                        $inventorytransaction->lorry_id = $trip->lorry_id;
                        $inventorytransaction->product_id = $wastage["product_id"];
                        $inventorytransaction->quantity = $wastage["quantity"] * -1;
                        $inventorytransaction->type = 5;
                        $inventorytransaction->date = date('Y-m-d H:i:s');
                        $inventorytransaction->user = $driver->employeeid . " (" . $driver->name . ")";
                        $inventorytransaction->trip_id = $driver->trip_id;
                        $inventorytransaction->save();
                    }
                    //snapshot lorry stock at end of trip, then clear driver's active trip
                    $endbalances = InventoryBalance::where('lorry_id', $trip->lorry_id)->get();
                    foreach($endbalances as $endbalance){
                        TripInventoryBalance::create([
                            'trip_id' => $driver->trip_id,
                            'driver_id' => $driver->id,
                            'lorry_id' => $trip->lorry_id,
                            'product_id' => $endbalance->product_id,
                            'quantity' => $endbalance->quantity,
                            'type' => TripInventoryBalance::TYPE_END,
                        ]);
                    }
                    Driver::where('id', $driver->id)->update(['trip_id' => null, 'lorry_id' => null]);
                    DB::commit();
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'Trip had been ended successfully',
                        'data' => $newtrip
                    ], 200);
                }
            }else{
                DB::rollback();
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Trip had not started',
                    'data' => null
                ], 400);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Summary of this driver's most recently ENDED trip (Daily Sales
     * Summary, per the requirement doc: "accessible after performing an
     * end trip"). Each end-trip() call writes a fresh type=2 Trip row; the
     * type=1 row immediately before it is the trip that was actually
     * driven, and its id is what Invoice.trip_id / TripInventoryBalance
     * rows were tagged with while the trip was active.
     */
    public function getlasttripsummary(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }

            $endTrip = Trip::where('driver_id', $driver->id)->where('type', 2)->orderby('date','desc')->first();
            if(empty($endTrip)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.no_trip_found',
                    'data' => null
                ], 200);
            }

            $startTrip = Trip::where('driver_id', $driver->id)->where('type', 1)
                ->where('date', '<=', $endTrip->getRawOriginal('date'))
                ->orderby('date','desc')->first();
            $tripId = $startTrip->id ?? null;

            $invoices = Invoice::where('driver_id', $driver->id)
                ->where('trip_id', $tripId)
                ->with('invoicedetail.product', 'customer')
                ->get();
            $salesOrderCount = SalesOrder::where('driver_id', $driver->id)->where('trip_id', $tripId)->count();

            $totalAmount = 0;
            $totalCredit = 0;
            $productsSold = [];
            foreach($invoices as $invoice){
                $invoiceTotal = 0;
                foreach($invoice->invoicedetail as $line){
                    $invoiceTotal += $line->totalprice;
                    $productId = $line->product_id;
                    if(!isset($productsSold[$productId])){
                        $productsSold[$productId] = ['name' => $line->product?->name ?? 'Unknown', 'quantity' => 0];
                    }
                    $productsSold[$productId]['quantity'] += $line->quantity;
                }
                $totalAmount += $invoiceTotal;
                if($invoice->paymentterm == 2){
                    $totalCredit += $invoiceTotal;
                }
            }

            $stockSummary = TripInventoryBalance::where('trip_id', $tripId)
                ->where('type', TripInventoryBalance::TYPE_END)
                ->with('product')
                ->get()
                ->map(function($row){
                    return [
                        'product_id' => $row->product_id,
                        'product_code' => $row->product?->code,
                        'product_name' => $row->product?->name,
                        'quantity' => $row->quantity,
                    ];
                });

            $duration = null;
            if($startTrip){
                $duration = $endTrip->getRawOriginal('date') && $startTrip->getRawOriginal('date')
                    ? \Carbon\Carbon::parse($startTrip->getRawOriginal('date'))->diffForHumans(\Carbon\Carbon::parse($endTrip->getRawOriginal('date')), true)
                    : null;
            }

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.trip_summary_found',
                'data' => [
                    'trip_summary' => [
                        'trip_id' => $tripId,
                        'driver_name' => $driver->name,
                        'start_time' => optional($startTrip)->getRawOriginal('date'),
                        'end_time' => $endTrip->getRawOriginal('date'),
                        'trip_duration' => $duration,
                    ],
                    'sales_summary' => [
                        'total_invoices' => $invoices->count(),
                        'total_sales_orders' => $salesOrderCount,
                        'total_amount' => round($totalAmount, 2),
                        'total_credit' => round($totalCredit, 2),
                        'total_cash' => round($totalAmount - $totalCredit, 2),
                    ],
                    'stock_summary' => $stockSummary,
                    'products_sold' => array_values($productsSold),
                ]
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function trip(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $validator = Validator::make($request->all(), [
            'kelindan_id' => 'required|numeric',
            'lorry_id' => 'required|numeric',
            'type' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        // $kelindan = Kelindan::where('id', $data['kelindan_id'])->first();
        // if(empty($kelindan)){
        //     return response()->json([
        //         'result' => false,
        //         'message' => __LINE__.$this->message_separator.'Invalid Kelindan',
        //         'data' => null
        //     ], 400);
        // }
        $lorry = Lorry::where('id', $data['lorry_id'])->first();
        if(empty($lorry)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_lorry',
                'data' => null
            ], 400);
        }
        if(!($data['type'] == 1 || $data['type'] == 2)){
            return response()->json([
               'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_type',
                'data' => null
            ], 400);
        }
        //process
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if($data['type'] == 1){
            if(!empty($trip)){
                if($trip->type == 2){
                    //insert trip
                    $newtrip = new Trip();
                    $newtrip->driver_id = $driver->id;
                    $newtrip->kelindan_id = $data['kelindan_id'];
                    $newtrip->lorry_id = $data['lorry_id'];
                    $newtrip->type = 1;
                    $newtrip->date = date("Y-m-d H:i:s");
                    $newtrip->save();
                    //record active trip on driver
                    Driver::where('id', $driver->id)->update(['trip_id' => $newtrip->id, 'lorry_id' => $data['lorry_id']]);
                    //snapshot lorry stock at start of trip
                    $startbalances = InventoryBalance::where('lorry_id', $data['lorry_id'])->get();
                    foreach($startbalances as $startbalance){
                        TripInventoryBalance::create([
                            'trip_id' => $newtrip->id,
                            'driver_id' => $driver->id,
                            'lorry_id' => $data['lorry_id'],
                            'product_id' => $startbalance->product_id,
                            'quantity' => $startbalance->quantity,
                            'type' => TripInventoryBalance::TYPE_START,
                        ]);
                    }
                    //generate task
                    $assigns = Assign::where('driver_id', $driver->id)->orderby('sequence','asc')->get()->toarray();
                    $count = 1;
                    foreach($assigns as $assign){
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $driver->id;
                        $task->customer_id = $assign['customer_id'];
                        $task->sequence = $count;
                        $task->status = 0;
                        $task->save();
                        $count = $count + 1;
                    }
                    $invoices = Invoice::where('driver_id', $driver->id)->where('status',0)->where('date',date('Y-m-d'))->get()->toarray();
                    foreach($invoices as $invoice){
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $driver->id;
                        $task->customer_id = $invoice['customer_id'];
                        $task->invoice_id = $invoice['id'];
                        $task->sequence = $count;
                        $task->status = 0;
                        $task->save();
                        $count = $count + 1;
                    }
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_started_successfully',
                        'data' => $newtrip
                    ], 200);
                }else{
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_started',
                        'data' => null
                    ], 401);
                }
            }else{
                //insert trip
                $newtrip = new Trip();
                $newtrip->driver_id = $driver->id;
                $newtrip->kelindan_id = $data['kelindan_id'];
                $newtrip->lorry_id = $data['lorry_id'];
                $newtrip->type = 1;
                $newtrip->date = date("Y-m-d H:i:s");
                $newtrip->save();
                //record active trip on driver
                Driver::where('id', $driver->id)->update(['trip_id' => $newtrip->id, 'lorry_id' => $data['lorry_id']]);
                //snapshot lorry stock at start of trip
                $startbalances = InventoryBalance::where('lorry_id', $data['lorry_id'])->get();
                foreach($startbalances as $startbalance){
                    TripInventoryBalance::create([
                        'trip_id' => $newtrip->id,
                        'driver_id' => $driver->id,
                        'lorry_id' => $data['lorry_id'],
                        'product_id' => $startbalance->product_id,
                        'quantity' => $startbalance->quantity,
                        'type' => TripInventoryBalance::TYPE_START,
                    ]);
                }
                //generate task
                $assigns = Assign::where('driver_id', $driver->id)->orderby('sequence','asc')->get()->toarray();
                $count = 1;
                foreach($assigns as $assign){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $assign['customer_id'];
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->save();
                    $count = $count + 1;
                }
                $invoices = Invoice::where('driver_id', $driver->id)->where('status',0)->where('date',date('Y-m-d'))->get()->toarray();
                foreach($invoices as $invoice){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $invoice['customer_id'];
                    $task->invoice_id = $invoice['id'];
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->save();
                    $count = $count + 1;
                }
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_started_successfully',
                    'data' => $newtrip
                ], 200);
            }
        }else if($data['type'] == 2){
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 401);
                }else{
                    $newtrip = new Trip();
                    $newtrip->driver_id = $driver->id;
                    $newtrip->kelindan_id = $data['kelindan_id'];
                    $newtrip->lorry_id = $data['lorry_id'];
                    $newtrip->type = 2;
                    $newtrip->date = date("Y-m-d H:i:s");
                    $newtrip->save();
                    //snapshot lorry stock at end of trip, then clear driver's active trip
                    $endbalances = InventoryBalance::where('lorry_id', $data['lorry_id'])->get();
                    foreach($endbalances as $endbalance){
                        TripInventoryBalance::create([
                            'trip_id' => $driver->trip_id,
                            'driver_id' => $driver->id,
                            'lorry_id' => $data['lorry_id'],
                            'product_id' => $endbalance->product_id,
                            'quantity' => $endbalance->quantity,
                            'type' => TripInventoryBalance::TYPE_END,
                        ]);
                    }
                    Driver::where('id', $driver->id)->update(['trip_id' => null, 'lorry_id' => null]);
                    //cancelled task
                    $task = Task::where('driver_id', $driver->id)->where('date',date('Y-m-d'))->whereIn('status',[0,1])->update(['status' => 9]);
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_ended_successfully',
                        'data' => $newtrip
                    ], 200);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
        }
    }

    //Kelindan
    public function getkelindan(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //process
            // $kelindan = Kelindan::where('status',1)->select('id','name')->get()->toarray();
            $kelindan = DB::select("select k.id, k.name from kelindans k left join ( select driver_id, type, kelindan_id from trips where id in ( select max(id) as id from trips group by driver_id ) ) b on k.id = b.kelindan_id and b.type = 1 where b.kelindan_id is null;");
            if(count($kelindan) != 0){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.kelindan_found',
                    'data' => $kelindan
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.kelindan_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //Lorry
    public function getlorry(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //process
            // $lorry = Lorry::where('status',1)->select('id','lorryno')->get()->toarray();
            $lorry = DB::select("select l.id, l.lorryno from lorrys l left join ( select driver_id, type, lorry_id from trips where id in (select max(id) as id from trips group by driver_id) ) b on l.id = b.lorry_id and b.type = 1 where b.lorry_id is null;");
            if(count($lorry) != 0){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.lorry_found',
                    'data' => $lorry
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.lorry_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //Task
    public function gettask(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $task = Task::where('driver_id', $driver->id)
                ->where('date',date('Y-m-d'))
                ->where(function ($query) use ($trip) {
                    $query->where('trip_id', $trip->id)
                        ->orWhere('trip_id', null);
                })
                // ->whereIn('trip_id',[NULL,$trip->id])
                ->with('customer.activefoc')
                ->with('invoice.invoicedetail.product:id,code,name')
                ->get()->toarray();
            if(count($task) != 0){
                $message = true;
                foreach($task as $c=>$t){
                    if(asset($t['customer']['id'])){
                        $task[$c]['customer']['credit'] = round(  (DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$t['customer']['id'].');')[0]->credit ?? 0) ,2);
                        // $task[$c]['customer']['credit'] = $t['customer']['id'];
                        $task[$c]['customer']['product'] = DB::table('products')
                            ->leftJoin('special_prices', function($join) use($t)
                                {
                                    $join->on('special_prices.customer_id','=',DB::raw("'".$t['customer']['id']."'"));
                                    $join->on('special_prices.product_id', '=', 'products.id');
                                    $join->on('special_prices.status', '=', DB::raw("'1'"));
                                })
                            ->where('products.status','1')
                            ->select('products.id','products.code','products.name',DB::raw('coalesce(special_prices.price,products.price) as "price"'))
                            ->get();
                        $task[$c]['customer']['groupcompany'] = DB::table('companies')
                            ->where('companies.group_id',explode(',',$t['customer']['group'])[0])
                            ->select('companies.*')
                            ->first() ?? null;
                    }
                }
            }else{
                $message = false;
            }
            $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)->with('product')->get()->toarray();
            if($message){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.task_found',
                    'data' => [
                        'task' => $task,
                        'stock' => $inventorybalance
                    ]
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.task_not_found',
                    'data' => [
                        'task' => null,
                        'stock' => $inventorybalance
                    ]
                ], 200);

            }

        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function gettaskpage(Request $request){
        try{
            $data = $request->all();
            $size = 20;
            if(isset($data['size']))
            {
                $size = $data['size'];
            }
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $task = Task::where('driver_id', $driver->id)
                ->where('date',date('Y-m-d'))
                //->where('status','!=',9)
                //->where('status','!=',0)
                ->where(function ($query) use ($trip) {
                    $query->where('trip_id', $trip->id)
                        ->orWhere('trip_id', null);
                })
                // ->whereIn('trip_id',[NULL,$trip->id])
                ->with('customer.activefoc')
                ->with('invoice.invoicedetail.product:id,code,name')
                ->paginate($size);

            if(count($task) != 0){
                $message = true;
                foreach($task as $c=>$t){
                    if(asset($t['customer']['id'])){
                        $task[$c]['customer']['credit'] = round(  (DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$t['customer']['id'].');')[0]->credit ?? 0) ,2);
                        // $task[$c]['customer']['credit'] = $t['customer']['id'];
                        $task[$c]['customer']['product'] = DB::table('products')
                            ->leftJoin('special_prices', function($join) use($t)
                                {
                                    $join->on('special_prices.customer_id','=',DB::raw("'".$t['customer']['id']."'"));
                                    $join->on('special_prices.product_id', '=', 'products.id');
                                    $join->on('special_prices.status', '=', DB::raw("'1'"));
                                })
                            ->where('products.status','1')
                            ->select('products.id','products.code','products.name',DB::raw('coalesce(special_prices.price,products.price) as "price"'))
                            ->get();
                        $task[$c]['customer']['groupcompany'] = DB::table('companies')
                            ->where('companies.group_id',explode(',',$t['customer']['group'])[0])
                            ->select('companies.*')
                            ->first() ?? null;
                    }
                }
            }else{
                $message = false;
            }
            $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)->with('product')->get()->toarray();
            if($message){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.task_found',
                    'data' => [
                        'task' => $task,
                        'stock' => $inventorybalance
                    ]
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.task_not_found',
                    'data' => [
                        'task' => null,
                        'stock' => $inventorybalance
                    ]
                ], 200);

            }

        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function starttask(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $task = Task::where('id',$data['task_id'])->first();
            if(empty($task)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_task',
                    'data' => null
                ], 400);
            }else{
                if($task->status == 8){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_completed',
                        'data' => null
                    ], 400);
                }
                if($task->status == 9){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled',
                        'data' => null
                    ], 400);
                }
                if($task->status == 1){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_in_progress',
                        'data' => null
                    ], 400);
                }
            }
            //process
            $task->status = 1;
            $task->save();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.task_had_been_started_successfully',
                'data' => $task
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function canceltask(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $task = Task::where('id',$data['task_id'])->first();
            if(empty($task)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_task',
                    'data' => null
                ], 400);
            }else{
                if($task->status == 8){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_completed',
                        'data' => null
                    ], 400);
                }
                if($task->status == 9){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled',
                        'data' => null
                    ], 400);
                }
            }
            //process
            $task->status = 9;
            $task->save();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled_successfully',
                'data' => $task
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getproduct(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            if(isset($data['customer_id'])){
                $customer = Customer::where('id', $data['customer_id'])->first();
                if(empty($customer)){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                        'data' => null
                    ], 400);
                }
            }
            //process
            if(isset($data['customer_id'])){
                $product = DB::table('products')
                ->leftJoin('special_prices', function($join) use($data)
                    {
                        $join->on('special_prices.customer_id','=',DB::raw("'".$data['customer_id']."'"));
                        $join->on('special_prices.product_id', '=', 'products.id');
                        $join->on('special_prices.status', '=', DB::raw("'1'"));
                    })
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('inventory_balances', function($join) use($driver)
                    {
                        $join->on('inventory_balances.product_id', '=', 'products.id');
                        $join->on('inventory_balances.lorry_id', '=', DB::raw($driver->lorry_id ?? 0));
                    })
                ->where('products.status','1')
                ->select('products.id','products.code','products.name','products.image_path','products.type_id','product_types.name as type_name',DB::raw('coalesce(inventory_balances.quantity,0) as "quantity"'),DB::raw('coalesce(special_prices.price,products.price) as "price"'))
                ->get()
                ->map(function($item){
                    $item->image_url = $item->image_path ? url($item->image_path) : null;
                    return $item;
                });
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.product_found',
                    'data' => $product
                ], 200);
            }else{
                $product = DB::table('products')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('inventory_balances', function($join) use($driver)
                    {
                        $join->on('inventory_balances.product_id', '=', 'products.id');
                        $join->on('inventory_balances.lorry_id', '=', DB::raw($driver->lorry_id ?? 0));
                    })
                ->where('products.status','1')
                ->select('products.id','products.code','products.name','products.image_path','products.type_id','product_types.name as type_name',DB::raw('coalesce(inventory_balances.quantity,0) as "quantity"'),DB::raw('products.price as "price"'))
                ->get()
                ->map(function($item){
                    $item->image_url = $item->image_path ? url($item->image_path) : null;
                    return $item;
                });
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.product_found',
                    'data' => $product
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getcustomer(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //process
            // Ordered by the driver's saved task sequence (assigns.sequence,
            // maintained by the drag-and-drop reorder screen); customers that
            // only appear via invoices (no assign row) come last, by name.
            // has_pending_so: the customer still has an SO from a previous day
            // that was neither converted nor cancelled - the app shows a red
            // dot on the customer card so the driver notices overnight orders.
            $customer = DB::select("SELECT customers.*,COALESCE(b.credit,0) as credit, EXISTS(SELECT 1 FROM sales_orders so WHERE so.customer_id = customers.id AND so.driver_id = ? AND so.status != 2 AND so.deliveryorder_id IS NULL AND so.invoice_id IS NULL AND so.deleted_at IS NULL AND so.date < CURDATE()) as has_pending_so FROM customers customers RIGHT JOIN ( SELECT customer_id, MIN(sequence) as sequence FROM ( SELECT customer_id, sequence FROM assigns WHERE driver_id = ? UNION ALL SELECT customer_id, NULL as sequence FROM invoices WHERE driver_id = ? ) u GROUP BY customer_id ) a on a.customer_id = customers.id LEFT JOIN ( select invoices.customer_id, sum(invoice_details.totalprice) as totalprice, COALESCE(paymentsummary.amount,0) as paid, ( sum(invoice_details.totalprice) - COALESCE(paymentsummary.amount,0) ) as credit from invoices left join invoice_details on invoices.id = invoice_details.invoice_id left join ( select invoice_payments.customer_id, sum(COALESCE(invoice_payments.amount,0)) as amount from invoice_payments where invoice_payments.status = 1 group by invoice_payments.customer_id ) as paymentsummary on invoices.customer_id = paymentsummary.customer_id where invoices.status = 1 group by invoices.customer_id, paymentsummary.customer_id, paymentsummary.amount ) b on b.customer_id = customers.id ORDER BY (a.sequence IS NULL) ASC, a.sequence ASC, customers.company ASC", [$driver->id, $driver->id, $driver->id]);
            if(count($customer) != 0){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.customer_found',
                    'data' => $customer
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.customer_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customerdetail(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id', $data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null], 400);
            }
            //process
            // status != 2: cancelled invoices stay out of the ledger (and out
            // of the app's pay-credit invoice picker, which reads this list).
            // outstanding = invoice total minus the completed payments recorded
            // against that specific invoice - drives the app's "fully paid
            // invoices don't show in the pay-credit picker" rule.
            $customer->customerdetail = DB::select("select i.date,i.id,'Invoice' as type, i.invoiceno as name, sum(COALESCE(id.totalprice,0)) as amount, sum(COALESCE(id.totalprice,0)) - COALESCE((select sum(p.amount) from invoice_payments p where p.invoice_id = i.id and p.status = 1),0) as outstanding from invoices i left join invoice_details id on i.id = id.invoice_id where i.customer_id = ".$customer->id." and i.status != 2 group by i.date, i.id, i.invoiceno, i.customer_id union select ip.created_at as date,ip.id, 'Payment' as type, '' as name, ip.amount as amount, 0 as outstanding from invoice_payments ip where ip.customer_id = ".$customer->id." and ip.status != 2;");
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.customer_found',
                'data' => $customer
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customermakepayment(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|numeric',
                'amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id', $data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            //process
            $invoicepayment = New InvoicePayment();
            $invoicepayment->customer_id = $customer->id;
            $invoicepayment->amount = $data['amount'];
            $invoicepayment->type = 1;
            $invoicepayment->status = 1;
            $invoicepayment->driver_id = $driver->id;
            $invoicepayment->approve_by = $driver->name;
            $invoicepayment->approve_at = date('Y-m-d H:i:s');
            $invoicepayment->save();
            $invoicepayment->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$invoicepayment->customer_id.');')[0]->credit,2);
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.payment_insert_successfully_found',
                'data' => $invoicepayment
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customerinvoice(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|numeric',
                'invoice_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $invoice = Invoice::where('customer_id', $data['customer_id'])
            ->where('id', $data['invoice_id'])
            ->with('invoicedetail.product')
            ->with('customer')
            ->with('driver')
            ->with('invoicepayment')
            ->first();
            if(empty($invoice)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_not_found',
                    'data' => null
                ], 200);
            }else{
               
               
                  
             try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
                
                if($credit)
                {
                    $invoice->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $invoice->newcredit  = 0;
            }
            
               
               //$invoice->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');')[0]->credit,2);
               
               
               
                $invoice->customer->groupcompany = DB::table('companies')
                ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
                ->select('companies.*')
                ->first() ?? null;
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_found',
                    'data' => $invoice
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customerpayment(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|numeric',
            'payment_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        //process
        $invoicepayment = InvoicePayment::where('customer_id', $data['customer_id'])->where('id', $data['payment_id'])->with('customer')->first();
        if(empty($invoicepayment)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_payment_not_found',
                'data' => null
            ], 200);
        }else{
            
            
              try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoicepayment->updated_at.'",'.$invoicepayment->customer_id.');');
                
                if($credit)
                {
                    $invoicepayment->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $invoicepayment->newcredit  = 0;
            }
            
            //$invoicepayment->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.$invoicepayment->created_at.'",'.$invoicepayment->customer_id.');')[0]->credit,2);
            
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_payment_found',
                'data' => $invoicepayment
            ], 200);
        }
    }

    public function addinvoice(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 401);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'date' => 'date_format:Y-m-d H:i:s',
                'customer_id' => 'required|numeric',
                'type' => 'required|numeric|gt:0|lt:6',
                'remark' => 'present|nullable|string',
                'invoice_id' => 'present|nullable|numeric',
                'invoiceno' => 'present|nullable|string',
                'invoicedetail' => 'required|array',
                'invoicedetail.*.product_id' => 'required',
                'invoicedetail.*.quantity' => 'required',
                'invoicedetail.*.price' => 'required',
                'invoicedetail.*.foc' => 'required|boolean',
                'attachments.*' => 'nullable|image|max:10240'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id',$data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            //process
            DB::beginTransaction();
            $extinvoice = Invoice::where('id',$data['invoice_id'])->where('status',0)->first();
            $invoiceno = null;
            $id = null;
            if(!empty($extinvoice)){
                if($extinvoice->invoiceno != $data['invoiceno'] && $data['invoiceno'] != null){
                    $invoiceno = $data['invoiceno'] . "(" . $extinvoice->invoiceno . ")";
                }else{
                    $invoiceno = $extinvoice->invoiceno;
                }
                $id = $extinvoice->id;
                Invoice::where('id',$data['invoice_id'])->delete();
                InvoiceDetail::where('invoice_id',$data['invoice_id'])->delete();
            }else{
                if($data['invoiceno'] != null){
                    $invoiceno = $data['invoiceno'];
                    $invoicerunningnumber = substr($invoiceno, -6);
                    if(($driver->invoice_runningnumber <=> $invoicerunningnumber) == -1){
                        Driver::where('id',$driver->id)->update(['invoice_runningnumber' => $invoicerunningnumber]);
                    }

                }else{
                    $invoiceno = Code::nextRunningNumber('invoicerunningnumber', 'IV');
                }
            }
            $invoice = new Invoice();
            if($id != null){
                $invoice->id = $id;
            }
            $invoice->date = $data['date'] ?? date('Y-m-d H:i:s');
            $invoice->invoiceno = $invoiceno;
            $invoice->customer_id = $data['customer_id'];
            $invoice->driver_id = $trip->driver_id;
            $invoice->kelindan_id = $trip->kelindan_id;
            $invoice->agent_id = $customer->agent_id;
            $invoice->supervisor_id = $customer->supervisor_id;
            $invoice->paymentterm = $data['type'];
            $invoice->status = 1;
            $invoice->chequeno = $data['cheque_no'];
            $invoice->remark = $data['remark'];
            $invoice->trip_id = $driver->trip_id;
            $invoice->save();
            $totalprice = 0;
            foreach($data['invoicedetail'] as $id){
                $product = Product::where('id',$id['product_id'])->first();
                if(empty($product)){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_product',
                        'data' => null
                    ], 400);
                    DB::rollback();
                }
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $invoice->id;
                $invoicedetail->product_id = $id['product_id'];
                $invoicedetail->quantity = $id['quantity'];
                $invoicedetail->price = $id['price'];
                $invoicedetail->totalprice = $id['quantity'] * $id['price'];
                $totalprice = $totalprice + $invoicedetail->totalprice;
                if($id['foc']) {
                    $invoicedetail->remark = "FOC"; // Mark as FOC but do NOT count towards achievequantity
                } else {
                    // Only update FOC achievequantity if the product is NOT FOC
                    $foc = Foc::where('customer_id', $customer->id)
                        ->where('product_id', $id['product_id'])
                        ->where('startdate', '<=', date('Y-m-d H:i:s'))
                        ->where('enddate', '>', date('Y-m-d H:i:s'))
                        ->where('status', 1)
                        ->first();

                    if($foc) {
                        $newAchieveQuantity = $foc->achievequantity + $id['quantity'];
                        $newStatus = ($newAchieveQuantity >= $foc->quantity) ? 0 : 1;

                        $foc->update([
                            'achievequantity' => $newAchieveQuantity,
                            'status' => $newStatus
                        ]);
                    }
                }
                $invoicedetail->save();
                $inventorybalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->where('product_id', $id['product_id'])->first();
                if(empty($inventorybalance)){
                    $newinventorybalance = New InventoryBalance();
                    $newinventorybalance->lorry_id = $trip->lorry_id;
                    $newinventorybalance->product_id = $id['product_id'];
                    $newinventorybalance->quantity = 0 - $id['quantity'];
                    $newinventorybalance->save();
                }else{
                    $inventorybalance->quantity = $inventorybalance->quantity - $id['quantity'];
                    $inventorybalance->save();
                }
                $inventorytransaction = New InventoryTransaction();
                $inventorytransaction->lorry_id = $trip->lorry_id;
                $inventorytransaction->product_id = $id['product_id'];
                $inventorytransaction->quantity = $id['quantity'] * -1;
                $inventorytransaction->type = 3;
                $inventorytransaction->user = $driver->employeeid . " (".$driver->name.")";
                $inventorytransaction->date = date('Y-m-d H:i:s');
                $inventorytransaction->save();
            }
            if($data['type'] == 1){
                $invoicepayment = New InvoicePayment();
                $invoicepayment->invoice_id = $invoice->id;
                $invoicepayment->type = 1;
                $invoicepayment->customer_id = $invoice->customer_id;
                $invoicepayment->amount = $totalprice;
                $invoicepayment->status = 1;
                $invoicepayment->driver_id = $driver->id;
                $invoicepayment->approve_by = $driver->name;
                $invoicepayment->approve_at = date('Y-m-d H:i:s');
                // Cash the customer handed over; the receipt prints the change
                // (cash_received - total) when this is more than the total.
                if(isset($data['cash_received']) && is_numeric($data['cash_received']) && $data['cash_received'] >= $totalprice){
                    $invoicepayment->cash_received = $data['cash_received'];
                }
                $invoicepayment->save();
            }
            $task = Task::where('customer_id', $data['customer_id'])->where('driver_id',$driver->id)->update(['status' => 8]);
            $this->storePaymentAttachments($request, $invoice);
            DB::commit();
            $iv = Invoice::where('id',$invoice->id)->with('invoicedetail.product', 'paymentAttachments')->get()->first();
            
             
             try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');');
                
                if($credit)
                {
                    $iv->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $iv->newcredit  = 0;
            }
            
            
           //$iv->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');')[0]->credit,2);
            
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_add_successfully',
                'data' => $iv
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Bulk-create invoices that were queued on the device while offline
     * (no/poor connectivity) and are now being synced back once the driver
     * is online again. Each item is processed in its own DB transaction so
     * one bad item doesn't fail the whole batch - the response reports
     * which client_refs succeeded/failed so the mobile queue only keeps
     * retrying the failed ones.
     *
     * Deliberately a SEPARATE, self-contained code path from addinvoice()
     * rather than a shared refactor, so this new bulk/offline path can
     * never regress the existing single-invoice online create path.
     *
     * Running numbers get an "A" prefix (AIV instead of IV) so anyone
     * looking at an invoice can tell it was created while the driver had
     * no signal at the time, same counter/sequence either way.
     */
    public function addinvoicebulk(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(empty($trip) || $trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'invoices' => 'required|array|min:1',
                'invoices.*.client_ref' => 'required|string',
                'invoices.*.date' => 'nullable|date_format:Y-m-d H:i:s',
                'invoices.*.customer_id' => 'required|numeric',
                'invoices.*.type' => 'required|numeric|gt:0|lt:6',
                'invoices.*.remark' => 'present|nullable|string',
                'invoices.*.invoicedetail' => 'required|array',
                'invoices.*.invoicedetail.*.product_id' => 'required',
                'invoices.*.invoicedetail.*.quantity' => 'required',
                'invoices.*.invoicedetail.*.price' => 'required',
                'invoices.*.invoicedetail.*.foc' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }

            $created = [];
            $failed = [];

            foreach($request->input('invoices') as $item){
                DB::beginTransaction();
                try{
                    $customer = Customer::where('id', $item['customer_id'])->first();
                    if(empty($customer)){
                        throw new \Exception('Invalid customer');
                    }

                    // Same counter/format as an online invoice (IV2609/0012),
                    // just with an "A" right before the running number itself
                    // (IV2609/A0012) to flag it as created while offline.
                    $invoiceno = preg_replace('/\/(\d+)$/', '/A$1', Code::nextRunningNumber('invoicerunningnumber', 'IV'));

                    $invoice = new Invoice();
                    $invoice->date = $item['date'] ?? date('Y-m-d H:i:s');
                    $invoice->invoiceno = $invoiceno;
                    $invoice->customer_id = $item['customer_id'];
                    $invoice->driver_id = $trip->driver_id;
                    $invoice->kelindan_id = $trip->kelindan_id;
                    $invoice->agent_id = $customer->agent_id;
                    $invoice->supervisor_id = $customer->supervisor_id;
                    $invoice->paymentterm = $item['type'];
                    $invoice->status = 1;
                    $invoice->chequeno = $item['cheque_no'] ?? null;
                    $invoice->remark = $item['remark'] ?? null;
                    $invoice->trip_id = $driver->trip_id;
                    $invoice->save();

                    $totalprice = 0;
                    foreach($item['invoicedetail'] as $line){
                        $product = Product::where('id', $line['product_id'])->first();
                        if(empty($product)){
                            throw new \Exception('Invalid product');
                        }
                        $invoicedetail = new InvoiceDetail();
                        $invoicedetail->invoice_id = $invoice->id;
                        $invoicedetail->product_id = $line['product_id'];
                        $invoicedetail->quantity = $line['quantity'];
                        $invoicedetail->price = $line['price'];
                        $invoicedetail->totalprice = $line['quantity'] * $line['price'];
                        $totalprice = $totalprice + $invoicedetail->totalprice;
                        if($line['foc']){
                            $invoicedetail->remark = "FOC";
                        } else {
                            $foc = Foc::where('customer_id', $customer->id)
                                ->where('product_id', $line['product_id'])
                                ->where('startdate', '<=', date('Y-m-d H:i:s'))
                                ->where('enddate', '>', date('Y-m-d H:i:s'))
                                ->where('status', 1)
                                ->first();
                            if($foc){
                                $newAchieveQuantity = $foc->achievequantity + $line['quantity'];
                                $newStatus = ($newAchieveQuantity >= $foc->quantity) ? 0 : 1;
                                $foc->update([
                                    'achievequantity' => $newAchieveQuantity,
                                    'status' => $newStatus
                                ]);
                            }
                        }
                        $invoicedetail->save();

                        $inventorybalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->where('product_id', $line['product_id'])->first();
                        if(empty($inventorybalance)){
                            $newinventorybalance = new InventoryBalance();
                            $newinventorybalance->lorry_id = $trip->lorry_id;
                            $newinventorybalance->product_id = $line['product_id'];
                            $newinventorybalance->quantity = 0 - $line['quantity'];
                            $newinventorybalance->save();
                        } else {
                            $inventorybalance->quantity = $inventorybalance->quantity - $line['quantity'];
                            $inventorybalance->save();
                        }

                        $inventorytransaction = new InventoryTransaction();
                        $inventorytransaction->lorry_id = $trip->lorry_id;
                        $inventorytransaction->product_id = $line['product_id'];
                        $inventorytransaction->quantity = $line['quantity'] * -1;
                        $inventorytransaction->type = 3;
                        $inventorytransaction->user = $driver->employeeid . " (".$driver->name.")";
                        $inventorytransaction->date = date('Y-m-d H:i:s');
                        $inventorytransaction->trip_id = $driver->trip_id;
                        $inventorytransaction->save();
                    }

                    if($item['type'] == 1){
                        $invoicepayment = new InvoicePayment();
                        $invoicepayment->invoice_id = $invoice->id;
                        $invoicepayment->type = 1;
                        $invoicepayment->customer_id = $invoice->customer_id;
                        $invoicepayment->amount = $totalprice;
                        $invoicepayment->status = 1;
                        $invoicepayment->driver_id = $driver->id;
                        $invoicepayment->approve_by = $driver->name;
                        $invoicepayment->approve_at = date('Y-m-d H:i:s');
                        // Same change-on-receipt support as the online addinvoice path.
                        if(isset($item['cash_received']) && is_numeric($item['cash_received']) && $item['cash_received'] >= $totalprice){
                            $invoicepayment->cash_received = $item['cash_received'];
                        }
                        $invoicepayment->save();
                    }

                    Task::where('customer_id', $item['customer_id'])->where('driver_id', $driver->id)->update(['status' => 8]);

                    DB::commit();

                    $created[] = [
                        'client_ref' => $item['client_ref'],
                        'invoice_id' => $invoice->id,
                        'invoiceno' => $invoiceno,
                    ];
                }
                catch(\Exception $e){
                    DB::rollback();
                    $failed[] = [
                        'client_ref' => $item['client_ref'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.bulk_invoice_processed',
                'data' => ['created' => $created, 'failed' => $failed]
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * List this driver's own Invoices (most recent first), for the mobile
     * "My Invoices" screen. Mirrors getsalesorder()/getdeliveryorder().
     */
    public function getinvoicelist(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $invoices = Invoice::where('driver_id', $driver->id)
                ->with('customer', 'invoicedetail.product')
                ->orderby('date','desc')
                ->get();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_list_successfully',
                'data' => $invoices
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getinvoicebyid($id, Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $invoice = Invoice::where('id', $id)
                ->where('driver_id', $driver->id)
                ->with('customer', 'driver', 'invoicedetail.product', 'paymentAttachments', 'invoicepayment')
                ->first();
            if(empty($invoice)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_not_found',
                    'data' => null
                ], 404);
            }
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_get_successfully',
                'data' => $invoice
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Cancel (delete) one of this driver's own Invoices, scoped to the
     * driver's current trip (same reasoning as cancelsalesorder()). Unlike
     * SO/DO, an Invoice already deducted lorry stock (addinvoice()/
     * combineconvertdeliveryorder()), so cancelling reverses that deduction
     * and removes any auto-created cash InvoicePayment - admin's own
     * InvoiceController::destroy() does NOT do this reversal, but a mobile
     * "cancel" is reachable moments after a driver's own mistake, so leaving
     * stock permanently short would be worse than the admin page's behavior.
     * FOC achievequantity counters are intentionally left as-is - reversing
     * promo counters precisely is out of scope for a same-trip undo.
     */
    public function cancelinvoice($id, Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $invoice = Invoice::where('id', $id)->where('driver_id', $driver->id)->with('invoicedetail')->first();
            if(empty($invoice)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_not_found',
                    'data' => null
                ], 404);
            }
            if(!empty($invoice->trip_id) && $invoice->trip_id != $driver->trip_id){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invoice belongs to a previous trip and can no longer be cancelled.',
                    'data' => null
                ], 400);
            }
            if($invoice->status == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_already_cancelled',
                    'data' => null
                ], 400);
            }
            if(in_array($invoice->sync_status, [Invoice::SYNC_SYNCING, Invoice::SYNC_SYNCED])){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_synced_locked',
                    'data' => null
                ], 400);
            }
            DB::beginTransaction();
            if($driver->lorry_id){
                foreach($invoice->invoicedetail as $line){
                    $inventorybalance = InventoryBalance::where('lorry_id', $driver->lorry_id)->where('product_id', $line->product_id)->first();
                    if(empty($inventorybalance)){
                        $newinventorybalance = new InventoryBalance();
                        $newinventorybalance->lorry_id = $driver->lorry_id;
                        $newinventorybalance->product_id = $line->product_id;
                        $newinventorybalance->quantity = $line->quantity;
                        $newinventorybalance->save();
                    }else{
                        $inventorybalance->quantity = $inventorybalance->quantity + $line->quantity;
                        $inventorybalance->save();
                    }
                    $inventorytransaction = new InventoryTransaction();
                    $inventorytransaction->lorry_id = $driver->lorry_id;
                    $inventorytransaction->product_id = $line->product_id;
                    $inventorytransaction->quantity = $line->quantity;
                    $inventorytransaction->type = 3;
                    $inventorytransaction->user = $driver->employeeid . " (".$driver->name.") - invoice cancelled";
                    $inventorytransaction->date = date('Y-m-d H:i:s');
                    $inventorytransaction->trip_id = $invoice->trip_id;
                    $inventorytransaction->save();
                }
            }
            // Keep the payments for history too - status 2 (Canceled, same
            // convention as the admin panel) takes them out of the credit
            // math, which only counts status-1 payments.
            InvoicePayment::where('invoice_id', $invoice->id)->update(['status' => 2]);
            // Keep the invoice and its lines for history; status 2 = Cancelled.
            $invoice->status = 2;
            $invoice->save();
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_cancelled_successfully',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

      public function invoicepdf(Request $request)
	{
	    try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            
            $invoice = Invoice::where('id', $data['invoice_id'])->first();
            if (empty($invoice)) {
                abort('404');
            }

            // Same renderer as the admin panel, so a DO-converted invoice gets the
            // A4 business layout here too instead of always the narrow receipt.
            $pdf = InvoicePdf::render($invoice->id);

            $invoiceFilename = 'invoice-' . $invoice->invoiceno . '.pdf';
            $path = 'invoices-pdf/' . $invoiceFilename;
            
            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }


	}

    /**
     * PDF for one of this driver's own Sales Orders, for the mobile "View
     * Sales Order PDF" button. Mirrors invoicepdf()'s Storage+url() pattern.
     * Uses sales_orders.print unchanged (same format as the admin print).
     */
    public function sopdf(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'sales_order_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }

            $salesOrder = SalesOrder::where('id', $request->sales_order_id)
                ->where('driver_id', $driver->id)
                ->with('customer')
                ->with('driver')
                ->with('salesorderdetail.product')
                ->first();

            if (empty($salesOrder)) {
                abort('404');
            }

            $min = 450;
            $each = 23;
            $height = (count($salesOrder['salesorderdetail']) * $each) + $min;

            $pdf = Pdf::loadView('sales_orders.print', ['salesOrder' => $salesOrder]);
            $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

            // sono contains a "/" (e.g. "SO2609/0011") - sanitize so it
            // doesn't get read as a subdirectory in the storage path/URL.
            $filename = 'so-' . str_replace('/', '-', $salesOrder->sono) . '.pdf';
            $path = 'salesorders-pdf/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * PDF for one of this driver's own Delivery Orders, for the mobile
     * "View Delivery Order PDF" button. Mirrors invoicepdf()'s Storage+
     * url() pattern. Uses delivery_orders.print, which is a checklist-style
     * layout (product + qty only, no price) - the packing/picking format
     * requested for DO, distinct from sales_orders.print's priced layout.
     */
    public function dopdf(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'do_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }

            $deliveryOrder = DeliveryOrder::where('id', $request->do_id)
                ->where('driver_id', $driver->id)
                ->with('customer')
                ->with('driver')
                ->with('deliveryorderdetail.product')
                ->first();

            if (empty($deliveryOrder)) {
                abort('404');
            }

            $min = 450;
            $each = 23;
            $height = (count($deliveryOrder['deliveryorderdetail']) * $each) + $min;

            $pdf = Pdf::loadView('delivery_orders.print', ['deliveryOrder' => $deliveryOrder]);
            $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

            // dono contains a "/" (e.g. "DO2609/0008") - sanitize so it
            // doesn't get read as a subdirectory in the storage path/URL.
            $filename = 'do-' . str_replace('/', '-', $deliveryOrder->dono) . '.pdf';
            $path = 'deliveryorders-pdf/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }


     public function addpayment(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null,
                    'color_code' => ''
                ], 401);
            }
            //validation
            
            $validator = Validator::make($request->all(), [
                'date' => 'date_format:Y-m-d H:i:s',
                'customer_id' => 'required|numeric',
                'type' => 'required|numeric|gt:0|lt:6',
                'remark' => 'present|nullable|string',
                'amount' =>'required|numeric',
                
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null,
                ], 400);
            }
            $customer = Customer::where('id',$data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null,
                ], 400);
            }
            //process
            if(isset($data['invoice_id']) && Invoice::where('id', $data['invoice_id'])->where('status', 2)->exists()){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_already_cancelled',
                    'data' => null,
                ], 400);
            }

            // Multi-invoice payment: the app can send invoice_ids[] to settle
            // several invoices at once. One payment row is created per invoice
            // (allocated oldest-first), all sharing a batch_id so the receipt
            // PDF prints them as a single combined receipt.
            $multiInvoiceIds = [];
            if(!empty($data['invoice_ids']) && is_array($data['invoice_ids'])){
                $multiInvoiceIds = array_values(array_unique(array_map('intval', $data['invoice_ids'])));
            }

            DB::beginTransaction();

            if(count($multiInvoiceIds) > 0){
                $invoices = Invoice::whereIn('id', $multiInvoiceIds)
                    ->where('customer_id', $data['customer_id'])
                    ->where('status', '!=', 2)
                    ->orderBy('date', 'asc')
                    ->get();
                if($invoices->count() != count($multiInvoiceIds)){
                    DB::rollback();
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_invoice_selection',
                        'data' => null,
                    ], 400);
                }

                $batchId = (string) \Illuminate\Support\Str::uuid();
                $remaining = round((float) $data['amount'], 2);
                $created = [];
                foreach($invoices as $inv){
                    if($remaining <= 0){
                        break;
                    }
                    $outstanding = round((float) InvoiceDetail::where('invoice_id', $inv->id)->sum('totalprice')
                        - (float) InvoicePayment::where('invoice_id', $inv->id)->where('status', 1)->sum('amount'), 2);
                    if($outstanding <= 0){
                        continue;
                    }
                    $alloc = min($outstanding, $remaining);
                    $invoicepayment = new InvoicePayment();
                    $invoicepayment->invoice_id = $inv->id;
                    $invoicepayment->batch_id = $batchId;
                    $invoicepayment->type = $data['type'];
                    $invoicepayment->customer_id = $data['customer_id'];
                    $invoicepayment->amount = $alloc;
                    $invoicepayment->status = 1;
                    $invoicepayment->chequeno = $data['cheque_no'];
                    $invoicepayment->driver_id = $driver->id;
                    $invoicepayment->approve_by = $driver->name;
                    $invoicepayment->approve_at = date('Y-m-d H:i:s');
                    $invoicepayment->save();
                    $created[] = $invoicepayment;
                    $remaining = round($remaining - $alloc, 2);
                }
                if(empty($created)){
                    DB::rollback();
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.no_outstanding_invoices',
                        'data' => null,
                    ], 400);
                }
                DB::commit();
                // The first payment fronts the batch: its id is the receipt no,
                // and paymentpdf prints every payment sharing the batch_id.
                $invoicepayment = $created[0];
            }else{
                $invoicepayment = New InvoicePayment();
                if(isset($data['invoice_id'])){
                    $invoicepayment->invoice_id = $data['invoice_id'];
                }

                $invoicepayment->type = $data['type'];
                $invoicepayment->customer_id = $data['customer_id'];
                $invoicepayment->amount = $data['amount'];
                $invoicepayment->status = 1;
                $invoicepayment->chequeno = $data['cheque_no'];
                $invoicepayment->driver_id = $driver->id;
                $invoicepayment->approve_by = $driver->name;
                $invoicepayment->approve_at = date('Y-m-d H:i:s');
                //$invoicepayment->created_at = $data['date'];
                $invoicepayment->save();

                DB::commit();
            }
            $iv = InvoicePayment::where('id',$invoicepayment->id)->get()->first();
           
            $iv['payment_no'] = sprintf('PR%05d',$iv->id);
            
            
             try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');');
                
                if($credit)
                {
                    $iv->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $iv->newcredit  = 0;
            }
            
           
           // $iv->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');')[0]->credit,2);
           
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_add_successfully',
                'data' => $iv
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
      public function paymentpdf(Request $request)
	{
	    try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            
            $id = $data['payment_id'];
            
            
            $invoice = InvoicePayment::where('id',$id)
                    ->with('customer')
                    ->first();

            if (empty($invoice)) {
                abort('404');
            }

            $min = 450;
            $each = 23;

            // Multi-invoice payment: print every payment in the batch as one
            // combined receipt (one line per settled invoice).
            $batchPayments = null;
            $batchTotal = null;
            if (!empty($invoice->batch_id)) {
                $batchPayments = InvoicePayment::where('batch_id', $invoice->batch_id)
                    ->where('status', '!=', 2)
                    ->with('invoice:id,invoiceno')
                    ->orderBy('id')
                    ->get();
                $batchTotal = round($batchPayments->sum('amount'), 2);
                $min = $min + (max(0, $batchPayments->count() - 1) * $each);
            }
    
            try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
                
                if($credit)
                {
                    $invoice->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $invoice->newcredit  = 0;
            }
            
            $invoice->customer->groupcompany = DB::table('companies')
            ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
            ->select('companies.*')
            ->first() ?? null;
            
            $pdf = Pdf::loadView('invoice_payments.print', array(
                'invoice' => $invoice,
                'batchPayments' => $batchPayments,
                'batchTotal' => $batchTotal,
            ));

    
            $pdf->setPaper(array(0, 0, 300, $min), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);
            
            $invoiceFilename = 'payment-' . $invoice->id . '.pdf';
            $path = 'payments/' . $invoiceFilename;
            
            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
        
	  
	}
	
	
    public function getstock(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            //if(!empty($trip)){
            //    if($trip->type == 2){
            //        return response()->json([
            //            'result' => false,
            //            'message' => __LINE__.$this->message_separator.'Trip had not started',
            //            'data' => null
            //        ], 401);
            //    }
            //}else{
            //    return response()->json([
            //        'result' => false,
            //        'message' => __LINE__.$this->message_separator.'Trip had not started',
            //        'data' => null
            //    ], 401);
            //}
            //process
            $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)
            ->leftjoin('products','products.id','=','inventory_balances.product_id')
            ->leftjoin('product_types','product_types.id','=','products.type_id')
            ->get(['inventory_balances.id','inventory_balances.quantity','inventory_balances.product_id','products.name','products.image_path','products.type_id','product_types.name as type_name'])
            ->map(function($item){
                $item->image_url = $item->image_path ? url($item->image_path) : null;
                return $item;
            })
            ->toarray();
            if(count($inventorybalance) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.no_stock_found',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.stock_found',
                    'data' => $inventorybalance
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    // public function getstock(Request $request){
    //     try{
    //         $data = $request->all();
    //         //check session
    //         $driver = Driver::where('session', $request->header('session'))->first();
    //         if(empty($driver)){
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'Invalid session',
    //                 'data' => null
    //             ], 401);
    //         }
    //         //validation
    //         $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
    //         if(!empty($trip)){
    //             if($trip->type == 2){
    //                 return response()->json([
    //                     'result' => false,
    //                     'message' => __LINE__.$this->message_separator.'Trip had not started',
    //                     'data' => null
    //                 ], 401);
    //             }
    //         }else{
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'Trip had not started',
    //                 'data' => null
    //             ], 401);
    //         }
    //         //process
    //         $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)
    //         ->leftjoin('products','products.id','=','inventory_balances.product_id')
    //         ->get(['inventory_balances.id','inventory_balances.quantity','inventory_balances.product_id','products.name'])->toarray();
    //         if(count($inventorybalance) == 0){
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'No stock found',
    //                 'data' => null
    //             ], 200);
    //         }else{
    //             return response()->json([
    //                 'result' => true,
    //                 'message' => __LINE__.$this->message_separator.'Stock found',
    //                 'data' => $inventorybalance
    //             ], 200);
    //         }
    //     }
    //     catch(Exception $e){
    //         return response()->json([
    //             'result' => false,
    //             'message' => __LINE__.$this->message_separator.$e->getMessage(),
    //             'data' => null
    //         ], 500);
    //     }
    // }

    public function listotherdriver(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null], 401);
            }
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $drivers = Trip::where('driver_id','!=',$trip->driver_id)
            ->select('driver_id','drivers.name','drivers.employeeid')
            ->groupby('driver_id','drivers.name','drivers.employeeid')
            ->havingRaw('(count(driver_id) % 2) > 0')
            ->leftjoin('drivers','drivers.id','=','trips.driver_id')
            ->get()->toarray();
            if(count($drivers) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.no_driver_found',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.driver_found',
                    'data' => $drivers
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function transferstock(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|numeric',
            'transferdetail' => 'present|array',
            'transferdetail.*.product_id' => 'required|numeric',
            'transferdetail.*.quantity' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $todriver = Driver::where('id',$data['driver_id'])->first();
        if(empty($todriver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $totrip = Trip::where('driver_id', $data['driver_id'])->orderby('date','desc')->first();
        if(!empty($totrip)){
            if($totrip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.selected_driver_trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.selected_driver_trip_had_not_started',
                'data' => null
            ], 400);
        }
        //process
        try{

            DB::beginTransaction();
            foreach($data['transferdetail'] as $td){
                $product = Product::where('id',$td['product_id'])->first();
                if(empty($product)){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_product',
                        'data' => null
                    ], 400);
                }
                $inventorytransfer = New InventoryTransfer();
                $inventorytransfer->date = date('Y-m-d H:i:s');
                $inventorytransfer->from_driver_id = $trip->driver_id;
                $inventorytransfer->from_lorry_id = $trip->lorry_id;
                $inventorytransfer->to_driver_id = $totrip->driver_id;
                $inventorytransfer->to_lorry_id = $totrip->lorry_id;
                $inventorytransfer->product_id = $td['product_id'];
                $inventorytransfer->quantity = $td['quantity'];
                $inventorytransfer->status = 1;
                $inventorytransfer->save();
            }
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.pending_driver_accept_transfer',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function gettransfer(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null], 401);
            }
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $request = InventoryTransfer::where('from_driver_id', $trip->driver_id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            ->with('product:id,name')
            ->with('todriver:id,name')
            ->orderby('date','desc')
            ->get(['id','date','status','quantity','product_id','to_driver_id'])
            ->toarray();
            $pending = InventoryTransfer::where('to_driver_id', $trip->driver_id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            // ->where('status', 1)
            ->with('product:id,name')
            ->with('fromdriver:id,name')
            ->orderby('date','desc')
            ->get(['id','date','status','quantity','product_id','from_driver_id'])
            ->toarray();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.transfer_found',
                'data' => [
                    'request' => $request,
                    'pending' => $pending
                ]
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function updatetransfer(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'transfer_id' => 'required|numeric',
            'status' => 'required|numeric|gt:1|lt:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        // $inventorytransfer = InventoryTransfer::where('id', $data['transfer_id'])->where('to_driver_id',$driver->id)->first();
        $inventorytransfer = InventoryTransfer::where('id', $data['transfer_id'])->first();
        if(empty($inventorytransfer)){
            return response()->json([
               'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.transfer_not_found',
                'data' => null
            ], 400);
        }
        if($inventorytransfer->status == 2){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.transfer_already_accepted',
                'data' => null
            ], 400);
        }
        if($inventorytransfer->status == 3){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.transfer_already_rejected',
              'data' => null
            ], 400);
        }
        $fromdriver = Driver::where('id',$inventorytransfer->from_driver_id)->first();
        if(empty($fromdriver)){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.from_driver_not_found',
                'data' => null
            ], 400);
        }
        $todriver = Driver::where('id',$inventorytransfer->to_driver_id)->first();
        if(empty($fromdriver)){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.to_driver_not_found',
                'data' => null
            ], 400);
        }
        //process
        try{

            DB::beginTransaction();
            if($data['status'] == 3){
                $inventorytransfer->status = 3;
                $inventorytransfer->save();
                DB::commit();
                return response()->json([
                   'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.transfer_rejecet_successfully',
                    'data' => null
                ], 200);
            }
            if($data['status'] == 2){
                $inventorytransfer->status = 2;
                $inventorytransfer->save();
                 //from
                 $frominventorybalance = Inventorybalance::where('lorry_id',$inventorytransfer->from_lorry_id)
                 ->where('product_id',$inventorytransfer->product_id)->first();
                 if(empty($frominventorybalance)){
                     $newfrominventorybalance = New Inventorybalance();
                     $newfrominventorybalance->lorry_id = $inventorytransfer->from_lorry_id;
                     $newfrominventorybalance->product_id = $inventorytransfer->product_id;
                     $newfrominventorybalance->quantity = 0 - $inventorytransfer->quantity;
                     $newfrominventorybalance->save();
                 }else{
                     $frominventorybalance->quantity = $frominventorybalance->quantity - $inventorytransfer->quantity;
                     $frominventorybalance->save();
                 }
                 $frominventorytransaction = New InventoryTransaction();
                 $frominventorytransaction->lorry_id = $inventorytransfer->from_lorry_id;
                 $frominventorytransaction->product_id = $inventorytransfer->product_id;
                 $frominventorytransaction->quantity = $inventorytransfer->quantity * -1;
                 $frominventorytransaction->type = 4;
                 $frominventorytransaction->user = $fromdriver->employeeid . " (".$fromdriver->name.") => " . $todriver->employeeid . " (".$todriver->name.")";
                 $frominventorytransaction->date = date('Y-m-d H:i:s');
                 $frominventorytransaction->trip_id = $fromdriver->trip_id;
                 $frominventorytransaction->save();
                 //to
                 $toinventorybalance = Inventorybalance::where('lorry_id',$inventorytransfer->to_lorry_id)
                 ->where('product_id',$inventorytransfer->product_id)->first();
                 if(empty($toinventorybalance)){
                     $newtoinventorybalance = New Inventorybalance();
                     $newtoinventorybalance->lorry_id = $inventorytransfer->to_lorry_id;
                     $newtoinventorybalance->product_id = $inventorytransfer->product_id;
                     $newtoinventorybalance->quantity = $inventorytransfer->quantity;
                     $newtoinventorybalance->save();
                 }else{
                     $toinventorybalance->quantity = $toinventorybalance->quantity + $inventorytransfer->quantity;
                     $toinventorybalance->save();
                 }
                 $toinventorytransaction = New InventoryTransaction();
                 $toinventorytransaction->lorry_id = $inventorytransfer->to_lorry_id;
                 $toinventorytransaction->product_id = $inventorytransfer->product_id;
                 $toinventorytransaction->quantity = $inventorytransfer->quantity;
                 $toinventorytransaction->type = 4;
                 $toinventorytransaction->user = $fromdriver->employeeid . " (".$fromdriver->name.") => " . $todriver->employeeid . " (".$todriver->name.")";
                 $toinventorytransaction->date = date('Y-m-d H:i:s');
                 $toinventorytransaction->trip_id = $todriver->trip_id;
                 $toinventorytransaction->save();
                 DB::commit();
                 return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.transfer_accept_successfully',
                     'data' => null
                 ], 200);
            }
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * This driver's most recent trip's own inventory activity, scoped by
     * lorry + the trip's own start/end timestamps (read from the `trips`
     * table itself) rather than a calendar date range or the
     * InventoryTransaction.trip_id / TripInventoryBalance.trip_id columns -
     * those are written from Driver.trip_id (see starttrip()/endtrip()),
     * which is not reliably populated across this dataset (confirmed empty
     * for every existing driver/row), so trusting it here would silently
     * show an empty screen. The `trips` table's own date-ordered rows are
     * the one source that's actually kept correct (it's what the
     * trip-already-started/not-started checks elsewhere rely on), so we
     * derive the boundary from that directly. Always the latest trip
     * regardless of whether it's still active or already ended, per
     * product decision (no trip picker for now).
     */
    public function getstocktransaction(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $latest = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(empty($latest)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }

            if($latest->type == 1){
                // Latest event is a start with no end after it - trip still active.
                $startDate = $latest->getRawOriginal('date');
                $endDate = date('Y-m-d H:i:s');
                $lorryId = $latest->lorry_id;
            }else{
                // Latest event is an end - find the start that preceded it.
                $endDate = $latest->getRawOriginal('date');
                $lorryId = $latest->lorry_id;
                $start = Trip::where('driver_id', $driver->id)
                    ->where('type', 1)
                    ->where('date', '<=', $endDate)
                    ->orderby('date','desc')
                    ->first();
                $startDate = $start ? $start->getRawOriginal('date') : $endDate;
            }

            $opening = InventoryTransaction::where('lorry_id', $lorryId)
                ->where('date', '<', $startDate)
                ->leftjoin('products','products.id','=','inventory_transactions.product_id')
                ->groupby('inventory_transactions.product_id','products.name')
                ->havingRaw('sum(inventory_transactions.quantity) != 0')
                ->select('inventory_transactions.product_id', 'products.name', DB::raw('sum(inventory_transactions.quantity) as quantity'))
                ->get();

            $transactions = InventoryTransaction::where('lorry_id', $lorryId)
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->with('product')
                ->orderby('date','desc')
                ->get()
                ->map(function($row){
                    return [
                        'id' => $row->id,
                        'product_id' => $row->product_id,
                        'name' => optional($row->product)->name,
                        'quantity' => $row->quantity,
                        'type' => $row->type,
                        'date' => $row->date,
                    ];
                });

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.transaction_found',
                'data' => [
                    'trip_date' => $startDate,
                    'lorry_id' => $lorryId,
                    'opening' => $opening,
                    'transactions' => $transactions,
                ]
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function listalldriver(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $driver = Driver::where('id','!=',$trip->driver_id)->get()->toarray();
            if(count($driver) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_driver',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.driver_found',
                    'data' => $driver
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //NA
    public function getdrivertask(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json(['result' => false, 'message' => 'Session not found', 'data' => null], 401);
        }
        //validation
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
            }
        }else{
            return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
        }
        $messages = array(
            'driver_id.required' => 'Driver ID is required',
        );
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors(),
                'data' => null
            ], 400);
        }
        $fromdriver = Driver::where('id',$data['driver_id'])->first();
        if(empty($fromdriver)){
            return response()->json(['result' => false,'message' => 'Driver not found', 'data' => null], 400);
        }
        //process
        $fromdrivertrip = Trip::where('driver_id', $fromdriver->id)->orderby('date','desc')->first();
        if(!empty($fromdrivertrip)){
            if($fromdrivertrip->type == 2){
                //Take from assign & invoice
                $assigns = Assign::where('driver_id', $fromdriver->id)
                ->orderby('sequence','asc')
                ->select('customer_id','sequence',DB::RAW('0 as invoice_id'));
                $task = Invoice::where('driver_id', $fromdriver->id)
                ->where('status',0)
                ->where('date',date('Y-m-d'))
                ->select('customer_id',DB::RAW('0 as sequence'),DB::RAW('id as invoice_id'))
                ->union($assigns)
                ->with('customer')
                ->get()->toarray();
                if(empty($task)){
                    return response()->json(['result' => false,'message' => 'Task not found', 'data' => null], 200);
                }else{
                    return response()->json(['result' => true,'message' => 'Task found', 'data' => $task], 200);
                }
            }else{
                //Take from task
                $task = Task::where('driver_id',$fromdriver->id)
                ->wherein('status',[0,1])
                ->select('customer_id','sequence','invoice_id')
                ->with('customer')
                ->get()->toarray();
                if(empty($task)){
                    return response()->json(['result' => false,'message' => 'Task not found', 'data' => null], 200);
                }else{
                    return response()->json(['result' => true,'message' => 'Task found', 'data' => $task], 200);
                }
            }
        }else{
            //Take from assign & invoice
            $assigns = Assign::where('driver_id', $fromdriver->id)
            ->orderby('sequence','asc')
            ->select('customer_id','sequence',DB::RAW('0 as invoice_id'));
            $task = Invoice::where('driver_id', $fromdriver->id)
            ->where('status',0)
            ->where('date',date('Y-m-d'))
            ->select('customer_id',DB::RAW('0 as sequence'),DB::RAW('id as invoice_id'))
            ->union($assigns)
            ->with('customer')
            ->get()->toarray();
            if(empty($task)){
                return response()->json(['result' => false,'message' => 'Task not found', 'data' => null], 200);
            }else{
                return response()->json(['result' => true,'message' => 'Task found', 'data' => $task], 200);
            }
        }
    }

    //NA
    public function pulldrivertask(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json(['result' => false, 'message' => 'Session not found', 'data' => null], 401);
        }
        //validation
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
            }
        }else{
            return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
        }
        $messages = array(
            'driver_id.required' => 'Driver ID is required',
            'transferdetail.*.customer_id.required' => 'Customer ID is required',
        );
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
            'transferdetail.*.customer_id' => 'required',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors(),
                'data' => null
            ], 400);
        }
        try{
            if(count($data['transferdetail']) == 0){
                return response()->json(['result' => false, 'message' => 'Invalid format, transfer detail is empty', 'data' => null], 400);
            }
        }
        catch(Exception $e){
            return response()->json(['result' => false, 'message' => 'Invalid format', 'data' => null], 400);
        }
        $fromdriver = Driver::where('id', $data['driver_id'])->first();
        if(empty($fromdriver)){
            return response()->json(['result' => false,'message' => 'Driver not found', 'data' => null], 400);
        }
        //process
        try{
            DB::beginTransaction();
            foreach($data['transferdetail'] as $key => $c){
                $customer = Customer::where('id',$c['customer_id'])->first();
                if(empty($customer)){
                    DB::rollback();
                    return response()->json(['result' => false,'message' => 'Customer not found', 'data' => null], 400);
                }else{
                    $fromdrivertrip = Trip::where('driver_id', $fromdriver->id)->orderby('date','desc')->first();
                    if(!empty($fromdrivertrip)){
                        if($fromdrivertrip->type == 2){
                            //take from assign & invoice
                            $invoice = Invoice::where('driver_id', $fromdriver->id)
                            ->where('status',0)
                            ->where('date',date('Y-m-d'))
                            ->where('customer_id',$customer->id)
                            ->get()->toarray();
                            if(empty($invoice)){
                                $newtask =  New Task();
                                $newtask->driver_id = $driver->id;
                                $newtask->customer_id = $customer->id;
                                $newtask->status = 0;
                                $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                                if(empty($sequence)){
                                    $sequence = 0;
                                }else{
                                    $sequence = $sequence->sequence;
                                }
                                $newtask->sequence =  $sequence + 1;
                                $newtask->date = date('Y-m-d');
                                $newtask->save();
                            }else{
                                foreach($invoice as $i){
                                    $newtask =  New Task();
                                    $newtask->driver_id = $driver->id;
                                    $newtask->customer_id = $customer->id;
                                    $newtask->invoice_id = $i['id'];
                                    $newtask->status = 0;
                                    $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                                    if(empty($sequence)){
                                        $sequence = 0;
                                    }else{
                                        $sequence = $sequence->sequence;
                                    }
                                    $newtask->sequence =  $sequence + 1;
                                    $newtask->date = date('Y-m-d');
                                    $newtask->save();
                                }
                            }
                        }else{
                            //take from task
                            $task = Task::where('driver_id',$fromdriver->id)
                            ->wherein('status',[0,1])
                            ->where('customer_id',$customer->id)->first();
                            $newtask =  New Task();
                            $newtask->driver_id = $driver->id;
                            $newtask->customer_id = $customer->id;
                            $newtask->status = 0;
                            $newtask->invoice_id = $task->invoice_id;
                            $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                            if(empty($sequence)){
                                $sequence = 0;
                            }else{
                                $sequence = $sequence->sequence;
                            }
                            $newtask->sequence =  $sequence + 1;
                            $newtask->date = date('Y-m-d');
                            $newtask->save();
                            $task->update(['status' => 9]);
                        }
                    }else{
                        //take from assign & invoice
                        $invoice = Invoice::where('driver_id', $fromdriver->id)
                        ->where('status',0)
                        ->where('date',date('Y-m-d'))
                        ->where('customer_id',$customer->id)
                        ->get()->toarray();
                        if(empty($invoice)){
                            $newtask =  New Task();
                            $newtask->driver_id = $driver->id;
                            $newtask->customer_id = $customer->id;
                            $newtask->status = 0;
                            $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                            if(empty($sequence)){
                                $sequence = 0;
                            }else{
                                $sequence = $sequence->sequence;
                            }
                            $newtask->sequence =  $sequence + 1;
                            $newtask->date = date('Y-m-d');
                            $newtask->save();
                        }else{
                            foreach($invoice as $i){
                                $newtask =  New Task();
                                $newtask->driver_id = $driver->id;
                                $newtask->customer_id = $customer->id;
                                $newtask->invoice_id = $i['id'];
                                $newtask->status = 0;
                                $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                                if(empty($sequence)){
                                    $sequence = 0;
                                }else{
                                    $sequence = $sequence->sequence;
                                }
                                $newtask->sequence =  $sequence + 1;
                                $newtask->date = date('Y-m-d');
                                $newtask->save();
                            }
                        }
                    }

                }
            }
            DB::commit();
            return response()->json(['result' => true, 'message' => 'Pulled task successfully', 'data' => null], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json(['result' => false,'message' => $e->getMessage(), 'data' => null], 400);
        }
    }

    public function pushdrivertask(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|numeric',
            'transferdetail' => 'present|array',
            'transferdetail.*.task_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $todriver = Driver::where('id', $data['driver_id'])->first();
        if(empty($todriver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_driver',
                'data' => null
            ], 400);
        }
        //process
        try{
            DB::beginTransaction();
            foreach($data['transferdetail'] as $key => $c){
                $task = Task::where('id',$c['task_id'])->first();
                if(empty($task)){
                    DB::rollback();
                    return response()->json([
                       'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_task',
                        'data' => null
                    ], 400);
                }
                if($task->status == 9){
                    DB::rollback();
                    return response()->json([
                       'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled',
                        'data' => null
                    ], 400);
                }
                if($task->status == 8){
                    DB::rollback();
                    return response()->json([
                       'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_completed',
                        'data' => null
                    ], 400);
                }
                $sequence = Task::where('driver_id',$todriver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                if(empty($sequence)){
                    $sequence = 0;
                }else{
                    $sequence = $sequence->sequence;
                }
                $task->sequence = $sequence + 1;
                $task->driver_id = $todriver->id;
                $task->status = 0;
                $task->based = 0;
                $task->trip_id = null;
                $task->save();

                $tasktransfer = new TaskTransfer();
                $tasktransfer->date = date("Y-m-d H:i:s");
                $tasktransfer->from_driver_id = $driver->id;
                $tasktransfer->to_driver_id = $todriver->id;
                $tasktransfer->task_id = $c['task_id'];
                $tasktransfer->save();
            }
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.push_task_successfully',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function listtranfer(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        //process
        try{
            $tasktransfer = TaskTransfer::where('from_driver_id',$driver->id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            ->with('fromdriver:id,name')
            ->with('todriver:id,name')
            ->with('task.customer')
            ->get()->toArray();
            if(!empty($tasktransfer)){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.task_transfer_found',
                    'data' => $tasktransfer
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.task_transfer_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function dashboard_bk(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            if($data['date'] > date('Y-m-d H:i:s')){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.date_cannot_be_future_date',
                    'data' => null
                ], 400);
            }
            //process
            $sales = DB::Select('select sum(a.totalprice) as sales from(select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' group by i.id) a')[0]->sales;
            $cash = DB::Select('select coalesce(sum(coalesce(amount,0)),0) as cash from invoice_payments where type = 1 and status = 1 and driver_id = '.$driver->id.' and approve_at >= "'.$data['date'].'" and approve_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            // $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id left join invoice_payments ip on ip.invoice_id = i.id where i.status = 1 and i.date = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and ip.id is null group by i.id ) a')[0]->credit;
            $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and i.paymentterm = 2 group by i.id ) a')[0]->credit;
            $productsold = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id)[0]->productsold;
            $solddetail = DB::select('select p.name, sum(id.quantity) as quantity from invoices i left join invoice_details id on id.invoice_id = i.id left join products p on p.id = id.product_id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' group by id.product_id, p.id, p.name');
            $trip = DB::select('select t.id, d.name as driver_name, k.name as kelindan_name, l.lorryno from trips t left join drivers d on d.id = t.driver_id left join kelindans k on k.id = t.kelindan_id left join lorrys l on l.id = t.lorry_id where t.driver_id = '.$driver->id.' and t.type = 1 and t.date >= "'.$data['date'].'" and t.date < "'.$data['date'].' 23:59:59"');
            // $trip = Trip::where('driver_id', $driver->id)
            // ->where('date','>=',$data['date'].' 00:00:00')
            // ->where('date','<',$data['date'].' 23:59:59')
            // ->where('type',1)
            // ->with('driver')
            // ->with('kelindan')
            // ->with('lorry')
            // ->get()
            // ->toArray();
            $result = [
                'sales' => round($sales,2),
                'cash' => round($cash,2),
                'credit' => round($credit,2),
                'productsold' => [
                    'total_quantity' =>round($productsold,2),
                    'details' =>$solddetail
                ],
                'trip' => $trip
            ];
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.get_dashboard_successfully',
                'data' => $result
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
     public function dashboard(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            if($data['date'] > date('Y-m-d H:i:s')){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.date_cannot_be_future_date',
                    'data' => null
                ], 400);
            }
            //process
            // Sales/credit/product-sold figures are scoped to the driver's
            // CURRENT trip (not the whole calendar day) so they reset to 0
            // when a new trip starts, instead of accumulating across every
            // trip the driver happens to run on the same day.
            $tripId = (int) ($driver->trip_id ?? 0);
            $sales = DB::Select('select sum(a.totalprice) as sales from(select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' group by i.id) a')[0]->sales;
            $cash = DB::Select('select coalesce(sum(coalesce(amount,0)),0) as cash from invoice_payments where type = 1 and status = 1 and driver_id = '.$driver->id.' and approve_at >= "'.$data['date'].'" and approve_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            $bank_in = DB::Select('select coalesce(sum(coalesce(bank_in,0)),0) as bank_in from trips where type = 2 and driver_id = '.$driver->id.' and created_at >= "'.$data['date'].'" and created_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->bank_in;
            $cash_left = DB::Select('select coalesce(sum(coalesce(cash,0)),0) as cash from trips where type = 2 and driver_id = '.$driver->id.' and created_at >= "'.$data['date'].'" and created_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' and i.paymentterm = 2 group by i.id ) a')[0]->credit;
            $bank = DB::select('select sum(a.totalprice) as bank from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' and i.paymentterm = 3 group by i.id ) a')[0]->bank;
            $tng = DB::select('select sum(a.totalprice) as tng from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' and i.paymentterm = 4 group by i.id ) a')[0]->tng;
            $cheque = DB::select('select sum(a.totalprice) as cheque from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' and i.paymentterm = 5 group by i.id ) a')[0]->cheque;
            $productsold = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and id.totalprice > 0 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id)[0]->productsold;
            $solddetail = DB::select('select p.name, sum(id.quantity) as quantity, sum(id.totalprice) as price from invoices i left join invoice_details id on id.invoice_id = i.id  left join products p on p.id = id.product_id where i.status = 1 and id.totalprice > 0 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' group by id.product_id, p.id, p.name');
            $productfoc = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and id.totalprice = 0 and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id)[0]->productsold;
            $focdetail = DB::select('select p.name, sum(id.quantity) as quantity, sum(id.totalprice) as price from invoices i left join invoice_details id on id.invoice_id = i.id left join products p on p.id = id.product_id where i.status = 1 and id.totalprice = 0  and i.trip_id = '.$tripId.' and i.driver_id = '.$driver->id.' group by id.product_id, p.id, p.name');
            $trip = DB::table('trips as t')
                ->select([
                    't.id',
                    't.advance_amount',  // Make sure this matches your column name exactly
                    'd.name as driver_name',
                    'k.name as kelindan_name', 
                    'l.lorryno'
                ])
                ->leftJoin('drivers as d', 'd.id', '=', 't.driver_id')
                ->leftJoin('kelindans as k', 'k.id', '=', 't.kelindan_id')
                ->leftJoin('lorrys as l', 'l.id', '=', 't.lorry_id')
                ->where('t.driver_id', $driver->id)
                ->where('t.type', 1)
                ->whereDate('t.date', $data['date'])  // Better date filtering
                ->get()
                ->map(function ($trip) {
                    // Convert null advance_amount to 0 if needed
                    $trip->advance_amount = $trip->advance_amount ?? 0;
                    return $trip;
                });                        
            $transaction = DB::table('inventory_transactions as i_t')
            ->join('products as p', 'p.id', '=', 'i_t.product_id')
            ->join('drivers as d', function($join) use ($driver) {
                $join->where('d.id', '=', $driver->id)
                    ->where(DB::raw("SUBSTRING_INDEX(i_t.user, ' ', 1)"), '=', DB::raw('d.employeeid'))
                    ->where(DB::raw("REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(i_t.user, '(', -1), ')', 1), ')', '')"), '=', DB::raw('d.name'));
            })
            ->where('i_t.type', 5)
            ->where('i_t.created_at', '>=', $data['date'] . ' 00:00:00')
            ->where('i_t.created_at', '<', $data['date'] . ' 23:59:59')
            ->select('p.name', 'i_t.quantity')
            ->get();

            // $trip = Trip::where('driver_id', $driver->id)
            // ->where('date','>=',$data['date'].' 00:00:00')
            // ->where('date','<',$data['date'].' 23:59:59')
            // ->where('type',1) 
            // ->with('driver')
            // ->with('kelindan')
            // ->with('lorry')
            // ->get()
            // ->toArray();
            $result = [
                'sales' => round($sales,2),
                'cash' => round($cash,2),
                'cash_left' =>  ceil($cash_left),
                'bank_in' => round($bank_in,2),
                'wastage' => $transaction,
                'credit' => round($credit,2),
                'onlinebank' =>round($bank,2),
                'tng' =>round($tng,2),
                'cheque' =>round($cheque,2),
                'productsold' => [
                    'total_quantity' =>round($productsold,2),
                    'details' =>$solddetail
                ],
                'productfoc' => [
                    'total_quantity' =>round($productfoc,2),
                    'details' =>$focdetail
                ],
                'trip' => $trip
            ];
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.get_dashboard_successfully',
                'data' => $result
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getAllLanguages(Request $request)
    {
        // Intentionally NOT session-gated: the app loads this list once at
        // startup, which can happen before login / after logout. Requiring a
        // session made the language switcher vanish for the whole app
        // lifetime whenever the startup fetch 401'd. Language names are not
        // sensitive.
        $languages = MobileTranslationVersion::with('language')->get();

        $translations = [];

        foreach ($languages as $languageVersion) {
            if (empty($languageVersion->language) || !$languageVersion->language->is_active) {
                continue;
            }
            $translations[] = [
                'language' => $languageVersion->language->name,
                'code'     => $languageVersion->language->code,
                'version'  => $languageVersion->version,
            ];
        }
        return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator,
                'data' => $translations
            ], 200);
    }

    public function getTranslations(Request $request)
    {
        $data = $request->all();
        // Not session-gated, same reasoning as getAllLanguages: translations
        // must be loadable on the login screen, before any session exists.
        //validation
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]); 
        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $code = $data['code'];
        $language = Language::where('code', $code)->first();

        if(empty($language)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invalid Language Code',
                    'data' => null
                ], 401);
            }
        $version = MobileTranslationVersion::where('language_id', $language->id)->first();
        $translations = MobileTranslation::where('language_id', $language->id)
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        $result = [
            'version' => $version->version ?? 0,
            'translation' => $translations
        ];

        return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.language_update_successfully',
                'data' => $result
            ], 200);

    }

    // ── Sales Orders ─────────────────────────────────────────────────────────
    // No payment method is required at creation - it's chosen at convert() time.
    // No inventory/FOC effects here either - those only happen once an Invoice
    // actually exists (convertsalesorder / combineconvertdeliveryorder).

    public function addsalesorder(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            // An active trip is NOT required: customers call in orders at night,
            // before the driver starts the next trip. An SO created outside a
            // trip simply has no trip/kelindan attached.
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            $hasActiveTrip = !empty($trip) && $trip->type != 2;
            $validator = Validator::make($request->all(), [
                'sales_order_id' => 'present|nullable|numeric',
                'date' => 'date_format:Y-m-d H:i:s',
                'customer_id' => 'required|numeric',
                'remark' => 'present|nullable|string',
                'salesorderdetail' => 'required|array',
                'salesorderdetail.*.product_id' => 'required',
                'salesorderdetail.*.quantity' => 'required',
                'salesorderdetail.*.price' => 'required',
                'salesorderdetail.*.foc' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id',$data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            //process
            DB::beginTransaction();
            $extsalesorder = SalesOrder::where('id',$data['sales_order_id'] ?? null)->where('status','!=',2)
                ->whereNull('deliveryorder_id')->whereNull('invoice_id')->first();
            $sono = null;
            $id = null;
            if(!empty($extsalesorder)){
                $sono = $extsalesorder->sono;
                $id = $extsalesorder->id;
                SalesOrderDetail::where('sales_order_id',$extsalesorder->id)->delete();
            }else{
                $sono = Code::nextRunningNumber('sorunningnumber', 'SO');
            }
            $salesOrder = new SalesOrder();
            if($id != null){
                $salesOrder->id = $id;
            }
            $salesOrder->sono = $sono;
            $salesOrder->date = $data['date'] ?? date('Y-m-d H:i:s');
            $salesOrder->customer_id = $data['customer_id'];
            $salesOrder->driver_id = $driver->id;
            $salesOrder->kelindan_id = $hasActiveTrip ? $trip->kelindan_id : null;
            $salesOrder->agent_id = $customer->agent_id;
            $salesOrder->supervisor_id = $customer->supervisor_id;
            $salesOrder->status = 0;
            $salesOrder->remark = $data['remark'] ?? null;
            $salesOrder->trip_id = $hasActiveTrip ? $driver->trip_id : null;
            $salesOrder->save();
            foreach($data['salesorderdetail'] as $line){
                $product = Product::where('id',$line['product_id'])->first();
                if(empty($product)){
                    DB::rollback();
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_product',
                        'data' => null
                    ], 400);
                }
                $detail = new SalesOrderDetail();
                $detail->sales_order_id = $salesOrder->id;
                $detail->product_id = $line['product_id'];
                $detail->quantity = $line['quantity'];
                $detail->price = $line['price'];
                $detail->totalprice = $line['quantity'] * $line['price'];
                $detail->remark = $line['foc'] ? 'FOC' : null;
                $detail->save();
            }
            Task::where('customer_id', $data['customer_id'])->where('driver_id',$driver->id)->update(['status' => 8]);
            DB::commit();
            $so = SalesOrder::where('id',$salesOrder->id)->with('salesorderdetail.product')->first();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.sales_order_add_successfully',
                'data' => $so
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Bulk-create sales orders queued on the device while offline - see
     * addinvoicebulk() for the full rationale (same design, no inventory/
     * FOC/payment side effects here since SO doesn't touch stock).
     * Running numbers get an "A" prefix (ASO instead of SO).
     */
    public function addsalesorderbulk(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(empty($trip) || $trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'salesorders' => 'required|array|min:1',
                'salesorders.*.client_ref' => 'required|string',
                'salesorders.*.date' => 'nullable|date_format:Y-m-d H:i:s',
                'salesorders.*.customer_id' => 'required|numeric',
                'salesorders.*.remark' => 'present|nullable|string',
                'salesorders.*.salesorderdetail' => 'required|array',
                'salesorders.*.salesorderdetail.*.product_id' => 'required',
                'salesorders.*.salesorderdetail.*.quantity' => 'required',
                'salesorders.*.salesorderdetail.*.price' => 'required',
                'salesorders.*.salesorderdetail.*.foc' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }

            $created = [];
            $failed = [];

            foreach($request->input('salesorders') as $item){
                DB::beginTransaction();
                try{
                    $customer = Customer::where('id', $item['customer_id'])->first();
                    if(empty($customer)){
                        throw new \Exception('Invalid customer');
                    }

                    // Same counter/format as an online SO (SO2609/0012),
                    // just with an "A" right before the running number
                    // itself (SO2609/A0012) to flag it as created offline.
                    $sono = preg_replace('/\/(\d+)$/', '/A$1', Code::nextRunningNumber('sorunningnumber', 'SO'));

                    $salesOrder = new SalesOrder();
                    $salesOrder->sono = $sono;
                    $salesOrder->date = $item['date'] ?? date('Y-m-d H:i:s');
                    $salesOrder->customer_id = $item['customer_id'];
                    $salesOrder->driver_id = $trip->driver_id;
                    $salesOrder->kelindan_id = $trip->kelindan_id;
                    $salesOrder->agent_id = $customer->agent_id;
                    $salesOrder->supervisor_id = $customer->supervisor_id;
                    $salesOrder->status = 0;
                    $salesOrder->remark = $item['remark'] ?? null;
                    $salesOrder->trip_id = $driver->trip_id;
                    $salesOrder->save();

                    foreach($item['salesorderdetail'] as $line){
                        $product = Product::where('id', $line['product_id'])->first();
                        if(empty($product)){
                            throw new \Exception('Invalid product');
                        }
                        $detail = new SalesOrderDetail();
                        $detail->sales_order_id = $salesOrder->id;
                        $detail->product_id = $line['product_id'];
                        $detail->quantity = $line['quantity'];
                        $detail->price = $line['price'];
                        $detail->totalprice = $line['quantity'] * $line['price'];
                        $detail->remark = $line['foc'] ? 'FOC' : null;
                        $detail->save();
                    }

                    Task::where('customer_id', $item['customer_id'])->where('driver_id', $driver->id)->update(['status' => 8]);

                    DB::commit();

                    $created[] = [
                        'client_ref' => $item['client_ref'],
                        'sales_order_id' => $salesOrder->id,
                        'sono' => $sono,
                    ];
                }
                catch(\Exception $e){
                    DB::rollback();
                    $failed[] = [
                        'client_ref' => $item['client_ref'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.bulk_sales_order_processed',
                'data' => ['created' => $created, 'failed' => $failed]
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getsalesorder(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            // Converted SOs are included (the app shows them as read-only history
            // with the DO/invoice they became); convert/cancel still reject them.
            $salesOrders = SalesOrder::where('driver_id', $driver->id)
                ->with('customer', 'salesorderdetail.product', 'deliveryorder:id,dono', 'invoice:id,invoiceno')
                ->orderby('date','desc')
                ->get();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.sales_order_list_successfully',
                'data' => $salesOrders
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getsalesorderbyid($id, Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $salesOrder = SalesOrder::where('id', $id)
                ->where('driver_id', $driver->id)
                ->with('customer', 'salesorderdetail.product', 'deliveryorder:id,dono', 'invoice:id,invoiceno')
                ->first();
            if(empty($salesOrder)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_not_found',
                    'data' => null
                ], 404);
            }
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.sales_order_get_successfully',
                'data' => $salesOrder
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Cancel (delete) one of this driver's own Sales Orders. Mirrors
     * SalesOrderController::destroy() - blocked once converted - and, since
     * a mobile "cancel" is far more reachable than the admin-only delete
     * page, additionally scoped to the driver's CURRENT trip so a driver
     * cannot reach back and delete an old SO from a previous, closed trip.
     */
    public function cancelsalesorder($id, Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $salesOrder = SalesOrder::where('id', $id)->where('driver_id', $driver->id)->first();
            if(empty($salesOrder)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_not_found',
                    'data' => null
                ], 404);
            }
            if(!empty($salesOrder->trip_id) && $salesOrder->trip_id != $driver->trip_id){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Sales Order belongs to a previous trip and can no longer be cancelled.',
                    'data' => null
                ], 400);
            }
            if(!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_already_converted',
                    'data' => null
                ], 400);
            }
            if($salesOrder->status == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_already_cancelled',
                    'data' => null
                ], 400);
            }
            DB::beginTransaction();
            // Keep the sales order and its lines for history; status 2 = Cancelled.
            $salesOrder->status = 2;
            $salesOrder->save();
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.sales_order_cancelled_successfully',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Convert a Sales Order into a Delivery Order (if customer.is_do_customer
     * and Credit is chosen) or straight into an Invoice - mirroring
     * SalesOrderController::convert(). The Invoice path additionally deducts
     * lorry inventory and applies FOC rules, matching addinvoice() - the
     * DO path does not, since a DO isn't a completed sale yet.
     */
    public function convertsalesorder(Request $request){
        try{
            $data = $request->all();
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'sales_order_id' => 'required|numeric',
                'paymentterm' => 'required|numeric|gt:0|lt:6',
                'cheque_no' => 'present|nullable|string',
                'attachments.*' => 'nullable|image|max:10240',
                'items' => 'nullable|array',
                // Each entry either overrides an existing SO line's quantity
                // (sales_order_detail_id) or ADDS a new product to the
                // converted document (product_id) - the SO itself stays as the
                // customer originally ordered it.
                'items.*.sales_order_detail_id' => 'nullable|numeric|required_without:items.*.product_id',
                'items.*.product_id' => 'nullable|numeric|required_without:items.*.sales_order_detail_id',
                'items.*.quantity' => 'required_with:items|numeric|gt:0',
                'cash_received' => 'nullable|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $salesOrder = SalesOrder::with('salesorderdetail')->where('id',$data['sales_order_id'])->where('status','!=',2)->first();
            if(empty($salesOrder)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_not_found',
                    'data' => null
                ], 400);
            }
            if(!empty($salesOrder->deliveryorder_id) || !empty($salesOrder->invoice_id)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_already_converted',
                    'data' => null
                ], 400);
            }
            if($salesOrder->salesorderdetail->isEmpty()){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_empty',
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id',$salesOrder->customer_id)->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            $quantityOverrides = [];
            $newItems = [];
            if(!empty($data['items'])){
                $detailIds = $salesOrder->salesorderdetail->pluck('id')->all();
                foreach($data['items'] as $item){
                    if(!empty($item['sales_order_detail_id'])){
                        if(!in_array($item['sales_order_detail_id'], $detailIds)){
                            return response()->json([
                                'result' => false,
                                'message' => __LINE__.$this->message_separator.'api.message.invalid_sales_order_detail',
                                'data' => null
                            ], 400);
                        }
                        $quantityOverrides[$item['sales_order_detail_id']] = $item['quantity'];
                    }else{
                        $product = Product::where('id', $item['product_id'])->where('status', 1)->first();
                        if(empty($product)){
                            return response()->json([
                                'result' => false,
                                'message' => __LINE__.$this->message_separator.'api.message.product_not_found',
                                'data' => null
                            ], 400);
                        }
                        // Price comes from the customer's price list, never the client.
                        $price = SpecialPrice::where('customer_id', $salesOrder->customer_id)
                            ->where('product_id', $product->id)
                            ->where('status', 1)
                            ->value('price') ?? $product->price;
                        $newItems[] = [
                            'product_id' => $product->id,
                            'quantity' => $item['quantity'],
                            'price' => $price,
                        ];
                    }
                }
            }
            $paymentterm = (int) $data['paymentterm'];
            DB::beginTransaction();
            if($customer->is_do_customer && $paymentterm == 2){
                $deliveryOrder = $this->convertSalesOrderToDeliveryOrder($salesOrder, $paymentterm, $data['cheque_no'] ?? null, $quantityOverrides, $newItems);
                DB::commit();
                $result = DeliveryOrder::where('id',$deliveryOrder->id)->with('deliveryorderdetail.product')->first();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_converted_to_delivery_order',
                    'data' => $result
                ], 200);
            }else{
                $invoice = $this->convertSalesOrderToInvoice($salesOrder, $paymentterm, $data['cheque_no'] ?? null, $quantityOverrides, $newItems, $data['cash_received'] ?? null);
                $this->storePaymentAttachments($request, $invoice);
                DB::commit();
                $result = Invoice::where('id',$invoice->id)->with('invoicedetail.product', 'paymentAttachments')->first();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.sales_order_converted_to_invoice',
                    'data' => $result
                ], 200);
            }
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    private function convertSalesOrderToDeliveryOrder(SalesOrder $salesOrder, $paymentterm, $chequeno, $quantityOverrides = [], $newItems = []){
        $dono = Code::nextRunningNumber('dorunningnumber', 'DO');

        $deliveryOrder = new DeliveryOrder();
        $deliveryOrder->dono = $dono;
        $deliveryOrder->date = $salesOrder->getRawOriginal('date');
        $deliveryOrder->customer_id = $salesOrder->customer_id;
        $deliveryOrder->driver_id = $salesOrder->driver_id;
        $deliveryOrder->kelindan_id = $salesOrder->kelindan_id;
        $deliveryOrder->agent_id = $salesOrder->agent_id;
        $deliveryOrder->supervisor_id = $salesOrder->supervisor_id;
        $deliveryOrder->paymentterm = $paymentterm;
        $deliveryOrder->status = 0;
        $deliveryOrder->remark = $salesOrder->remark;
        $deliveryOrder->chequeno = $chequeno;
        $deliveryOrder->trip_id = $salesOrder->trip_id;
        $deliveryOrder->save();

        foreach($salesOrder->salesorderdetail as $line){
            $quantity = $quantityOverrides[$line->id] ?? $line->quantity;

            $detail = new DeliveryOrderDetail();
            $detail->deliveryorder_id = $deliveryOrder->id;
            $detail->product_id = $line->product_id;
            $detail->quantity = $quantity;
            $detail->price = $line->price;
            $detail->totalprice = $quantity * $line->price;
            $detail->remark = $line->remark;
            $detail->save();
        }

        // Items added by the driver at conversion time - they go on the DO
        // only, the original SO stays as the customer ordered it.
        foreach($newItems as $newItem){
            $detail = new DeliveryOrderDetail();
            $detail->deliveryorder_id = $deliveryOrder->id;
            $detail->product_id = $newItem['product_id'];
            $detail->quantity = $newItem['quantity'];
            $detail->price = $newItem['price'];
            $detail->totalprice = $newItem['quantity'] * $newItem['price'];
            $detail->save();
        }

        $salesOrder->deliveryorder_id = $deliveryOrder->id;
        $salesOrder->save();

        return $deliveryOrder;
    }

    private function convertSalesOrderToInvoice(SalesOrder $salesOrder, $paymentterm, $chequeno, $quantityOverrides = [], $newItems = [], $cashReceived = null){
        if($paymentterm == 2){
            $invoiceno = Code::nextRunningNumber('invoicerunningnumber', 'IV');
        }else{
            $invoiceno = Code::nextRunningNumber('cashsalesrunningnumber', 'CS');
        }

        $invoice = new Invoice();
        $invoice->invoiceno = $invoiceno;
        $invoice->date = date('Y-m-d H:i:s');
        $invoice->customer_id = $salesOrder->customer_id;
        $invoice->driver_id = $salesOrder->driver_id;
        $invoice->kelindan_id = $salesOrder->kelindan_id;
        $invoice->agent_id = $salesOrder->agent_id;
        $invoice->supervisor_id = $salesOrder->supervisor_id;
        $invoice->paymentterm = $paymentterm;
        $invoice->status = 1;
        $invoice->remark = $salesOrder->remark;
        $invoice->chequeno = $chequeno;
        $invoice->trip_id = $salesOrder->trip_id;
        $invoice->save();

        $lorryId = optional(Driver::find($salesOrder->driver_id))->lorry_id;
        $totalprice = 0;
        foreach($salesOrder->salesorderdetail as $line){
            $qty = $quantityOverrides[$line->id] ?? $line->quantity;

            $detail = new InvoiceDetail();
            $detail->invoice_id = $invoice->id;
            $detail->product_id = $line->product_id;
            $detail->sales_order_id = $salesOrder->id;
            $detail->quantity = $qty;
            $detail->price = $line->price;
            $detail->totalprice = $qty * $line->price;
            $detail->remark = $line->remark;
            $detail->save();
            $totalprice = $totalprice + $detail->totalprice;

            if($line->remark !== 'FOC'){
                $focrule = foc::where('customer_id', $salesOrder->customer_id)
                    ->where('product_id', $line->product_id)
                    ->where('startdate', '<=', date('Y-m-d H:i:s'))
                    ->where('enddate', '>', date('Y-m-d H:i:s'))
                    ->where('status', 1)
                    ->first();
                if($focrule){
                    $newAchieveQuantity = $focrule->achievequantity + $qty;
                    $newStatus = ($newAchieveQuantity >= $focrule->quantity) ? 0 : 1;
                    $focrule->update([
                        'achievequantity' => $newAchieveQuantity,
                        'status' => $newStatus
                    ]);
                }
            }

            if($lorryId){
                $inventorybalance = InventoryBalance::where('lorry_id', $lorryId)->where('product_id', $line->product_id)->first();
                if(empty($inventorybalance)){
                    $newinventorybalance = new InventoryBalance();
                    $newinventorybalance->lorry_id = $lorryId;
                    $newinventorybalance->product_id = $line->product_id;
                    $newinventorybalance->quantity = 0 - $qty;
                    $newinventorybalance->save();
                }else{
                    $inventorybalance->quantity = $inventorybalance->quantity - $qty;
                    $inventorybalance->save();
                }
                $inventorytransaction = new InventoryTransaction();
                $inventorytransaction->lorry_id = $lorryId;
                $inventorytransaction->product_id = $line->product_id;
                $inventorytransaction->quantity = $qty * -1;
                $inventorytransaction->type = 3;
                $inventorytransaction->user = optional($salesOrder->driver)->employeeid ?? 'system';
                $inventorytransaction->date = date('Y-m-d H:i:s');
                $inventorytransaction->trip_id = $salesOrder->trip_id;
                $inventorytransaction->save();
            }
        }

        // Items added by the driver at conversion time: invoice lines with
        // the same stock deduction as SO lines; the SO itself is untouched.
        foreach($newItems as $newItem){
            $qty = $newItem['quantity'];
            $detail = new InvoiceDetail();
            $detail->invoice_id = $invoice->id;
            $detail->product_id = $newItem['product_id'];
            $detail->sales_order_id = $salesOrder->id;
            $detail->quantity = $qty;
            $detail->price = $newItem['price'];
            $detail->totalprice = $qty * $newItem['price'];
            $detail->save();
            $totalprice = $totalprice + $detail->totalprice;

            if($lorryId){
                $inventorybalance = InventoryBalance::where('lorry_id', $lorryId)->where('product_id', $newItem['product_id'])->first();
                if(empty($inventorybalance)){
                    $newinventorybalance = new InventoryBalance();
                    $newinventorybalance->lorry_id = $lorryId;
                    $newinventorybalance->product_id = $newItem['product_id'];
                    $newinventorybalance->quantity = 0 - $qty;
                    $newinventorybalance->save();
                }else{
                    $inventorybalance->quantity = $inventorybalance->quantity - $qty;
                    $inventorybalance->save();
                }
                $inventorytransaction = new InventoryTransaction();
                $inventorytransaction->lorry_id = $lorryId;
                $inventorytransaction->product_id = $newItem['product_id'];
                $inventorytransaction->quantity = $qty * -1;
                $inventorytransaction->type = 3;
                $inventorytransaction->user = optional($salesOrder->driver)->employeeid ?? 'system';
                $inventorytransaction->date = date('Y-m-d H:i:s');
                $inventorytransaction->trip_id = $salesOrder->trip_id;
                $inventorytransaction->save();
            }
        }

        if($paymentterm == 1){
            $invoicepayment = new InvoicePayment();
            $invoicepayment->invoice_id = $invoice->id;
            $invoicepayment->type = 1;
            $invoicepayment->customer_id = $invoice->customer_id;
            $invoicepayment->amount = $totalprice;
            $invoicepayment->status = 1;
            $invoicepayment->driver_id = $salesOrder->driver_id;
            $invoicepayment->approve_by = optional($salesOrder->driver)->name;
            $invoicepayment->approve_at = date('Y-m-d H:i:s');
            // Receipt prints the change when the cash handed over covers the total.
            if(is_numeric($cashReceived) && $cashReceived >= $totalprice){
                $invoicepayment->cash_received = $cashReceived;
            }
            $invoicepayment->save();
        }

        $salesOrder->invoice_id = $invoice->id;
        $salesOrder->save();

        return $invoice;
    }

    /**
     * Stores any uploaded payment-proof images (E-wallet/Online Banking/QR
     * Code transfers) against the given model (an Invoice, so far - other
     * document types can reuse this once they collect payment proof too).
     * No-op when the request carries no "attachments" files.
     */
    private function storePaymentAttachments(Request $request, $model){
        if(!$request->hasFile('attachments')){
            return;
        }
        foreach($request->file('attachments') as $file){
            if(!$file || !$file->isValid()){
                continue;
            }
            $path = $file->store('payment-attachments', 'public');
            PaymentAttachment::create([
                'attachable_type' => get_class($model),
                'attachable_id' => $model->id,
                'file_path' => $path,
            ]);
        }
    }

    // ── Delivery Orders ──────────────────────────────────────────────────────

    public function getdeliveryorder(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            // Converted DOs are included (the app shows them as read-only history
            // with the invoice they became); cancel/combine-convert still reject them.
            $deliveryOrders = DeliveryOrder::where('driver_id', $driver->id)
                ->with('customer', 'deliveryorderdetail.product', 'invoice:id,invoiceno')
                ->orderby('date','desc')
                ->get();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.delivery_order_list_successfully',
                'data' => $deliveryOrders
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getdeliveryorderbyid($id, Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $deliveryOrder = DeliveryOrder::where('id', $id)
                ->where('driver_id', $driver->id)
                ->with('customer', 'deliveryorderdetail.product', 'invoice:id,invoiceno')
                ->first();
            if(empty($deliveryOrder)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_not_found',
                    'data' => null
                ], 404);
            }
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.delivery_order_get_successfully',
                'data' => $deliveryOrder
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Cancel (delete) one of this driver's own Delivery Orders. Mirrors
     * DeliveryOrderController::destroy() - blocked once converted - and,
     * like cancelsalesorder(), scoped to the driver's current trip.
     */
    public function canceldeliveryorder($id, Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $deliveryOrder = DeliveryOrder::where('id', $id)->where('driver_id', $driver->id)->first();
            if(empty($deliveryOrder)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_not_found',
                    'data' => null
                ], 404);
            }
            if(!empty($deliveryOrder->trip_id) && $deliveryOrder->trip_id != $driver->trip_id){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Delivery Order belongs to a previous trip and can no longer be cancelled.',
                    'data' => null
                ], 400);
            }
            if(!empty($deliveryOrder->invoice_id)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_already_converted',
                    'data' => null
                ], 400);
            }
            if($deliveryOrder->status == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_already_cancelled',
                    'data' => null
                ], 400);
            }
            DB::beginTransaction();
            // Keep the delivery order and its lines for history; status 2 = Cancelled.
            $deliveryOrder->status = 2;
            $deliveryOrder->save();
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.delivery_order_cancelled_successfully',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Combine one or more same-customer Delivery Orders into a single Invoice -
     * mirroring DeliveryOrderController::combineConvert(), plus inventory/FOC
     * effects (an invoice now genuinely exists), matching addinvoice().
     */
    public function combineconvertdeliveryorder(Request $request){
        try{
            $data = $request->all();
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $deliveryOrders = DeliveryOrder::with('deliveryorderdetail')->whereIn('id',$data['ids'])->whereNull('invoice_id')->where('status','!=',2)->get();
            if($deliveryOrders->isEmpty()){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_not_found',
                    'data' => null
                ], 400);
            }
            // Reject rather than silently invoicing only part of the selection
            // when some of the chosen DOs were already converted.
            if($deliveryOrders->count() !== collect($data['ids'])->unique()->count()){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_already_converted',
                    'data' => null
                ], 400);
            }
            $customerIds = $deliveryOrders->pluck('customer_id')->unique();
            if($customerIds->count() > 1){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.delivery_order_must_be_same_customer',
                    'data' => null
                ], 400);
            }
            DB::beginTransaction();
            $first = $deliveryOrders->first();
            $invoiceno = Code::nextRunningNumber('invoicerunningnumber', 'IV');

            $invoice = new Invoice();
            $invoice->invoiceno = $invoiceno;
            $invoice->date = date('Y-m-d H:i:s');
            $invoice->customer_id = $first->customer_id;
            $invoice->driver_id = $first->driver_id;
            $invoice->kelindan_id = $first->kelindan_id;
            $invoice->agent_id = $first->agent_id;
            $invoice->supervisor_id = $first->supervisor_id;
            $invoice->paymentterm = $first->paymentterm;
            $invoice->status = 1;
            $invoice->remark = $deliveryOrders->count() > 1 ? 'Combined from ' . $deliveryOrders->count() . ' delivery order(s)' : $first->remark;
            $invoice->chequeno = $first->chequeno;
            $tripIds = $deliveryOrders->pluck('trip_id')->unique();
            $invoice->trip_id = $tripIds->count() === 1 ? $tripIds->first() : null;
            $invoice->save();

            $lorryId = optional(Driver::find($first->driver_id))->lorry_id;
            $totalprice = 0;
            foreach($deliveryOrders as $deliveryOrder){
                foreach($deliveryOrder->deliveryorderdetail as $line){
                    $detail = new InvoiceDetail();
                    $detail->invoice_id = $invoice->id;
                    $detail->product_id = $line->product_id;
                    $detail->deliveryorder_id = $deliveryOrder->id;
                    $detail->quantity = $line->quantity;
                    $detail->price = $line->price;
                    $detail->totalprice = $line->totalprice;
                    $detail->remark = $line->remark;
                    $detail->save();
                    $totalprice = $totalprice + $detail->totalprice;

                    if($line->remark !== 'FOC'){
                        $focrule = foc::where('customer_id', $deliveryOrder->customer_id)
                            ->where('product_id', $line->product_id)
                            ->where('startdate', '<=', date('Y-m-d H:i:s'))
                            ->where('enddate', '>', date('Y-m-d H:i:s'))
                            ->where('status', 1)
                            ->first();
                        if($focrule){
                            $newAchieveQuantity = $focrule->achievequantity + $line->quantity;
                            $newStatus = ($newAchieveQuantity >= $focrule->quantity) ? 0 : 1;
                            $focrule->update([
                                'achievequantity' => $newAchieveQuantity,
                                'status' => $newStatus
                            ]);
                        }
                    }

                    if($lorryId){
                        $inventorybalance = InventoryBalance::where('lorry_id', $lorryId)->where('product_id', $line->product_id)->first();
                        if(empty($inventorybalance)){
                            $newinventorybalance = new InventoryBalance();
                            $newinventorybalance->lorry_id = $lorryId;
                            $newinventorybalance->product_id = $line->product_id;
                            $newinventorybalance->quantity = 0 - $line->quantity;
                            $newinventorybalance->save();
                        }else{
                            $inventorybalance->quantity = $inventorybalance->quantity - $line->quantity;
                            $inventorybalance->save();
                        }
                        $inventorytransaction = new InventoryTransaction();
                        $inventorytransaction->lorry_id = $lorryId;
                        $inventorytransaction->product_id = $line->product_id;
                        $inventorytransaction->quantity = $line->quantity * -1;
                        $inventorytransaction->type = 3;
                        $inventorytransaction->user = optional($first->driver)->employeeid ?? 'system';
                        $inventorytransaction->date = date('Y-m-d H:i:s');
                        $inventorytransaction->trip_id = $invoice->trip_id;
                        $inventorytransaction->save();
                    }
                }

                $deliveryOrder->invoice_id = $invoice->id;
                $deliveryOrder->save();
            }

            if($first->paymentterm == 1){
                $invoicepayment = new InvoicePayment();
                $invoicepayment->invoice_id = $invoice->id;
                $invoicepayment->type = 1;
                $invoicepayment->customer_id = $invoice->customer_id;
                $invoicepayment->amount = $totalprice;
                $invoicepayment->status = 1;
                $invoicepayment->driver_id = $first->driver_id;
                $invoicepayment->approve_by = optional($first->driver)->name;
                $invoicepayment->approve_at = date('Y-m-d H:i:s');
                $invoicepayment->save();
            }

            DB::commit();
            $result = Invoice::where('id',$invoice->id)->with('invoicedetail.product')->first();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.delivery_order_converted_to_invoice',
                'data' => $result
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // ── Packing List ─────────────────────────────────────────────────────────

    /**
     * Build the driver's packing list from their Sales Orders for the day.
     * Sourced from SalesOrder/SalesOrderDetail (what's planned to be loaded/
     * delivered), not Invoice (which may not exist yet at packing time).
     * Pass sales_order_ids (array) to scope it to exactly those SOs (e.g.
     * driver-checked items in a "select which SO to pack" modal) - takes
     * priority over date/group filtering. Otherwise pass customer_group_id
     * to scope to one customer group, or omit both to get every one of the
     * driver's Sales Orders for the day.
     */
    public function packinglistpdf(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $date = $request->input('date', date('Y-m-d'));
            $groupId = $request->input('customer_group_id');
            $salesOrderIds = $request->input('sales_order_ids');

            $salesOrdersQuery = SalesOrder::where('driver_id', $driver->id)
                ->with(['customer:id,company', 'salesorderdetail.product:id,code,name']);

            if (!empty($salesOrderIds) && is_array($salesOrderIds)) {
                // Driver picked specific SOs (e.g. from the packing list
                // modal's checklist) - scope to exactly those, no date/group
                // filtering.
                $salesOrdersQuery->whereIn('id', $salesOrderIds);
            } else {
                $salesOrdersQuery->whereDate('date', $date);

                if (!empty($groupId)) {
                    $salesOrdersQuery->whereHas('customer', function($q) use ($groupId) {
                        $q->whereRaw('FIND_IN_SET(?, `group`)', [$groupId]);
                    });
                }
            }

            $salesOrders = $salesOrdersQuery->get();

            // Preserve today's planned visit order where available, so the
            // packing list still lines up with the driver's route sequence.
            $taskSequences = Task::where('driver_id', $driver->id)
                ->where('date', $date)
                ->pluck('sequence', 'customer_id');

            $products = Product::orderBy('id')->get(['id', 'code', 'name']);

            $rows = $salesOrders
                ->groupBy('customer_id')
                ->map(function ($orders) use ($products) {
                    $quantities = array_fill_keys($products->pluck('id')->all(), 0);
                    foreach ($orders as $order) {
                        foreach ($order->salesorderdetail as $detail) {
                            if (isset($quantities[$detail->product_id])) {
                                $quantities[$detail->product_id] += $detail->quantity;
                            }
                        }
                    }
                    $first = $orders->first();
                    return [
                        'customer_id' => $first->customer_id,
                        'customer_name' => $first->customer?->company ?? '-',
                        'quantities' => $quantities,
                    ];
                })
                ->sortBy(function ($row) use ($taskSequences) {
                    return $taskSequences[$row['customer_id']] ?? PHP_INT_MAX;
                })
                ->values();

            $totals = array_fill_keys($products->pluck('id')->all(), 0);
            foreach ($rows as $row) {
                foreach ($row['quantities'] as $productId => $qty) {
                    $totals[$productId] += $qty;
                }
            }

            $pdf = Pdf::loadView('reports.packing_list_pdf', [
                'driver' => $driver,
                'date' => $date,
                'products' => $products,
                'rows' => $rows,
                'totals' => $totals,
            ])->setPaper('a4', 'landscape');

            $filename = 'packing-list-' . $driver->id . '-' . now()->format('YmdHis') . '.pdf';
            $path = 'packing-list-pdf/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * List the customer group(s) this driver has customers assigned in
     * (Assign.customer_id -> Customer.group), for the mobile group-picker
     * shown before the drag-and-drop reorder screen. Returns an empty list
     * until admin has assigned this driver at least one customer via the
     * existing Assigns -> By customer group screen.
     */
    public function getcustomergroups(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }

            $customerIds = Assign::where('driver_id', $driver->id)->pluck('customer_id');
            $groupIds = Customer::whereIn('id', $customerIds)
                ->pluck('group')
                ->flatMap(function($group) {
                    return explode(',', (string) $group);
                })
                ->map(function($value) {
                    return trim($value);
                })
                ->filter(function($value) {
                    return $value !== '';
                })
                ->unique()
                ->values();

            $groups = Code::where('code', 'customer_group')
                ->whereIn('value', $groupIds)
                ->get(['value as id', 'description as name']);

            return response()->json([
                'result' => true,
                'message' => 'OK',
                'data' => $groups
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Return a customer group's customers, in this driver's current visit
     * order (Assign.sequence), for the mobile drag-and-drop reorder screen.
     * Mirrors the admin web AssignController::customerfindgroup(), scoped to
     * the authenticated driver instead of an arbitrary driver_id/lorry_id.
     */
    public function getcustomergroup(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $groupId = $request->input('group_id');

            $customers = Customer::whereRaw('FIND_IN_SET(?, `group`)', [$groupId])
                ->get(['id', 'company']);

            $existingAssignments = Assign::where('driver_id', $driver->id)
                ->whereIn('customer_id', $customers->pluck('id'))
                ->orderBy('sequence', 'asc')
                ->get()
                ->keyBy('customer_id');

            $result = [];
            foreach ($existingAssignments as $assignment) {
                $customer = $customers->firstWhere('id', $assignment->customer_id);
                if ($customer) {
                    $result[] = [
                        'customer_id' => $customer->id,
                        'company' => $customer->company,
                        'sequence' => $assignment->sequence,
                    ];
                }
            }
            $maxSequence = $existingAssignments->max('sequence') ?: 0;
            $remainingCustomers = $customers->whereNotIn('id', $existingAssignments->pluck('customer_id'))->values();
            foreach ($remainingCustomers as $index => $customer) {
                $result[] = [
                    'customer_id' => $customer->id,
                    'company' => $customer->company,
                    'sequence' => $maxSequence + $index + 1,
                ];
            }
            usort($result, function($a, $b) {
                return $a['sequence'] - $b['sequence'];
            });

            return response()->json([
                'result' => true,
                'message' => 'OK',
                'data' => [
                    'group_id' => (int) $groupId,
                    'customers' => $result,
                ]
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Accept a driver-reordered customer group from the mobile app and
     * persist the new visit order into Assign.sequence for this driver.
     * Takes effect the next time the driver starts a trip - starttrip()
     * already builds that trip's Tasks from
     * Assign::where('driver_id', ...)->orderBy('sequence') (unchanged here),
     * so no separate task-generation logic is needed. Does not touch any
     * trip/Task rows already in progress.
     */
    public function updatecustomergroup(Request $request){
        try{
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|numeric',
                'customers' => 'required|array|min:1',
                'customers.*' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $groupId = $request->input('group_id');
            $customerIds = $request->input('customers');

            $validCustomerIds = Customer::whereRaw('FIND_IN_SET(?, `group`)', [$groupId])
                ->pluck('id')
                ->all();
            $invalidIds = array_diff($customerIds, $validCustomerIds);
            if (!empty($invalidIds)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }

            DB::beginTransaction();
            foreach ($customerIds as $index => $customerId) {
                Assign::updateOrCreate(
                    ['driver_id' => $driver->id, 'customer_id' => $customerId],
                    ['sequence' => $index + 1]
                );
            }
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => 'OK',
                'data' => [
                    'group_id' => (int) $groupId,
                    'customers' => collect($customerIds)->values()->map(function($customerId, $index) {
                        return ['customer_id' => (int) $customerId, 'sequence' => $index + 1];
                    }),
                ]
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

}
