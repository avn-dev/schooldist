<?php

namespace Ts\Gui2\Style\Accommodation\Allocation;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use TsAccommodation\Dto\Allocation\ConfirmationStatus;

class Confirmation extends \Ext_Gui2_View_Style_Abstract {

    public function getStyle($value, &$column, &$rowData) {

		$allocationId			= (int)$rowData['first_accommodation_allocation'];
		$activeAllocations		= Arr::wrap($rowData['accommodation_allocations']);

		$getDate = function ($value) {
			return ($value !== null) ? Carbon::parse($value) : null;
		};

		$confirmDate = $getDate($value);
		$changedDate = $getDate($rowData['accommodation_allocation_changed']);

		$color = ConfirmationStatus::getColorByValues($allocationId, $confirmDate, $changedDate, $activeAllocations);

		return ($color) ? sprintf('background-color: %s;', $color) : '';
    }

}
