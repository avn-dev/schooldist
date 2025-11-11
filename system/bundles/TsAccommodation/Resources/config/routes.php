<?php

use TsAccommodation\Controller;
use Illuminate\Support\Facades\Route;
use Core\Middleware\Auth\BackendAuth;
use Core\Middleware\Backend;

Route::get('assets/ts-accommodation/{sFile}', [Controller\ResourceController::class, 'outputBackendResource'])
	->name('assets')
	->where(['sFile' => '.+?']);

Route::group(['prefix' => 'admin/ts/accommodation/', 'namespace' => '\TsAccommodation\Controller'], function() {
	
	Route::get('mealplan', 'MealPlanController@pageAction')->name('meal_plan');

	Route::get('resources/{sFile}', 'ResourceController@printFile')->name('resource')->where(['sFile' => '.+?']);

	Route::get('mealplan/export', 'MealPlanController@export')->name('meal_plan_export');
	
});
	

Route::group([
	'prefix' => 'admin/ts/accommodation/availability', 
	'namespace' => '\TsAccommodation\Controller', 
	'middleware' => [Backend::class, BackendAuth::class.':thebing_accommodation_availability']], function() {

		Route::get('', 'AvailabilityController@overview')->name('availability');
		Route::post('/results', 'AvailabilityController@results')->name('availability_results');
		Route::get('/export', 'AvailabilityController@export')->name('availability_export');

	}
);
