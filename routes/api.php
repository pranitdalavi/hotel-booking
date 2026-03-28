<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SearchController;

Route::get('/test', function () {
    return response()->json(['message' => 'API working']);
});

Route::get('/search', [SearchController::class, 'search']);
Route::post('/book', [BookingController::class, 'book']);
Route::post('/pay', [BookingController::class, 'pay']);