<?php

use Illuminate\Support\Facades\Route;

Route::any('/ts-salesforce/webhook/{sObject}', [TsSalesForce\Controller\WebhookController::class, 'call'])->name('ts_salesforce_webhook');