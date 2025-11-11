<?php

use Illuminate\Support\Facades\Route;

Route::get('gui2/page/{sName}/{sSet?}', [Ext_Gui2_Page_Controller::class, 'createGui'])
	->name('page');

Route::any('gui2/request', [Gui2\Controller\RequestController::class, 'handle'])
	->name('request');

Route::get('assets/gui/{sFile}', [Gui2\Controller\ResourceController::class, 'printFile'])
	->name('assets')
	->where(['sFile' => '.+?']);

Route::any('api/{version}/gui2/{hash}/search', [Gui2\Controller\ApiController::class, 'request'])
	->middleware(\Api\Middleware\ErrorHandling::class)
	->name('api_search');
