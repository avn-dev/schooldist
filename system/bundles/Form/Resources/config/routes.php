<?php

use Illuminate\Support\Facades\Route;

/*
 * @todo Route prüfen
 */
Route::any('/form/fields/{iFormId}', [Form\Controller\FieldsController::class, 'page'])
	->name('form_fields');