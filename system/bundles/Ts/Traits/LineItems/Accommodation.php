<?php

namespace Ts\Traits\LineItems;

trait Accommodation {
	
	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {
		
		$oInquiry = $this->getInquiry();
		
		$oAccommodationCategory = $this->getCategory();
		$oAccommodationRoomType = $this->getRoomType();
		$oAccommodationMeal = $this->getMeal();

		$sWeeks = $this->weeks.' ';

		if($this->weeks == 1) {
			$sWeeks .= $oLanguage->translate('Woche');
		} else {
			$sWeeks .= $oLanguage->translate('Wochen');
		}
		
		$oSmarty->assign('accommodation', $oAccommodationCategory->getName($oLanguage->getLanguage())); // TODO Sollte mit category ersetzt werden
		$oSmarty->assign('nickname', $oAccommodationCategory->getShortName($oLanguage->getLanguage())); // TODO Sollte irgendwie anders heißen
		$oSmarty->assign('from', \Ext_Thebing_Format::LocalDate($this->from, $oInquiry->getSchoolId()));
		$oSmarty->assign('until', \Ext_Thebing_Format::LocalDate($this->until, $oInquiry->getSchoolId()));
		$oSmarty->assign('weeks', $sWeeks);
		$oSmarty->assign('category', $oAccommodationCategory->getName($oLanguage->getLanguage()));
		$oSmarty->assign('date_accommodation_start', \Ext_Thebing_Format::LocalDate($this->from, $oInquiry->getSchoolId()));
		$oSmarty->assign('date_accommodation_end', \Ext_Thebing_Format::LocalDate($this->until, $oInquiry->getSchoolId()));
		$oSmarty->assign('roomtype', $oAccommodationRoomType->getShortName($oLanguage->getLanguage()));
		$oSmarty->assign('roomtype_full', $oAccommodationRoomType->getName($oLanguage->getLanguage()));
		$oSmarty->assign('meal', $oAccommodationMeal->getShortName($oLanguage->getLanguage()));
		$oSmarty->assign('meal_full', $oAccommodationMeal->getName($oLanguage->getLanguage()));
		
		$bFree = false;
		if(
			is_object($oInquiry) &&
			$oInquiry instanceof \Ext_TS_Inquiry_Abstract &&
			$oInquiry->hasGroup() &&
			$oInquiry->hasTraveller() &&
			$oInquiry->isGuide() &&
			(
				$oInquiry->getJourneyTravellerOption('free_accommodation') ||
				$oInquiry->getJourneyTravellerOption('free_all')
			)
		) {
			$bFree = true;
		}
		
		$oSmarty->assign('free', $bFree);

	}
	
}
