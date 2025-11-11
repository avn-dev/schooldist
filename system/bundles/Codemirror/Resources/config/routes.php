<?php

use Illuminate\Support\Facades\Route;

Route::get('/codemirror/resource/{sFile}', [Codemirror\Controller\ResourceController::class, 'printFile'])->name('codemirror_resources')->where(['sFile' => '.+?']);