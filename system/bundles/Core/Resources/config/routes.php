<?php

# Storage

use Illuminate\Support\Facades\Route;



Route::group(['prefix' => '/storage/'], function () {

	/*
	 * @todo Route prüfen
	 */
	Route::any('public/download/{sFile}', [Core\Controller\PublicStorageController::class, 'downloadStorageFile'])
		->name('core_public_storage_download')
		->where('sFile','.+');

	Route::any('public/{sFile}', [Core\Controller\PublicStorageController::class, 'openStorageFile'])
		->name('core_public_storage')
		->where('sFile','.+');

	Route::any('download/{sFile}', [Core\Controller\StorageController::class, 'downloadStorageFile'])
		->name('core_storage_download')
		->where('sFile','.+');

	Route::any('{sFile}', [Core\Controller\StorageController::class, 'openStorageFile'])
		->name('core_storage')
		->where('sFile','.+');

});

Route::any('/secure.php', [Core\Controller\StorageController::class, 'secure'])
	->name('core_secure_php');

Route::get('/assets/core/{sFile}', [Core\Controller\ResourceController::class, 'printFile'])
	->name('assets')
	->where('sFile','.*');
