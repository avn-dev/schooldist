<?php

class Ext_Thebing_Gui2_Selection_School_AccommodationMeal extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @var string
	 */
	private $sLanguage = '';

	/**
	 * @param string $sLanguage
	 */
	public function __construct($sLanguage = '') {
		$this->sLanguage = (string)$sLanguage;
	}

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSelectedSchoolIds = [];

		if($oWDBasic instanceof Ext_Thebing_Accommodation) {
			$aSelectedSchoolIds = $oWDBasic->schools;
		}

		return Ext_Thebing_Accommodation_Meal::getListForSchools($aSelectedSchoolIds, true, $this->sLanguage);

	}

}
