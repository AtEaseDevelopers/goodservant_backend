<?php

namespace App\Http\Controllers;

use App\DataTables\MobileErrorLogDataTable;
use App\Models\MobileErrorLog;
use Flash;
use Illuminate\Support\Facades\Crypt;

class MobileErrorLogController extends AppBaseController
{
    /**
     * Display a listing of the MobileErrorLog.
     */
    public function index(MobileErrorLogDataTable $mobileErrorLogDataTable)
    {
        return $mobileErrorLogDataTable->render('mobile_error_logs.index');
    }

    /**
     * Display the full message/stack trace for one log entry.
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $mobileErrorLog = MobileErrorLog::with('driver')->find($id);

        if (empty($mobileErrorLog)) {
            Flash::error('Log entry not found');

            return redirect(route('mobileErrorLogs.index'));
        }

        return view('mobile_error_logs.show')->with('mobileErrorLog', $mobileErrorLog);
    }

    /**
     * Remove one log entry.
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $mobileErrorLog = MobileErrorLog::find($id);

        if (empty($mobileErrorLog)) {
            Flash::error('Log entry not found');

            return redirect(route('mobileErrorLogs.index'));
        }

        $mobileErrorLog->delete();

        Flash::success('Log entry deleted successfully.');

        return redirect(route('mobileErrorLogs.index'));
    }

    /**
     * Clear every log entry - housekeeping once old crashes are resolved.
     */
    public function clear()
    {
        MobileErrorLog::query()->delete();

        Flash::success('All log entries cleared.');

        return redirect(route('mobileErrorLogs.index'));
    }
}
