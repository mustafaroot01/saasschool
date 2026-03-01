<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return response()->json([
        'status' => 'running',
        'app'    => config('app.name'),
        'tenancy' => 'central'
    ]);
});

// Fail-safe for Laravel's default redirection
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
