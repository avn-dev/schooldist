<?php

namespace Core\Tests;

trait DatabaseConnection
{
	public function setupConnection()
	{
		global $db_data;
		require_once __DIR__.'/../../../../config/config.php';
		\DB::getDefaultConnection();
	}
}