<?php

use Illuminate\Support\Facades\Route;
use TsTuition\Controller;

Route::get('assets/ts-tuition/{sFile}', [Controller\ResourceController::class, 'printFile'])
	->name('assets')
	->where(['sFile' => '.+?']);

Route::get('ts/tuition/gui2/page/placementtests', [Controller\PlacementtestController::class, 'placementtests'])
	->name('placementtests');

Route::group(['prefix' => '/admin/'], function() {

	Route::get('ts/teacher-overview', [Controller\TeacherOverview\PageController::class, 'getTeacherOverview'])
		->name('ts_tuition_teacher_overview');

	Route::post('ts/teacher-overview/ajax/{sWeek}/{schoolId?}', [Controller\TeacherOverview\PageController::class, 'getWeekBlocks'])
		->name('ts_tuition_teacher_overview_ajax');

	Route::get('ts/teacher-overview/{sFile}', [Controller\TeacherOverview\ResourceController::class, 'printFile'])
		->name('ts_tuition_teacher_overview_resources')
		->where('sFile', '.+');

	Route::get('ts-tuition/classrooms/tags', [Controller\Classrooms\TagController::class, 'getTags'])
		->name('ts_tuition_classrooms_tags');

	Route::group(['prefix' => 'ts-tuition/own-overview/', 'middleware' => [\TsTuition\Middleware\OwnOverviewMonitoring::class]], function() {

		Route::get('export', [Controller\OwnOverviewController::class, 'getExport'])
		->name('ts_tuition_own_overview_export');

		Route::post('load-table', [Controller\OwnOverviewController::class, 'loadTable'])
		->name('ts_tuition_own_overview_loadtable');

	});
});

Route::group([
	'prefix' => 'api/1.0/ts/halloai',
	'as' => 'ts_hallo_ai.',
	'middleware' => [\Api\Middleware\ErrorHandling::class, \Core\Middleware\Frontend::class]
], function () {
	Route::post('/webhooks/assessment', [TsTuition\Controller\HalloAi\ApiController::class, 'assessmentWebhook'])->name('assessmentWebHook');

	Route::group([
		'prefix' => 'assessment',
		'as' => 'assessment.',
		'middleware' => [\TcApi\Middleware\Auth::class.':ts_api_halloai']
	], function () {
		Route::get('/getAssessmentUrl/{inquiryId}/{courselanguageId}', [TsTuition\Controller\HalloAi\ApiController::class, 'getAssessmentUrl'])->name('getAssessmentUrl');
	});
});