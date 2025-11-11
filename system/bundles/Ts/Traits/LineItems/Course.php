<?php

namespace Ts\Traits\LineItems;

trait Course {
	
	/**
	 * @param \Core\Service\Templating $oSmarty
	 * @param \Tc\Service\Language\Frontend $oLanguage
	 */
	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {
				
		$oInquiry = $this->getInquiry();
		$oCourse = $this->getCourse(); /** @var \Ext_Thebing_Tuition_Course $oCourse */

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Generate units part of name
		$sWeeksUnits = '';
		if (
			!$oCourse->calculateByUnit() &&
			$oCourse->getField('price_calculation') === 'month'
		) {
			$iMonths = \Ext_TS_Inquiry_Journey_Service::getMonthCount($this);
			$sLabel = $iMonths === 1 ? 'Monat' : 'Monate';
			if(fmod($iMonths, 1)) {
				$sMonth = \Ext_Thebing_Format::Number($iMonths);
			} else {
				$sMonth = (string)$iMonths;
			}
			$sWeeksUnits = sprintf('%s %s', $sMonth, $oLanguage->translate($sLabel));
		} elseif (!$oCourse->calculateByUnit()) {
			$sWeeksUnits = $this->weeks . ' ';

			if($this->weeks == 1) {
				$sWeeksUnits .= $oLanguage->translate('Woche');
			} else {
				$sWeeksUnits .= $oLanguage->translate('Wochen');
			}
		} elseif ($oCourse->isPerUnitCourse()) {

			$units = $this->getUnits();

			$sWeeksUnits = $units . ' ';

			if($units == 1) {
				$sWeeksUnits .= $oLanguage->translate('Lektion');
			} else {
				$sWeeksUnits .= $oLanguage->translate('Lektionen');
			}
		}

		$courseLanguageObject = $this->getJoinedObject('course_language');
		
		$courseLanguage = '';
		if($courseLanguageObject) {
			$courseLanguage = $courseLanguageObject->getName($oLanguage->getLanguage());
		}
		
		$oSmarty->assign('course', $oCourse->getName($oLanguage->getLanguage())); // TODO Sollte wie bei allen anderen name heißen
		$oSmarty->assign('name_frontend', $oCourse->getFrontendName($oLanguage->getLanguage()));
		$oSmarty->assign('nickname', $oCourse->getShortName($oLanguage->getLanguage())); // TODO Sollte irgendwie anders heißen
        $oSmarty->assign('course_language', $courseLanguage);
        $oSmarty->assign('weeks_units', $sWeeksUnits);
		$oSmarty->assign('category', $oCourse->getCategory()->getName($oLanguage->getLanguage()));
		$oSmarty->assign('from', \Ext_Thebing_Format::LocalDate($this->from, $oInquiry->getSchoolId()));
		$oSmarty->assign('until', \Ext_Thebing_Format::LocalDate($this->until, $oInquiry->getSchoolId()));

		if($this instanceof \Ext_TS_Inquiry_Journey_Course) {

			$firstAllocation = $this->getFirstTuitionAllocation();

			if(!empty($firstAllocation)) {

				$from = new \DateTime($firstAllocation['from']);
				$until = new \DateTime($firstAllocation['until']);

				$days = \Ext_Thebing_Util::getLocaleDays($oLanguage->getLanguage());

				$oSmarty->assign('first_assignment_time', $from->format('H:i') . ' - ' . $until->format('H:i'));
				$oSmarty->assign('first_assignment_weekday', $days[$firstAllocation['weekday']]);

			}

		}

		// Gratis-Gruppen-Guides extra aufführen in Maske
		$bFree = false;
		if(
			is_object($oInquiry) &&
			$oInquiry instanceof \Ext_TS_Inquiry_Abstract &&
			$oInquiry->hasGroup() &&
			$oInquiry->hasTraveller() &&
			$oInquiry->isGuide() &&
			(
				$oInquiry->getJourneyTravellerOption('free_course') ||
				$oInquiry->getJourneyTravellerOption('free_all')
			)
		) {
			$bFree = true;
		}
		
		$oSmarty->assign('free', $bFree);

	}
	
}
