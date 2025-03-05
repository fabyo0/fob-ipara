<?php

use Botble\Ipara\Http\Controllers\IparaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['core'])->group(function () {
    Route::post('payment/ipara/webhook', [IparaController::class, 'webhook'])
        ->name('payments.ipara.webhook');

    Route::any('payment/ipara/callback', [IparaController::class, 'callback'])
        ->name('payments.ipara.callback');

    Route::post('payment/ipara/process', [IparaController::class, 'process'])
        ->name('payments.ipara.process');
});
