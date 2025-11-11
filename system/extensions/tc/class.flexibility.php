<?php

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $user_id
 * @property int $section_id
 * @property string $title
 * @property int $visible
 * @property int $i18n
 * @property string $description
 * @property string $placeholder
 * @property int|string $type
 * @property int $required
 * @property string $validate_by
 * @property string $regex
 * @property int $max_length
 * @property string $error
 * @property int $position
 * @property int $editor_id
 * @property string $usage
 * @property int $i18n_sort
 * 3
 */
class Ext_TC_Flexibility extends Ext_TC_Basic {

	const TYPE_TEXT = 0;
	const TYPE_TEXTAREA = 1;
	const TYPE_CHECKBOX = 2;
	const TYPE_HEADLINE = 3;
	const TYPE_DATE = 4;
	const TYPE_SELECT = 5;
	const TYPE_HTML = 6;
	const TYPE_YESNO = 7;
	const TYPE_MULTISELECT = 8;
	const TYPE_REPEATABLE = 9;

	const HOOK_VALIDATE = 'tc_flex_field_validate';

	const FILTERABLE_TYPES = [
		self::TYPE_SELECT,
		self::TYPE_DATE,
		self::TYPE_YESNO,
		self::TYPE_MULTISELECT,
		self::TYPE_CHECKBOX,
		self::TYPE_TEXT
	];

	/**
	 * Maximale Felder pro Bereich
	 *
	 * Kann auf der Installation mit dem Key "tc_flex_fields_per_section_limit" angepasst werden,
	 * sollte aber zuerst hinterfragt werden! Das Limit hat schon einen Sinn.
	 */
	const FIELD_LIMIT_PER_SECTION = 10;

	const PLACEHOLDER_CATEGORIES_CACHE_KEY = 'flex_placeholder_categories';

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_flex_sections_fields';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kfsf';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'title' => [
			'required' => true
		],
		'changed' => array(
			'format' => 'TIMESTAMP'
		),
		'created' => array(
			'format' => 'TIMESTAMP'
		),
		'section_id' => [
			'required' => true
		],
		'max_length' => array(
			'validate' => 'INT_NOTNEGATIVE'
		),
		'placeholder' => [
			'validate' => ['REGEX', 'UNIQUE'],
			'validate_value' => '[a-z][a-z0-9_]{2,}'
		]
	);

	/**
	 * Verknüpfung zum GUI-Designer, damit man die Felder nicht löschen kann, wenn die im Designer verwendet werden
	 */
	protected $_aJoinTables = array(
		'gui2_designer_elements' => [
			'table' => 'tc_gui2_designs_tabs_elements',
			'class' => 'Ext_TC_Gui2_Design_Tab_Element',
			'primary_key_field' => 'special_type',
			'delete_check' => true,
			'check_active'=>true,
			'readonly' => true,
			'autoload'=>false,
			'static_key_fields' => array('type' => 'flexibility')
		],
	);

	protected $_aJoinedObjects = [
		'options' => [
			'class' => Ext_TC_Flexible_Option::class,
			'key' => 'field_id',
			'type'=>'child',
			'check_active' => true,
			'cloneable' => true,
			'on_delete' => 'cascade'
		],
		'child_fields' => [
			'class'=> Ext_TC_Flexibility::class,
			'key'=> 'parent_id',
			'type'=>'child',
			'on_delete' => 'cascade'
		]
	];

	public static $sL10NDescription = 'Thebing » Admin » Flexibility';

	/**
	 * @var array
	 */
	protected static $aCache = array();

	/**
	 * @var array
	 */
	protected static $_aGetSectionFieldData = array();

	/**
	 * @var array
	 */
	protected static $_aFieldData = array();

	/**
	 * @var int
	 */
	public $iIdBeforeSave = 0;

	/**
	 * Wird nicht mehr verwendet, der Wert steht direkt in der Tabelle
	 * Musste aber noch bleiben, damit man im Check darauf zugreifen kann.
	 * 
	 * @deprecated 
	 * @var array
	 */
	protected $_aAttributes = array(
		'gui_designer_usage' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar'
		)
	);

	public function isChildField(): bool {
		return ($this->parent_id > 0);
	}

	public function isI18N(): bool {
		return ((int)$this->i18n === 1);
	}

	public function isDateField(): bool {
		return ((int)$this->type === self::TYPE_DATE);
	}

	public function isRepeatableContainer(): bool {
		return ((int)$this->type === self::TYPE_REPEATABLE);
	}

	public function isRequired(): bool {
		return $this->required;
	}

	public function isFilterable(): bool {
		return in_array((int)$this->type, self::FILTERABLE_TYPES);
	}

	public function __get($sName) {
		
		/*
		 * @todo Parent Überprüfung einbauen
		 */
		if($sName == 'allowed_parent') {
			$mValue = null;
		} else {
			$mValue = parent::__get($sName);
		}
		
		return $mValue;
	}

	/**
	 * @return \Ext_TC_Flexibility[]
	 */
	public function getChildFields(): array {
		return $this->getJoinedObjectChilds('child_fields', true);
	}

	public static function getFlexSections($bSelect = true){
		$sSql = "SELECT
						*
					FROM
						`tc_flex_sections` AS `kfs`
					WHERE
						`kfs`.`active` = 1
					ORDER BY
						 `title` ASC
				";
		$aResult = DB::getQueryData($sSql);
		if($bSelect){
			$ReturnSelect = array();
			foreach((array) $aResult as $Data){
				$ReturnSelect[$Data['id']] = L10N::t($Data['title'],self::$sL10NDescription);
			}
			return $ReturnSelect;
		}else{
			return $aResult;
		}
	}
	
	public static function getFlexFieldTypes() {

		$aFieldTypes = array(
			self::TYPE_TEXT => L10N::t('Text',self::$sL10NDescription),
			self::TYPE_TEXTAREA => L10N::t('Textarea',self::$sL10NDescription),
			self::TYPE_CHECKBOX => L10N::t('Checkbox',self::$sL10NDescription),
			self::TYPE_HEADLINE => L10N::t('Überschrift',self::$sL10NDescription),
			self::TYPE_DATE => L10N::t('Datum',self::$sL10NDescription),
			self::TYPE_SELECT => L10N::t('Dropdown',self::$sL10NDescription),
			self::TYPE_HTML => L10N::t('HTML',self::$sL10NDescription),
			self::TYPE_YESNO => L10N::t('Ja/Nein',self::$sL10NDescription),
			self::TYPE_MULTISELECT => L10N::t('Mehrfachauswahl',self::$sL10NDescription),
			// Muss über getSectionsWithRepeatableContainers für eine Section freigeschaltet werden
			self::TYPE_REPEATABLE => L10N::t('Wiederholbarer Bereich',self::$sL10NDescription),
		);

		return $aFieldTypes;

	}
	
	/**
	 * Gibt alle Felder einer Section zurück ATG 1/2
	 * @param $sSection
	 * @return Ext_TC_Flexibility[]
	 */
	public static function getFields($sSection = '', $bCheckPlaceholder = false, $aUsage=null) {

		$sCacheKey = 'tc_flexibility_getFields_'.$sSection.'_'.$bCheckPlaceholder;
		
		if(!empty($aUsage)) {
			
			if(!is_array($aUsage)) {
				$aUsage = (array)$aUsage;
			}
			
			$sCacheKey .= '_'.implode('-', $aUsage);

		}
		
		if(!isset(self::$aCache[$sCacheKey])) {

			if($sSection == ''){
				return array();
			}

			$aSql = array();
			$aSql['type'] = $sSection;
			
			$sWhere = '';
			if($bCheckPlaceholder) {
				$sWhere = 'AND `kfsf`.`placeholder` != "" ';
			}

			if($aUsage !== null) {
				$sWhere = 'AND `kfsf`.`usage` IN (:usage) ';
				$aSql['usage'] = (array)$aUsage;
			}

			$sSql = "SELECT
							`kfsf`.*
						FROM 
							`tc_flex_sections_fields` AS `kfsf` INNER JOIN 
							`tc_flex_sections` AS `kfs` ON 
								`kfs`.`id` = `kfsf`.`section_id`
						WHERE 
							`kfsf`.`active` = 1 AND
							`kfsf`.`parent_id` = 0 AND
							`kfs`.`active` = 1 AND 
							`kfs`.`type` = :type 
							".$sWhere."
						ORDER BY
							`kfsf`.`position` ASC";

			$aResults = DB::getPreparedQueryData($sSql,$aSql);

			$aFields = array();
			foreach($aResults as $aResult) {

				$aFields[] = Ext_TC_Factory::executeStatic('Ext_TC_Flexibility', 'getObjectFromArray', array($aResult));

			}
			
			self::$aCache[$sCacheKey] = $aFields;
			
		}

		return self::$aCache[$sCacheKey]; 
	}
	
	public static function checkRequiredFields($aData){
		
		// check mandational Fields
		$aError = array();

		foreach((array)$aData as $iId => $sValue){
			$sSql = "SELECT 
							`kfsf`.`required`, `kfsf`.`type`, `kfsf`.`title`
						FROM 
							`tc_flex_sections_fields` AS `kfsf` 
						WHERE 
							`kfsf`.`id` = :fieldId
						";
			$aSql['fieldId'] = $iId;
			$aResult = DB::getPreparedQueryData($sSql,$aSql);

			if($aResult[0]['required'] == 1 && $aResult[0]['type'] != 2 && empty($sValue)){
				// pflichtfeld nicht ausgefüllt
				$aError[] = '"' . $aResult[0]['title'] . '" ' . L10N::t('muss ausgefüllt werden',self::$sL10NDescription);
			}
			
		}

		
		return $aError;
		
	}
	
	/**
	 * Funktion vergleicht bereits gespeicherte Values
	 * mit den übermittelten Flex Feldern.
	 * Fügt neue hinzu oder updatet vorhandene
	 */
	public static function saveData($aData, $iRowId = 0, $sItemType = ''){
		$aBack = array();

		// $iId => Field-ID
		foreach((array)$aData as $iId => $mValue){
			// Gültige Werte prüfen
			if($iId <= 0 || $iRowId <= 0){
				continue;
			}

			////////////////////////////////////////////////////////////////////////////////

			$aFieldData = self::checkFieldCache($iId);

			if ($aFieldData['type'] == self::TYPE_DATE) {

				$cConvertDate = function($mValue) {
					$oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
					/** @var Ext_TC_Gui2_Format_Date $oFormat */
					$oColumn = null;
					$aResult = array();
					return $oFormat->convert($mValue, $oColumn, $aResult);
				};

				if(is_array($mValue)) {
					// Datumsfelder in einem wiederholbarer Bereich
					foreach($mValue as $iIndex => $mInnerValue) {
						$mValue[$iIndex] = $cConvertDate($mInnerValue);
					}
				} else {
					$mValue = $cConvertDate($mValue);
				}

			} else if($aFieldData['type'] == self::TYPE_REPEATABLE) {

				if(!is_array($mValue)) {
					throw new \RuntimeException('Structure of repeatable field seems to be wrong');
				}

				// Werte aus den einzelnen Containern nach ID der Felder gruppieren damit jedes Feld die Werte aus allen
				// Containern enthält

				$aChildFieldValues = [];
				foreach($mValue as $iContainerIndex => $aContainerValues) {
					foreach($aContainerValues as $iChildField => $mChildValue) {

						$aChildFieldData = self::checkFieldCache($iChildField);

						if($aChildFieldData['i18n'] == 1 && is_array($mChildValue)) {
							foreach ($mChildValue as $sIso => $sIsoValue) {
								$aChildFieldValues[$iChildField][$sIso][$iContainerIndex] = $sIsoValue;
							}
						} else {
							$aChildFieldValues[$iChildField][$iContainerIndex] = $mChildValue;
						}

					}
				}

				// Childs rekursiv speichern
				self::saveData($aChildFieldValues, $iRowId, $sItemType);

				// Das Feld des Containers an sich muss auch einen Wert haben da es sonst in den ganzen Methoden die Values
				// zurückliefern nicht auftaucht
				if(!empty($mValue)) {
					$mValue = count($mValue);
				} else {
					$mValue = 0;
				}

			}

			// Array zu JSON
			$toColumnValue = function($mValue) {
				if(is_array($mValue)) {
					return json_encode($mValue);
				}
				return $mValue;
			};

			// I18N
			if (
				is_array($mValue) &&
				$aFieldData['i18n'] == 1
			) {
				foreach ($mValue as $sLangIso => $mIsoValue) {
					self::_replaceData($iId, $iRowId, $toColumnValue($mIsoValue), $sItemType, $sLangIso);
				}
			} else {
				self::_replaceData($iId, $iRowId, $toColumnValue($mValue), $sItemType);
			}

			$aBack[] = 'new';
		}
		
		return $aBack;
	}

	protected static function checkFieldCache($iId) {

		if(!isset(self::$_aFieldData[$iId])) {

			$sSql = "SELECT
						`kfsf`.*
					FROM
						`tc_flex_sections_fields` AS `kfsf`
					WHERE
						`kfsf`.`id` = ?
					LIMIT 1
					";

			$aSql = array();
			$aSql[]	= (int)$iId;

			$oDB        = DB::getDefaultConnection();
			$stmt       = $oDB->getPreparedStatement($sSql);
			$aFieldData = $oDB->fetchPreparedStatement($stmt, $aSql);
			$aFieldData = reset($aFieldData);

			self::$_aFieldData[$iId] = $aFieldData;

		}

		return self::$_aFieldData[$iId];
	}

	protected static function _replaceData($iFieldId, $iItemId, $mValue, $sItemType = '', $sLangIso = '') {

		$aSql = array(
			(int)$iFieldId,
			(int)$iItemId,
			$sItemType,
			$sLangIso
		);

		if(!static::checkIfEmptyValue($iFieldId, $mValue)) {

			$aSql[] = $mValue;

			$sSql = "
				REPLACE INTO
					`tc_flex_sections_fields_values`
				SET
					`field_id` = ?,
					`item_id` = ?,
					`item_type` = ?,
					`language_iso` = ?,
					`value` = ?
			";

		} else {

			$sSql = "
				DELETE FROM
					`tc_flex_sections_fields_values`
				WHERE
					`field_id` = ? AND
					`item_id` = ? AND
					`item_type` = ? AND
					`language_iso` = ?
			";

		}

		$oStmt = DB::getPreparedStatement($sSql);
		DB::executePreparedStatement($oStmt, $aSql);

	}

	/**
	 * Prüfen, ob Wert leer ist (in Bezug auf Feldtyp)
	 *
	 * @param int $iFieldId
	 * @param mixed $mValue
	 * @return bool
	 */
	protected static function checkIfEmptyValue($iFieldId, $mValue) {

		if(!isset(self::$_aFieldData[$iFieldId])) {
			throw new RuntimeException('Field is not in static cache!');
		}

		if(
			$mValue === null ||
			$mValue === '' || (
				// is_numeric abfragen da sonst auch Strings falsch geprüft werden z.B. 'no' == 0 => true
				is_numeric($mValue) &&
				$mValue == 0 && ( // Vielleicht auch '0'?
					self::$_aFieldData[$iFieldId]['type'] == self::TYPE_CHECKBOX || // Checkbox
					self::$_aFieldData[$iFieldId]['type'] == self::TYPE_SELECT || // Dropdown
					self::$_aFieldData[$iFieldId]['type'] == self::TYPE_YESNO // Ja/Nein
				)
			) || (
				// Mehrfachauswahl
				self::$_aFieldData[$iFieldId]['type'] == self::TYPE_MULTISELECT && (
					empty($mValue) ||
					$mValue === '[]'
				)
			)
		) {
			return true;
		}

		return false;

	}

	/**
	 * @param array $aItems
	 * @param array|string $mCategory
	 * @param bool|false $bIgnoreVisible
	 * @param bool|true $bFormatValues
	 * @return array
	 */
	public static function getListData($aItems, $mCategory, $bIgnoreVisible = false, $bFormatValues = true) {

		$sWhereAddon = "";
		
		$sLang		= System::getInterfaceLanguage();
		
		// Bei nicht sichtbarenFeldern dürfen diese auch nicht mit ausgelesen werden.
		// Hinweis: Where Addon wurde ergänzt und war früher im GROUP BY Teil -> schlecht
		if (!$bIgnoreVisible) {
			//$sWhereAddon = " AND `sgl`.`visible` = 1 ";
		}

		$sUsage = $sItemType = null;
		if(is_array($mCategory)) {
			$sSection = $mCategory[0];
			$sUsage = $mCategory[1];
			$sItemType = $mCategory[2];
		} else {
			$sSection = $mCategory;
		}
		
		if($sUsage !== null) {
			$sWhereAddon .= " AND `kfsf`.`usage` = :usage "; 
			$aSql['usage'] = $sUsage;
		}

		if($sItemType !== null) {
			$sWhereAddon .= " AND `kfsfv`.`item_type` = :item_type "; 
			$aSql['item_type'] = $sItemType;
		}
		
		$sSql = "
			SELECT
				`kfsfv`.`item_id`,
				`kfsfv`.`field_id`,
				`kfsfv`.`value`,
				`kfsfv`.`language_iso`,
				`kfsf`.`type`,
				`kfsf`.`id`,
				`kfsf`.`i18n`
			FROM
				`tc_flex_sections_fields_values` AS `kfsfv` INNER JOIN
				`tc_flex_sections_fields` AS `kfsf`
					ON `kfsf`.`id` = `kfsfv`.`field_id` INNER JOIN
				`tc_flex_sections` AS `kfs`
					ON `kfs`.`id` = `kfsf`.`section_id`
			WHERE
				`kfsfv`.`item_id` IN (:items) AND
				`kfsf`.`active` = 1 AND 
				`kfsf`.`visible` = 1 AND (
					`kfsfv`.`language_iso` = '' OR
					`kfsfv`.`language_iso` = :language_iso
				) AND (
					`kfs`.`category` = :category OR
					`kfs`.`type` = :category
				)
				{$sWhereAddon}
			GROUP BY
				`kfsfv`.`item_id`,
				`kfsfv`.`field_id`
		";

		$aSql['category'] = $sSection;
		$aSql['items'] = (array)$aItems;
		$aSql['language_iso'] = Ext_TC_System::getInterfaceLanguage();

		$oCollection = DB::getDefaultConnection()->getCollection($sSql, $aSql);

		$aBack = array();
		foreach($oCollection as $aField) {
			$sValue = $aField['value'];
			if($bFormatValues){
				// Bei Select feldern korrekten Wert holen
				if($aField['type'] == 5) {
					$aOptions = self::getOptions($aField['id'], $sLang);
					$sValue = $aOptions[$sValue];
				}	
			}
			
			if($sUsage !== null) {
				$mKey = $sUsage . '_' . $mKey;
			}
			
			$aBack[$aField['item_id']][$aField['field_id']] = $sValue;
		}

		return $aBack;

	}

	/**
	 * Funktion liefert den Inhalt eines Feldes anhand des Platzhalters und der ItemId
	 * 
	 * @todo Formattierung auslagern, bzw. vorhandene Methode verwenden
	 * @todo Die gesamte Methode ist ineffizient und muss ausgetauscht werden
	 *
	 * @param string $sPlaceholder
	 * @param int $iItemId
	 * @param bool $bComplete
	 * @param string $sLang
	 * @return mixed
	 */
	public static function getPlaceholderValue($sPlaceholder, $iItemId, $bComplete=false, $sLang = 'en', $sItemType=null) {

		// Texte laufen immer über Frontendübersetzungen
		$oLanguage = new Tc\Service\Language\Frontend($sLang);
		
		$sSql = "
			SELECT
				`kfsfv`.`value`, 
				`kfsfv`.`language_iso`, 
				`kfsf`.`type`, 
				`kfsf`.`id`, 
				`kfsf`.`i18n`
			FROM 
				`tc_flex_sections_fields_values` AS `kfsfv` INNER JOIN 
				`tc_flex_sections_fields` AS `kfsf`
					ON `kfsf`.`id` = `kfsfv`.`field_id` 
			WHERE 
				`kfsfv`.`item_id` = :item_id AND
				`kfsf`.`placeholder` = :placeholder AND
				`kfsf`.`active` = 1
		";

		$aSql = [
			'placeholder' => $sPlaceholder,
			'item_id' => $iItemId
		];
		
		if($sItemType !== null) {
			$sSql .= " AND `kfsfv`.`item_type` = :item_type ";
			$aSql['item_type'] = $sItemType;
		}
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		/*
		 * Wenn kein Wert gefunden wird, muss zumindest der Typ ermittelt werden.
		 * Da die Default-Werte nicht (mehr) in der Datenbank stehen (#7267), ist das zwingend notwendig,
		 * damit bspw. auch bei einer Checkbox weiterhin »nein« funktioniert.
		 */
		if(empty($aResult)) {

			// Eigener Query und kein LEFT JOIN oben, wegen Performance bei einer ggf. riesigen Tabelle
			$sSql = "
				SELECT
					`id`,
					`type`,
					NULL `value`
				FROM
					`tc_flex_sections_fields`
				WHERE
					`placeholder` = :placeholder
			";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

		} else {

			/*
			 * Auf I18N überprüfen und ggfls. Wert der Sprache ermitteln
			 */
			$aFirstValue = reset($aResult);
			if(!empty($aFirstValue['i18n'])) {
				foreach($aResult as $aValue) {
					if($aValue['language_iso'] === $oLanguage->getLanguage()) {
						$aResult = array($aValue);
						break;
					}
				}
			}

		}

		// Bei Select passenden Wert holen
		if(
			$aResult[0]['type'] == 5 ||
			$aResult[0]['type'] == 8
		) {
			$aOptions = self::getOptions($aResult[0]['id'], $sLang);

			if($aResult[0]['type'] == 5) {
				$aResult[0]['value'] = $aOptions[$aResult[0]['value']];
			} else {
				$aJson = (array)json_decode($aResult[0]['value']);
				$aValues = [];
				foreach($aOptions as $iOptionId => $sValue) {
					if(in_array($iOptionId, $aJson ?? [])) {
						$aValues[] = $sValue;
					}
				}

				$aResult[0]['value'] = join(', ', $aValues);
			}
		}

		if(!empty($aResult)) {

			if($bComplete) {

				return $aResult[0];

			} else {

				if($aResult[0]['type'] == 2) {
					// checkbox
					if($aResult[0]['value'] == 1){
						return $oLanguage->translate('Ja');
					}else{
						return $oLanguage->translate('Nein');
					}
				} elseif($aResult[0]['type'] == 7) {
					if($aResult[0]['value'] == 'yes') {
						return $oLanguage->translate('Ja');
					} elseif($aResult[0]['value'] == 'no') {
						return $oLanguage->translate('Nein');
					}
				} elseif($aResult[0]['type'] == 4) {
					// Date
					$oDummy = null;
					$aResultData = array();
					$oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
					return $oFormat->format($aResult[0]['value'], $oDummy, $aResultData);
				} elseif($aResult[0]['type'] == 5){
					// Select
					// TODO Anpassen wenn es irgendwo fehler gibt
					return $aResult[0]['value'];
				} else {
					return $aResult[0]['value'];
				}

			}

		}

	}

	/**
	 * Platzhalter => Kategorie
	 *
	 * @return array
	 */
	public static function getPlaceholderCategories() {

		return WDCache::remember(self::PLACEHOLDER_CATEGORIES_CACHE_KEY, 3600 * 24 * 7, function() {
			
			$sSql = "
				SELECT
					`tc_fsf`.`placeholder`,
					`tc_fs`.`category`
				FROM
					`tc_flex_sections_fields` `tc_fsf` INNER JOIN
					`tc_flex_sections` `tc_fs` ON
						`tc_fs`.`id` = `tc_fsf`.`section_id`
				WHERE
					`tc_fsf`.`active` = 1 AND
					`tc_fsf`.`placeholder` != ''
			";
			
			return (array)DB::getQueryPairs($sSql);
			
		});

	}

	/**
	 * Funktion liefert anhend eines Platzhalters die dazugeghörige category
	 * @param $sPlaceholder
	 * @return string
	 */
	public static function getPlaceholderCategory($sPlaceholder) {

		$aCategories = self::getPlaceholderCategories();

		if(array_key_exists($sPlaceholder, $aCategories)) {
			return $aCategories[$sPlaceholder];
		}

		return null;

	}
	
	public static function checkIfSectionExist($aLayoutData){		
		
		$sSql = "SELECT
					COUNT(*) `anzahl`
				FROM 
					`tc_flex_sections` AS `kfs` 					
				WHERE 
					`kfs`.`active` = 1 AND
					`kfs`.`category` = :category ";
					
		$aSql = array(
						'category'=>$aLayoutData['section']
					);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		
		if($aResult[0]['anzahl'] <= 0){
			return false;
		}
		
		return true;	
	}
	
	public static function getSectionFieldDataCacheKey(array $aSections, $bAsObject, $sFieldType){

		$sKey = json_encode($aSections);
	
		if($bAsObject){
			$sKey .= '_1';
		} else {
			$sKey .= '_0';
		}
		if($sFieldType){
			$sKey .= '_1';
		}else{
			$sKey .= '_0';
		}
		
		return $sKey;
	}

	/**
	 * Liefert alle Felddaten zu einer Section
	 *
	 * @TODO $bAsObject entfernen
	 *
	 * @param array $aSections
	 * @param bool $bAsObject
	 * @param bool $bOnlyVisible
	 * @return self[]|array[]
	 */
	public static function getSectionFieldData(array $aSections, $bAsObject = false, $bOnlyVisible = false) {

		if (empty($aSections)) {
			return [];
		}

		$aCache = self::$_aGetSectionFieldData;
		
		$sCacheKey = self::getSectionFieldDataCacheKey($aSections, $bAsObject, $bOnlyVisible);

		if(!isset($aCache[$sCacheKey])) {
			
			$aSql = array();
			$aWhereAddon = array();

			$aSections = array_values($aSections);
			
			foreach($aSections as $iKey=>$mSection) {

				$sWhereAddon = "";
				
				$sUsage = null;
				if(is_array($mSection)) {
					$sSection = $mSection[0];
					$sUsage = $mSection[1];
				} else {
					$sSection = $mSection;
				}

				$sWhereAddon .= "
								(
									(
										`kfs`.`category` = :section_".$iKey." OR
										`kfs`.`type` = :section_".$iKey."
									)";
				$aSql['section_'.$iKey] = $sSection;

				if($sUsage !== null) {
					$sWhereAddon .= " AND `kfsf`.`usage` = :usage_".$iKey." "; 
					$aSql['usage_'.$iKey] = $sUsage;
				}
				
				$sWhereAddon .= ")"; 
				
				$aWhereAddon[] = $sWhereAddon;
				
			}

			// Parameter wird nicht mehr benutzt und konnte auch nicht mehr funktionieren
//			if($sFieldType){
//				$sWhereAddon .= " AND `kfsf`.`type` = :field_type";
//
//				$aSql['field_type'] = $sFieldType;
//			}

			$sWhere = "";
			if($bOnlyVisible) {
				$sWhere = " AND `kfsf`.`visible` = 1 ";
			}

			$sSql = "SELECT
							`kfsf`.*
						FROM 
							`tc_flex_sections_fields` AS `kfsf` INNER JOIN
							`tc_flex_sections` AS `kfs`
								ON `kfs`.`id` = `kfsf`.`section_id`
						WHERE 
							`kfs`.`active` = 1 AND
							`kfsf`.`active` = 1 AND (".implode(" OR ", $aWhereAddon).")
							{$sWhere}
						GROUP BY
							`kfsf`.`id`
						ORDER BY 
							`kfsf`.`position`
						";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aBack = array();

			if($bAsObject){
				foreach((array)$aResult as $aData){
					$aBack[$aData['id']] = self::getObjectFromArray($aData);
				}
			} else {
				$aBack = $aResult;
			}
			
			$aCache[$sCacheKey] = $aBack;
			self::$_aGetSectionFieldData = $aCache;
		}

		return $aCache[$sCacheKey];
	}

	// Liefert die Optionen zu einem Selectfeld
	public static function getOptions($iFieldId, $sLang = 'en', $bWithSeparator = false) {

		// Sprache muss überprüft werden, da es ansonsten gar keine Übersetzung gibt (z.B. auf Japanisch eingeloggt)
		$aLanguages	= array_column(Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages', ['en']), 'iso');
		if (!in_array($sLang, $aLanguages)) {
			if (in_array('en', $aLanguages)) {
				$sLang = 'en';
			} else {
				$sLang = reset($aLanguages);
			}
		}

		$sKey = 'get_options_'.$iFieldId.'_'.$sLang.'_'.(int)$bWithSeparator;

		if(!isset(self::$aCache[$sKey])) {

			$aSql = [
				'field_id' => (int)$iFieldId,
				'lang_id' => $sLang,
			];

			$sSql = "
				SELECT
					`i18n_sort`
				FROM
					`tc_flex_sections_fields`
				WHERE
					`id` = :field_id					
				LIMIT
					0, 1
			";
			$bSortByTitle = (bool)DB::getQueryOne($sSql, $aSql);

			$sWhere = "";
			if($bWithSeparator === false) {
				$sWhere .= " AND `kfsfo`.`separator_option` = 0 ";
			}
			
			$sSql = "
				SELECT
					`kfsfo`.`id` `option_id`,
					`kfsfov`.`title`,
					`kfsfo`.`separator_option`
				FROM
					`tc_flex_sections_fields_options` `kfsfo` LEFT JOIN
					`tc_flex_sections_fields_options_values` `kfsfov` ON
						`kfsfov`.`option_id` = `kfsfo`.`id` AND
						`kfsfov`.`lang_id` = :lang_id
				WHERE
					`kfsfo`.`active` = 1 AND
					`kfsfo`.`field_id` = :field_id
					".$sWhere." 
				ORDER BY
					`kfsfo`.`position`
			";
			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aBack = [];
			$bHasSeparator = false;
			foreach((array)$aResult as $aData) {

				if($aData['separator_option'] == 1) {
					$aBack[Ext_TC_Flexible_Option::OPTION_SEPARATOR_KEY] = $aData['title'];	
					$bHasSeparator = true;
				} else {
					if(
						$aData['title'] === null || 
						$aData['title'] === ''
					) {
						$aData['title'] = '#'.$aData['option_id'].' TRANSLATION MISSING ('.strtoupper($sLang).')';
					}

					$aBack[$aData['option_id']] = $aData['title'];
				}
				
			}

			if(
				$bSortByTitle &&
				$bHasSeparator === false
			) {
				asort($aBack);
			}

			self::$aCache[$sKey] = $aBack;

		}

		return self::$aCache[$sKey];

	}

	/**
	 * Bei I18N-Feldern wird geschaut, ob es für ein Item ein Wert ohne Sprache gibt
	 * Bei normalen Feldern wird geschaut, ob es ein Item ohne Wert und Wert in der Defaultsprache gibt
	 */
	public function convertI18NValues() {

		$aObjectLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages');
		$aLanguages = array_keys($aObjectLanguages);
		$sFirstLanguageIso = reset($aLanguages);

		$aSql = array(
			'field_id' => (int)$this->id,
			'language_iso' => $sFirstLanguageIso
		);

		$sWhere = "";
		$sJoinOn = "";
		if($this->i18n) {
			
			$sWhere .= " AND `tc_fsfv`.`language_iso` = ''";
			$sJoinOn .= " AND `tc_fsfv_compare`.`language_iso` = :language_iso";
			
		} else {
			
			$sWhere .= " AND `tc_fsfv`.`language_iso` = :language_iso";
			$sJoinOn .= " AND `tc_fsfv_compare`.`language_iso` = ''";

		}

		/*
		 * Query holt alle Werte, die nicht der aktuellen Einstellung entsprechen, wenn es keinen Wert mit der richtigen
		 * Einstellung gibt.
		 */
		$sSql = "
			SELECT 
				`tc_fsfv`.*,
				`tc_fsfv_compare`.`value` `compare_value`
			FROM
				`tc_flex_sections_fields_values` `tc_fsfv` LEFT JOIN
				`tc_flex_sections_fields_values` `tc_fsfv_compare` ON
					`tc_fsfv`.`field_id` = `tc_fsfv_compare`.`field_id` AND
					`tc_fsfv`.`item_id` = `tc_fsfv_compare`.`item_id` AND
					`tc_fsfv`.`item_type` = `tc_fsfv_compare`.`item_type`
					".$sJoinOn."
			WHERE
				`tc_fsfv`.`field_id` = :field_id AND 
				`tc_fsfv`.`value` != ''
				".$sWhere." AND
				`tc_fsfv_compare`.`value` IS NULL
				";
		$aValues = DB::getQueryRows($sSql, $aSql);

		// Je nach Feldeinstellung wird der Wert mit oder ohne Sprache neu abgespeichert
		if(!empty($aValues)) {
			
			foreach($aValues as $aValue) {

				self::checkFieldCache($aValue['field_id']);
				
				if($this->i18n) {
					self::_replaceData($aValue['field_id'], $aValue['item_id'], $aValue['value'], (string)$aValue['item_type'], $sFirstLanguageIso);
				} else {
					self::_replaceData($aValue['field_id'], $aValue['item_id'], $aValue['value'], (string)$aValue['item_type']);
				}

			}

		}
		
	}
	
	/**
	 * holt den eingetragenen Wert für eine WDBasic
	 * @param WDBasic $oWDBasic
	 * @return mixed
	 */
	public function getValue(WDBasic $oWDBasic, $sLang = '') {

		$sSql = "
			SELECT
				`value` 
			FROM
			    `tc_flex_sections_fields_values`
			WHERE
				`field_id` = :field_id AND
				`item_id` = :item_id
		";
				
		$aSql = array(
			'field_id'	=> (int) $this->id,
			'item_id'	=> (int) $oWDBasic->id
		);
		
		if($oWDBasic instanceof Ext_TC_Basic) {
			$sSql .= " AND `item_type` = :item_type";
			$aSql['item_type'] = $oWDBasic->getEntityFlexType();
		}
		
		if(
			$this->i18n == 1 &&
			!empty($sLang)
		) {
			$sSql .= " AND language_iso = :language_iso";
			$aSql['language_iso'] = $sLang; 
		}
		
		$aData = (array) DB::getQueryOne($sSql, $aSql);

		$mReturn = '';
		if(!empty($aData)) {
			$mReturn = reset($aData);
		}

		if($this->isRepeatableContainer()) {
			// Alle Felder des wiederholbaren Bereichs abfragen und eine Struktur bringen [container_index => [field_id => value]]
			$aChildFields = $this->getChildFields();

			$mReturn = [];
			foreach($aChildFields as $oChildField) {

				$mChildValue = $oChildField->getValue($oWDBasic, $sLang);

				foreach($mChildValue as $iContainerIndex => $mContainerValue) {
					$mReturn[$iContainerIndex][$oChildField->getId()] = $mContainerValue;
				}
			}

		} else if($this->isChildField()) {
			if(!empty($mReturn)) {
				$mReturn = json_decode($mReturn, true);
			} else {
				$mReturn = [];
			}
		}

		return $mReturn;
	}
	
	/**
	 * Holt den eingetragenen Wert für eine WDBasic und gibt diesen formatiert zurück
	 * 
	 * @param WDBasic $oWDBasic
	 * @return mixed
	 */
	public function getFormattedValue(WDBasic $oWDBasic, $sLanguage) {

		if($this->isI18N()) {
			$mValue = $this->getValue($oWDBasic, $sLanguage);
		} else {
			$mValue = $this->getValue($oWDBasic);
		}

		if($this->isRepeatableContainer()) {

			$aChildFields = $this->getChildFields();

			foreach($mValue as $iContainerIndex => $aContainer) {
				foreach($aContainer as $iChildField => $mChildFieldValue) {
					if(is_array($mChildFieldValue)) {
						foreach($mChildFieldValue as $iLanguageIso => $mLanguageValue) {
							$mValue[$iContainerIndex][$iChildField][$iLanguageIso] = $this->formatValue($mLanguageValue, $sLanguage);
						}
					} else {
						$mValue[$iContainerIndex][$iChildField] = $aChildFields[$iChildField]->formatValue($mChildFieldValue, $sLanguage);
					}
				}
			}

		} else if(is_array($mValue)) {
			foreach($mValue as $iIndex => $mChildValue) {
				$mValue[$iIndex] = $this->formatValue($mChildValue, $sLanguage);
			}
		} else {
			$mValue = $this->formatValue($mValue, $sLanguage);
		}

		return $mValue;
	}
	
	public function getOption(WDBasic $oWDBasic) {

		$mValue = $this->getValue($oWDBasic);
				
		$oOption = Ext_TC_Flexible_Option::getInstance($mValue);

		return $oOption;
	}
	
	public function getOptionKey(WDBasic $oWDBasic) {
		
		$oOption = $this->getOption($oWDBasic);
		
		if($oOption instanceof Ext_TC_Flexible_Option) {
			return $oOption->key;
		}
		
	}
	
	/**
	 * Liefert den formatierten Wert für die Ausgabe des Platzhalters
	 *
	 * @param WDBasic $oParent
	 * @param WDBasic $oWDBasic
	 * @param string $sLanguage
	 * @return mixed
	 */
	public function getFormattedPlaceholderValue(WDBasic $oParent, WDBasic $oWDBasic, $sLanguage) {
		return $this->getFormattedValue($oWDBasic, $sLanguage);
	}
	
	/**
	 * Formattiert je nach Typ einen Wert
	 * 
	 * @param mixed $mValue
	 * @param string $sLanguage
	 * @return mixed
	 */
	public function formatValue($mValue, $sLanguage, bool $toString=true) {

		// Bei Select passenden Wert holen
		switch($this->type) {
			case 5:
				// Select
				$aOptions = self::getOptions($this->id, $sLanguage);
				$mValue = $aOptions[$mValue];
				if(
					$this->i18n_sort &&
					is_array($mValue)
				) {
					asort($mValue);
				}
				break;
			case 8:
				// Select
				$aOptions = self::getOptions($this->id, $sLanguage);

				if (empty($mValue)) {
					$aJson = [];
				} else if(is_array($mValue)) {
					$aJson = $mValue;
				} else {
					$aJson = json_decode($mValue);
				}

				$aValues = [];
				if (is_array($aJson)) {
					foreach($aOptions as $iOptionId => $sValue) {
						if(in_array($iOptionId, $aJson ?? [])) {
							$aValues[] = $sValue;
						}
					}
				}

				if(
					$this->i18n_sort &&
					is_array($aValues)
				) {
					asort($aValues);
				}
				
				if($toString) {
					$mValue = join(', ', $aValues);
				} else {
					$mValue = $aValues;
				}
				
				break;
			case 2:
				// checkbox
				if($mValue == 1){
					return Ext_TC_Placeholder_Abstract::translateFrontend('Ja', $sLanguage);
				}else{
					return Ext_TC_Placeholder_Abstract::translateFrontend('Nein', $sLanguage);
				}
				break;
			case 4:
				// Date
				$oDummy = null;
				$aResultData = array();
				$oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
				/** @var Ext_TC_Gui2_Format_Date $oFormat */
				$mValue = $oFormat->format($mValue, $oDummy, $aResultData);
				break;
			default:
				break;
		}

		return $mValue;
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		System::wd()->executeHook(self::HOOK_VALIDATE, $mValidate, $this);

		if($mValidate === true) {

			// Dieses Gewurschtel mit ID/String ist furchtbar
			$oSection = Ext_TC_Flexible_Section::getInstance($this->section_id);
			if(!$oSection->exist()) {
				throw new RuntimeException('No valid section for flex field');
			}

			$aFields = $this->getFields($oSection->type);

			// Maximale Felder pro Bereich prüfen und ggf. anlegen verhindern
			if(
				!$this->exist() &&
				count($aFields) >= System::d('tc_flex_fields_per_section_limit', self::FIELD_LIMIT_PER_SECTION)
			) {
				return ['id' => ['TOO_MANY_FIELDS']];
			}

			// Da leider das Limit schon per Tools für alles auf einen beliebigen Wert gesetzt werden kann…
			$aLimits = Factory::executeStatic(get_class($this), 'getFixFieldSectionLimits');
			if(
				!$this->exist() &&
				isset($aLimits[$oSection->type]) &&
				count($aFields) >= $aLimits[$oSection->type]
			) {
				return ['id' => ['TOO_MANY_FIELDS']];
			}

			$aFieldsVisible = array_filter($aFields, function(self $oField) {
				return $oField->id != $this->id && $oField->visible;
			});

			// Anzahl von Felder, die in der GUI angezeigt werden können, beschränken
			if(
				!$this->exist() &&
				$this->visible &&
				count($aFieldsVisible) >= System::d('tc_flex_fields_per_section_visible_limit', self::FIELD_LIMIT_PER_SECTION / 2)
			) {
				return ['visible' => 'TOO_MANY_FIELDS_VISIBLE'];
			}

		}

		return $mValidate;

	}

	public function save($bLog = true) {

		$this->iIdBeforeSave = $this->id;
		
		$mReturn = parent::save($bLog);

		// Index-Mapping aktualisieren
		if($this->visible) {
			// TODO Das müsste eigentlich nicht für jeden Index gemacht werden
			foreach(array_keys(\Ext_TC_System_Tools::getToolsService()->getIndexes()) as $sIndex) {
				try {
					$oGenerator = new Ext_Gui2_Index_Generator($sIndex);
					$oGenerator->setMapping();
				} catch(Throwable $e) {
					// Diverse Fehler wie z.B. yml-Datei fehlt
				} catch(Elastica\Exception\ResponseException $e) {
					// mapper [xyz] of different type, ES 5 Problem mit legacy string vs. text/keyword
				}
			}
		}

		WDCache::delete(self::PLACEHOLDER_CATEGORIES_CACHE_KEY);
		WDCache::deleteGroup(Ext_TC_Gui2_Filterset_Bar_Element::TC_GUI2_FILTERSET_FLEX_FIELDS);
		
		return $mReturn;

	}
	
	/**
	 *
	 * @return Ext_TC_Flexible_Section
	 */
	public function getSection()
	{
		$oFlexSection = Ext_TC_Flexible_Section::getInstance($this->section_id);
		
		return $oFlexSection;
	}

	public static function getSectionAllocations() {
		return array();
	}
	
	/**
	 * Generiert eine eindeutige ID(Hash) die für den Designer benätigt wird
	 * 
	 * @param bool $bSkipId
	 * @return type 
	 */
	public function generateDesignerID($bSkipId=false) {

		$sBack = 'element_';
		if($bSkipId === false){
			$sBack = md5('flexibility_'.$this->id);
		} else {
			$sBack .= md5('flexibility_'.$this->id);
		}

		return $sBack;
	}
	
	public function getName() {
		return $this->title;
	}	
	
	/**
	 * gibt die aktuellen daten zuräck
	 * @return type 
	 */
	public function getArray() {
		$aData = $this->_aData;
		
		$oGuiDesignerSection = Ext_TC_Flexible_Section::getGuiDesignerSection();
		
		if($this->section_id == $oGuiDesignerSection->id) {
			$aData['allowed_parent'] = $this->allowed_parent;
		}
	
		return $aData;
	}

	public function getDialogRow($iLoopId, Ext_Gui2_Dialog $oDialog, $iSelectedId, Ext_TC_Gui2 $oGui, $sFieldIdentifierPrefix, $iReadOnly, $iDisabled = 1) {

		$sLang = (string)System::getInterfaceLanguage();

		$mValue = '';
		
		$aHookData = [
			'loop_id' => $iLoopId,
			'field_id' => $this->id,
			'value' => $mValue
		];

		System::wd()->executeHook('tc_edit_dialog_data_flexvalue_dialog_row', $aHookData);

		$mValue = $aHookData['value'];
		
		/** @var Ext_TC_Gui2_Data $oData */
		$oData = $oGui->getDataObject();
		$sHtml = $oData->generateFlexEditDataField($oDialog, $this, $iSelectedId, $mValue, $sLang, $iReadOnly, $sFieldIdentifierPrefix, array(), $iDisabled);

		$oRow = new Ext_Gui2_Html_Div;
		$oRow->setElement($sHtml);
			
		return $oRow;		
	}

	/**
	 * Liefert für den Usage eines individuellen Feldes alle erlaubten Sections des GUI Designer
	 * 
	 * [
	 *		'usage' => ['section1', 'section2']
	 * ]
	 * 
	 * @return array
	 */
	public static function getFieldUsageSectionMapping() {
		return [];
	}

	/**
	 * @return int[]
	 */
	public static function getFixFieldSectionLimits() {
		return [];
	}

	/**
	 * Sections, die in keiner GUI angezeigt werden, damit die entsprechende Checkbox ausgeblendet wird
	 *
	 * @return int[]
	 */
	public static function getFieldSectionIdsWithoutListView() {
		return [];
	}

	/**
	 * Sections, die Platzhalter haben, damit entsprechender Bereich angezeigt wird
	 *
	 * @return array
	 */
	public static function getFieldSectionsWithPlaceholders() {
		return [];
	}
	
	public static function getCategoryUsage(Ext_Gui2 $gui) {
		return [];
	}
	
}
