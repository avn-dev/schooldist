<?php

use Illuminate\Support\Facades\Route;

Route::post('api/1.0/ts/learncube/verify', [TsLearncube\Controller\VerificationController::class, 'verify'])
	->name('api.learncube.verify');
