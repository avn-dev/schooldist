<?php

use TsCompany\Controller\Gui2Controller;
use Core\Facade\Routing\Route;

Route::group([
	'prefix' => 'ts/companies',
	'as' => 'companies.'
], function() {

	Route::group([
		'prefix' => 'gui2/page', // siehe Ext_TC_Gui2::__construct(), hier wird immer /page benötigt
		'as' => 'gui2.'
	], function() {
		Route::get('agencies', [Gui2Controller::class, 'agencies'])->name('agencies');
		Route::get('companies', [Gui2Controller::class, 'companies'])->name('companies');
		Route::get('industries', [Gui2Controller::class, 'industries'])->name('industries');
		Route::get('job_opportunities', [Gui2Controller::class, 'jobOpportunities'])->name('job_opportunities');
		//Route::get('job_requirements', [Gui2Controller::class, 'jobRequirements'])->name('job_requirements');
	});

});

Route::group([
	'prefix' => 'ts/co-op',
	'as' => 'co_op.'
], function() {

	Route::group([
		'prefix' => 'gui2/page', // siehe Ext_TC_Gui2::__construct(), hier wird immer /page benötigt
		'as' => 'gui2.'
	], function() {
		Route::get('students', [Gui2Controller::class, 'journeyEmployments'])->name('students');
	});

});
