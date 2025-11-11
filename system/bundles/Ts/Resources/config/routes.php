<?php

use Illuminate\Support\Facades\Route;


Route::get('/assets/ts/backend/{sFile}', [\Ts\Controller\ResourceController::class, 'outputBackendResource'])
	->where(['sFile' => '.+'])
	->name('assets.backend');

Route::get('/assets/ts/{sFile}', [Ts\Controller\ResourceController::class, 'printFile'])
	->name('assets')
	->where(['sFile' => '.+?']);

Route::get('/assets-public/ts/{sFile}', [\Ts\Controller\PublicResourceController::class, 'outputResource'])
	->where(['sFile' => '.+'])
	->name('public_resources');

Route::get('/assets/typeahead/{sFile}', [\Ts\Controller\PublicResourceController::class, 'outputTypeAheadResource'])
	->where(['sFile' => '.+'])
	->name('typeahead_resources');

Route::get('/ts/salesperson/overview/{sType}', [\Ts\Controller\SalespersonController::class, 'getOverview'])
	->name('ts_salesperson_overview');

Route::any('/system/extensions/kolumbus_placementtest.php', [\Ts\Controller\ApiController::class, 'handleLegacyPlacementtest'])
	->name('ts_placementtest_legacy');

Route::get('/ts/api/visa-qr-code/{uniqueKey}', [\Ts\Controller\VisaApiController::class, 'handleVisaQrCode'])
	->name('ts_visa_qr_code');
