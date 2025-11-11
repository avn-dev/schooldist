<?php

use Illuminate\Support\Facades\Route;

/*
 * @todo Routen prüfen
 */

Route::group(['prefix' => '/system/extensions/office/'], function() {

	Route::any('cronjob.daily.php', [Office\Controller\CronjobController::class, 'request'])
		->name('office_cronjob');

	Route::any('office.api.php', [Office\Controller\ApiController::class, 'request'])
		->name('office_api');
});

Route::group(['prefix' => '/admin/office/'], function() {

	Route::any('payment/{paymentId}/delete', [Office\Controller\DocumentController::class, 'deletePayment'])
		->name('office_payment_delete');

	Route::any('document/{documentId}/{documentPath}', [Office\Controller\DocumentController::class, 'openPdf'])
		->name('office_open_pdf');
});