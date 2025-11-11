<?php

namespace TsGel\Hook;

use TsGel\Api;
use TsGel\Handler\ExternalApp;

class InquirySaveHook extends \Core\Service\Hook\AbstractHook
{
	public function run(\Ext_TS_Inquiry $inquiry)
	{
		if (!ExternalApp::isEnabled()) {
			return;
		}

		Api::default()->sendBooking($inquiry, \Util::isDebugIP());
	}
}