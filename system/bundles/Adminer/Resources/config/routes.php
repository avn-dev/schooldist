<?php

use Core\Facade\Routing\Route;

Route::match(['GET', 'POST'], '/admin/adminer', [\Adminer\Controller\AdminerController::class, 'view'])->name('admin_adminer');
