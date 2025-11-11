<?php


class Ext_TS_Frontend_Combination_Login_Student_Transfer extends Ext_TS_Frontend_Combination_Login_Student_Abstract {

	protected function _setData(){

		$oInquiry	= $this->_getInquiry();

		$oForm		= self::getTransferForm($this, $oInquiry);

		$this->_assign('sTransferForm', (string)$oForm);
		
		$this->_setTask('showTransferData');
	
	}

	public static function getTransferForm(Ext_TS_Frontend_Combination_Login_Student_Abstract $oDataClass, Ext_TS_Inquiry $oInquiry)
	{
		$mArrival		= $oInquiry->getTransfers('arrival');
		$mDeparture		= $oInquiry->getTransfers('departure');
		$mAdditional	= $oInquiry->getTransfers('additional');

		$oSchool = $oInquiry->getSchool();

		$oFormTransferType = new Ext_Thebing_Gui2_Format_Transfer();
		$oFormDate = new Ext_Thebing_Gui2_Format_Date(false, $oSchool->id);

		$aTransferArrival		= $oInquiry->getTransferLocations('arrival');
		$aTransferDeparture		= $oInquiry->getTransferLocations('departure');
		$aTransferIndividual	= $oInquiry->getTransferLocations();

		$oForm = new Ext_TS_Frontend_Combination_Login_Student_Form($oDataClass);

		$oForm->addRow('input', 'Type', $oFormTransferType->format($oInquiry->tsp_transfer), array('readonly' => true));

		if(is_object($mArrival)){
			$iStartId				= (int) $mArrival->start;
			$iEndId					= (int) $mArrival->end;
			// Select IDs zusammensetzen
			$iStartId	= $mArrival->start_type . '_' . $iStartId;
			$iEndId		= $mArrival->end_type . '_' . $iEndId;

			$oForm ->addHeadline('Arrival');

			$oForm->addRow('input', 'Pick up', $aTransferArrival[$iStartId], array('readonly' => true));
			$oForm->addRow('input', 'Drop off', $aTransferArrival[$iEndId], array('readonly' => true));
			$oForm->addRow('input', 'Airline', $mArrival->airline);
			$oForm->addRow('input', 'Flightnumber', $mArrival->flightnumber);
			$oForm->addRow('input', 'Date', $oFormDate->format($mArrival->transfer_date), array('readonly' => true));
			$oForm->addRow('input', 'Arrival time', substr($mArrival->transfer_time, 0 , 5));
			$oForm->addRow('input', 'Pick up time', substr($mArrival->pickup, 0 , 5));
		}

		if(is_object($mDeparture)){
			$iStartId				= (int) $mDeparture->start;
			$iEndId					= (int) $mDeparture->end;
			// Select IDs zusammensetzen
			$iStartId	= $mDeparture->start_type . '_' . $iStartId;
			$iEndId		= $mDeparture->end_type . '_' . $iEndId;

			$oForm ->addHeadline('Departure');

			$oForm->addRow('input', 'Pick up', $aTransferDeparture[$iStartId], array('readonly' => true));
			$oForm->addRow('input', 'Drop off', $aTransferDeparture[$iEndId], array('readonly' => true));
			$oForm->addRow('input', 'Airline', $mDeparture->airline);
			$oForm->addRow('input', 'Flightnumber', $mDeparture->flightnumber);
			$oForm->addRow('input', 'Date', $oFormDate->format($mDeparture->transfer_date), array('readonly' => true));
			$oForm->addRow('input', 'Arrival time', substr($mDeparture->transfer_time, 0 , 5));
			$oForm->addRow('input', 'Pick up time', substr($mDeparture->pickup, 0 , 5));
		}

		if(count($mAdditional) > 0){
			$oForm ->addHeadline('Individual');
		}

		foreach((array)$mAdditional as $oTransfer){
			$iStartId				= (int) $oTransfer->start;
			$iEndId					= (int) $oTransfer->end;
			// Select IDs zusammensetzen
			$iStartId	= $oTransfer->start_type . '_' . $iStartId;
			$iEndId		= $oTransfer->end_type . '_' . $iEndId;

			$oForm->addRow('input', 'Pick up', $aTransferIndividual[$iStartId], array('readonly' => true));
			$oForm->addRow('input', 'Drop off', $aTransferIndividual[$iEndId], array('readonly' => true));
			$oForm->addRow('input', 'Airline', $oTransfer->airline);
			$oForm->addRow('input', 'Flightnumber', $oTransfer->flightnumber);
			$oForm->addRow('input', 'Date', $oFormDate->format($oTransfer->transfer_date), array('readonly' => true));
			$oForm->addRow('input', 'Arrival time', substr($oTransfer->transfer_time, 0 , 5));
			$oForm->addRow('input', 'Pick up time', substr($oTransfer->pickup, 0 , 5));
		}

		return $oForm;
	}
	
}
?>
