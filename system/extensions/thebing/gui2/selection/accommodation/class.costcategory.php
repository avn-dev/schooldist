<?php

class Ext_Thebing_Gui2_Selection_Accommodation_CostCategory extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @var string
	 */
	protected $_sDescription;

	/**
	 * @param string $sDescription
	 */
	public function  __construct($sDescription) {
		$this->_sDescription = $sDescription;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oAccommodation = Ext_Thebing_Accommodation::getInstance($oWDBasic->accommodation_id);

		$aAccommodationCategories = $oAccommodation->getCategories();
		$aAccommodationCategoryIds = array_map(
			function(Ext_Thebing_Accommodation_Category $oAccommodationCategory) {
				return $oAccommodationCategory->id;
			},
			$aAccommodationCategories
		);
		// TODO #9834 - hier kommen die Schulen raus die gespeichert sind, nicht das was aktuell im Dialog ausgewählt ist
		$aSchoolIds = (array)$oAccommodation->getJoinTableData('schools');

		$aCostcategories = Ext_Thebing_Marketing_Costcategories::getAccommodationCategories(true, $aAccommodationCategoryIds, $aSchoolIds);
//		$aFirst = ['-1'=>L10N::t('Festgehalt', $this->_sDescription)];
//		$aCostcategories = (array)$aFirst + (array)$aCostcategories;
		$aCostcategories = Ext_Thebing_Util::addEmptyItem($aCostcategories, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));

		return $aCostcategories;

	}

}
