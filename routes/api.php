<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/registro', [UserController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/mapa/busca', [MapController::class, 'busca_por_nome']);
Route::get('/mapa/busca_reversa', [MapController::class, 'busca_reversa']);


Route::middleware([AuthMiddleware::class])->group(function () {
    Route::patch('users/atualizar_endereco', [UserController::class, 'atualizar_endereco']);
    Route::get('users/clima_atual', [UserController::class, 'clima_atual']);
});
