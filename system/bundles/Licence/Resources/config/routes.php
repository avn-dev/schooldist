<?php

use Core\Facade\Routing\Route;

Route::group(['prefix' => 'licence', 'namespace' => '\Licence\Controller'], function() {
	
	Route::get('billings', 'BillingController@indexAction')->name('billing_list');
	
	Route::get('billing/pdf/{id}', 'BillingController@pdfAction')->name('billing_pdf');
	
});

