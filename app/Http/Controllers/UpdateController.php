<?php
namespace App\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use App\UpdateLog;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    // List of all tables to export
    private $tablesToExport = [
        'account_transactions',
        'account_types',
        'accounting_acc_trans_mappings',
        'accounting_account_types',
        'accounting_accounts',
        'accounting_accounts_transactions',
        'accounting_budgets',
        'accounts',
        'activity_log',
        'barcodes',
        'bookings',
        'brands',
        'business',
        'business_locations',
        'cash_denominations',
        'cash_register_transactions',
        'cash_registers',
        'categories',
        'categorizables',
        'contacts',
        'currencies',
        'customer_groups',
        'dashboard_configurations',
        'devices',
        'discount_variations',
        'discounts',
        'document_and_notes',
        'essentials_allowances_and_deductions',
        'essentials_attendances',
        'essentials_document_shares',
        'essentials_documents',
        'essentials_holidays',
        'essentials_kb',
        'essentials_kb_users',
        'essentials_leave_types',
        'essentials_leaves',
        'essentials_messages',
        'essentials_payroll_group_transactions',
        'essentials_payroll_groups',
        'essentials_reminders',
        'essentials_shifts',
        'essentials_to_dos',
        'essentials_todo_comments',
        'essentials_todos_users',
        'essentials_user_allowance_and_deductions',
        'essentials_user_sales_targets',
        'essentials_user_shifts',
        'expense_categories',
        'group_sub_taxes',
        'invoice_layouts',
        'invoice_schemes',
        'media',
        'migrations',
        'model_has_permissions',
        'model_has_roles',
        'notification_templates',
        'notifications',
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
        'packages',
        'password_resets',
        'permissions',
        'printers',
        'product_locations',
        'product_racks',
        'product_variations',
        'products',
        'purchase_lines',
        'reference_counts',
        'res_product_modifier_sets',
        'res_tables',
        'role_has_permissions',
        'roles',
        'sell_line_warranties',
        'selling_price_groups',
        'sessions',
        'stock_adjustment_lines',
        'subscriptions',
        'superadmin_communicator_logs',
        'superadmin_coupons',
        'superadmin_frontend_pages',
        'system',
        'tax_rates',
        'transaction_payments',
        'transaction_sell_lines',
        'transaction_sell_lines_purchase_lines',
        'transactions',
        'types_of_services',
        'units',
        'user_contact_access',
        'users',
        'variation_group_prices',
        'variation_location_details',
        'variation_templates',
        'variation_value_templates',
        'variations',
        'warranties'
    ];

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

    // public function exportCompleteData(Request $request)
    // {
    //     $export_format = $request->input('export_format');
    //     $user_id = $request->input('user_id');
    //     $business_id = $request->input('business_id');

    //     if (empty($user_id) && empty($business_id)) {
    //         return response()->json(['error' => 'Either user_id or business_id must be provided'], 400);
    //     }

    //     if (!in_array($export_format, ['json', 'csv', 'xml'])) {
    //         return response()->json(['error' => 'Invalid export format'], 400);
    //     }
    //     // Initialize export data array
    //     $exportData = [];

    //     // Get user and business info if provided
    //     if (!empty($user_id)) {
    //         $user = User::find($user_id);
    //         if (!$user) {
    //             return response()->json(['error' => 'User not found'], 404);
    //         }
    //         $exportData['user_info'] = $user;
    //     }
      
    //     if (!empty($business_id)) {
    //         $business = Business::find($business_id);
    //         if (!$business) {
    //             return response()->json(['error' => 'Business not found'], 404);
    //         }
    //         $exportData['business_info'] = $business;
    //     }
        
    //     // Export data from each table
    //     foreach ($this->tablesToExport as $table) {
    //         try {
    //             $query = DB::table($table);
              
    //             // Filter by user_id if provided (for tables that have user_id/created_by)
    //             if (!empty($user_id)) {
    //                 if (in_array('user_id', $this->getTableColumns($table))) {
    //                     $query->where('user_id', $user_id);
    //                 } elseif (in_array('created_by', $this->getTableColumns($table))) {
    //                     $query->where('created_by', $user_id);
    //                 }
    //             }
                
    //             // Filter by business_id if provided
    //             if (!empty($business_id) && in_array('business_id', $this->getTableColumns($table))) {
    //                 $query->where('business_id', $business_id);
    //             }
                
    //             $exportData[$table] = $query->get();
    //         } catch (\Exception $e) {
    //             // Log error but continue with other tables
    //             Log::error("Error exporting table $table: " . $e->getMessage());
    //             $exportData[$table] = ['error' => 'Could not export table'];
    //         }
    //     }
    //     return $exportData;die;
    //     // Return data in requested format
    //     switch ($export_format) {
    //         case 'json':
    //             return response()->json($exportData);
    //         case 'csv':
    //             return $this->convertToCsv($exportData);
    //         case 'xml':
    //             return $this->convertToXml($exportData);
    //         default:
    //             return response()->json($exportData);
    //     }
    // }

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
    
    foreach ($this->tablesToExport as $table) {
        try {
            $query = DB::table($table);
            
            if ($user_id) {
                if (in_array('user_id', $this->getTableColumns($table))) {
                    $query->where('user_id', $user_id);
                } elseif (in_array('created_by', $this->getTableColumns($table))) {
                    $query->where('created_by', $user_id);
                }
            }
            
            if ($business_id && in_array('business_id', $this->getTableColumns($table))) {
                $query->where('business_id', $business_id);
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
    
    // Write SQL to file
    foreach ($data as $table => $records) {
        if ($table === 'user_info' || $table === 'business_info' || !is_iterable($records)) {
            continue;
        }
        
        if (count($records) > 0) {
            $columns = array_keys((array)$records[0]);
            fwrite($handle, "-- Table: $table\n");
            fwrite($handle, "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES\n");
            
            foreach ($records as $i => $record) {
                $record = (array)$record;
                $values = array_map(function($value) {
                    if ($value === null) return 'NULL';
                    if (is_numeric($value)) return $value;
                    if (is_bool($value)) return $value ? 1 : 0;
                    return "'" . addslashes($value) . "'";
                }, array_values($record));
                
                fwrite($handle, "(" . implode(",", $values) . ")");
                fwrite($handle, ($i < count($records)-1 ? ",\n" : ";\n\n"));
            }
        }
    }
    
    fclose($handle);
    
    // Create download response
    $filename = 'export_' . ($user_id ? 'user_'.$user_id : 'business_'.$business_id) . '.sql';
    
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
}
