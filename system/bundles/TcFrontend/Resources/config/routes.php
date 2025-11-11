<?php

use Core\Facade\Routing\Route;
use Core\Middleware\Frontend;
use TcFrontend\Middleware;
use TcFrontend\Controller;

Route::get('tc-frontend/widget', [Controller\WidgetController::class, 'app'])
	->middleware(Frontend::class)
	->name('widget.app');

Route::get('assets/tc-frontend/js/widget.js', [Controller\WidgetController::class, 'js'])
	->middleware(Frontend::class)
	->name('widget.js');

Route::get('assets/tc-frontend/js/wrapper.js', [Controller\WrapperController::class, 'js'])
	->middleware(Frontend::class)
	->name('wrapper.js');

Route::post('assets/tc-frontend/js/wrapper', [Controller\WrapperController::class, 'php'])
	->middleware([Frontend::class, Middleware\CorsHeaders::class])
	->name('wrapper.php');

Route::get('assets/tc-frontend/{sFile}', [Controller\ResourceController::class, 'printFile'])
	->name('assets')
	->where(['sFile' => '.+?']);
