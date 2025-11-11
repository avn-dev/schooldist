<?php

use Illuminate\Support\Facades\Route;
use Core\Middleware\Frontend;
use TcFrontend\Middleware;
use TsFrontend\Controller;

// Webhooks
Route::group([
	'prefix' => 'api/1.0/ts/frontend/webhook',
	'middleware' => [Frontend::class],
	'as' => 'api.webhook.'
], function () {
	Route::post('payment/{handler}', [Controller\WebhookController::class, 'payment'])->name('payment');
});

// Payment Form
Route::group([
	'prefix' => 'api/1.0/ts/frontend/payment',
	'middleware' => [Frontend::class, Middleware\CorsHeaders::class, Middleware\CombinationByHeader::class],
	'as' => 'api.payment.'
], function () {
	Route::match(['OPTIONS', 'POST'], 'load', [Controller\PaymentFormController::class, 'load'])->name('load');
	Route::match(['OPTIONS', 'POST'], 'submit', [Controller\PaymentFormController::class, 'submit'])->name('submit');
});

Route::group(['prefix' => 'admin/ts/frontend', 'namespace' => '\TsFrontend\Controller'], function() {
	
	Route::get('course-structure', 'CourseStructureController@page')->name('course_structure_page');

	Route::post('course-structure-save', 'CourseStructureController@save')->name('course_structure_page_save');

	Route::post('course-structure-reset', 'CourseStructureController@reset')->name('course_structure_page_reset');

	Route::get('preview', [Controller\PreviewController::class, 'preview']);

});
