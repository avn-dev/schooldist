<?php

use Core\Facade\Routing\Route;

Route::group(['namespace' => '\TsRdstation\Controller'], function() {
	
	Route::post('/admin/apps/rdstation/setup', 'SetupController@forward')->name('ts_rdstation_forward');
	Route::get('/admin/apps/rdstation/callback', 'SetupController@callback')->name('ts_rdstation_callback');

});
