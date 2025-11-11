<?php

use Illuminate\Support\Facades\Route;

/*
 * @todo Route prüfen
 */
Route::any('/assets/notices/{sType}/{sFile}', [Notices\Http\Controller\ResourceController::class, 'outputNoticesResource'])
	->name('notices_resources')
	->where('sFile', '.+');

Route::any('/notices/interface/view', [Notices\Http\Controller\InterfaceController::class, 'ViewAction'])
	->name('notices_view');

Route::get('/notices/delete/{sClass}/{iId}', [Notices\Http\Controller\InterfaceController::class, 'delete'])
	->name('notices_delete');

Route::post('/notices/save/{sClass}/{iId}', [Notices\Http\Controller\InterfaceController::class, 'save'])
	->name('notices_save');