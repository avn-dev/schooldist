<?php


class Ext_TC_Gui2_Selection_Flexibility_FieldType extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aFieldTypes = $aSaveField['select_options'];

		$aSectionsWithRepeatableContainers = $this->getSectionsWithRepeatableContainers();

		$oSection = $oWDBasic->getSection();

		if(
			// Section wurde noch nicht ausgewählt
			!$oSection->exist() ||
			// Section wurde noch nicht freigeschaltet
			!in_array($oSection->type, $aSectionsWithRepeatableContainers)
		) {
			unset($aFieldTypes[Ext_TC_Flexibility::TYPE_REPEATABLE]);
		}

		return $aFieldTypes;
	}

	protected function getSectionsWithRepeatableContainers(): array {
		return [];
	}

}
