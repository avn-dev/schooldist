<?php

class Ext_TS_Referrer extends Ext_TC_Referrer {

	public function __construct($iDataID = 0, $sTable = null) {

		$this->_aJoinTables['objects'] = [
			'table' => 'ts_referrers_to_schools',
	 		'foreign_key_field' => 'school_id',
	 		'primary_key_field' => 'referrer_id',
		];

		parent::__construct($iDataID, $sTable);

	}

	public function checkUse() {

		$sSql = "
			SELECT
				COUNT(`id`) `count`
			FROM
				`ts_inquiries`
			WHERE
				`active` = 1 AND
				`referer_id` = :id
		";

		$iCount = (int)DB::getQueryOne($sSql, $this->_aData);
		if($iCount > 0) {
			return true;
		}

		return false;

	}


	/**
	 * @param bool $bForSelect
	 * @param int $iSchoolId
	 * @param string $sLanguage
	 * @return static[]|string[]
	 */
	public static function getReferrers($bForSelect = false, $iSchoolId = null, $sLanguage = null) {

		$aSql = [];

		$sSelect = $sJoin = "";
		if($iSchoolId !== null) {
			$sJoin .= " INNER JOIN
				`ts_referrers_to_schools` `ts_rts` ON
					`ts_rts`.`referrer_id` = `tc_r`.`id` AND
					`ts_rts`.`school_id` = :school_id
			";

			$aSql['school_id'] = $iSchoolId;
		}

		if($bForSelect) {
			if($sLanguage === null) {
				$sLanguage = System::getInterfaceLanguage();
			}

			$sSelect .= ", `tc_r_i18n`.`name` ";
			$sJoin .= " LEFT JOIN
				`tc_referrers_i18n` `tc_r_i18n` ON
					`tc_r_i18n`.`referrer_id` = `tc_r`.`id` AND
					`tc_r_i18n`.`language_iso` = :language
			";

			$aSql['language'] = $sLanguage;
		}

		$sSql = "
			SELECT
				`tc_r`.*
				{$sSelect}
			FROM
				`tc_referrers` `tc_r`
				{$sJoin}
			WHERE
				`tc_r`.`active` = 1 AND (
					`tc_r`.`valid_until` = '0000-00-00' OR
					`tc_r`.`valid_until` >= NOW()
				)
			GROUP BY
				`tc_r`.`id`
			ORDER BY
				`tc_r`.`position`
		";

		$aReferrers = (array)DB::getQueryRows($sSql, $aSql);

		$aReturn = [];
		foreach($aReferrers as $aReferrer) {
			if(!$bForSelect) {
				$aReturn[] = static::getObjectFromArray($aReferrer);
			} else {
				$aReturn[$aReferrer['id']] = $aReferrer['name'];
			}
		}

		return $aReturn;

	}

}
