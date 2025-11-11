<?php

/**
 * @property integer $id
 * @property string $changed
 * @property string $created
 * @property string $client_id
 * @property string $school_id
 * @property string $name
 * @property string $active
 * @property string $creator_id
 * @property string $numberformat
 * @property string $user_id
 * @property string $position
 *
 */
class Ext_Thebing_Visum extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_visum_status';
	protected $_sTableAlias = 'kvs';

	protected $_aJoinTables = [
		'flex_fields' => [
			'table' => 'kolumbus_visum_status_flex_fields',
			'foreign_key_field' => 'flex_field_id',
			'primary_key_field' => 'visa_status_id',
		]
	];

	public static function getVisumStatusList($iSchool){

		$sSql = "
				SELECT 
					* 
				FROM 
					`kolumbus_visum_status` 
				WHERE 
					`active` = 1 AND 
					`school_id` = :school_id
				ORDER BY
					`name`";
		$aSql = array('school_id'=>(int)$iSchool);
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		$aBack = array();
		foreach($aResult as $aVis) {
			$aBack[$aVis['id']] = $aVis['name'];
		}
	
		return $aBack;
	}

	/**
	 * Abhängigkeiten zum Ausblenden der Flex-Felder für SR
	 *
	 * @return array
	 */
	public static function getVisaStatusListWithFlexFieldIds() {

		$sSql = "
			SELECT
				`kvs`.`id`,
				GROUP_CONCAT(`kvsff`.`flex_field_id`) `flex_ids`
			FROM
				`kolumbus_visum_status` `kvs` INNER JOIN
				`kolumbus_visum_status_flex_fields` `kvsff` ON
					`kvsff`.`visa_status_id` = `kvs`.`id`
			WHERE
				`kvs`.`active` = 1
			GROUP BY
				`kvs`.`id`
		";

		$aResult = (array)DB::getQueryPairs($sSql);
		$aResult = array_map(function($sFlexIds) {
			return explode(',', $sFlexIds);
		}, $aResult);

		return $aResult;

	}

	/**
	 * @param Ext_Thebing_Pdf_Template $oTemplate
	 * @return Ext_Thebing_Inquiry_Document_Numberrange|null
	 */
	public static function getNumberrangeObject(Ext_Thebing_Pdf_Template $oTemplate) {

		$oNumberrange = null;
		$oConfig = new Ext_TS_Config();
		$iNumberrangeId = (int)$oConfig->getValue('ts_visas_numbers');

		if($iNumberrangeId != 0) {
			$oTmpNumberRange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($iNumberrangeId);
			if($oTmpNumberRange->exist()) {
				$oNumberrange = $oTmpNumberRange;
			}
		}

		// Hook für LD (niemand anders braucht das sonst)
		$aHookData = ['numberrange' => &$oNumberrange, 'template' => $oTemplate];
		System::wd()->executeHook('ts_numberrange_visa', $aHookData);

		return $oNumberrange;

	}
	
}
