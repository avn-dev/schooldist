<?php

class Ext_Thebing_Gui2_Selection_School_SchoolsWithSameValue extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @var string
	 */
	private $sSelectedSchoolIdsField = 'schools';

	/**
	 * @var string
	 */
	private $sCompareField = '';

	/**
	 * @param string $sCompareField
	 * @param string $sSelectedSchoolIdsField
	 */
	public function __construct($sCompareField, $sSelectedSchoolIdsField = 'schools') {
		$this->sCompareField = (string)$sCompareField;
		$this->sSelectedSchoolIdsField = (string)$sSelectedSchoolIdsField;
	}

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSelectedSchoolIds = $oWDBasic->{$this->sSelectedSchoolIdsField};
		$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);

		/*
		 * als Vergleichswert den Wert der ersten selektierten Schule nehmen, welche auch als gültige
		 * Auswahl in $aSchools auftaucht
		 */
		$bCompareValueFound = false;
		$mCompareValue = null;
		foreach($aSelectedSchoolIds as $iSelectedSchoolId) {
			$oSelectedSchool = Ext_Thebing_School::getInstance($iSelectedSchoolId);
			foreach($aSchools as $oSchool) {
				if($oSchool->getId() === $oSelectedSchool->getId()) {
					$mCompareValue = $oSelectedSchool->{$this->sCompareField};
					$bCompareValueFound = true;
					break 2;
				}
			}
		}

		/*
		 * wenn ein Vergleichswert verfügbar ist, dürfen nur Schulen als Auswahloption zur Verfügung stehen,
		 * die den gleich Wert haben - ansonsten wird die Auswahl nicht weiter eingeschränkt
		 */
		$aSelectOptions = [];
		foreach($aSchools as $oSchool) {
			if(
				!$bCompareValueFound ||
				$oSchool->{$this->sCompareField} === $mCompareValue
			) {
				$aSelectOptions[$oSchool->id] = $oSchool->ext_1;
			}
		}

		return $aSelectOptions;

	}

}
