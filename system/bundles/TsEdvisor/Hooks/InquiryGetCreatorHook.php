<?php

namespace TsEdvisor\Hooks;

use Core\Service\Hook\AbstractHook;

class InquiryGetCreatorHook extends AbstractHook
{
	public function run(\Ext_TS_Inquiry $inquiry, int &$creator)
	{
		if (!\TcExternalApps\Service\AppService::hasApp(\TsEdvisor\Handler\ExternalApp::APP_NAME)) {
			return;
		}

		if ($inquiry->getMeta('edvisor_id')) {
			$creator = -2;
		}
	}
}
