<?php

namespace TsGel\Hook;

use TsGel\Api;
use TsGel\Handler\ExternalApp;

class TuitionAttendanceSaveHook extends \Core\Service\Hook\AbstractHook
{
	public function run(\Ext_Thebing_Tuition_Attendance $attendance)
	{
		if (!ExternalApp::isEnabled()) {
			return;
		}

		Api::default()->sendBooking($attendance->getInquiry(), \Util::isDebugIP());
	}
}