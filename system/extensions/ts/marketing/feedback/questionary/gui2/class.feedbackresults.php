<?php

/**
 * Class Ext_TS_Marketing_Feedback_Questionary_Gui2_FeedbackResults
 */
class Ext_TS_Marketing_Feedback_Questionary_Gui2_FeedbackResults extends Ext_TC_Gui2 {

	/**
	 * @TODO Gibt es hierfür nicht das Recht all_schools?
	 *
	 * @param bool $bPositionTop
	 * @return Ext_Gui2_Bar
	 */
	public function getBarList($bPositionTop = true) {

		$aBars = parent::getBarList($bPositionTop);

		if(Ext_Thebing_System::isAllSchools()) {

			if($bPositionTop === true) {

				/** @var Ext_Gui2_Bar $oBar */
				foreach ($aBars as $oBar) {

					foreach ($oBar->getElements() as $oBarElement) {

						if ($oBarElement->element_type == 'filter') {

							if($oBarElement->id === 'school_filter') {
								$oBarElement->visibility = true;
							}
						}
					}
				}
			}
		}

		return $aBars;
	}

	/**
	 * @TODO Gibt es hierfür nicht das Recht all_schools?
	 *
	 * @param string $sFlexType
	 * @param array|null $aColumnList
	 * @return array
	 */
	public function getVisibleColumnList($sFlexType = 'list', $aColumnList=null) {

		$aColumns = parent::getVisibleColumnList($sFlexType, $aColumnList);

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if($oSchool->getId() !== 0) {
			/** @var Ext_Gui2_Head $oColumn */
			foreach($aColumns as $mKey => $oHead) {

				if($oHead->db_column === 'ext_1') {
					unset($aColumns[$mKey]);
				}

			}
		}

		// JSON-Array erzwingen
		return array_values($aColumns);
	}

}