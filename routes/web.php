<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorageController;

Route::get('/', function () {
    return view('welcome');
});

// Storage files route with CORS support
Route::get('/storage/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->middleware(\App\Http\Middleware\StorageCors::class)
    ->name('storage.serve');

