<?php

namespace TsGel\Hook;

use TsGel\Api;
use TsGel\Handler\ExternalApp;

class TuitionBlockSaveHook extends \Core\Service\Hook\AbstractHook {

	private static array $cache = [];

	public function run(\Ext_Thebing_School_Tuition_Block $block) {

		if (!ExternalApp::isEnabled()) {
			return;
		}

		$inquiries = $this->getInquiriesOfTuitionBlock($block);

		foreach ($inquiries as $inquiry) {

			if (isset(self::$cache[$inquiry->id])) {
				// Wurde im selben Request bereits bearbeitet
				continue;
			}

			self::$cache[$inquiry->id] = true;

			Api::default()->sendBooking($inquiry, \Util::isDebugIP());
		}
	}

	private function getInquiriesOfTuitionBlock(\Ext_Thebing_School_Tuition_Block $block): array {

		$allocations = $block->getAllocations();

		$inquiries = [];
		foreach ($allocations as $allocation) {
			$inquiry = $allocation->getJourneyCourse()?->getJourney()?->getInquiry();
			if ($inquiry) {
				$inquiries[] = $inquiry;
			}
		}

		return $inquiries;
	}

}
