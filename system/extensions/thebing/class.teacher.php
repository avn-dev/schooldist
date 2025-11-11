<?php

use Communication\Interfaces\Model\CommunicationContact;
use Communication\Interfaces\Model\CommunicationSubObject;
use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Interfaces\Entity\DocumentRelation;
use TsPrivacy\Interfaces\Entity as PrivacyEntity;
use TsTuition\Enums\TimeUnit;

/**
 * @property-read	$id	
 * @property		$idSaison	
 * @property		$active	
 * @property		$valid_until	
 * @property		$creator_id	
 * @property		$username	
 * @property		$password	
 * @property		$email	
 * @property		$phone	
 * @property		$mobile_phone	
 * @property		$firstname	
 * @property		$lastname	
 * @property		$name_of_bank	
 * @property		$adress_of_bank	
 * @property		$account_number	
 * @property		$account_holder	
 * @property		$course_cats	
 * @property		$course_intens	
 * @property		$work_from	
 * @property		$work_to	
 * @property		$street	
 * @property		$additional_address	
 * @property		$zip	
 * @property		$city	
 * @property		$country_id	
 * @property		$changed	
 * @property		$created	
 * @property		$birthday	
 * @property		$socialsecuritynumber	
 * @property		$gender	
 * @property		$nationality	
 * @property		$mother_tongue	
 * @property		$state	
 * @property		$skype	
 * @property		$fax	
 * @property		$phone_business	
 * @property		$comment
 * @property string $iban
 * @property 		$access_rights
 */
class Ext_Thebing_Teacher extends Ext_Thebing_Basic implements PrivacyEntity, DocumentRelation, HasCommunication, CommunicationContact {
	use \FileManager\Traits\FileManagerTrait,
		\Illuminate\Notifications\RoutesNotifications,
		\Ts\Traits\Entity\HasDocuments,
		\Tc\Traits\Username {
		\Tc\Traits\Username::generateUsername as traitGenerateUsername;
	}

	protected $_sTable = 'ts_teachers';
	protected $_sTableAlias = 'kt';

	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\Teacher::class;

	/** @var int Stundenplansrecht */
	const ACCESS_TIMETABLE = 1;

	/** @var int Anwesenheitsrecht */
	const ACCESS_ATTENDANCE = 2;

	/** @var int Kommunikationsrecht */
	const ACCESS_COMMUNICATION = 4;

	/** @var int Recht für den Zugriff auf alle Klassen und Zuweisungen */
	const ACCESS_TEACHERS = 8;

	/** @var int Reportcards-Recht */
	const ACCESS_REPORTCARDS = 16;

	/** @var int Recht um Klassen zu erstellen */
	const ACCESS_CLASS_SCHEDULING = 32;

	const ACCESS_CLASS_SCHEDULING_EDIT = 64;

	/** @var int Recht um Abwesenheit zu entschuldigen */
	const ACCESS_EXCUSED_ABSENCE = 128;

	const CACHE_SCHOOL_LIST = 'ts_teacher_school_list';

	public $usernameColumn = 'username';

	protected $_aFormat = array(
		'firstname' => array(
			'required'	=> true
			),
		'lastname' => array(
			'required'	=> true
			),
		'birthday' => array(
			'validate'	=> 'DATE_PAST'
			),
		'username' => array(
			'required'	=> true,
			'validate'	=> 'UNIQUE',
			),
		'iban' => [
			'validate' => 'IBAN'
		],
		'email' => [
			'validate' => ['MAIL', 'UNIQUE']
		],
		'zip' => [
			'validate'    => 'ZIP',
			'parameter_settings' => [
				'type' => 'field',
				'source' => 'country_id'
			]
		],
		'phone' => [
			'validate'    => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'field',
				'source' => 'country_id'
			]
		],
		'phone_business' => [
			'validate'    => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'field',
				'source' => 'country_id'
			]
		],
		'mobile_phone' => [
			'validate'    => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'field',
				'source' => 'country_id'
			]
		],
		'fax' => [
			'validate'    => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'field',
				'source' => 'country_id'
			]
		],
	);

	protected $_aJoinTables = array(
		'rooms'=>array(
			'table'=>'kolumbus_tuition_blocks',
			'class'=>'Ext_Thebing_School_Tuition_Block',
			'primary_key_field'=>'teacher_id',
			'autoload'=>false,
			'check_active'=>true,
			'delete_check'=>true,
			'on_delete' => 'no_purge'
		),
		'levels'=>array(
			'table'=>'kolumbus_teacher_levels',
			'primary_key_field'=>'teacher_id',
			'autoload'=>false,
			'foreign_key_field' => 'level_id',
			'class'=> 'Ext_Thebing_Tuition_Level',
			'on_delete' => 'no_action'
		),
		'course_categories'=>array(
			'table'=>'kolumbus_teacher_courses',
			'primary_key_field'=>'teacher_id',
			'autoload'=>false,
			'foreign_key_field' => 'course_id',
			'class'=> 'Ext_Thebing_Tuition_Course_Category',
			'on_delete' => 'no_action'
		),
		'course_languages'=>array(
			'table'=>'ts_teachers_courselanguages',
			'primary_key_field'=>'teacher_id',
			'autoload'=>false,
			'foreign_key_field' => 'courselanguage_id',
			'class'=> 'Ext_Thebing_Tuition_LevelGroup',
			'on_delete' => 'no_action'
		),
		'contracts'=>array(
			'table'=>'kolumbus_contracts',
			'primary_key_field'=>'item_id',
			'autoload'=>false,
			'check_active'=>true,
			'static_key_fields' => [
				'item' => 'teacher'
			],
			'on_delete' => 'no_action'
		),
		'absence' => [ // Purge
			'table' => 'kolumbus_absence',
			'primary_key_field' => 'item_id',
			'autoload' => false,
			'check_active' => true,
			'static_key_fields' => [
				'item' => 'teacher'
			],
			'on_delete' => 'no_action'
		],
		'placementtest_results' => [ // Purge
			'table' => 'ts_placementtests_results_teachers',
			'primary_key_field' => 'teacher_id',
			'foreign_key_field' => 'placementtest_result_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'schools' => [
			'table' => 'ts_teachers_to_schools',
			'primary_key_field' => 'teacher_id',
			'foreign_key_field' => 'school_id'
		]
	);
	
	protected $_aJoinedObjects = array(
        'salary' => array(
			'class'					=> 'Ext_Thebing_Teacher_Salary',
			'key'					=> 'teacher_id',
			'check_active'			=> true,
			'type'					=> 'child',
			'on_delete' => 'cascade'
        ),
//		'accounting_payments_active' => array(
//			'class' => 'Ext_Thebing_Teacher_Payment',
//			'key' => 'teacher_id',
//			'check_active' => true,
//			'type' => 'child'
//        ),
		 'schedule' => array(
				'class'					=> 'Ext_Thebing_Teacher_Schedule',
				'key'					=> 'idTeacher',
				'check_active'			=> true,
				'type'					=> 'child'
        ),
		'payed_payment_groupings' => array( // Methode!
			'class' => 'Ext_TS_Accounting_Provider_Grouping_Teacher',
			'key' => 'teacher_id',
			'check_active' => true,
			'type' => 'child'
		),
		'comments' => [ // Purge
			'class' => 'Ext_Thebing_Teacher_Comments',
			'key' => 'teacher_id',
			//'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'uploads' => [ // Purge
			'class' => 'Ext_Thebing_Teacher_Upload',
			'key' => 'teacher_id',
			//'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		]
    );
	
	protected $_aFlexibleFieldsConfig = [
		'teachers_bank' => [],
		'teachers_general' => [],
		'teachers_qualification' => []
	];

	public function  __set($sName, $sValue) {

		// Ein leeres Passwort darf nicht gespeichert werden
		if(
			$sName == 'password' &&
			empty($sValue)
		) {

		} elseif(strpos($sName, 'access_right_') === 0) {

			$sKey = str_replace('access_right_', '', $sName);

			$iBit = constant('self::ACCESS_'.strtoupper($sKey));

			if(empty($sValue)) {
				$this->access_rights &= ~$iBit;
			} else {
				$this->access_rights |= $iBit;
			}

		} else {
			parent::__set($sName, $sValue);
		}

	}

	public function  __get($sName) {

		if($sName == 'name') {
			if($this->_aData['id'] > 0) {
				$oFormat = new Ext_Thebing_Gui2_Format_TeacherName();
				$sValue = $oFormat->format('', $aDummy, $this->_aData);
			} else {
				$sValue = '';
			}
		} elseif(strpos($sName, 'access_right_') === 0) {

			$sKey = str_replace('access_right_', '', $sName);

			$iBit = constant('self::ACCESS_'.strtoupper($sKey));

			$sValue = $iBit & $this->access_rights;

		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	public function hasAccessRight(string $bit): bool {
		return \Core\Helper\BitwiseOperator::has($this->access_rights, $bit);
	}

	public function getBirthday() {
		if(
			$this->birthday > 0
		) {
			$iBirthday = $this->birthday;
		} else {
			$iBirthday = 0;
		}
		return $iBirthday;
	} 
	
	public function getSchool() {
		
		$iFirstSchool = reset($this->schools);
		
		$oSchool = Ext_Thebing_School::getInstance($iFirstSchool);
		return $oSchool;
	}

	/**
	 * Errechnet das Alter des Lehrers
	 * @todo: Methode zentral auslagern, wird bestimmt an vielen anderen Stellen gebraucht / verwendet
	 * @return <int>
	 */
	public function getAge() {
	
		$oBirthday = new WDDate($this->birthday, WDDate::DB_DATE);
		$iAge = $oBirthday->getAge();
		
		return $iAge;
	}

	public function getProfilePicture() {
		return $this->getFirstFile('Profile-Picture');
	}

    public function saveDefaultSchedule() {
    	
    	// add entries for monday to friday
    	for($i=1; $i<=5;$i++) {
    		$oScheduleEntry = new Ext_Thebing_Teacher_Schedule();
	        $oScheduleEntry->idTeacher  = (int)$this->id;
	        $oScheduleEntry->idDay      = (int)$i;
	        $oScheduleEntry->timeFrom   = '06:00:00';
	        $oScheduleEntry->timeTo     = '23:59:59';
	        $oScheduleEntry->valid_from = null;
	        $oScheduleEntry->valid_until = null;
	        $oScheduleEntry->active     = 1;
	        $oScheduleEntry->save();
    	}

    }

	/**
	 * @inheritdoc
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

    	$sLanguage = \Ext_Thebing_School::fetchInterfaceLanguage();

		$aSqlParts['select'] .= ",
			IFNULL(`kts`.`costcategory_id`, -2) `costcategory_id`,
			GROUP_CONCAT(DISTINCT `ktcc`.`name_{$sLanguage}` ORDER BY `ktcc`.`name_{$sLanguage}` SEPARATOR ', ') `course_category_name`,
			GROUP_CONCAT(DISTINCT `ktul`.`name_{$sLanguage}` ORDER BY `ktul`.`name_{$sLanguage}` SEPARATOR ', ') `level_name`,
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) `schools`,
			GROUP_CONCAT(DISTINCT `ktlg`.`name_{$sLanguage}` ORDER BY `ktlg`.`name_{$sLanguage}` SEPARATOR ', ') `course_languages`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_teacher_salary` `kts` ON
				`kts`.`teacher_id` = `kt`.`id` AND
				`kts`.`active` = 1 AND
				`kts`.`valid_from` <= DATE(NOW()) AND (
					`kts`.`valid_until` = '0000-00-00' OR
					`kts`.`valid_until` >= DATE(NOW())
				) LEFT JOIN
			`kolumbus_teacher_levels` `ktl` ON
				`kt`.`id` = `ktl`.`teacher_id` LEFT JOIN
			`kolumbus_teacher_courses` `ktec` ON
				`kt`.`id` = `ktec`.`teacher_id` LEFT JOIN
			`ts_tuition_coursecategories` `ktcc` ON
				`ktec`.`course_id` = `ktcc`.`id` LEFT JOIN
			`ts_tuition_levels` `ktul` ON
				`ktl`.`level_id` = `ktul`.`id` LEFT JOIN
			`ts_teachers_to_schools` `filter_schools` ON
				`kt`.`id` = `filter_schools`.`teacher_id` LEFT JOIN
			`ts_teachers_courselanguages` `ts_tc` ON
				`ts_tc`.`teacher_id` = `kt`.`id` LEFT JOIN
			`ts_tuition_courselanguages` `ktlg` ON
				`ktlg`.`id` = `ts_tc`.`courselanguage_id` LEFT JOIN
			`ts_teachers_courselanguages` `ts_tcl` ON
				`ts_tcl`.`teacher_id` = `kt`.`id`
		";

		

	}

	public function validate($bThrowExceptions = false) {

		if($this->email === '') {
			$this->email = null;
		}

		$this->_fillUniqueFields();

		$mValidate = parent::validate($bThrowExceptions);

		if($this->valid_until != '0000-00-00' && $this->valid_until != '')
		{
			if($mValidate === true)
			{
				$mValidate = array();
			}
			
			$aBlocks = $this->getTuitionBlocks($this->valid_until);
			
			if(!empty($aBlocks))
			{
				$mValidate[] = 'DEACTIVATE_ERROR_BLOCKS_EXISTS';
			}
			
			if(empty($mValidate))
			{
				$mValidate = true;
			}
		}

		return $mValidate;

	}

	/**
	 * Speichert zusätzlich Client ID und School ID
	 * @global <array> $user_data
	 * @return Ext_Thebing_Teacher
	 */
	public function save($bLog = true) {

		// Alter oder neuer Eintrag
		if($this->_aData['id'] > 0) {

			$bInsert = false;

		} else {

			$bInsert = true;

		}

		parent::save($bLog);

		if($bInsert == true) {
			$this->saveDefaultSchedule();
		}

		WDCache::deleteGroup(self::CACHE_SCHOOL_LIST);

		return $this;
	}

	/**
	 * @todo Redundant zu Ext_TS_Inquiry_Contact_Login
	 * @return string
	 */
	public function generateUsername() {

		$sName = '';
		$iCount = 0;

		$sName .= $this->firstname;
		$sName .= $this->lastname;

		return $this->traitGenerateUsername($sName);
	}

	public function delete() {

		// Feld ist UNIQUE
		$this->email = Ext_TC_Util::generateRandomString(8).'_'.$this->email;

		/** @var Ext_Thebing_Contract[] $aContracts */
		$aContracts = $this->getContracts();

		$bSuccess = parent::delete();

		if($bSuccess) {
			// Nur eine JoinTable
			foreach($aContracts as $oContract) {
				$oContract->enablePurgeDelete();
				$oContract->delete();
			}
		}

		return $bSuccess;

	}

	/**
	 * @inheritdoc
	 */
	public function purge($bAnonymize = false) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		if(!$bAnonymize) {
			$this->enablePurgeDelete();
		}

		// Kommentare in jedem Fall löschen
		$aComments = $this->getJoinedObjectChilds('comments', false); /** @var Ext_Thebing_Teacher_Comments[] $aComments */
		foreach($aComments as $oComment) {
			$oComment->enablePurgeDelete();
			$oComment->delete();
		}

		// Uploads in jedem Fall löschen
		$aUploads = $this->getJoinedObjectChilds('uploads', false); /** @var Ext_Thebing_Teacher_Upload[] $aUploads */
		foreach($aUploads as $oUpload) {
			$oUpload->enablePurgeDelete();
			$oUpload->delete();
		}

		// E-Mails in jedem Fall löschen
		$oMessageRepository = Ext_TC_Communication_Message::getRepository();
		$aLogs = $oMessageRepository->searchByEntityRelation($this);
		foreach($aLogs as $oLog) {
			$oLog->enablePurgeDelete();
			$oLog->delete();
		}

		if(!$bAnonymize) {
			$this->delete();
		} else {
			$this->firstname = ucfirst(strtolower(Util::generateRandomString(8, ['no_numbers' => true])));
			$this->lastname = 'Anonym';
			$this->username = '';
			$this->password = '';
			$this->street = '';
			$this->additional_address = '';
			$this->phone = '';
			$this->phone_business = '';
			$this->mobile_phone = '';
			$this->fax = '';
			$this->email = '';
			$this->skype = '';
			$this->socialsecuritynumber = '';
			$this->account_holder = '';
			$this->account_number = '';
			$this->adress_of_bank = '';
			$this->name_of_bank = '';
			$this->bank_address = '';
			$this->iban = '';
			$this->anonymized = 1;
			$this->save();
		}

	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeLabel() {
		return L10N::t('Lehrer', \TsPrivacy\Service\Notification::TRANSLATION_PATH);
	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeSettings() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return [
			'action' => $oClient->privacy_provider_action,
			'quantity' => $oClient->privacy_provider_quantity,
			'unit' => $oClient->privacy_provider_unit,
			'basedon' => 'valid_until'
		];
	}

	public function getSalary($sDate, int $schoolId) {

		$sSql = "
				SELECT
					*
				FROM
					kolumbus_teacher_salary
				WHERE
					`active` = 1 AND
					`teacher_id` = :teacher_id AND
				    `school_id` = :school_id AND
					`valid_from` <= DATE(:date) AND
					(
						`valid_until` = '0000-00-00' OR
						`valid_until` >= DATE(:date)
					) 
				LIMIT 1
					";
		$aSql = array(
			'teacher_id' => (int)$this->id,
			'date' => $sDate,
			'school_id' => $schoolId
		);
		
		$aSalary = DB::getQueryRow($sSql, $aSql);

		return $aSalary;
	}
	
	/*
	 * Liefert die Kostenkategorien der Familie
	 * optional mit Zeitfilter zu einem Zeitpunkt
	 */
	public function getCostCategories($sFrom = '', $sUntil = ''){
		
		return Ext_Thebing_Costcategory::getCostCategories($this, $sFrom, $sUntil);
		
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function __ToString() {
		return $this->getName();
	}

	/**
	 * Liefert alle Verträge des Lehrers
	 * @return Ext_Thebing_Contract[]
	 */
	public function getContracts(){
		$aContracts = array();
		
		foreach($this->contracts as $aContractData){		
			$aContracts[] = Ext_Thebing_Contract::getInstance((int)$aContractData['id']);
		}
		
		return $aContracts;
	}
	
	public function getTuitionBlocks($sDate)
	{	
		$sSql = "
			SELECT
				`ktb`.`id`
			FROM
				`ts_teachers` `kt` LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
					`ktbst`.`teacher_id` = `kt`.`id` AND
					`ktbst`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					(
						`ktb`.`id` = `ktbst`.`block_id`
					) OR
					(
						`ktb`.`teacher_id` = `kt`.`id`
					) AND
					`ktb`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id` AND
					`ktcl`.`active` = 1 
			WHERE
				`kt`.`id` = :teacher_id AND
				getRealDateFromTuitionWeek(
					`ktb`.`week`,
					`ktbd`.`day`,
					:course_startday
				) >= :date
		";
		
		$aSql = array(
			'teacher_id' => (int)$this->id,
			'date' => $sDate,
			'course_startday' => $this->getSchool()->course_startday
		);
		
		$aResult = (array)DB::getQueryCol($sSql, $aSql);
		
		return $aResult;
	}
	
	/**
	 * Liefert die formatierte Mailadresse für die Kommunikation
	 * @return string 
	 */
	public function getEmailformatForCommunication(){
		$sMail = '';	
		$sMail .= $this->name . ' (' . $this->email . ')';
		return $sMail;
	}
	
	/**
	 * Liefert alle gewählten Kurskategorien
	 * @param type $bForSelect
	 * @return Ext_Thebing_Tuition_Course_Category 
	 */
	public function getCategories($bForSelect = false){
		$aCategories = $this->getJoinTableObjects('course_categories');
		
		if($bForSelect){
			$aCategoryTemp = array();
			foreach($aCategories as $oCategory){
				$aCategoryTemp[$oCategory->id] = $oCategory->getName();
			}
			$aCategories = $aCategoryTemp;
		}
		
		return $aCategories;
	}
	
	/**
	 * Liefert alle gewählten Level zu diesem Lehrer zurück
	 * @param type $bForSelect
	 * @return Ext_Thebing_Tuition_Level 
	 */
	public function getLevels($bForSelect = false){
		$aLevel = $this->getJoinTableObjects('levels');
		
		$oSchool	= Ext_Thebing_Client::getFirstSchool();
		$sLanguage	= $oSchool->getLanguage();
		
		if($bForSelect){
			$aLevelTemp = array();
			foreach($aLevel as $oLevel){
				$aLevelTemp[$oLevel->id] = $oLevel->getName($sLanguage);
			}
			$aLevel = $aLevelTemp;
		}
		
		return $aLevel;
	}
	
	/**
	 * Liefert alle eingetragenen Verfügbarkeiten des Lehrers
	 * @return type 
	 */
	public function getSchedule(){
		$aAvailabilities = $this->getJoinedObjectChilds('schedule');

		return $aAvailabilities;
	}
	
	/**
	 * Der Index sortiert zuerst immer die Groß und dann die Kleinbuchstaben, darum indizieren wir
	 * in dieser Methode den Namen des Lehrers in Kleinbuchstaben, damit das sortieren unabhängig
	 * von der Klein-Großbbuchstaben funktioniert
	 * 
	 * @return string
	 */
	public function getNameStrToLower() {
		
		$sName = $this->getName();
		
		$sName = strtolower($sName);
		
		return $sName;
	}

	/**
	 * Liefert alle bezahlten Gruppierungseinträge dieses Lehrers
	 * @return Ext_TS_Accounting_Provider_Grouping_Teacher[]
	 */
	public function getPayedPaymentGroupings() {
		$aGroupings = (array)$this->getJoinedObjectChilds('payed_payment_groupings');
		return $aGroupings;
	}

	/**
	 * prüft, ob der Lehrer die übergebene Kurskategorie unterrichtet
	 * 
	 * @param Ext_Thebing_Tuition_Course_Category $oCourseCategory
	 * @return boolean
	 */
	public function checkCourseCategory(Ext_Thebing_Tuition_Course_Category $oCourseCategory) {
		
		$aTeacherCourseCategories = $this->getCategories();
		foreach ($aTeacherCourseCategories as $oTeacherCourseCategory) {
			if($oCourseCategory->id === $oTeacherCourseCategory->id) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * @todo Entfernen, da nicht mehr verwendet und auch nicht funktioniert (Schulumstellung)
	 * @param bool $bEmptyItem
	 * @return array
	 */
//	public static function getSelectOptions($bEmptyItem = true) {
//
//		$oSelf = new static();
//		$aList = $oSelf->getArrayList();
//		$aReturn = array();
//
//		if(!Ext_Thebing_System::isAllSchools()) {
//			$oSchool = Ext_Thebing_School::getSchoolFromSession();
//		}
//
//		foreach($aList as $aTeacher) {
//			if(!isset($oSchool) || in_array($oSchool->id == $aTeacher['idSchool']) {
//				$aReturn[$aTeacher['id']] = $aTeacher['firstname']. '&nbsp;' . $aTeacher['lastname'];
//			}
//		}
//
//		if($bEmptyItem) {
//			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
//			asort($aReturn);
//		}
//
//		return $aReturn;
//	}

	/**
	 * @inheritdoc
	 */
	public function getLanguage() {
		return $this->getSchool()->getLanguage();
	}

	/**
	 * Lehrerbezahlungen pro Lektion
	 *
	 * Bei Kostenkategorien, die monatlich bezahlt werden (nicht mit Fixgehalt verwechseln),
	 * gibt es keine block_id und hier muss – analog zu den Fixgehältern – der ganze Zeitraum
	 * des Monats überprüft werden. Das mit der Abfrage nach den Zeiträumen läuft auch so
	 * im Mega-Query bei den Lehrerbezahlungen (zu bezahlende Einträge).
	 *
	 * @param DateTime|null $dDate Blockwoche (monatlich)
	 * @param int $iBlockId Direkter Block (wöchentlich)
	 * @return Ext_Thebing_Teacher_Payment[]
	 */
	public function getLessonPayments(DateTime $dDate = null, $iBlockId = 0) {

		if($dDate === null) {
			$dDate = new DateTime();
		}

		$dFirstDay = clone $dDate;
		$dFirstDay->modify('first day of this month');
		$dLastDay = clone $dDate;
		$dLastDay->modify('last day of this month');

		$sSql = "
			SELECT
				`ktep`.*
			FROM
				`ts_teachers_payments` `ktep` LEFT JOIN
				`kolumbus_teacher_salary` `kts` ON
					`kts`.`teacher_id` = `ktep`.`teacher_id` AND
					`kts`.`active` = 1 AND
					`kts`.`valid_from` <= :date AND (
						`kts`.`valid_until` = '0000-00-00' OR
						`kts`.`valid_until` >= :date
					) LEFT JOIN
				`kolumbus_costs_kategorie_teacher` `kckt` ON
					`kckt`.`id` = `kts`.`costcategory_id` AND
					`kts`.`costcategory_id` != -1
			WHERE
				`ktep`.`active` = 1 AND
				`ktep`.`teacher_id` = :teacher_id AND (
					(
						`ktep`.`block_id` != 0 AND
						`ktep`.`block_id` = :block_id
					
					) OR (
						`ktep`.`block_id` = 0 AND
						`kckt`.`grouping` = 'month' AND
						`ktep`.`timepoint` BETWEEN :first_day AND :last_day
					)
				)
			GROUP BY
				`ktep`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql, [
			'teacher_id' => $this->id,
			'date' => $dDate->format('Y-m-d'),
			'first_day' => $dFirstDay->format('Y-m-d'),
			'last_day' => $dLastDay->format('Y-m-d'),
			'block_id' => $iBlockId
		]);

		$aResult = array_map(function($aRow) {
			return Ext_Thebing_Teacher_Payment::getObjectFromArray($aRow);
		}, $aResult);

		return $aResult;

	}

	/**
	 * Liefert die unterrichtete Zeit eines Lehrers für einen bestimmten Zeitraum
	 *
	 * @param \Carbon\Carbon $from
	 * @param \Carbon\Carbon $until
	 * @param TimeUnit $unit
	 * @return float
	 * @throws Exception
	 */
	public function getTeachingTime(\Carbon\Carbon $from, \Carbon\Carbon $until, TimeUnit $unit): float {

		$blocks = [];
		foreach ($this->schools as $schoolId) {
			$school = Ext_Thebing_School::getInstance($schoolId);
			$blocks = array_merge(
				$blocks,
				Ext_Thebing_School_Tuition_Block::getRepository()->getTuitionBlocks($from, $until, $school, $this)
			);
		}

		$seconds = 0;
		foreach ($blocks as $block) {
			$from = strtotime($block['from']);
			$until = strtotime($block['until']);
			$seconds += ($until - $from);
		}

		return match ($unit) {
			TimeUnit::MINUTES => round(($seconds / 60), 2),
			TimeUnit::HOURS => round(($seconds / 60 / 60), 2),
			TimeUnit::DAYS => round(($seconds / 60 / 60 / 24), 2),
			default => throw new \InvalidArgumentException('Invalid time unit for teaching time calculation')
		};

	}

	public function setPassword(string $sPassword) {

		$this->_aData['password'] = password_hash($sPassword, PASSWORD_DEFAULT);
	}

	public function getPasswordHash() {

		return $this->_aData['password'];
	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'mail' => (!empty($email = $this->email))
				? [$email, $this->getName()]
				: null,
			default => null,
		};
	}

	/**
	 * @return $this|null
	 *
	 * Wenn der Lehrer Zugriff auf alle Klassen und Zuweisungen hat, gibt den Lehrer nicht zurück.
	 */
	public function getTeacherForQuery() {

		if($this->access_right_teachers == 0) {
			return $this;
		} else {
			return null;
		}
	}
	
	/**
	 * @param bool $bEmptyItem
	 * @return array
	 */
	public static function getSelectOptions($bEmptyItem = true, $bAllSchools=false) {

		$iSchoolId = 0;
		if(
			$bAllSchools === false &&
			!Ext_Thebing_System::isAllSchools()
		) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId = $oSchool->id;
		}
		
		$sCacheKey = __METHOD__.'_'.$iSchoolId.'_'.$bEmptyItem;
		
		$aReturn = WDCache::get($sCacheKey);
		
		if($aReturn === null) {
		
			$oSelf = new static();
			$aList = $oSelf->getArrayList();

			$aReturn = [];

			$oFormat = new \Ext_Thebing_Gui2_Format_TeacherName;
			
			foreach($aList as $aTeacher) {
				$oTeacher = self::getInstance($aTeacher['id']);
				if(
					!isset($oSchool) || 
					in_array($oSchool->id, $oTeacher->schools) === true
				) {
					$aReturn[$aTeacher['id']] = $oFormat->formatByResult($aTeacher);
				}
			}

			if($bEmptyItem) {
				$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
				asort($aReturn);
			}

			WDCache::set($sCacheKey, (60*60*24), $aReturn, false, self::CACHE_SCHOOL_LIST);

		}

		return $aReturn;
	}

	public static function getBirthdays() {

		$sWhere = '';
		$aSql = array();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = $oSchool->getId();

		if($iSchoolId > 0) {
			$sWhere = " `ts_ts`.`school_id` = :school_id AND ";
			$aSql['school_id'] = $iSchoolId;
		}

		$sSql = "
			SELECT
				`kt`.`id`,
				`kt`.`birthday`,
				`kt`.`firstname`,
				`kt`.`lastname`,
				getAge(`kt`.`birthday`) `age`
			FROM
				`ts_teachers` `kt` JOIN
				`ts_teachers_to_schools` `ts_ts` ON
					kt.id = `ts_ts`.`teacher_id`
			WHERE
				(
					(
						DAYOFYEAR(`birthday`)+IF(DAYOFYEAR(CURDATE())>DAYOFYEAR(`birthday`),1000,0)
					) BETWEEN 
						DAYOFYEAR(CURDATE()) AND 
						(
							DAYOFYEAR(CURDATE() + INTERVAL 14 DAY)+IF(DAYOFYEAR(CURDATE())>DAYOFYEAR(CURDATE() + INTERVAL 14 DAY),1000,0)
						)
				) AND
				".$sWhere."
				`kt`.`active` = 1 AND (
					`kt`.`valid_until` = '0000-00-00' OR
					`kt`.`valid_until` > NOW()
				)
		";

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;

	}

	public function getCurrency() {

	}

	public function getDocumentLanguage() {
		return $this->getLanguage();
	}

	public function getTypeForNumberrange($sDocumentType, $mTemplateType = null) {
		return 'additional_document';
	}

	public function getCorrespondenceLanguage() {
		return $this->getLanguage();
	}

	public static function getCommunicationChannels(\Tc\Service\LanguageAbstract $l10n): array
	{
		return [
			'mail' => [],
			'sms' => [],
			'notice' => []
		];
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getSchool();
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsTuition\Communication\Application\Teacher::class;
	}

	public function getCommunicationLabel(LanguageAbstract $l10n): string
	{
		return sprintf('%s: %s', $l10n->translate('Lehrer'), $this->getName());
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->getName();
	}

	public function getCommunicationRoutes(string $channel): ?Collection
	{
		return match ($channel) {
			'mail' => (!empty($this->email)) ? collect([[$this->email, $this->getName()]]) : null,
			'sms' => (!empty($this->mobile_phone)) ? collect([[$this->mobile_phone, $this->getName()]]) : null,
			default => null,
		};
	}

	public function getCorrespondenceLanguages(): array
	{
		return [
			$this->getLanguage()
		];
	}

}