<?php

/**
 * Wird sowohl für Buchungen als auch Gruppen verwendet
 */
class Ext_TS_Inquiry_Gui2_Selection_Agency extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		/** @var Ext_TS_Inquiry $oWDBasic */

		$school = $oWDBasic->getSchool();

		if(
			empty($school) ||
			!$school->exist()
		) {
			$school = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}

		$aAgencies = $school->getAgencies(true);

		// Gelöschten/deaktivierten Eintrag wieder hinzufügen
		if(
			$oWDBasic->exist() &&
			!empty($oWDBasic->agency_id) &&
			!isset($aAgencies[$oWDBasic->agency_id])
		) {
			$oAgency = $oWDBasic->getAgency();
			$aAgencies[$oAgency->id] = $oAgency->getName(true);
		}

		$aAgencies = Ext_Thebing_Util::addEmptyItem($aAgencies, Ext_Thebing_L10N::getEmptySelectLabel('agency'));

		return $aAgencies;
	}

}