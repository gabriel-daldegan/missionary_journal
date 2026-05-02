<?php

use App\Http\Controllers\PaymentProviders\CreemController;
use App\Http\Controllers\PaymentProviders\LemonSqueezyController;
use App\Http\Controllers\PaymentProviders\PaddleController;
use App\Http\Controllers\PaymentProviders\PolarController;
use App\Http\Controllers\PaymentProviders\StripeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/payments-providers/stripe/webhook', [
    StripeController::class,
    'handleWebhook',
])->name('payments-providers.stripe.webhook');

Route::post('/payments-providers/paddle/webhook', [
    PaddleController::class,
    'handleWebhook',
])->name('payments-providers.paddle.webhook');

Route::post('/payments-providers/lemon-squeezy/webhook', [
    LemonSqueezyController::class,
    'handleWebhook',
])->name('payments-providers.lemon-squeezy.webhook');

Route::post('/payments-providers/creem/webhook', [
    CreemController::class,
    'handleWebhook',
])->name('payments-providers.creem.webhook');

Route::post('/payments-providers/polar/webhook', [
    PolarController::class,
    'handleWebhook',
])->name('payments-providers.polar.webhook');
