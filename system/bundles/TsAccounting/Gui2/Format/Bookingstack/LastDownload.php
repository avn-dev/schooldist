<?php

namespace TsAccounting\Gui2\Format\Bookingstack;

use Carbon\Carbon;

class LastDownload extends \Ext_Gui2_View_Format_Abstract
{
	public function format($value, &$column = null, &$resultData = null)
	{
		if (!empty($value)) {
			[$userId, $timestamp] = explode('|', $value);

			$user = \Factory::getInstance(\User::class, $userId);
			$date = Carbon::createFromTimestamp($timestamp);

			$value = sprintf(
				'%s (%s)',
				(new \Ext_Thebing_Gui2_Format_Date_Time())->format($date, $column, $resultData),
				$user->getName()
			);
		}

		return $value;
	}

}