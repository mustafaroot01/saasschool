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

        // Read the last 500 lines using shell command for efficiency on large files
        $output = shell_exec('tail -n 500 ' . escapeshellarg($logFile));
        
        return response()->json([
            'logs' => $output ?? ''
        ]);
    }
}
