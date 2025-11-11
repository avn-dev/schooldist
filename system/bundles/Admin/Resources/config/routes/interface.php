<?php

use Admin\Http\Controller;
use Admin\Http\Middleware;
use Illuminate\Support\Facades\Route;

// Alte URLs
Route::get('system.html', [Controller\AdminController::class, 'legacyRedirect'])->name('legacy.alias_1');
Route::get('index.html', [Controller\AdminController::class, 'legacyRedirect'])->name('legacy.alias_2');

Route::get('storage/manager', [Controller\Storage\ManagerController::class, 'page'])->name('storage');
Route::any('modules', [Controller\ModulesController::class, 'overview'])->name('modules');
Route::get('credits', [Controller\SoftwareController::class, 'credits'])->name('credits');
Route::get('phpinfo', [Controller\SoftwareController::class, 'phpinfo'])
	->middleware([\Core\Middleware\Auth\FideloUser::class])
	->name('phpinfo');

Route::prefix('interface')
	->name('interface.')
	->group(function () {
		Route::post('tenant/switch', [Controller\AdminController::class, 'switchTenant'])->name('tenant.switch');
		Route::any('component/{component_key}/{action}', [Controller\AdminController::class, 'componentAction'])
			->middleware(Middleware\ComponentResolving::class)
			->name('component.action');
		Route::get('bookmarks', [Controller\AdminController::class, 'openBookmarks'])->name('bookmarks');
		Route::get('userboard', [Controller\AdminController::class, 'openUserBoard'])->name('userboard');
		Route::get('support', [Controller\AdminController::class, 'openSupport'])->name('support');
	});

Route::match(['GET', 'POST', 'HEAD'], '/{sFile}', [Controller\ResourceController::class, 'outputLegacyFile'])
	->where('sFile','.+')
	->name('legacy.file');

Route::middleware([Middleware\HandleBackendInertiaRequests::class])
	->group(function () {
		Route::get('/', [Controller\AdminController::class, 'index'])->name('index');
	});
