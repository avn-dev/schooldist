<?php
use Illuminate\Support\Facades\Route;

Route::any('/admin/hubspot/activate', [TsHubspot\Controller\AppController::class, 'activateHubspot']) ->name('admin_hubspot_activate');
Route::any('/admin/hubspot/deactivate',[TsHubspot\Controller\AppController::class, 'deactivateHubspot']) ->name('admin_hubspot_deactivate');
Route::any('/admin/hubspot/redirect',[TsHubspot\Controller\AppController::class, 'handleRedirectFromHubspot']) ->name('admin_hubspot_redirect');
