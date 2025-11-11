<?php

class Ext_Thebing_Gui2_Selection_School_AccommodationCategory extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @var string
	 */
	private $sLanguage = '';

	/**
	 * @var bool
	 */
	private $bMatchAllSchools = false;

	/**
	 * @param string $sLanguage
	 * @param bool $bMatchAllSchools
	 */
	public function __construct($sLanguage = '', $bMatchAllSchools = false) {
		$this->sLanguage = (string)$sLanguage;
		$this->bMatchAllSchools = (bool)$bMatchAllSchools;
	}

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSelectedSchoolIds = [];

		if(
			$oWDBasic instanceof Ext_Thebing_Accommodation_Cost_Category ||
			$oWDBasic instanceof Ext_Thebing_Accommodation ||
			$oWDBasic instanceof \TsAccommodation\Entity\Cleaning\Type
		) {
			$aSelectedSchoolIds = $oWDBasic->schools;
		}

		return Ext_Thebing_Accommodation_Category::getListForSchools(
			$aSelectedSchoolIds,
			true,
			$this->sLanguage,
			$this->bMatchAllSchools
		);

	}

}
