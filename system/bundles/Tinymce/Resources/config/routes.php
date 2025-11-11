<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/tinymce/resource/'], function() {

	/*
 	* @todo Route prüfen
 	*/
	Route::any('basic/langs/{sFile}', [Tinymce\Controller\ResourceLangController::class, 'printFile'])
		->name('tinymce_languages')
		->where('sFile','.+');

	/*
 	* @todo Route prüfen
 	*/
	Route::match(['GET', 'POST'], 'filemanager/{sFile}', [Tinymce\Controller\ResourceFilemanagerController::class, 'printFile'])
		->name('tinymce_filemanager')
		->where('sFile','.+');

	Route::get('basic/{sFile}', [Tinymce\Controller\ResourceController::class, 'printFile'])
		->name('tinymce_resources')
		->where('sFile','.+');
});
