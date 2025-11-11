<?php

/**
 * @TODO Umbenennen zu CourseLanguage
 *
 * @property int|string $id
 * @property string $changed  	
 * @property string $created 	
 * @property int $active 	
 * @property int $creator_id 	
 * @property int $user_id 	
 * @property int $school_id 	
 * @property string $frontend_icon_class
 * @property string $language_iso
 * @method static \Ext_Thebing_Tuition_LevelGroupRepository getRepository()
 */
class Ext_Thebing_Tuition_LevelGroup extends Ext_Thebing_Basic {

	use FileManager\Traits\FileManagerTrait;
	
	protected $_sTable = 'ts_tuition_courselanguages';

	protected $_sTableAlias = 'ktlg';

	protected $_aFormat = array(
		'title' => array(
			'required' => true
		),
//		'school_id' => array(
//			'required' => true,
//			'validate' => 'INT_POSITIVE'
//		),
	);

	protected $_aJoinTables = [
		'tuition_classes' => [
			'table' => 'kolumbus_tuition_classes',
			'class' => \Ext_Thebing_Tuition_Class::class,
			'foreign_key_field' => 'id',
			'primary_key_field' => 'courselanguage_id',
			'check_active' => true,
			'delete_check' => true,
			'readonly' => true,
			'autoload' => false
		],
		'inquiry_courses' => [
			'table' => 'ts_inquiries_journeys_courses',
			'class' => \Ext_TS_Inquiry_Journey_Course::class,
			'foreign_key_field' => 'id',
			'primary_key_field' => 'courselanguage_id',
			'check_active' => true,
			'delete_check' => true,
			'readonly' => true,
			'autoload' => false
		]
	];

	protected $_aAttributes = [
		'frontend_icon_class' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	protected $_aFlexibleFieldsConfig = [
		'tuition_course_languages' => [],
	];
	
	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\CourseLanguage::class;
	
	public function getName($sIso = null) {
		// @TODO Das ergibt doch gar kein Sinn mehr mit der Schule oder? Man kann die nirgend wo einstellen und man sieht
		// auch alle Kurssprachen in der Liste, das war glaub ich damals so?
		if ($sIso === null) {
			$school = Ext_Thebing_School::getInstance($this->school_id);
			$sIso = $school->getInterfaceLanguage();
		}

		$sField = 'name_'.$sIso;

		return $this->$sField;

	}

	/**
	 * @deprecated
	 * @return string[]
	 */
	public static function getSelectOptions(Ext_Gui2 $gui2 = null, string $language = null, array $schools = []) {

		/** @var self[] $aLevelGroups */
		if (!empty($schools)) {
			$aLevelGroups = self::getRepository()->findUsedBySchools($schools);
		} else {
			$aLevelGroups = self::getRepository()->findAll();
		}

		$aOptions = [];
		foreach ($aLevelGroups as $oLevelGroup) {
			$aOptions[$oLevelGroup->id] = $oLevelGroup->getName($language);
		}

		return $aOptions;

	}

}