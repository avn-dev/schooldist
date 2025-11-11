<?php

class Checks_DashboardUpdate extends GlobalChecks
{
	public function getTitle()
	{
		return 'Dashboard';
	}

	public function getDescription()
	{
		return 'Updates dashboard widgets';
	}

	public function executeCheck()
	{
		(new Core\Helper\Cache())->clearDashboard();
		return true;
	}
}
