<?php

namespace Tc\Traits;

/**
 * @TODO Das ist doch absolutes Chaos hier und fast alles existiert nochmal redundant in Ext_TC_Flexibility
 */
trait Flexibility {
	
	/**
	 * @var array
	 */
	protected static $_aFlexibilityValues = [];

	protected static $_aFieldCache = [];
	
	/**
	 * @TODO Irgendwie wird das zwar zwingend für getFlexValue() der Entität benötigt, aber bzgl. Index-GUIs/Mapping müssen die Sections explizit in der YML angegeben werden
	 *
	 * Einstellung
	 * Welche Flexiblen Felder gehören zu dieser Entity
	 *
	 * @var array
	 */
	protected $_aFlexibleFieldsConfig = [];
	
	/**
	 * Flex-Werte dieses Objektes
	 * field_id => value
	 *
	 * @var array
	 */
	protected $_aEntityFlexValues = null;

	/**
	 * Type für Flex-Values
	 * 
	 * @var string
	 */
	protected $_sEntityFlexType = '';

	/**
	 * Methode liefert die gespeicherten Flex2 Werte
	 * @param string $sSection
	 * @param boolean $bAll
	 * @return array 
	 */
	public function getFlexibilityValues($sSection, $bAll = false) {

		$sUsage = null;
		
		// Wenn das ein Array ist, dann ist die Usage auch mit angegeben
		if(is_array($sSection)) {
			$sUsage = $sSection[1];
			$sSection = $sSection[0];
		}

		$sCacheKey = (string)$sSection . '_' . (string)$sUsage . '_' . (int)$bAll.'_'.$this->id;
		$aCache = self::$_aFlexibilityValues;
		
		if(!isset($aCache[$sCacheKey])){
			
			$aSql = array();
            $sWhereAddon    = "";
            $sJoin          = "";
            
			// Bei nicht sichtbarenFeldern dürfen diese auch nicht mit ausgelesen werden.
			// Hinweis: Where Addon wurde ergänzt und war früher im GROUP BY Teil -> schlecht
			if (!$bAll) {
                $sJoin = " LEFT JOIN
							`system_gui2_flex_data` AS `sgfd`
								ON
									`sgfd`.`db_column` = CONCAT('flex_', `kfsf`.`id`)";
				$sWhereAddon .= " AND
							`sgfd`.`visible` = 1 ";
			}

			if($sUsage !== null) {
				$sWhereAddon .= " AND 
							`kfsf`.`usage` = :usage ";
				$aSql['usage'] = $sUsage;
			}

			// TODO Hier sollte eingebaut werden, dass im umgekehrten Fall auch nach einem leeren String gesucht wird
			if(!empty($this->_sEntityFlexType)) {
				$sWhereAddon .= " AND
							`kfsfv`.`item_type` = :item_type";
				$aSql['item_type'] = $this->_sEntityFlexType;
			}

			$sSql = "
						SELECT
							`kfsfv`.`field_id`, 
							`kfsfv`.`value`, 
							`kfsf`.`type`, 
							`kfsfv`.`item_id`,
							`kfsf`.`i18n`, 
							`kfsfv`.`language_iso`,
							`kfsf`.`parent_id`						       
						FROM
							`tc_flex_sections_fields_values` AS `kfsfv` INNER JOIN
							`tc_flex_sections_fields` AS `kfsf`
								ON `kfsf`.`id` = `kfsfv`.`field_id` INNER JOIN
							`tc_flex_sections` AS `kfs`
								ON `kfs`.`id` = `kfsf`.`section_id` ".$sJoin."
						WHERE
							(
								`kfs`.`type` = :section OR
								`kfs`.`category` = :section
							) AND
							`kfsfv`.`item_id` = :id ".$sWhereAddon."
						GROUP BY
							`kfsfv`.`field_id`,
							`kfsfv`.`language_iso`";

			$aSql['section'] = $sSection;
            $aSql['id'] = $this->id;
			
            $aResult = \DB::getPreparedQueryData($sSql,$aSql);
            $aFinal = array();

            foreach($aResult as $aData){
                $aFinal[$aData['item_id']][] = $aData;
            }
            
			self::$_aFlexibilityValues[$sCacheKey] = $aFinal;
		
		}

        $iId = (int)$this->id;
        
		return (array)(self::$_aFlexibilityValues[$sCacheKey][$iId] ?? null);
	}

	/**
	 * @param string $sField
	 * @param string $sSection
	 * @param bool $bAll
	 * @return null|string
	 */
	public function getFlexibilityValue($sField, $sSection, $bAll = false) {

		$iField = $sField;

		if(mb_strpos($sField, 'flex_') === 0){
			$aField = explode('_', $sField);
			$iField = end($aField);
		}

		$aValues = $this->getFlexibilityValues($sSection, $bAll);

		foreach($aValues as $aValue){
			if($aValue['field_id'] == $iField){
				return (string)$aValue['value'];
			}
		}
		
		return null;
	}
	
	
	/**
	 * @return array
	 */
	public function getFlexValues() {
		$this->_getFlexibleFieldsValues();
		return $this->_aEntityFlexValues;
	}

	/**
	 * @return string
	 */
	public function getFlexibleFieldsCacheKey() {
		return 'tc_basic_flex_fields_cache_key_'.get_class($this);
	}

	protected function _getFlexibleFieldsValues() {

		if($this->_aEntityFlexValues === null) {

			$aFields = $this->getFlexibleFields();
			$aFieldIds = array_keys($aFields);

			$sSql = "
				SELECT
					CONCAT(`field_id`, '_', `language_iso`),
					`value`
				FROM
					`tc_flex_sections_fields_values`
				WHERE
					`item_id` = :item_id AND
					(`item_type` = :item_type OR `item_type` = '') AND
					`field_id` IN (:field_ids)
				";
			$aSql = array(
				'item_id' => (int)$this->id,
				'item_type' => $this->_sEntityFlexType,
				'field_ids' => (array)$aFieldIds
			);

			$aResults = (array)\DB::getQueryPairs($sSql, $aSql);

			$aValues = array();
			foreach($aResults as $sKey=>$mValue) {

				$aKey = explode('_', $sKey, 2);
				/* @var $oFlexField \Ext_TC_Flexibility */
				$oFlexField = $aFields[$aKey[0]];

				if($oFlexField->isRepeatableContainer()) {
					// Alle Felder des wiederholbaren Bereichs abfragen und in eine Struktur bringen [container_index => [field_id => value]]

					$aChildFields = $oFlexField->getChildFields();

					$aClonedSql = $aSql;
					$aClonedSql['field_ids'] = array_keys($aChildFields);

					$aChildResults = (array)\DB::getQueryPairs($sSql, $aClonedSql);

					$aCombined = [];
					foreach($aChildResults as $sChildKey => $mChildValue) {

						$mChildValue = json_decode($mChildValue, true);

						$aChildKey = explode('_', $sChildKey, 2);
						/* @var $oChildFlexField \Ext_TC_Flexibility */
						$oChildFlexField = $aChildFields[$aChildKey[0]];

						foreach($mChildValue as $iContainerIndex => $mContainerValue) {
							if($oChildFlexField->isI18N()) {
								$aCombined[$iContainerIndex][$oChildFlexField->getId()][$aChildKey[1]] = $mContainerValue;
							} else {
								$aCombined[$iContainerIndex][$oChildFlexField->getId()] = $mContainerValue;
							}
						}
					}

					$aValues[$aKey[0]] = $aCombined;

				} else {

					if($oFlexField->type == 8) {
						$mValue = json_decode($mValue, true);
					}

					// Sprache darf nur übermittelt werden, wenn es auch ein mehrsprachiges Feld ist!
					if(
						$oFlexField->i18n == 1 &&
						!empty($aKey[1])
					) {
						if(!is_array($aValues[$aKey[0]] ?? null)) {
							$aValues[$aKey[0]] = array();
						}
						$aValues[$aKey[0]][$aKey[1]] = $mValue;
					} else {
						$aValues[$aKey[0]] = $mValue;
					}
				}

			}

			// Annahme: Die Werte im Objekte sind aktueller als die aus der DB
			$this->_aEntityFlexValues = (array)$this->_aEntityFlexValues + $aValues;
		}

	}
	
	/**
	 * Liefert den Type des Entities für die Values von individuellen Feldern
	 * 
	 * @return string
	 */
	public function getEntityFlexType() {
		return $this->_sEntityFlexType;
	}
	
	/**
	 * Gibt alle flexiblen Felder für GUI-Designer Usage "Kontakt" zurück
	 *
	 * @return array
	 */
	public function getFlexibleFields() {

		if (empty($this->_aFlexibleFieldsConfig)) {
			return [];
		}

		$cacheKey = $this->getFlexibleFieldsCacheKey();

		if (isset(self::$_aFieldCache[$cacheKey])) {
			return self::$_aFieldCache[$cacheKey];
		}

		/* @var \Core\Database\WDBasic\Builder $query */
		$query = \Factory::executeStatic(\Ext_TC_Flexibility::class, 'query');

		$query->select('kfsf.*');

		$query->join('tc_flex_sections as tc_fs', function ($join) {
			$join->on('tc_fs.id', '=', 'kfsf.section_id')
				->where('tc_fs.active', 1);
		});

		// Die einzelnen Sections mit OR verknüpft abfragen
		// AND (section1 OR section2 OR section3...)
		$query->where(function($query) {
			foreach($this->_aFlexibleFieldsConfig as $sectionKey => $subSections) {
				$query->orWhere(function($query) use ($sectionKey, $subSections) {
					$query->where('tc_fs.type', $sectionKey);

					if (!empty($subSections)) {
						// Falls usages angegeben sind diese mit prüfen
						$query->whereIn('kfsf.usage', $subSections);
					}
				});
			}
		});

		$query->groupBy('kfsf.id');

		$fields = $query->get()
			->keyBy('id') // ID als Key benutzen
			->toArray();

		self::$_aFieldCache[$cacheKey] = $fields;

		return $fields;

		/*$sCacheKey = self::getFlexibleFieldsCacheKey();
		
		$aFlexibleFields = \WDCache::get($sCacheKey);
		$aFlexibleFields = null;
		
		if($aFlexibleFields === null) {

			$aFlexibleFields = [];

			foreach($this->_aFlexibleFieldsConfig as $sSection=>$aSubSections) {

				$oSectionRepository = \Ext_TC_Flexible_Section::getRepository();
				$oSection = $oSectionRepository->findOneBy(['type' => $sSection]);

				if($oSection) {

//					$oFieldRepository = \Ext_TC_Flexibility::getRepository();
//					$aFields = $oFieldRepository->findBy(array('section_id' => $oSection->id));

					$aFields = $oSection->getFields();

					foreach($aFields as $oField) {
						$sUsage = $oField->usage;
						if(
							empty($aSubSections) ||
							in_array($sUsage, $aSubSections)
						) {
							$aFlexibleFields[$oField->id] = $oField;
						}
					}

				}
				
			}

			\WDCache::set($sCacheKey, 60*60, $aFlexibleFields);
			
		}

		return $aFlexibleFields;*/
	}

	
	/**
	 * @param int $iFieldId
	 * @param string $sLanguageIso
	 * @return mixed
	 */
	public function getFlexValue($iFieldId, $sLanguageIso = null, $mDefaultValue = null, $format = false) {

		/*
		 * Achtung: Bescheuerte Vertauschung der Parameter durch Index-Generator wird hier abgefangen
		 */
		if(
			is_string($iFieldId) &&
			is_int($sLanguageIso)
		) {
			$iTmpFieldId = $sLanguageIso;
			$sLanguageIso = $iFieldId;
			$iFieldId = $iTmpFieldId;
		}

		$this->_getFlexibleFieldsValues();

		$mValue = $this->_aEntityFlexValues[$iFieldId] ?? $mDefaultValue;

		if(
			is_array($mValue) &&
			$sLanguageIso !== null
		) {
			// Sprachen oder wiederholbare Felder
			$firstValueKey = key($mValue);
			if(
				isset($mValue[$sLanguageIso]) ||
				!is_numeric($firstValueKey)
			) {
				$mValue = $mValue[$sLanguageIso]??'';
			} else {
				if(is_array($mValue)) {
					// Wiederholbare Flexfelder
					foreach($mValue as $iContainerIndex => $aContainer) {
						if(is_array($aContainer)) {
							foreach($aContainer as $iChildField => $mChildValue) {
								if(isset($mChildValue[$sLanguageIso])) {
									$mValue[$iContainerIndex][$iChildField] = $mChildValue[$sLanguageIso];
								}
							}
						}
					}
				} else {
					$mValue = '';
				}
			}
		}

		if (
			$format &&
			!empty($mValue)
		) {
			$flexibility = new \Ext_TC_Flexibility($iFieldId);
			$mValue = $flexibility->formatValue($mValue, $sLanguageIso);
		}

		return $mValue;
	}
	
	/**
	 * Setzt einen Flex-Wert, wenn der Wert nicht leer ist oder wenn der Wert aktuell nicht leer ist und ein leerer 
	 * gesetzt werden soll.
	 * 
	 * @param integer $iFieldId
	 * @param mixed $mValue
	 */
	public function setFlexValue($iFieldId, $mValue) {

		$this->_getFlexibleFieldsValues();

		if(
			!empty($mValue) ||
			(
				empty($mValue) &&
				!empty($this->_aEntityFlexValues[$iFieldId])
			)
		) {
			$this->_aEntityFlexValues[$iFieldId] = $mValue;
		}

	}

	public function saveFlexValues() {
		
		if($this->_aEntityFlexValues !== null) {
			\Ext_TC_Flexibility::saveData($this->_aEntityFlexValues, $this->id, $this->_sEntityFlexType);
		}

	}

	/**
	 * Alle Flex-Felder dieser Entität löschen
	 */
	public function deleteFlexValues() {

		if(empty($this->_aFlexibleFieldsConfig)) {
			return;
		}

		$aFields = \Ext_TC_Flexibility::getSectionFieldData(array_keys($this->_aFlexibleFieldsConfig));
		$aFieldIds = array_column($aFields, 'id');

		$sSql = "
			DELETE FROM
				`tc_flex_sections_fields_values`
			WHERE
				`field_id` IN (:field_ids) AND
				`item_id` = :item_id
		";

		\DB::executePreparedQuery($sSql, [
			'field_ids' => $aFieldIds,
			'item_id' => $this->id
		]);

	}

	public function getFlexibleFieldsConfig() {
		return $this->_aFlexibleFieldsConfig;
	}


	public function newQuery() {

		static::addGlobalScope(new \Tc\Database\WDBasic\Scope\FlexFieldScope());

		return parent::newQuery();
	}

}
