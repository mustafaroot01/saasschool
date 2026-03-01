<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        // Read last 500 lines safely
        $output = shell_exec('tail -n 500 ' . escapeshellarg($logFile));
        $lines = explode("\n", $output ?? '');

        // Parse log lines: only return the header line (not stack traces)
        $entries = [];
        foreach ($lines as $line) {
            // Match log entry header: [timestamp] env.LEVEL: message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)$/', trim($line), $matches)) {
                $entries[] = [
                    'time'    => $matches[1],
                    'level'   => $matches[2],
                    'message' => mb_strimwidth($matches[3], 0, 300, '...'),
                ];
            }
        }

        // Return most recent first
        return response()->json([
            'logs' => array_reverse(array_slice($entries, -100))
        ]);
    }

}
