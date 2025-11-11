<?php

namespace TsEdvisor\Hooks;

use Core\Service\Hook\AbstractHook;
use Illuminate\Support\Collection;

class InquiryGetCreatorOptionsHook extends AbstractHook
{
	public function run(Collection $users)
	{
		if (!\TcExternalApps\Service\AppService::hasApp(\TsEdvisor\Handler\ExternalApp::APP_NAME)) {
			return;
		}

		$users->put(-2, 'Edvisor');
	}
}
