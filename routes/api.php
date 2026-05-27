<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookLodgifyController;
use App\Http\Middleware\VerifyLodgifySignature;

Route::post('/webhook/lodgify', [WebhookLodgifyController::class, 'handle']);

Route::get('/setup-lodgify-webhooks', [WebhookLodgifyController::class, 'registerWebhooks']);

Route::post('/webhook/lodgify', [WebhookLodgifyController::class, 'handle'])
     ->middleware(VerifyLodgifySignature::class);