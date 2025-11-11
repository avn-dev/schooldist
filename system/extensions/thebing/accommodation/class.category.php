<?php

use FileManager\Traits\FileManagerTrait;

/**
 * @property int $id
 * @property int $active
 * @property string $valid_until YYYY-MM-DD
 * @property int $creator_id Ersteller
 * @property int $changed Timestamp
 * @property int $user_id Bearbeiter
 * @property int $created Timestamp
 * @property int $type_id
 * @property int $position
 * @property string $arrival_time HH:MM:SS
 * @property string $departure_time HH:MM:SS
 * @property int $max_extra_nights_prev
 * @property int $max_extra_nights_after
 * @property string $name_LANG pro verfügbarer Sprache, siehe Ext_Thebing_Accommodation_Category::getName()
 * @property string $short_LANG pro verfügbarer Sprache, siehe Ext_Thebing_Accommodation_Category::getShortName()
 * @property ?string $accommodation_start
 * @property int $inclusive_nights
 */
class Ext_Thebing_Accommodation_Category extends Ext_TS_Inquiry_Journey_Accommodation_Info_Abstract {

	use FileManagerTrait, \Tc\Traits\Placeholder;

	const TYPE_OTHERS = 0;
	const TYPE_HOSTFAMILY = 1;
	const TYPE_PARKING = 2;

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_accommodations_categories';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kac';

	protected $_sEditorIdColumn = 'editor_id';
	
	/**
	 * @var mixed[]
	 */
	protected $_aFormat = [
		'arrival_time' => [
			'format' => 'TIME',
			'validate' => 'TIME',
		],
		'departure_time' => [
			'format' => 'TIME',
			'validate' => 'TIME',
		],
		'max_extra_nights_prev' => [
			'validate'	=> 'INT_NOTNEGATIVE',
		],
		'max_extra_nights_after' => [
			'validate'	=> 'INT_NOTNEGATIVE',
		],
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'accommodation_costs' => [
			'table' => 'kolumbus_costs_accommodations',
			'foreign_key_field' => '',
			'primary_key_field' => 'customer_db_8_id',
			'on_delete' => 'no_action'
		],
//		'schools' => [
//			'table' => 'ts_accommodation_categories_schools',
//			'foreign_key_field' => 'school_id',
//			'primary_key_field' => 'accommodation_category_id',
//			'on_delete' => 'no_action'
//		],
		'requirements' => [
			'table' => 'ts_accommodation_categories_to_requirements',
			'foreign_key_field' => 'requirement_id',
			'primary_key_field' => 'accommodation_category_id',
			'on_delete' => 'delete'
		],
		'pdf_templates' => [
			'table' => 'kolumbus_pdf_templates_services',
			'class' => 'Ext_Thebing_Pdf_Template',
			'primary_key_field' => 'service_id',
			'foreign_key_field' => 'template_id',
			'static_key_fields'	=> ['service_type' => 'accommodation'],
			'autoload' => false
		]
	];

	protected $_aJoinedObjects = array( 
        'school_settings' => array(
			'class'	=> 'TsAccommodation\Entity\Provider\SchoolSetting',
			'key' => 'category_id',
			'type' => 'child',
			'query' => true
        )
	);
	
	/**
	 * @var mixed[]
	 */
	protected $_aFlexibleFieldsConfig = [
		'accommodations' => [],
	];

	protected $_aAttributes = [
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	protected $_sPlaceholderClass = \TsAccommodation\Service\Placeholder\CategoryPlaceholder::class;

	public function isParking(): bool {
	    return ((int)$this->type_id === self::TYPE_PARKING);
    }

	/**
	 * @TODO Es gibt hier vier Methoden, die nahezu dasselbe machen (auch bei Roomtype und Meal)
	 * @TODO $checkValid wurde erst spät eingebaut und deswegen wird der Parameter selten benutzt
	 * @TODO (->alle Vorkommen checken und eventuell $checkValid = true hinzufügen)
	 *
	 * Gibt eine Liste mit Einträgen zurück, die der angegebenen Schule zugewiesen sind.
	 *
	 * Die Keys sind die IDs der Einträge, das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation_Category[]
	 */
	public static function getListBySchool(Ext_Thebing_School $oSchool, $checkValid = false) {

		# #20547
		$whereAddOn = '';
		if ($checkValid) {
			$whereAddOn = ' AND (`kac`.`valid_until` >= CURDATE() OR `kac`.`valid_until` = 0000-00-00)';
		}

		$sSql = "
			SELECT
				`kac`.*
			FROM
				`kolumbus_accommodations_categories` `kac` JOIN
				`ts_accommodation_categories_settings` `ts_acs` ON
					`ts_acs`.`category_id` = `kac`.`id` JOIN
				`ts_accommodation_categories_settings_schools` `ts_acss` ON
					`ts_acs`.`id` = `ts_acss`.`setting_id`
			WHERE
				`kac`.`active` = 1 AND
				`ts_acss`.`school_id` = :school_id".$whereAddOn."
			GROUP BY
				`kac`.`id`
			ORDER BY
				`kac`.`position` ASC,
				`kac`.`id` ASC
		";

		$aResult = (array)DB::getQueryRowsAssoc($sSql, ['school_id' => (int)$oSchool->id]);

		array_walk($aResult, function(&$aRow, $iId) {
			$aRow['id'] = $iId;
			$aRow = static::getObjectFromArray($aRow);
		});

		return $aResult;
	}

	/**
	 * @TODO Es gibt hier vier Methoden, die nahezu dasselbe machen (auch bei Roomtype und Meal)
	 *
	 * Gibt eine Liste mit Einträgen zurück die für mindestens eine ($bMatchAllSchools = false)
	 * oder alle ($bMatchAllSchools = true) der angegebenen Schulen gültig sind.
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * Wenn keine Schulen angegeben sind ($aSchoolIds leer), wird eine leere Liste zurück gegeben.
	 *
	 * @param int[] $aSchoolIds
	 * @param bool $bForSelect
	 * @param string $sLanguage
	 * @param bool $bMatchAllSchools
	 * @return mixed[]
	 */
	public static function getListForSchools(array $aSchoolIds, $bForSelect = true, $sLanguage = null, $bMatchAllSchools = false) {

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$aCategories = self::getList(false, $sLanguage);
		$aResult = [];

		if(empty($aSchoolIds)) {
			return $aResult;
		}

		foreach($aCategories as $aCategory) {

			$aCategoryAvailableSchoolIds = explode(',', $aCategory['schools']);

			if($bMatchAllSchools) {
				foreach($aSchoolIds as $iSchoolId) {
					if(!in_array($iSchoolId, $aCategoryAvailableSchoolIds)) {
						continue 2;
					}
				}
				$aResult[] = $aCategory;
			} else {
				foreach($aSchoolIds as $iSchoolId) {
					if(in_array($iSchoolId, $aCategoryAvailableSchoolIds)) {
						$aResult[] = $aCategory;
						break;
					}
				}
			}

		}

		if(!$bForSelect) {
			return $aResult;
		}

		$aBack = [];
		foreach ($aResult as $aData) {
			$aBack[$aData['id']] = $aData['name_'.$sLanguage];
		}
		return $aBack;

	}

	/**
	 * @TODO Es gibt hier vier Methoden, die nahezu dasselbe machen (auch bei Roomtype und Meal)
	 *
	 * Gibt die Unterkunftskategorien für die angegebene oder aktuell ausgewählte Schule als Select-Optionen zurück.
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param bool $bEmptyItem
	 * @param string $sLanguage
	 * @param Ext_Thebing_School $oSchool
	 * @return mixed[]
	 */
	public static function getSelectOptions($bEmptyItem = true, $sLanguage = null, Ext_Thebing_School $oSchool = null, $checkValid = false) {

		if(!($oSchool instanceof Ext_Thebing_School)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		if(!$sLanguage) {
			$sLanguage = $oSchool->getInterfaceLanguage();
		}
		if(empty($sLanguage)) {
			$sLanguage = 'en';
		}

		/*
		 * Falls keine Schule da ist, werden alle Kategorien zurückgegeben. Ist nicht sinnvoll, aber notwendig für
		 * \Ext_Thebing_School_Gui2_Selection_SepaColumns::getOptions()
		 */
		if(!$oSchool->exist()) {
			$aCategories = self::getList(true, $sLanguage);
		} else {

			$aCategories = Ext_Thebing_Accommodation_Category::getListBySchool($oSchool, $checkValid);
			$aCategories = array_map(
				function(Ext_Thebing_Accommodation_Category $oCategory) use ($sLanguage) {
					return $oCategory->getName($sLanguage);
				},
				$aCategories
			);

		}

		if($bEmptyItem) {
			$aCategories = Ext_Thebing_Util::addEmptyItem($aCategories);
		}

		return $aCategories;

	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($sField) {

		Ext_Gui2_Index_Registry::set($this);

		switch($sField) {
			case 'schools':
				$schools = [];
				$settings = $this->getJoinedObjectChilds('school_settings');
				foreach($settings as $setting) {
					$schools += (array)$setting->schools;
				}
				return $schools;

		}

		return parent::__get($sField);

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {
		
		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `ts_acss`.`school_id`) `schools`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`ts_accommodation_categories_settings_schools` `ts_acss` ON
				`school_settings`.`id` = `ts_acss`.`setting_id`
		";
		
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $sLanguage
	 * @param bool $bShort
	 */
	public function getName($sLanguage = '', $bShort = false) {

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$sColumn = 'name_'.$sLanguage;

		if($bShort) {
			$sColumn = 'short_'.$sLanguage;
		}

		if(!isset($this->_aData[$sColumn])) {
			if($bShort) {
				$sColumn = 'short_en';
			} else {
				$sColumn = 'name_en';
			}
		}

		return $this->$sColumn;

	}

	/**
	 * @param string $sLanguage
	 * @return string
	 */
	public function getShortName($sLanguage = '') {
		return $this->getName($sLanguage, true);
	}

	/**
	 * Liefert alle Zusatzkosten die der Unterkunftskategorie zugeordnet sind.
	 *
	 * Achtung: Zusatzkosten haben eine Schule, aber Unterkunftskategorien können mehrere Schulen haben!
	 *
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getAdditionalCosts(Ext_Thebing_School $school = null) {

		$aReturn = [];

		foreach((array)$this->accommodation_costs as $aData) {

			$oObject = Ext_Thebing_School_Additionalcost::getInstance($aData['kolumbus_costs_id']);

			if(
				$oObject->active == 1 &&
				$oObject->type == 1
			) {
				if (
					(
						$oObject->valid_until == '0000-00-00' ||
						new DateTime($oObject->valid_until) >= new DateTime()
					) &&
					(
						$school === null ||
						$oObject->idSchool == $school->id
					)
				) {
					$aReturn[$oObject->id] = $oObject;
				}
			}

		}

		// Kombinationen werden nicht benötigt
		return array_values($aReturn);

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _getInfoKey() {
		return 'accommodation_id';
	}

	/**
	 * @return string
	 */
	public function getMatchingType() {

		if($this->type_id == 1) {
			return 'host_family';
		}

		return 'residential';

	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			// Klick auf All + verhindern
			if(count($this->pdf_templates) > System::d('ts_max_attached_additional_docments', Ext_Thebing_Document::MAX_ATTACHED_ADDITIONAL_DOCUMENTS)) {
				$mValidate = ['pdf_templates' => 'TOO_MANY'];
			}

			// SpeakUp hat es mit 30 Tagen in diversen Kategorien so übertrieben, dass der RAM mehr als voll war (#13866)
			if($this->max_extra_nights_prev > 6) {
				$mValidate = ['kac.max_extra_nights_prev' => 'TO_HIGH'];
			}
			if($this->max_extra_nights_after > 6) {
				$mValidate = ['kac.max_extra_nights_after' => 'TO_HIGH'];
			}
		}

		return $mValidate;

	}

	/**
	 * {@inheritdoc}
	 */
	public function save($bLog = true) {

		parent::save($bLog);

		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
		$oStackRepository->writeToStack('ts-accommodation/requirements-status', ['category_id' => $this->id], 1);

		return $this;
	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'An accommodation category can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'An accommodation category can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @return bool
	 */
	public function belongsToSchool(Ext_Thebing_School $oSchool) {

		return $this->getSetting($oSchool) !== null;

	}

	/**
	 * Pfad überschreiben, damit nicht ext_thebing_accommodation_category verwendet wird
	 *
	 * @return string
	 */
	public function getFileManagerEntityPath(): string {
		// Abhängigkeit auf proxy.fidelo.com!
		return \Util::getCleanFilename('Ts\Accommodation\Category');
	}

	/**
	 * @return boolean
	 */
	public function hasNightPrice(Ext_Thebing_School $school) {
		
		$setting = $this->getSetting($school);
		
		if(
			$setting->price_night == Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT ||
			$setting->price_night == Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT_WEEKS
		) {
			return true;
		}
		
		return false;
	}

	public function getSetting(Ext_Thebing_School $school) {
		
		$settings = $this->getJoinedObjectChilds('school_settings', true);
		
		foreach($settings as $setting) {
			if(in_array($school->id, $setting->schools)) {
				return $setting;
			}
		}

		return null;

	}

	public static function getTypeOptions(\Tc\Service\LanguageAbstract $language): array {
		return [
			self::TYPE_OTHERS => $language->translate('Andere'),
			self::TYPE_HOSTFAMILY => $language->translate('Gastfamilie'),
			self::TYPE_PARKING => $language->translate('Parkplatz')
		];
	}

	public function getAccommodationStart(\Ext_Thebing_School $school): string
	{
		if ($this->accommodation_start !== null) {
			return $this->accommodation_start;
		}

		return $school->accommodation_start;
	}

	public function getAccommodationInclusiveNights(\Ext_Thebing_School $school): int
	{
		if ($this->inclusive_nights !== null) {
			return (int)$this->inclusive_nights;
		}

		return (int)$school->inclusive_nights;
	}
}
