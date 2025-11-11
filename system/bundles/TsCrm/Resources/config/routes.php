<?php

use Core\Facade\Routing\Route;

Route::group(['prefix' => 'admin/ts/crm/', 'namespace' => '\TsCrm\Controller'], function() {
	
	Route::get('pipeline', 'PipelineController@main')->name('ts_crm_pipeline');

	Route::get('resources/{sFile}', 'ResourceController@printFile')->name('ts_crm_resource')->where(['sFile' => '.+?']);

});

