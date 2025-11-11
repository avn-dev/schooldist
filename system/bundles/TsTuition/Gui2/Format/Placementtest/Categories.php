<?php

namespace TsTuition\Gui2\Format\Placementtest;

class Categories extends \Ext_Gui2_View_Format_Selection {

	static array $options = [];

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{

		if(empty(self::$options)) {
			self::$options = \Ext_Thebing_Placementtests_Question_Category::getSelectOptions();
		}

		$this->aSelectOptions = self::$options;

		return parent::format($mValue, $oColumn, $aResultData);
	}

}