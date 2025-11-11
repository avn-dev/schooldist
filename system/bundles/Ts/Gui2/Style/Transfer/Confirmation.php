<?php

namespace Ts\Gui2\Style\Transfer;

use Carbon\Carbon;
use Ts\Dto\Transfer\ConfirmationStatus;

class Confirmation extends \Ext_Gui2_View_Style_Abstract {

    public function getStyle($value, &$column, &$rowData) {

		$getDate = function ($value) {
			return ($value !== null) ? Carbon::parse($value) : null;
		};

		$confirmDate = $getDate($value);
		$changedDate = $getDate($rowData['transfer_arrival_updated']);
		$cancellationDate = $getDate($rowData['cancellation_date_original']);

		$color = ConfirmationStatus::getColorByValues($confirmDate, $changedDate, $cancellationDate);

		return ($color) ? sprintf('background-color: %s;', $color) : '';
    }

}
