<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'system/extensions'], function() {

	Route::any('/tc_api.php', '\TcFrontend\Controller\ApiController@handleLegacyApi')
		->name('tc_frontend_api_legacy');

	Route::group(['prefix' => 'tc', 'namespace' => '\Tc\Controller'], function() {

		Route::any('/system/update/database.php', 'UpdateController@database')
			->name('tc_update_database');

		Route::post('/system/cronjob/request/request.php', 'CronjobController@request')
			->name('tc_cronjob_request_legacy');

		Route::post('/uploader/request.php', 'UploaderController@request')
			->name('tc_uploader_request_legacy');

	});

});

Route::any('/zendesk/sso', '\Tc\Controller\ZendeskController@sso')->name('tc_zendesk');

Route::get('/wishlist', '\Tc\Controller\WishlistController@sso')->name('tc_wishlist');

Route::get('/assets/tc/{sFile}', [\Tc\Controller\ResourceController::class, 'printFile'])
	->where(['sFile' => '.+?'])
	->name('resources');

Route::get('/assets/tc-statistic/{sFile}', '\TcStatistic\Controller\ResourceController@printFile')
	->name('tc_statistic_resources')
	->where('sFile', '.+?');

Route::get('/assets/tc-statistic/assets/{sFile}', '\TcStatistic\Controller\ResourceController@outputAssetsResource')
	->name('tc_statistic_assets')
	->where('sFile', '.+?');

