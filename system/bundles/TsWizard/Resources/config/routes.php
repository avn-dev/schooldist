<?php

use Illuminate\Support\Facades\Route;

Route::group([
	'middleware' => [
		\Core\Middleware\Backend::class,
		\Core\Middleware\Auth\BackendAuth::class,
		\Core\Middleware\Auth\AccessRight::class.':ts_wizard_setup|show',
	]
], function () {
	// Setup-Wizard
	Route::group([
		'prefix' => 'admin/wizard',
		'as' => 'setup.'
	], function () {
		Route::get('help/import/users', [\TsWizard\Controller\HelpController::class, 'userImportFile'])->name('help.user_import.file');
		\Tc\Service\Wizard::routes(\TsWizard\Handler\Setup\WizardMiddleware::class);
	});
});