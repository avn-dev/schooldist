<?php

namespace TsSponsoring\Gui2\Selection;

use TsSponsoring\Entity\Sponsor;

class SponsorContacts extends \Ext_Gui2_View_Selection_Abstract {

	/**
	 * {@inheritdoc}
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {


		$iSponsorId = (int) $oWDBasic->sponsor_id;
		$aContacts = [];

		if($iSponsorId > 0) {

			$oSponsor = Sponsor::getInstance($iSponsorId);

			$aContacts = $oSponsor->getContacts();

			foreach($aContacts as $oContact) {
				$aContacts[$oContact->getId()] = $oContact->getName();
			}

		}

		return $aContacts;

	}

}