<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\UpdateLog;

class CheckForUpdate extends Command
{
    protected $signature = 'check:update';
    protected $description = 'Check remote server for available update';

    public function handle()
    {
        // Step 1: Check internet
        try {
            Http::timeout(3)->get('https://www.google.com');
        } catch (\Exception $e) {
            $this->info("Internet not available");
            return;
        }

        // Step 2: Call remote API
        $res = Http::get('https://your-api.com/check-version');

        if ($res->ok() && $res->json('update') === true) {
            UpdateLog::create([
                'status' => 'pending',
                'update_available' => true,
                'message' => 'Update available',
            ]);
            $this->info("Update available — log created");
        } else {
            $this->info("No update available");
        }
    }
}
