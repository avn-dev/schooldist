<?php

# TODO Alle Routen mit save müssen entfernt werden, da ein erneutes Aufrufen keine funktionierende Seite mehr erzeugt (reines POST)  -> yml

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/teacher'], function() {

    Route::any('/login',
        [TsTeacherLogin\Controller\InterfaceController::class, 'login'])
        ->name('teacher_login');
	Route::any('',
		[TsTeacherLogin\Controller\InterfaceController::class, 'teacher'])
		->name('teacher');
    Route::any('/logout',
        [TsTeacherLogin\Controller\InterfaceController::class, 'logout'])
        ->name('teacher_logout');
    Route::get('/forgot-password',
        [TsTeacherLogin\Controller\PasswordController::class, 'getForgotPasswordView'])
        ->name('teacher_forgot_password');
    Route::post('/password-reset/send',
        [TsTeacherLogin\Controller\PasswordController::class, 'postResetPassword'])
        ->name('teacher_reset_password_send');

	/*
	 * @todo Route prüfen
	 */
    Route::post('/password-reset/{sToken}/save',
        [TsTeacherLogin\Controller\PasswordController::class, 'postResetPasswordSave'])
        ->name('teacher_reset_password_save');

	/*
	 * @todo Route prüfen
	 */
    Route::get('/password-reset/{sToken}',
        [TsTeacherLogin\Controller\PasswordController::class, 'getResetPasswordView'])
        ->name('teacher_reset_password_link');

	/*
	 * @todo Route prüfen
	 */
    Route::any('/change-language/{sLanguage}',
        [TsTeacherLogin\Controller\AbstractController::class, 'changeLanguage'])
        ->name('teacher_change_language');
    Route::get('/data',
        [TsTeacherLogin\Controller\TeacherDataController::class, 'getTeacherDataView'])
        ->name('teacher_data');
    Route::post('/data/save',
        [TsTeacherLogin\Controller\TeacherDataController::class, 'saveTeacherData'])
        ->name('teacher_data_save');
    Route::post('/password/save',
        [TsTeacherLogin\Controller\TeacherDataController::class, 'savePassword'])
        ->name('teacher_password_save');
    Route::get('/timetable',
        [TsTeacherLogin\Controller\TimetableController::class, 'getTimetableView'])
        ->name('teacher_timetable');
    Route::get('/timetable/json',
        [TsTeacherLogin\Controller\TimetableController::class, 'getTimetableData'])
        ->name('teacher_timetable_json');

	Route::get('/timetable/new',
		[TsTeacherLogin\Controller\TimetableController::class, 'getTimetableNewModalData'])
		->name('teacher_timetable_new_modal');

	Route::post('/timetable/save/{block_id?}',
		[TsTeacherLogin\Controller\TimetableController::class, 'saveTimetableBlock'])
		->name('teacher_timetable_block_save');

	Route::get('/timetable/class/rooms',
		[TsTeacherLogin\Controller\TimetableController::class, 'loadAvailableClassRooms'])
		->name('teacher_timetable_class_rooms');

	Route::get('/timetable/class/students/search',
		[TsTeacherLogin\Controller\TimetableController::class, 'searchAvailableStudentsForClass'])
		->name('teacher_timetable_class_students_search');

	Route::get('/timetable/block/{block_id}/{day}/state',
		[TsTeacherLogin\Controller\TimetableController::class, 'loadBlockStateModalContent'])
		->name('teacher_timetable_block_state');

	Route::post('/timetable/block/{block_id}/{day}/state',
		[TsTeacherLogin\Controller\TimetableController::class, 'saveBlockState'])
		->name('teacher_timetable_block_state_save');

    Route::get('/timetable/{iBlockId}',
        [TsTeacherLogin\Controller\TimetableController::class, 'getTimetableModalData'])
        ->name('teacher_timetable_modal');

	Route::post('/attendance/save',
		[TsTeacherLogin\Controller\AttendanceController::class, 'saveAttendance'])
		->name('teacher_attendance_save');

    Route::match(['GET', 'POST'], '/attendance',
        [TsTeacherLogin\Controller\AttendanceController::class, 'getAttendanceView'])
        ->name('teacher_attendance');

    Route::post('/attendance/add-students',
        [TsTeacherLogin\Controller\AttendanceController::class, 'addStudents'])
        ->name('teacher_attendance_add_students');

    Route::get('/attendance/code',
        [TsTeacherLogin\Controller\AttendanceController::class, 'getAttendanceCodeView'])
        ->name('teacher_attendance_code');

	Route::match(['GET', 'POST'],'/communication',
        [TsTeacherLogin\Controller\CommunicationController::class, 'getCommunicationView'])
        ->name('teacher_communication');

    Route::post('/communication/submit',
        [TsTeacherLogin\Controller\CommunicationController::class, 'submitCommunicationForm'])
        ->name('teacher_communication_submit');

    Route::get('/reportcards/file/{iVersionId}',
        [TsTeacherLogin\Controller\ReportcardsController::class, 'openFile'])
        ->name('teacher_reportcards_file');

    Route::post('/reportcards/email/{iVersionId}/{iStudentId}',
        [TsTeacherLogin\Controller\ReportcardsController::class, 'emailToStudent'])
        ->name('teacher_reportcards_email');

    Route::get('/reportcards',
        [TsTeacherLogin\Controller\ReportcardsController::class, 'getReportcardsView'])
        ->name('teacher_reportcards');

    Route::get('/reportcards/modal/calculate-score/{iInquiryCourseId}/{iProgramServiceId}',
        [TsTeacherLogin\Controller\ReportcardsController::class, 'calculateAverageScore'])
        ->name('teacher_reportcards_modal_calculate_score');

    Route::get('/reportcards/modal/{iTemplateId}/{iInquiryCourseId}/{sExaminationDate}/{iVersionId}/{iExaminationId}/{iProgramServiceId}',
        [TsTeacherLogin\Controller\ReportcardsController::class, 'getReportcardsModal'])
        ->name('teacher_reportcards_modal');

    Route::post('/reportcards/modal',
        [TsTeacherLogin\Controller\ReportcardsController::class, 'saveReportcardsModal'])
        ->name('teacher_reportcards_modal_save');

	/*
	 * @todo Route prüfen
	 */
    Route::any('/resources/duallistbox/{sFile}',
        [TsTeacherLogin\Controller\ResourceDuallistboxController::class, 'printFile'])
        ->name('teacher_resources_duallistbox')
        ->where(['sFile' => '.+?']);

    Route::get('/resources/{sFile}',
        [TsTeacherLogin\Controller\ResourceController::class, 'printFile'])
        ->name('teacher_resources')
        ->where(['sFile' => '.+?']);
    Route::get('/logo',
        [TsTeacherLogin\Controller\StorageResourceController::class, 'getLogo'])
        ->name('teacher_logo');

	/*
	 * @todo Route prüfen
	 */
    Route::get('/storage{sFile}',
        [TsTeacherLogin\Controller\StorageResourceController::class, 'getFile'])
        ->name('teacher_storage')
        ->where(['sFile' => '.+?']);

	/*
	 * @todo Route prüfen
	 */
    Route::any('/{sPath}',
        [TsTeacherLogin\Controller\InterfaceController::class, 'redirectToHttps'])
        ->name('teacher_pages_redirect')
        ->where(['sPath', '.+?']);
});