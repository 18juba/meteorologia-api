<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/registro', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/maps/search', [MapController::class, 'search']);
Route::get('/maps/reverse_search', [MapController::class, 'reverse_search']);
