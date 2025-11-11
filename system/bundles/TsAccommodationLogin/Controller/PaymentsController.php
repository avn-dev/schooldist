<?php

namespace TsAccommodationLogin\Controller;

use Ext_Thebing_Gui2_Format_Amount;
use Ts\Gui2\AccommodationProvider\PaymentData;
use Ts\Gui2\AccommodationProvider\PaymentPeriodFormat;

class PaymentsController extends InterfaceController
{
	public function payments() {

		$accommodation = \Ext_Thebing_Accommodation::getInstance($this->_oAccess->id);

		$accommodationId = $accommodation->id;

		$dateFrom = new \Carbon\Carbon('-2 years');

		$groupings = \Ext_TS_Accounting_Provider_Grouping_Accommodation::query()
			->where('accommodation_id', $accommodationId)
			->where('date', '>', $dateFrom)
			->orderBy('date', 'desc')
			->get();

		$formatAmount = new Ext_Thebing_Gui2_Format_Amount();

		$this->set('groupings', $groupings);
		$this->set('formatAmount', $formatAmount);
		$this->set('dateFrom', $dateFrom);
		$this->set('accommodationId', $accommodationId);

	}

	public function getPdf($groupingId) {

		$accommodation = \Ext_Thebing_Accommodation::getInstance($this->_oAccess->id);
		$grouping = \Ext_TS_Accounting_Provider_Grouping_Accommodation::getInstance($groupingId);

		if($accommodation->id != $grouping->accommodation_id) {
			return response('No access', 401);
		}

		$file = \Util::getDocumentRoot().'storage'.$grouping->file;

		return response()->file($file);
	}
}