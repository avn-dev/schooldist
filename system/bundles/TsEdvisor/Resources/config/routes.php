<?php

use Illuminate\Support\Facades\Route;
use Core\Middleware\Frontend;
use TsEdvisor\Controller\WebhookController;

Route::post('api/1.0/ts/edvisor/webhook', WebhookController::class)
	->middleware(Frontend::class)
	->name('webhook');
