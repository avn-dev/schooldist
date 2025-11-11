<?php
use Illuminate\Support\Facades\Route;

Route::any('/assets/adminlte/{sType}/{sFile}', [AdminLte\Controller\ResourceController::class, 'outputAdminLTEResource']) ->name('adminlte_resources') ->where(['sFile' => '.+?']);