<?php

namespace App\Http\Controllers;

use App\DataTables\ApiLogDataTable;
use App\Models\ApiLog;
use Flash;
use Illuminate\Support\Facades\Crypt;

class ApiLogController extends AppBaseController
{
    public function index(ApiLogDataTable $apiLogDataTable)
    {
        return $apiLogDataTable->render('api_logs.index');
    }

    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $apiLog = ApiLog::with('driver')->find($id);

        if (empty($apiLog)) {
            Flash::error('Log entry not found');
            return redirect(route('apiLogs.index'));
        }

        return view('api_logs.show')->with('apiLog', $apiLog);
    }

    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $apiLog = ApiLog::find($id);

        if (empty($apiLog)) {
            Flash::error('Log entry not found');
            return redirect(route('apiLogs.index'));
        }

        $apiLog->delete();

        Flash::success('Log entry deleted successfully.');

        return redirect(route('apiLogs.index'));
    }

    /**
     * Clear log entries older than 7 days - housekeeping for a table that
     * gets a row on every single API call.
     */
    public function clear()
    {
        $deleted = ApiLog::where('created_at', '<', now()->subDays(7))->delete();

        Flash::success("Cleared $deleted log entries older than 7 days.");

        return redirect(route('apiLogs.index'));
    }
}
