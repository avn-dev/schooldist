<?php

namespace Ts\Gui2\Format;

class LatestLevelChange extends \Ext_Gui2_View_Format_Abstract
{
	
	protected $internalLevels = [];
	
	public function __construct() {
		$this->internalLevels = \Ext_Thebing_Tuition_Level::getList('internal');
	}

	public function format($value, &$column = null, &$resultData = null) {

		if(empty($resultData['latest_level_change_level_id'])) {
			return '';
		}
		
		$courseUntil = new \Carbon\Carbon($resultData['until']);
		$now = new \Carbon\Carbon();
		
		if($courseUntil > $now) {
			$courseUntil = $now;
		}
		
		$latestLevelChange = new \Carbon\Carbon($resultData['latest_level_change_week']);

		return sprintf($this->oGui->t('%1$s Wochen Level %2$s'), ceil($courseUntil->floatDiffInWeeks($latestLevelChange)), $this->internalLevels[$resultData['latest_level_change_level_id']]);
	}
	
}