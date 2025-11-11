<?php

class Ext_TS_Enquiry_Combination_Gui2_Format_Transfer extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var string
	 */
	private $type;

	public function __construct(string $type) {
		$this->type = $type;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$journey = Ext_TS_Inquiry_Journey::getInstance($aResultData['id']);

		$transfers = array_filter($journey->getTransfersAsObjects(), function (Ext_TS_Inquiry_Journey_Transfer $transfer) {
			return (
				(
					$this->type === 'arrival' &&
					$transfer->transfer_type == $transfer::TYPE_ARRIVAL
				) || (
					$this->type === 'departure' &&
					$transfer->transfer_type == $transfer::TYPE_DEPARTURE
				)
			);
		});

		$data = array_map(function (Ext_TS_Inquiry_Journey_Transfer $transfer) {
			// Analog zu den Buchungen
			return $transfer->getName(null, 2);
		}, $transfers);

		return join('<br>', $data);

	}

}