<?php

use Core\Facade\Routing\Route;

Route::group(['namespace' => '\TsScreen\Controller'], function() {
	
	Route::get('/screens/{sKey}', 'ScreenController@show')->name('ts_screens_show');
	Route::get('/screens/{sKey}/update', 'ScreenController@update')->name('ts_screens_update');

});

