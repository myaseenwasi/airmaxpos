<?php
namespace App\Http\Controllers;

use App\AppVersion;
use App\Business;
use Illuminate\Http\Request;
use App\UpdateLog;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
class UpdateController extends Controller
{
    // List of all tables to export
    
    private $tablesToExport = [];

    private function getTablesToExport()
    {
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            if (strpos($tableName, 'migrations') === false && strpos($tableName, 'password_resets') === false && $tableName != 'activity_log') {
                $this->tablesToExport[] = $tableName;
            }
        }
        return $this->tablesToExport;
    }

    public function pending(Request $request)
    {
        $latestLog = UpdateLog::latest('id')->first();

        $clientVersion = $latestLog->version ?? '0.0.0';

        // External API hit karo
        $url = env('UPDATEAPIURL') . '/api/version';

        $response = Http::withOptions([
            'verify' => false
        ])->get($url);

        if (!$response->successful()) {
            return response()->json([
                'update_available' => false,
                'log_id' => 0,
                'message' => 'Failed to check for update. Try again later.',
            ], 500);
        }

        $latestVersion = $response->json()['version'];



        if (is_null($clientVersion) || version_compare($latestVersion, $clientVersion, 'gt')) {
            return response()->json([
                'update_available' => true,
                'log_id' => 1, // dummy ID, or skip
                'message' => "New update {$latestVersion} is available.",
                'version' => $latestVersion,
                'current_version' => $clientVersion,
            ]);
        }

        return response()->json([
            'update_available' => false,
            'log_id' => 0, // dummy ID, or skip
            'message' => "Upto date.",
            'version' => $latestVersion,
            'current_version' => $clientVersion,
        ]);
    }

    public function version()
    {
        $latestLog = AppVersion::where('is_force_update', 1)->latest('id')->first();

        return response()->json([
            'version' => $latestLog->version ?? '1.0.0',
        ]);
    }

    public function approve(Request $request)
    {
        try {
            // Step 1: Git Pull
            $output = shell_exec('cd ' . base_path() . ' && git pull origin main 2>&1');

            // Step 2: Get latest version from external API
            $response = Http::withOptions([
                'verify' => false
            ])->get(env('UPDATEAPIURL') . '/api/version');

            if (!$response->successful() || !$response->json('version')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch version from external API.',
                ], 500);
            }

            $latestVersion = $response->json('version');

            // Step 3: Get or Create Log Record
            // $log = UpdateLog::where('update_available', 0)->latest()->first();

            // if ($log) {
            //     // Update existing log
            //     $log->message = $output;
            //     $log->update_available = 0;
            //     $log->save();
            // } else {
            //     // Create new log if not found
            //     UpdateLog::create([
            //         'version' => $latestVersion,
            //         'message' => $output,
            //         'update_available' => 0,
            //     ]);
            // }

            // Create new log if not found
            UpdateLog::create([
                'version' => !empty($latestVersion) ? $latestVersion : '1.0.0',
                'message' => $output,
                'update_available' => 0,
            ]);

            return response()->json(['success' => true, 'output' => $output]);
        } catch (\Exception $e) {
            // Optionally log as failed
            if (isset($log)) {
                $log->status = 'failed';
                $log->message = $e->getMessage();
                $log->save();
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function last()
    {
        return UpdateLog::latest()->first();
    }


    public function exportCompleteData(Request $request)
    {
        $export_format = $request->input('export_format');
        $user_id = $request->input('user_id');
        $business_id = $request->input('business_id');

        // Validation
        if (empty($user_id) && empty($business_id)) {
            return response()->json(['error' => 'Either user_id or business_id must be provided'], 400);
        }

        if (!in_array($export_format, ['json', 'csv', 'xml', 'sql'])) {
            return response()->json(['error' => 'Invalid export format'], 400);
        }

        // Get data
        $exportData = $this->fetchExportData($user_id, $business_id);

        // For debugging - check data before export
        Log::info('Export data collected', ['tables' => array_keys($exportData)]);

        // Return in requested format
        switch ($export_format) {
            case 'json': return response()->json($exportData);
            case 'csv': return $this->convertToCsv($exportData);
            case 'xml': return $this->convertToXml($exportData);
            case 'sql': return $this->generateSqlDownload($exportData, $user_id, $business_id);
            default: return response()->json($exportData);
        }
    }

    private function fetchExportData($user_id, $business_id)
    {
        $exportData = [];
        $this->getTablesToExport();
        foreach ($this->tablesToExport as $table) {
            try {
                $query = DB::table($table);
                
                

                if($table == "business"){
                    $query->where('id', $business_id);
                } else {
                    if ($business_id && in_array('business_id', $this->getTableColumns($table))) {
                        $query->where('business_id', $business_id);
                    } else if ($user_id) {
                        if (in_array('user_id', $this->getTableColumns($table))) {
                            $query->where('user_id', $user_id);
                        } elseif (in_array('created_by', $this->getTableColumns($table))) {
                            $query->where('created_by', $user_id);
                        }
                    }
                }
                
                $results = $query->get();
                
                if ($results->isNotEmpty()) {
                    $exportData[$table] = $results;
                }
                
            } catch (\Exception $e) {
                Log::error("Error exporting table $table: " . $e->getMessage());
                $exportData[$table] = ['error' => 'Could not export table'];
            }
        }
        
        return $exportData;
    }

    private function generateSqlDownload($data, $user_id, $business_id)
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'sql_export_');
        $handle = fopen($tempFile, 'w');
        
        // Write SQL header
        fwrite($handle, "-- POS Data Export (INSERT statements only)\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- User ID: " . ($user_id ?? 'N/A') . "\n");
        fwrite($handle, "-- Business ID: " . ($business_id ?? 'N/A') . "\n\n");
        
        // Disable foreign key checks temporarily
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        // Process business_info first (if exists)
        if (isset($data['business_info'])) {
            $businessData = (array)$data['business_info'];
            $columns = array_keys($businessData);
            $columnsStr = '`' . implode('`,`', $columns) . '`';
            
            $values = array_map(function($value) {
                if (is_null($value)) return 'NULL';
                if (is_numeric($value)) return $value;
                if (is_bool($value)) return $value ? 1 : 0;
                if ($value === '0000-00-00 00:00:00') return 'NULL';
                return "'" . addslashes($value) . "'";
            }, array_values($businessData));
            
            fwrite($handle, "-- Business information\n");
            fwrite($handle, "INSERT INTO `business` ($columnsStr) VALUES (".implode(",", $values).");\n\n");
        }

        // Process all other tables
        foreach ($data as $table => $records) {
            // Skip metadata tables we already processed
            if ($table === 'user_info' || $table === 'business_info') {
                continue;
            }
            
            // Skip if not iterable or empty
            if (!is_iterable($records) || 
                (is_array($records) && empty($records)) || 
                (is_object($records) && method_exists($records, 'isEmpty') && $records->isEmpty())) {
                continue;
            }

            // Convert to array if Collection
            $recordsArray = is_object($records) && method_exists($records, 'toArray') 
                ? $records->toArray() 
                : (array)$records;

            // Get columns from first record
            $firstRecord = (array)$recordsArray[0];
            $columns = array_keys($firstRecord);
            $columnsStr = '`' . implode('`,`', $columns) . '`';
            
            fwrite($handle, "-- Data for table `$table`\n");
            
            // Process in chunks
            $chunks = array_chunk($recordsArray, 100);
            foreach ($chunks as $chunk) {
                fwrite($handle, "INSERT INTO `$table` ($columnsStr) VALUES\n");
                
                foreach ($chunk as $i => $record) {
                    $record = (array)$record;
                    $values = array_map(function($value) {
                        if (is_null($value)) return 'NULL';
                        if (is_numeric($value)) return $value;
                        if (is_bool($value)) return $value ? 1 : 0;
                        if ($value === '0000-00-00 00:00:00') return 'NULL';
                        return "'" . addslashes($value) . "'";
                    }, array_values($record));
                    
                    fwrite($handle, "(" . implode(",", $values) . ")");
                    fwrite($handle, ($i < count($chunk)-1 ? ",\n" : ";\n\n"));
                }
            }
        }
        
        // Re-enable foreign key checks
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        
        fclose($handle);
        
        // Create download response
        $filename = 'pos_export_' . ($user_id ? 'user_'.$user_id : 'business_'.$business_id) . '.sql';
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/sql',
        ])->deleteFileAfterSend(true);
    }

    private function getTableColumns($table)
    {
        return DB::getSchemaBuilder()->getColumnListing($table);
    }

    private function convertToCsv($data)
    {
        // Implementation to convert data to CSV
        $csvData = [];
        
        // Flatten the data structure
        foreach ($data as $table => $records) {
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (is_object($record)) {
                        $record = (array)$record;
                    }
                    $csvData[] = array_merge(['Table' => $table], $record);
                }
            }
        }
        
        // Generate CSV
        $output = fopen('php://temp', 'w');
        if (!empty($csvData)) {
            fputcsv($output, array_keys($csvData[0]));
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="complete_export.csv"',
        ]);
    }

    private function convertToXml($data)
    {
        // Implementation to convert data to XML
        $xml = new \SimpleXMLElement('<root/>');
        
        foreach ($data as $table => $records) {
            $tableNode = $xml->addChild($table);
            if (is_array($records)) {
                foreach ($records as $record) {
                    $recordNode = $tableNode->addChild('record');
                    foreach ((array)$record as $key => $value) {
                        $recordNode->addChild($key, htmlspecialchars($value));
                    }
                }
            }
        }
        
        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="complete_export.xml"',
        ]);
    }

    public function importSql(Request $request)
{
    $request->validate([
        'sql_file' => 'required|file',
    ]);

    if ($request->file('sql_file')->getClientOriginalExtension() !== 'sql') {
        return response()->json([
            'success' => false,
            'message' => 'Only .sql files are allowed.',
        ]);
    }

    $sqlContent = file_get_contents($request->file('sql_file')->getRealPath());

    try {
        DB::beginTransaction();

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Get all table names
        // $database = env('DB_DATABASE');
        // $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$database]);

        // foreach ($tables as $table) {
        //     $tableName = $table->table_name;

        //     // Skip Laravel system tables if needed
        //     if (!in_array($tableName, ['migrations', 'password_resets', 'failed_jobs'])) {
        //         DB::statement("TRUNCATE TABLE `$tableName`");
        //     }
        // }

        // Import SQL file
        DB::unprepared($sqlContent);

        // Re-enable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => '✅ SQL imported successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json([
            'success' => false,
            'message' => '❌ Import failed: ' . $e->getMessage(),
        ]);
    }
}
}
