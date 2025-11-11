<?php

use Illuminate\Support\Facades\Route;

Route::group([
	'prefix' => '/api/1.0',
	'as' => 'api.',
	'middleware' => [\Api\Middleware\ErrorHandling::class]
], function () {

	Route::middleware([\Core\Middleware\Backend::class, \Core\Middleware\Auth\BackendAuth::class])
		->group(function () {
			Route::get('/oauth2/verify/{provider}', [\Api\Controller\OAuth2Controller::class, 'verify'])->name('oauth2.verify');
			Route::get('/oauth2/host/redirect', [\Api\Controller\OAuth2Controller::class, 'redirectHost'])->name('oauth2.host.redirect');
		});

});
