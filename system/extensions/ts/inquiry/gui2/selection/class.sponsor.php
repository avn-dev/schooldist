<?php

class Ext_TS_Inquiry_Gui2_Selection_Sponsor extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		/** @var Ext_TS_Inquiry $oWDBasic */

		$aSponsors = TsSponsoring\Entity\Sponsor::getRepository()->getSponsorsForSelect();

		// Gelöschten/deaktivierten Eintrag wieder hinzufügen
		if(
			$oWDBasic->exist() &&
			!empty($oWDBasic->sponsor_id) &&
			!isset($aSponsors[$oWDBasic->sponsor_id])
		) {
			$oSponsor = $oWDBasic->getSponsor();
			$aSponsors[$oSponsor->id] = $oSponsor->name;
		}

		$aSponsors = Ext_Thebing_Util::addEmptyItem($aSponsors);

		return $aSponsors;

	}

}
