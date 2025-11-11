<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

/**
 * @todo das hier ist nur auf die schnelle programmiert worden - evtl. mit SMARTY umsetzen?
 */
class Booking extends AbstractPage {
	
	public function render(array $aData = array()) {
		
		$aBookingData = $this->oApp->getBookingData();
		
		$sTemplate = $this->generatePageHeading($this->t('Booking data'));
		
		$bShowSchoolName = false;
		if(count($aBookingData) > 1) {
			$bShowSchoolName = true;
		}
		
		foreach($aBookingData as $iSchool => $aElements) {
			
			$oSchool = $aElements['school'];
			/* @var $oSchool \Ext_Thebing_School */
				
			if($bShowSchoolName) {
				$sTemplate .= '<h3 class="ui-bar ui-bar-a ui-corner-all">'.$oSchool->getName().'</h3>';
			}

			$sTemplate .=
				$this->_generateCourseBlock($aElements['courses']).
				$this->_generateAccommodationBlock($aElements['accommodations']).
				$this->_generateTransferBlock($aElements['transfers']).
				$this->_generateInsuranceBlock($aElements['insurances']);
		}
		
		return $sTemplate;
	}
	
	/**
	 * Generiert das HTML für die Kurse
	 * 
	 * @param array $aJourneyCourses
	 * @return string
	 */
	protected function _generateCourseBlock(array $aJourneyCourses) {		

		$sContent = '';
		foreach($aJourneyCourses as $oJourneyCourse) {
			/* @var $oJourneyCourse \Ext_TS_Inquiry_Journey_Course */
			$oCourse = $oJourneyCourse->getCourse();
			$sBlock = $this->t('Dates') . ': '.$this->formatDate($oJourneyCourse->from).' - '.$this->formatDate($oJourneyCourse->until).'<br>';
			
			$sBlock .= $this->t('Duration') . ': ';
			if($oJourneyCourse->weeks == 1) {
				$sBlock .= $oJourneyCourse->weeks . ' '. $this->t('week');
			} else {
				$sBlock .= $oJourneyCourse->weeks . ' '. $this->t('weeks');
			}
			
			$sContent .= $this->generateBlock($oCourse->getName($this->_sInterfaceLanguage), $sBlock);
		}
		
		
		if(empty($aJourneyCourses)) {
			$sContent .= $this->generateBlock($this->t('not booked'), '');
		}
		
		$sTemplate = $this->generatePageBlock($this->t('Courses'), $sContent);
		
		return $sTemplate;
	}

	/**
	 * Generiert das HTML für die Unterkünfte
	 * 
	 * @param array $aJourneyAccommodations
	 * @return string
	 */
	protected function _generateAccommodationBlock(array $aJourneyAccommodations) {		
		
		$sContent = '';
		foreach($aJourneyAccommodations as $oJourneyAccommodation) {
			/* @var $oJourneyAccommodation \Ext_TS_Inquiry_Journey_Accommodation */
			
			$aDescription = array();
			$oCategory = $oJourneyAccommodation->getCategory();
			$oRoomType = $oJourneyAccommodation->getRoomType();
			$oMeal = $oJourneyAccommodation->getMeal();

			if($oCategory->id > 0) {
				$aDescription[] = $oCategory->getName($this->_sInterfaceLanguage);
			}
			
			if($oRoomType->id > 0) {
				$aDescription[] = $oRoomType->getName($this->_sInterfaceLanguage);
			}
			
			if($oMeal->id > 0) {
				$aDescription[] = $oMeal->getName($this->_sInterfaceLanguage);
			}
			
			$sBlock = $this->t('Dates') . ': '.$this->formatDate($oJourneyAccommodation->from).' - '.$this->formatDate($oJourneyAccommodation->until).'<br>';
			$sContent .= $this->generateBlock(implode(', ', $aDescription), $sBlock);
		}
		
		if(empty($aJourneyAccommodations)) {
			$sContent .= $this->generateBlock($this->t('not booked'), '');
		}
		
		$sTemplate = $this->generatePageBlock($this->t('Accommodations'), $sContent);
		
		return $sTemplate;
	}	
	
	/**
	 * Generiert das HTML für die Transfere
	 * 
	 * @param array $aJourneyTransfers
	 * @return string
	 */
	protected function _generateTransferBlock(array $aJourneyTransfers) {		

		$oInquiry = $this->oApp->getInquiry();
		$oJourney = $oInquiry->getJourney();
		$aTransferTypes = \Ext_TS_Inquiry_Journey_Transfer::getTransferTypes($this->oApp->getInterfaceLanguage(), true);

		$sContent = '';
		foreach($aJourneyTransfers as $oJourneyTransfer) {
			/* @var $oJourneyTransfer \Ext_TS_Inquiry_Journey_Transfer */

			$sBlock = $this->t('Pick up') . ': '.$oJourneyTransfer->getStartLocation() . '<br>';
			$sBlock .= $this->t('Drop off') . ': '.$oJourneyTransfer->getEndLocation() . '<br>';

			// Gebucht oder nicht gebucht?
			if(
				(
					$oJourneyTransfer->transfer_type == $oJourneyTransfer::TYPE_ARRIVAL &&
					$oJourney->transfer_mode & $oJourney::TRANSFER_MODE_ARRIVAL
				) || (
					$oJourneyTransfer->transfer_type == $oJourneyTransfer::TYPE_DEPARTURE &&
					$oJourney->transfer_mode & $oJourney::TRANSFER_MODE_DEPARTURE
				)
			) {
				$sBlock .= $this->t('Booked').': '.$this->t('Yes').'<br>';
			} else {
				if($oJourneyTransfer->transfer_type != 0) {
					$sBlock .= $this->t('Booked').': '.$this->t('No').'<br>';
				}
			}

			$sBlock .= $this->t('Date') . ': '.$this->formatDate($oJourneyTransfer->transfer_date). '<br>';

			$sTransferTime = $oJourneyTransfer->transfer_time;
			if(!empty($sTransferTime)) {
				$oFormat = new \Ext_Thebing_Gui2_Format_Time();
				$sTransferTime = $oFormat->format($sTransferTime);
				$sBlock .= $this->t('Time') . ': '.$sTransferTime . '<br>';
			}

			$sContent .= $this->generateBlock($aTransferTypes[$oJourneyTransfer->transfer_type], $sBlock);
		}

		if(empty($aJourneyTransfers)) {
			$sContent = $this->generateBlock($this->t('not booked'), '');
		}

		$sTemplate = $this->generatePageBlock($this->t('Transfer'), $sContent);
		
		return $sTemplate;		
		
	}
	
	/**
	 * Generiert das HTML für die Versicherungen
	 * 
	 * @param array $aJourneyInsurances
	 * @return string
	 */
	protected function _generateInsuranceBlock(array $aJourneyInsurances) {		
				
		$sContent = '';
		foreach($aJourneyInsurances as $oJourneyInsurance) {
			/* @var $oJourneyInsurance \Ext_TS_Inquiry_Journey_Insurance */
			$oInsurance = $oJourneyInsurance->getInsurance();
			$sBlock = $this->t('Dates') . ': '.$this->formatDate($oJourneyInsurance->from).' - '.$this->formatDate($oJourneyInsurance->until);
			
			$sContent .= $this->generateBlock($oInsurance->getName($this->_sInterfaceLanguage), $sBlock);
		}
		
		if(empty($aJourneyInsurances)) {
			$sContent .= $this->generateBlock($this->t('not booked'), '');
		}

		$sTemplate = $this->generatePageBlock($this->t('Insurances'), $sContent);
		
		return $sTemplate;
		
	}

}