<?php

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property string $name
 * @property int $user_id
 * @property int $release_sl
 * @property int $position
 * @property int[] $schools
 */
class Ext_Thebing_School_Customerupload extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_flex_uploads';

	protected $_sTableAlias = 'ts_fu';

	protected $_aJoinTables = array(
		'schools' => array(
			'table' => 'ts_flex_uploads_schools',
			'foreign_key_field'	=> 'school_id',
	 		'primary_key_field'	=> 'upload_id',
			'autoload' => true
		)
	);

	/**
	 * @inheritdoc
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(`schools`.`school_id`) `schools`
		";

	}

	/**
	 * @param int[] $aSchoolIds
	 * @return static[]
	 */
	public static function getUploadFieldsBySchoolIds(array $aSchoolIds) {

		$sSql = "
			SELECT
				`ts_fu`.*,
				GROUP_CONCAT(DISTINCT `ts_fus`.`school_id`) `school_ids`
			FROM
				`ts_flex_uploads` `ts_fu` INNER JOIN
				`ts_flex_uploads_schools` `ts_fus` ON
					`ts_fus`.`upload_id` = `ts_fu`.`id`
			WHERE
				`ts_fu`.`active` = 1 AND
				`ts_fus`.`school_id` IN (:schools)
			GROUP BY
				`ts_fu`.`id`
			ORDER BY
				`ts_fu`.`position`
		";

		$aResult = (array)DB::getQueryRows($sSql, ['schools' => $aSchoolIds]);

		return array_map(function($aUploadField) {

			$aSchoolIds = $aUploadField['school_ids'];
			unset($aUploadField['school_ids']);

			$oUploadField = static::getObjectFromArray($aUploadField);

			// Vorbefüllen, da die Information ja ohnehin schon da ist
			$oUploadField->schools = explode(',', $aSchoolIds);

			return $oUploadField;

		}, $aResult);

	}

}
