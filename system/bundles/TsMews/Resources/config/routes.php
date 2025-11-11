<?php

use Core\Facade\Routing\Route;
use TsMews\Controller\WebHookController;

Route::post('api/1.0/ts/mews/webhook/action', [WebHookController::class, 'action'])
	->name('api.mews.webhook');
