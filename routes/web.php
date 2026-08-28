<?php

use Egarrido\NmsDevPanel\Http\Controllers\ClearCookiesController;
use Egarrido\NmsDevPanel\Http\Controllers\GenerateEmailController;
use Egarrido\NmsDevPanel\Http\Controllers\ReconfigurePaymentsController;
use Egarrido\NmsDevPanel\Http\Controllers\ReplaceDatabaseController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('nms-dev-panel.route_prefix'))
    ->middleware('web')
    ->name('nms-dev-panel.')
    ->group(function (): void {
        Route::post('/email', GenerateEmailController::class)->name('email');
        Route::post('/payments', ReconfigurePaymentsController::class)->name('payments.reconfigure');
        Route::post('/database', ReplaceDatabaseController::class)->name('database.replace');
        Route::post('/cookies', ClearCookiesController::class)->name('cookies.clear');
    });
