<?php

namespace TsSponsoring\Service;

use TsSponsoring\Entity\Sponsor;

class SponsorNumberrange extends \Ext_TS_NumberRange {

	protected $_sNumberTable = 'ts_sponsors';

	protected $_sNumberField = 'number';

	/**
	 * Liefert das Numberrange-Objekt, welches für diese Klasse benutzt wird
	 * Dieses wird zentral bei den Nummernkreisen eingestellt.
	 * @static
	 * @return \Ext_TC_NumberRange|null
	 */
	public static function getObject(\WDBasic $oEntity) {

		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$iNumberrangeId = $oConfig->getValue('ts_sponsors_numbers');
		$oNumberrange = null;

		if(is_numeric($iNumberrangeId)) {
			$oNumberrange = self::getInstance($iNumberrangeId);
		}

		return $oNumberrange;

	}

	public static function checkPossibility(Sponsor $oEntity, string $sNumber): bool {

		$oExisting = Sponsor::getRepository()->findOneBy(['number' => $sNumber]);

		if(
			$oExisting &&
			$oExisting->getId() !== $oEntity->getId()
		) {
			return false;
		}

		return true;
	}

}
