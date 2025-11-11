<?php

namespace TsSalesForce\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TsSalesForce\Service\Agency;
use TsSalesForce\Service\Inquiry;

/**
 * Class SalesForceTransfer Beginnt die Übermittlung der neuen Daten nach SalesForce
 *
 * @package TsSalesForce\Handler\ParallelProcessing
 */
class SalesForceTransfer extends TypeHandler {

	/**
	 * @param array $aData
	 * @param bool $bDebug
	 * @return bool
	 */
	public function execute(array $aData, $bDebug = false) {

		$bSendToSalesForce = false;
		if(isset($aData['inquiry_id'])) {

			$oInquiry = \Ext_TS_Inquiry::getInstance($aData['inquiry_id']);

			$oSalesForceApiInuqiry = new Inquiry($oInquiry);
			$bSuccess = $oSalesForceApiInuqiry->transfer();

			if ($bSuccess) {
				$bSendToSalesForce = true;
			}

		} elseif(isset($aData['agency_id'])) {

			$oAgency = \Ext_Thebing_Agency::getInstance($aData['agency_id']);

			$oSalesForceApiAgency = new Agency($oAgency);
			$bSuccess = $oSalesForceApiAgency->transfer();

			if($bSuccess) {
				$bSendToSalesForce = true;
			}

		}

		return $bSendToSalesForce;

	}

	/**
	 * Gibt den Namen für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('SalesForce', 'School');
	}

}