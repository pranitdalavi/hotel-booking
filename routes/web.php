<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::get('/', [SearchController::class, 'index']);
Route::get('/search-ui', [SearchController::class, 'index']);
