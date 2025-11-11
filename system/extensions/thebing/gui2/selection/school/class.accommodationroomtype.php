<?php

class Ext_Thebing_Gui2_Selection_School_AccommodationRoomtype extends Ext_Gui2_View_Selection_Abstract {

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

		if($oWDBasic instanceof Ext_Thebing_Accommodation_Room) {
			$oAccommodation = Ext_Thebing_Accommodation::getInstance($oWDBasic->accommodation_id);
			$aSelectedSchoolIds = $oAccommodation->getJoinTableData('schools');
		}

		return Ext_TC_Util::addEmptyItem(Ext_Thebing_Accommodation_Roomtype::getListForSchools(
			$aSelectedSchoolIds,
			true,
			$this->sLanguage,
			$this->bMatchAllSchools
		));

	}

}
