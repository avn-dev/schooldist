<?php

namespace TsGel\Hook;

use TsGel\Api;
use TsGel\Handler\ExternalApp;

class ClassAssignmentSaveHook extends \Core\Service\Hook\AbstractHook
{
	public function run(\Ext_Thebing_School_Tuition_Allocation $allocation)
	{
		if (!ExternalApp::isEnabled()) {
			return;
		}

		$inquiry = $allocation->getJourneyCourse()->getJourney()->getInquiry();

		Api::default()->sendBooking($inquiry, \Util::isDebugIP());
	}
}
