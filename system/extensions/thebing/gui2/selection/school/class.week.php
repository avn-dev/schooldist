<?php

class Ext_Thebing_Gui2_Selection_School_Week extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSelectedSchoolIds = [];

		if($this->oJoinedObject instanceof \TsAccommodation\Entity\Provider\SchoolSetting) {
			$aSelectedSchoolIds = $this->oJoinedObject->schools;
		}

		$options = Ext_Thebing_School_Week::getListForSchools($aSelectedSchoolIds, true);

		return $options;
	}

}
