<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UpdateLog;

class UpdateController extends Controller
{
    public function pending()
    {
        // $log = UpdateLog::where('update_available', true)
        //     ->latest()
        //     ->first();

        return response()->json([
            'update_available' => true,
            'log_id' => 1,
            'message' => "Version 1.0.5 is available",
        ]);

        // return response()->json([
        //     'update_available' => false,
        //     'log_id' => 0,
        //     'message' => "",
        // ]);
    }

    public function approve(Request $request)
    {
        $log = UpdateLog::where('update_available', true)->latest()->first();

        if (!$log) {
            return response()->json(['success' => false, 'message' => 'No update found']);
        }

        $log->update(['status' => 'approved', 'updated_by' => $request->user()->name ?? 'Anonymous']);

        try {
            $output = shell_exec('cd ' . base_path() . ' && git pull origin main 2>&1');

            $log->update([
                'status' => 'success',
                'message' => $output,
                'update_available' => false,
            ]);

            return response()->json(['success' => true, 'output' => $output]);
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function last()
    {
        return UpdateLog::latest()->first();
    }
}
