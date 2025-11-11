<?php

namespace TsCrm\Gui2\Data;

class Pipeline extends \Ext_Thebing_Gui2_Data {
	
	public static function createGui() {

		$oGui = new \Ext_Thebing_Gui2_Communication('ts_activities_planning', '\TsActivities\Gui2\Data\BlockData');
		$oGui->gui_description = \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH;
		$oGui->setWDBasic('\TsActivities\Entity\Activity\Block');
		$oGui->class_js = 'ActivityGui';

		$oGui->addCss('/assets/ts-tuition/css/progress_report.css', null);

		$aOptionalData = [
			'js' => [
				'/admin/extensions/thebing/js/communication.js',
				'/admin/extensions/tc/js/communication_gui.js',
				\Core\Helper\Routing::generateUrl('activities_resources', ['sFile' => 'js/ActivityGui.js'])
			]
		];

		$oGui->addOptionalData($aOptionalData);

		return $oGui;
	}
}
