<?php

use Core\Facade\Routing\Route;

if(\TcExternalApps\Service\AppService::hasApp(\Sso\Service\SsoApp::APP_NAME)) {

	Route::get('admin/sso', [\Sso\Controller\AdminController::class, 'redirectToLogin'])
		->name('redirect');

	Route::any('admin/sso/login', [\Sso\Controller\AdminController::class, 'login'])
		->name('admin_login');

	Route::any('admin/sso/acs', [\Sso\Controller\AdminController::class, 'acs'])
		->name('acs');

	Route::any('admin/sso/metadata', [\Sso\Controller\AdminController::class, 'metadata'])
		->name('metadata');

	Route::any('admin/sso/logout', [\Sso\Controller\AdminController::class, 'logout'])
		->name('logout');

}