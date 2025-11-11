<?php

namespace Tc\Gui2\Format;

class Weekday extends \Ext_TC_Placeholder_Format_Abstract
{
	public function format($value, &$column = null, &$resultData = null)
	{
		if (is_numeric($value)) {
			$value = \Ext_TC_Util::convertWeekdayToString($value);
		}

		$language = (!empty($this->_sLanguage)) ? $this->_sLanguage : \System::getInterfaceLanguage();

		$weekdays = \Ext_TC_Util::getWeekdaySelectOptions($language);

		return $weekdays[$value] ?? $value;
	}

}