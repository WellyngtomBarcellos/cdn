<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ReceiveVideo,
};
Route::post('/video/receipt', [ReceiveVideo::class, 'handle']);

Route::get('/', function () {return view('welcome');});

Route::get('/api/developers', function () {
    $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
    $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2); 

    return response()->json([
        'connection' => 'success',
        'memory' => [
            'current_usage_mb' => $memoryUsage,
            'peak_usage_mb' => $memoryPeak,
        ],
        'cpu' => [
            'status' => 'not available',
        ],
    ],200);
});