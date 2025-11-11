<?php

/**
 *   WDBasic-Klasse der Ländergruppen
 * 
 */
class Ext_TC_Countrygroup extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_countrygroups';

	static protected $sClassName = 'Ext_TC_Countrygroup';
	
	protected $_sTableAlias = 'tc_cg';

	protected static $aCache = array();

	private static array $countryGroupIdsByCountryIso = [];
	
	protected $_aFormat = array(
		'valid_until' => array(
			'format' => 'DATE'
		)
	);
	
	protected $_aJoinedObjects = array(
		'SubObjects'=>array(
			'class'=>'Ext_TC_Countrygroup_Object',
			'key'=>'countrygroup_id',
			'orderby'=>'position',
			'type'=>'child',
			'on_delete' => 'cascade'
		)
	);	 
	
	public static function getSelectOptions(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true);
		return $aList;
	}
	

	public function getName(){
		
		return $this->name;
		
	}

	/**
	 * Gets an array of all country group ids matching a country iso
	 * @param string $countryIso
	 * @return array
	 */
	public static function getCountryGroupIdsByCountryIso(string $countryIso): array {
		if (!isset(self::$countryGroupIdsByCountryIso[$countryIso])) {
			self::$countryGroupIdsByCountryIso[$countryIso] = collect(array_filter(self::query()->get()->toArray(), function ($countryGroup) use ($countryIso) {
				return in_array($countryIso, reset($countryGroup->getJoinedObjectChilds('SubObjects'))->countries);
			}))->pluck('id')->toArray();
		}
		return self::$countryGroupIdsByCountryIso[$countryIso];
	}

	public static function getByObjectAndCountry($iObjectId, $sCountryIso) {

		// Wenn noch nicht abgerufen
		if(!isset(self::$aCache[$iObjectId][$sCountryIso])) {

			if(!isset(self::$aCache[$iObjectId])) {
				self::$aCache[$iObjectId] = array();
			}
			
			$sSql = "
				SELECT
					`tc_cg`.`id`
				FROM
					`tc_countrygroups` `tc_cg` JOIN
					`tc_countrygroups_objects` `tc_cgo` ON
						`tc_cg`.`id` = `tc_cgo`.`countrygroup_id` JOIN
					`tc_countrygroups_objects_to_countries` `tc_cgtc` ON
						`tc_cgo`.`id` = `tc_cgtc`.`countrygroup_object_id` JOIN
					`tc_countrygroups_objects_to_objects` `tc_cgto` ON
						`tc_cgo`.`id` = `tc_cgto`.`countrygroup_object_id`
				WHERE
					`tc_cg`.`active` = 1 AND
					`tc_cgto`.`object_id` = :object_id AND
					`tc_cgtc`.`country_iso` = :country_iso
				";
			$aSql = array(
				'object_id'=>(int)$iObjectId,
				'country_iso'=>(string)$sCountryIso
			);
 
			$iCountryGroupId = (int)DB::getQueryOne($sSql, $aSql);
			
			self::$aCache[$iObjectId][$sCountryIso] = $iCountryGroupId;

		}

		$iCountryGroupId = self::$aCache[$iObjectId][$sCountryIso];

		if($iCountryGroupId !== null) {
			$oCountryGroup = self::getInstance($iCountryGroupId);
			return $oCountryGroup;
		}

		return;

	}

	public static function getByObjects($aObjectIds) {

		$sCacheKey = 'Ext_TC_Coutrygroup::getByObjects_'.implode("_", $aObjectIds);
		
		// Wenn noch nicht abgerufen
		if(!isset(self::$aCache[$sCacheKey])) {
			
			$sSql = "
				SELECT
					`tc_cg`.`id`,
					`tc_cg`.`name`
				FROM
					`tc_countrygroups` `tc_cg` JOIN
					`tc_countrygroups_objects` `tc_cgo` ON
						`tc_cg`.`id` = `tc_cgo`.`countrygroup_id` JOIN
					`tc_countrygroups_objects_to_objects` `tc_cgto` ON
						`tc_cgo`.`id` = `tc_cgto`.`countrygroup_object_id`
				WHERE
					`tc_cg`.`active` = 1 AND
					`tc_cgto`.`object_id` IN (:object_ids)
				ORDER BY
					`tc_cg`.`name`
				";
			$aSql = array(
				'object_ids'=>(array)$aObjectIds
			);
 
			$aCountrygroups = (array)DB::getQueryPairs($sSql, $aSql);
			
			self::$aCache[$sCacheKey] = $aCountrygroups;

		}

		return self::$aCache[$sCacheKey];

	}

	/**
	 * Gibt CountryCodes von einer Gruppe für ein Object zurück
	 * @todo WDCache implementieren, mit zurücksetzen beim Speichern
	 * 
	 * @param int $iObjectId
	 * @return array
	 */
	public function getCountryIsosByObject($oObject) {
		
		$sCacheKey = 'Ext_TC_Coutrygroup::getCountryIsosByObject_'.$this->id.'_'.$oObject->id;
		
		// Wenn noch nicht abgerufen
		if(!isset(self::$aCache[$sCacheKey])) {
			
			$sSql = "
				SELECT
					`tc_cgtc`.`country_iso`
				FROM
					`tc_countrygroups_objects` `tc_cgo` JOIN
					`tc_countrygroups_objects_to_countries` `tc_cgtc` ON
						`tc_cgo`.`id` = `tc_cgtc`.`countrygroup_object_id` JOIN
					`tc_countrygroups_objects_to_objects` `tc_cgto` ON
						`tc_cgo`.`id` = `tc_cgto`.`countrygroup_object_id`
				WHERE
					`tc_cgo`.`countrygroup_id` = :id AND
					`tc_cgto`.`object_id` = :object_id
				";
			$aSql = array(
				'id' => (int)$this->id,
				'object_id'=>(int)$oObject->id
			);
 
			$aCountries = (array)DB::getQueryCol($sSql, $aSql);
			
			self::$aCache[$sCacheKey] = $aCountries;

		}

		return self::$aCache[$sCacheKey];

	}
	
}