<?php

try {
	// Request the default database connection to established the
	// connection automatically.
	DB::getDefaultConnection();
} catch (Exception $e) {
	\Util::handleErrorMessage("The database connection could not be established.", 1, 1, 1);
	exit;
}
