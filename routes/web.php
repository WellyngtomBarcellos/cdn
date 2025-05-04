<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ReceiveVideo,
};
Route::post('/video/receipt', [ReceiveVideo::class, 'handle']);
Route::get('/', function () {return view('welcome');});
