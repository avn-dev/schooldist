<?php

use Illuminate\Support\Facades\Route;

Route::post('/api/1.0/ts/bitrix/{sPath}', [TsBitrix\Controller\RequestController::class, 'call']) ->name('ts_api_bitrix_all') ->where(['sFile' => '.+?']);
