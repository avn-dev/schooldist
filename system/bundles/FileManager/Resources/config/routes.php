<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/assets/'], function () {

	Route::get('dropzone/{sFile}', [FileManager\Controller\ResourceController::class, 'outputDropzoneResource'])
		->name('dropzone_resources')
		->where('sFile','.+');

	Route::get('filemanager/{sType}/{sFile}', [FileManager\Controller\ResourceController::class, 'outputFilemanagerResource'])
		->name('filemanager_resources')
		->where('sFile','.+');

});