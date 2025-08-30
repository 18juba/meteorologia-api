<?php

use App\Http\Controllers\MapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/maps/search', [MapController::class, 'search']);
Route::get('/maps/reverse_search', [MapController::class, 'reverse_search']);
