<?php

use Core\Facade\Routing\Route;

Route::group(['prefix' => 'external-apps', 'namespace' => '\TcExternalApps\Controller'], function() {
	
	Route::get('/', 'ListController@indexAction')->name('list');
		
	Route::post('/load/apps', 'ListController@loadAction')->name('loading');
	
	Route::get('resources/{sFile}', 'ResourceController@printFile')->name('resource')->where(['sFile' => '.+?']);
	
	Route::group(['prefix' => 'app'], function() {
		Route::any('/edit/{sAppKey}', 'ListController@editAction')->name('edit');
		Route::post('/save/{sAppKey}', 'ListController@saveAction')->name('save');
		Route::post('/delete', 'ListController@deleteAction')->name('delete');
		Route::post('/install', 'ListController@installAction')->name('install');
	});
	
});


