<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/communication/')
	->group(function () {

		Route::group([
			'middleware' => [
				\Core\Middleware\Backend::class,
				\Core\Middleware\Auth\BackendAuth::class,
				//\Core\Middleware\Auth\AccessRight::class . ':ts_wizard_setup|show',
			]
		], function () {
			// Setup-Wizard
			Route::group([
				'prefix' => 'email_accounts/wizard',
				'as' => 'email_accounts.wizard.'
			], function () {
				\Tc\Service\Wizard::routes(\Communication\Handler\Wizards\EmailAccounts\WizardMiddleware::class, ['continue']);
			});
		});

	});