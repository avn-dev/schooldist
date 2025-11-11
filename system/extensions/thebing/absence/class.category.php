<?php
 
/**
* @property $id 
* @property $changed
* @property $created
* @property $active 		
* @property $user_id 		
* @property $client_id 		
* @property $name
* @property $color
 */
class Ext_Thebing_Absence_Category extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_absence_categories';

	protected $_aFormat = array(
						'name'=>array(
							'required'=>true
						),
						'color'=>array(
							'required'=>true,
							'validate'=>'REGEX',
							'validate_value'=>'#[0-9A-Fa-f]{6}'
						)
	);

	protected static $aCache = array();

	static public function getList($bIncludeDefaults=true, $iClientId = 0) {
		global $user_data;

		if($iClientId === 0) {
			$iClientId = (int)$user_data['client'];
		}

		if(!isset(self::$aCache[$iClientId])) {

			$sSql = "SELECT
						*
					FROM
						#table
					WHERE
						`active` = 1 AND
						`client_id` = :client_id
					ORDER BY
						`name`
						";
			$aSql = array();
			$aSql['table'] = 'kolumbus_absence_categories';
			$aSql['client_id'] = (int)$iClientId;

			self::$aCache[$iClientId] = DB::getQueryRows($sSql, $aSql);

		}

		$aCategories = self::$aCache[$iClientId];

		if(!is_array($aCategories)) {
			$aCategories = array();
		}

		if($bIncludeDefaults) {
			array_unshift($aCategories, array('id'=>-1, 'color'=>'#66FFFF', 'name'=>L10N::t('Feiertage', 'Thebing » Absence')));
			array_unshift($aCategories, array('id'=>-2, 'color'=>'#22bbff', 'name'=>L10N::t('Schulferien', 'Thebing » Absence')));
			array_unshift($aCategories, array('id'=>-3, 'color'=>'#DDD', 'name'=>L10N::t('Wochenende', 'Thebing » Absence')));
		}

		return (array)$aCategories;

	}
 
}
