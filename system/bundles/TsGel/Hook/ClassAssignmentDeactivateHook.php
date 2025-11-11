<?php

namespace TsGel\Hook;

use TsGel\Api;
use TsGel\Handler\ExternalApp;

class ClassAssignmentDeactivateHook extends \Core\Service\Hook\AbstractHook
{
	public function run(\Ext_Thebing_School_Tuition_Block $block, array $blockIds, int $inquiryCourseId)
	{
		if (!ExternalApp::isEnabled()) {
			return;
		}

		$inquiry = \Ext_TS_Inquiry_Journey_Course::getInstance($inquiryCourseId)->getJourney()->getInquiry();

		Api::default()->sendBooking($inquiry, \Util::isDebugIP());
	}
}
