<?php
use Illuminate\Support\Facades\Route;

Route::any('/calendarsheet/request', [CalendarSheet\Controller\RequestController::class, 'handle']) ->name('calendarsheet_request');