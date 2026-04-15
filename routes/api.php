<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\CargaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExecucaoController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\ParametroController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

// Autenticação (públicas)
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Rotas do worker (token próprio)
Route::middleware('worker.token')->prefix('worker')->group(function () {
    Route::get('bot', [WorkerController::class, 'bot']);
    Route::post('cargas', [WorkerController::class, 'storeCarga']);
    Route::post('execucoes', [WorkerController::class, 'storeExecucao']);
    Route::patch('execucoes/{execucao}', [WorkerController::class, 'updateExecucao']);
    Route::post('logs', [WorkerController::class, 'storeLog']);
});

// Painel (autenticado)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::apiResource('bots', BotController::class);
    Route::post('bots/{bot}/iniciar', [BotController::class, 'iniciar']);
    Route::post('bots/{bot}/parar', [BotController::class, 'parar']);
    Route::post('bots/{bot}/reiniciar', [BotController::class, 'reiniciar']);

    Route::apiResource('bots.parametros', ParametroController::class)->shallow();

    Route::get('cargas/analisadas', [CargaController::class, 'analisadas']);
    Route::get('cargas/capturadas', [CargaController::class, 'capturadas']);
    Route::get('cargas/simuladas', [CargaController::class, 'simuladas']);
    Route::get('cargas/aceitas', [CargaController::class, 'aceitas']);
    Route::get('cargas', [CargaController::class, 'index']);

    Route::get('execucoes', [ExecucaoController::class, 'index']);

    Route::get('logs', [LogController::class, 'index']);

    Route::get('relatorio/resumo', [RelatorioController::class, 'resumo']);
});
