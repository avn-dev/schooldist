<?php

class Ext_TS_Marketing_Feedback_Questionary_Gui2_Format_NameOfDependency extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 *
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sReturnNames = '';

		if(
			!empty($mValue) &&
			is_string($mValue)
		) {

			$aResult = explode(',', $mValue);

			$aDependencyObject = [];

			foreach ($aResult as $iKey => $sResult) {
				if ($sResult !== '0{|}0') {
					$aDependencyObject[] = explode('{|}', $sResult);
				}
			}

			$aName = [];
			if (
				$oColumn->db_column === 'teacher' ||
				$oColumn->db_column === 'accommodation_provider'
			) {
				$aName = $this->fillReturnArrayWithNames($aDependencyObject, $aResultData, $oColumn->db_column);
			}

			$sReturnNames = implode(',<br />', $aName);

		}

		return $sReturnNames;

	}

	/**
	 * Füllt ein Array mit Namen von Lehrern und Unterkunftsanbietern des Fragebogens
	 *
	 * @param array $aDependencyObject
	 * @param array $aResultData
	 * @param $sCurrentDependency
	 * @return array
	 * @throws Exception
	 */
	public function fillReturnArrayWithNames(array $aDependencyObject, array $aResultData, $sCurrentDependency) {

		$aName = [];

		foreach($aDependencyObject as $iKey => $aDependency) {

			if($aDependency[0] !== $sCurrentDependency) {
				unset($aDependencyObject[$iKey]);
			}

			if(
				$aDependency[0] === 'teacher' &&
				$sCurrentDependency === 'teacher'
			) {
				$oTeacher = Ext_Thebing_Teacher::getInstance($aDependency[1]);
				$aName[$oTeacher->getId()] = $oTeacher->getName();
			} elseif(
				$aDependency[0] === 'accommodation_provider' &&
				$sCurrentDependency === 'accommodation_provider'
			) {

				$oJourney = Ext_TS_Inquiry_Journey::getInstance($aResultData['journey_id']);
				$oSchool = $oJourney->getSchool();
				$aAccommodationProviders = $oSchool->getAccommodationProvider(true);

				if($aAccommodationProviders[$aDependency[1]]) {
					$aName[$aDependency[1]] = $aAccommodationProviders[$aDependency[1]];
				}

			}

		}

		return $aName;

	}

}