<?php


class Ext_Thebing_System_Checks_DBStructure extends GlobalChecks
{

	public function getTitle()
	{
		$sTitle = 'Change Database Structure';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Change database to new structure.';
		return $sDescription;
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aUpdates = array();
		
		##### Änderungen kolumbus_accounting_inquiryposition_account ####
		$sTablePositionAccount						= 'kolumbus_accounting_inquiryposition_account';
		$aUpdates[$sTablePositionAccount]			= array();
		$aUpdates[$sTablePositionAccount][]			= "ALTER TABLE `kolumbus_accounting_inquiryposition_account` CHANGE `inquiry_position_id` `item_id` INT( 11 ) NOT NULL;";
		$aUpdates[$sTablePositionAccount][]			= "
			ALTER TABLE `kolumbus_accounting_inquiryposition_account` ADD `id` INT( 9 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ,
			ADD `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id` ,
			ADD `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `changed` ,
			ADD `active` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `created` ,
			ADD `creator_id` INT( 9 ) NOT NULL AFTER `active` ,
			ADD `user_id` INT( 9 ) NOT NULL AFTER `creator_id`;	
		";
		$aUpdates[$sTablePositionAccount][]			= $this->_createAddIndexQuery($sTablePositionAccount, 'item_id');
		$aUpdates[$sTablePositionAccount][]			= $this->_createAddIndexQuery($sTablePositionAccount, 'account_id');
		$aUpdates[$sTablePositionAccount][]			= $this->_createAddIndexQuery($sTablePositionAccount, 'user_id');
		$aUpdates[$sTablePositionAccount][]			= $this->_createAddIndexQuery($sTablePositionAccount, 'creator_id');
		
		##### Änderungen kolumbus_pt_questions ####
		$sTablePlacementtestQuestion				= 'kolumbus_pt_questions';
		$aUpdates[$sTablePlacementtestQuestion]		= array();
		$aUpdates[$sTablePlacementtestQuestion][]	= 'ALTER TABLE `kolumbus_pt_questions` ADD `editor_id` INT NOT NULL AFTER `created`;';
		
		##### Änderungen kolumbus_pt_categories ####
		$sTablePlacementtestCategory				= 'kolumbus_pt_categories';
		$aUpdates[$sTablePlacementtestCategory]		= array();
		$aUpdates[$sTablePlacementtestCategory][]	= 'ALTER TABLE `kolumbus_pt_categories` ADD `editor_id` INT NOT NULL AFTER `created`;';
		$aUpdates[$sTablePlacementtestCategory][]	= 'ALTER TABLE `kolumbus_pt_categories` CHANGE `category` `category` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
		
		##### Änderungen kolumbus_pt_answers ####
		$sTablePlacementtestAnswer					= 'kolumbus_pt_answers';
		$aUpdates[$sTablePlacementtestAnswer]		= array();
		$aUpdates[$sTablePlacementtestAnswer][]		= 'ALTER TABLE `kolumbus_pt_answers` ADD `editor_id` INT NOT NULL AFTER `created`;';
		
		##### Änderungen ts_teachers ####
		$sTableTeachers								= 'ts_teachers';
		$aUpdates[$sTableTeachers]					= array();
		$aUpdates[$sTableTeachers][]				= $this->_getAddValidUntilColumnQuery($sTableTeachers);
		
		##### Änderungen customer_db_3 ####
		$sTableCourses								= 'customer_db_3';
		$sTableCoursesNew							= 'kolumbus_tuition_courses';
		$aUpdates[$sTableCourses]					= array();
		$aUpdates[$sTableCourses][]					= $this->_getAddValidUntilColumnQuery($sTableCourses);
		$aUpdates[$sTableCourses][]					= $this->_getRenameTableQuery($sTableCourses, $sTableCoursesNew);
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` CHANGE `ext_1` `category_id` INT NOT NULL';
		$aUpdates[$sTableCourses][]					= $this->_createAddIndexQuery($sTableCoursesNew, 'category_id');
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` CHANGE `ext_2` `lessons_per_week` FLOAT( 11, 2 ) NOT NULL DEFAULT \'0.00\'';
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` CHANGE `ext_4` `lesson_duration` DECIMAL( 10, 2 ) NOT NULL DEFAULT \'0.00\'';
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` CHANGE `ext_5` `maximum_students` SMALLINT UNSIGNED NOT NULL';
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` CHANGE `ext_8` `school_id` INT( 11 ) NOT NULL';
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` DROP INDEX `ext_8`';
		$aUpdates[$sTableCourses][]					= $this->_createAddIndexQuery($sTableCoursesNew, 'school_id');
		$aUpdates[$sTableCourses][]					= $this->_createAddIndexQuery($sTableCoursesNew, array(
			'idClient', 
			'school_id', 
			'active'
		), 'courses_fk_1');
		$aUpdates[$sTableCourses][]					= 'ALTER TABLE `' . $sTableCoursesNew . '` CHANGE `ext_36` `name_short` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$this->_addDropColumnQuerys($aUpdates[$sTableCourses], array(
			'email',
			'nickname',
			'password',
			'last_login',
			'views',
			'groups',
			'access_code',
			'changed_by',
			'ext_3',
			'ext_34',
			'ext_37',
			'placementtest_id',
		), $sTableCoursesNew);
		
		
		##### Änderungen kolumbus_classroom ####
		$sTableClassRoom							= 'kolumbus_classroom';
		$aUpdates[$sTableClassRoom]					= array();
		$aUpdates[$sTableClassRoom][]				= $this->_getAddValidUntilColumnQuery($sTableClassRoom);
		
		##### Änderungen customer_db_4 ####
		$sTableAccommodation						= 'customer_db_4';
		$aUpdates[$sTableAccommodation]				= array();
		$aUpdates[$sTableAccommodation][]			= $this->_getAddValidUntilColumnQuery($sTableAccommodation);
		$this->_addDropColumnQuerys($aUpdates[$sTableAccommodation], array(
			'ext_58',
			'ext_59',
			'ext_60',
			'ext_61',
			'ext_111',
		), $sTableAccommodation);
		
		##### Änderungen customer_db_8 ####
		$sTableAccommodationCategory				= 'customer_db_8';
		$sTableAccommodationCategoryNew				= 'kolumbus_accommodations_categories';
		
		$aUpdates[$sTableAccommodationCategory]		= array();
		$aUpdates[$sTableAccommodationCategory][]	= $this->_getAddValidUntilColumnQuery($sTableAccommodationCategory);
		$aUpdates[$sTableAccommodationCategory][]	= $this->_getRenameTableQuery($sTableAccommodationCategory, $sTableAccommodationCategoryNew);
		$this->_addDropColumnQuerys($aUpdates[$sTableAccommodationCategory], array(
			'email',
			'nickname',
			'password',
			'last_login',
			'views',
			'groups',
			'access_code',
			'ext_2',
			'ext_3',
		), $sTableAccommodationCategoryNew);
		$aUpdates[$sTableAccommodationCategory][]	= 'ALTER TABLE `kolumbus_accommodations_categories` ENGINE = InnoDB';
		$aUpdates[$sTableAccommodationCategory][]	= 'ALTER TABLE `kolumbus_accommodations_categories` CHANGE `ext_6` `type_id` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableAccommodationCategory][]	= 'ALTER TABLE `kolumbus_accommodations_categories` CHANGE `ext_5` `school_id` MEDIUMINT( 9 ) NOT NULL';
		$aUpdates[$sTableAccommodationCategory][]	= 'ALTER TABLE `kolumbus_accommodations_categories` CHANGE `ext_8` `price_night` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableAccommodationCategory][]	= 'ALTER TABLE `kolumbus_accommodations_categories` CHANGE `ext_7` `weeks` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableAccommodationCategory][]	= $this->_createAddIndexQuery($sTableAccommodationCategoryNew, 'school_id');
		$aUpdates[$sTableAccommodationCategory][]	= $this->_createAddIndexQuery($sTableAccommodationCategoryNew, 'type_id');
		
		##### Änderungen customer_db_10 ####
		$sTableRoomType								= 'customer_db_10';
		$sTableRoomTypeNew							= 'kolumbus_accommodations_roomtypes';
		$aUpdates[$sTableRoomType]					= array();
		$aUpdates[$sTableRoomType][]				= $this->_getAddValidUntilColumnQuery($sTableRoomType);
		$aUpdates[$sTableRoomType][]				= $this->_getRenameTableQuery($sTableRoomType, $sTableRoomTypeNew);
		$this->_addDropColumnQuerys($aUpdates[$sTableRoomType], array(
			'email',
			'nickname',
			'password',
			'last_login',
			'views',
			'groups',
			'access_code',
			'ext_2',
			'ext_3',
			'ext_5',
			'ext_6',
		), $sTableRoomTypeNew);
		$aUpdates[$sTableRoomType][]				= 'ALTER TABLE `' . $sTableRoomTypeNew . '` CHANGE `ext_7` `school_id` INT NOT NULL';
		$aUpdates[$sTableRoomType][]				= $this->_createAddIndexQuery($sTableRoomTypeNew, 'idClient', 'client_id');
		$aUpdates[$sTableRoomType][]				= $this->_createAddIndexQuery($sTableRoomTypeNew, 'school_id');
		$aUpdates[$sTableRoomType][]				= $this->_createAddIndexQuery($sTableRoomTypeNew, array(
			'idClient', 
			'school_id', 
			'active'
		),'roomtype_fk_1');
		$aUpdates[$sTableRoomType][]				= 'ALTER TABLE `kolumbus_accommodations_roomtypes` CHANGE `ext_8` `type` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		
		##### Änderungen customer_db_11 ####
		$sTableMeal									= 'customer_db_11';
		$sTableMealNew								= 'kolumbus_accommodations_meals';
		
		$aUpdates[$sTableMeal]						= array();
		$aUpdates[$sTableMeal][]					= $this->_getAddValidUntilColumnQuery($sTableMeal);
		$aUpdates[$sTableMeal][]					= $this->_getRenameTableQuery($sTableMeal, $sTableMealNew);
		$this->_addDropColumnQuerys($aUpdates[$sTableMeal], array(
			'email',
			'nickname',
			'password',
			'last_login',
			'views',
			'groups',
			'access_code',
			'ext_2',
			'ext_3',
			'ext_5',
			'ext_6',
		), $sTableMealNew);
		$aUpdates[$sTableMeal][]					= 'ALTER TABLE `' . $sTableMealNew . '` CHANGE `ext_7` `school_id` INT NOT NULL;';
		$aUpdates[$sTableMeal][]					= $this->_createAddIndexQuery($sTableMealNew, 'idClient', 'client_id');
		$aUpdates[$sTableMeal][]					= $this->_createAddIndexQuery($sTableMealNew, 'school_id');
		$aUpdates[$sTableMeal][]					= $this->_createAddIndexQuery($sTableMealNew, 'active');
		$aUpdates[$sTableMeal][]					= $this->_createAddIndexQuery($sTableMealNew, array(
			'idClient',
			'school_id',
			'active',
		), 'meals_fk_1');
		
		##### Änderungen customer_db_11 ####
		$sTableRoom									= 'kolumbus_rooms';
		$aUpdates[$sTableRoom]						= array();
		$aUpdates[$sTableRoom][]					= $this->_getAddValidUntilColumnQuery($sTableRoom);
		
		##### Änderungen customer_db_7 ####
		$sTableCourseCategory						= 'customer_db_7';
		$sTableCourseCategoryNew					= 'ts_tuition_coursecategories';
		
		$aUpdates[$sTableCourseCategory]			= array();
		$aUpdates[$sTableCourseCategory][]			= $this->_getRenameTableQuery($sTableCourseCategory, $sTableCourseCategoryNew);
		$aUpdates[$sTableCourseCategory][]			= 'ALTER TABLE `' . $sTableCourseCategoryNew . '` CHANGE `ext_1` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
		$this->_addDropColumnQuerys($aUpdates[$sTableCourseCategory], array(
			'ext_2',
			'ext_3',
			'ext_4',
			'position',
			'access_code',
			'groups',
			'views',
			'last_login',
			'password',
			'nickname',
			'email',
		), $sTableCourseCategoryNew);
		$aUpdates[$sTableCourseCategory][]			= 'ALTER TABLE `' . $sTableCourseCategoryNew . '` CHANGE `ext_5` `school_id` INT( 11 ) NOT NULL;';
		
		##### Änderungen customer_db_24 ####
		$sTableLevel								= 'customer_db_24';
		$sTableLevelNew								= 'kolumbus_tuition_levels';
		
		$aUpdates[$sTableLevel]						= array();
		$aUpdates[$sTableLevel][]					= $this->_getRenameTableQuery($sTableLevel, $sTableLevelNew);
		$this->_addDropColumnQuerys($aUpdates[$sTableLevel], array(
			'access_code',
			'groups',
			'views',
			'last_login',
			'password',
			'nickname',
			'email',
		), $sTableLevelNew);
		$aUpdates[$sTableLevel][]					= 'ALTER TABLE `' . $sTableLevelNew . '` CHANGE `ext_2` `name_short` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
		$aUpdates[$sTableLevel][]					= $this->_createAddIndexQuery($sTableLevelNew, 'idSchool', 'school_id');
		$aUpdates[$sTableLevel][]					= $this->_createAddIndexQuery($sTableLevelNew, 'idClient', 'client_id');
		$aUpdates[$sTableLevel][]					= $this->_createAddIndexQuery($sTableLevelNew, 'active');
		$aUpdates[$sTableLevel][]					= $this->_createAddIndexQuery($sTableLevelNew, array(
			'idClient',
			'idSchool',
			'active',
		),'levels_fk_1');
		
		##### Änderungen kolumbus_inquiries_documents_versions_items_changes ####
		$sTableVersionItemChanges					= 'kolumbus_inquiries_documents_versions_items_changes';
		$aUpdates[$sTableVersionItemChanges]		= array();
		$aUpdates[$sTableVersionItemChanges][]		= $this->_createAddIndexQuery($sTableVersionItemChanges, array(
			'inquiry_id', 
			'active', 
			'visible'
		), 'item_changes_1');
		
		##### Änderungen ts_inquiries_journeys ####
		$sTableInquiryJourneys						= 'ts_inquiries_journeys';
		$aUpdates[$sTableInquiryJourneys]			= array();
		$aUpdates[$sTableInquiryJourneys][]			= $this->_createAddIndexQuery($sTableInquiryJourneys, array(
			'school_id',
			'active',
		), 'inquiry_journey_1');
		
		##### Änderungen customer_db_2 ####
		$sTableSchool								= 'customer_db_2';
		$aUpdates[$sTableSchool]					= array();
		
		/*
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_288` `currencies` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_289` `price_structure_week` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_337` `price_structure_unit` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_290` `price_structure` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_291` `language` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_292` `languages` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_293` `url` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_298` `decimal_place` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_299` `number_format` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_300` `date_format_long` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_312` `date_format_short` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_301` `prepay_days` INT( 3 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_302` `finalpay_days` INT( 3 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_303` `prepay` DECIMAL( 15, 5 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_304` `prepay_type` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_306` `extra_nights_price` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_307` `extra_nights_cost` TINYINT( 1 ) NOT NULL DEFAULT \'0\'';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_310` `critical_attendance` DECIMAL( 15, 5 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_311` `additional_costs_are_initial` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_313` `export_delimiter` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_314` `accommodation_start` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_315` `url_placementtest` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_316` `price_splitting` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_317` `teacher_payment_type` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_319` `url_feedback` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_320` `url_newsletter_unsubscribe` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_321` `currency` INT( 3 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_322` `currency_teacher` INT( 3 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_323` `currency_accommodation` INT( 3 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_324` `currency_transfer` INT( 3 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_326` `url_creditcard` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_327` `email_receipts` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_333` `price_calculation` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_334` `invoice_release` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_335` `invoice_booking` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_336` `teacherlogin_payments` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_339` `netto_column` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_340` `email_account_id` INT( 9 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_341` `tax` TINYINT( 1 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_342` `tax_exclusive` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_344` `adult_age` TINYINT( 2 ) NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `snippet_student_login` `url_studentlogin` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_6` `country_id` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_5` `city` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_4` `zip` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		$aUpdates[$sTableSchool][]					= 'ALTER TABLE `customer_db_2` CHANGE `ext_3` `address` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		 * 
		 */
		
		$this->_addDropColumnQuerys($aUpdates[$sTableSchool], array(
			'ext_2',
			'ext_11',
			'ext_12',
			'ext_13',
			'ext_14',
			'ext_15',
			'ext_16',
			'ext_17',
			'ext_18',
			'ext_19',
			'ext_20',
			'ext_21',
			'ext_22',
			'ext_23',
			'ext_24',
			'ext_25',
			'ext_26',
			'ext_27',
			'ext_28',
			'ext_29',
			'ext_30',
			'ext_31',
			'ext_32',
			'ext_33',
			'ext_34',
			'ext_35',
			'ext_36',
			'ext_37',
			'ext_38',
			'ext_39',
			'ext_40',
			'ext_41',
			'ext_60',
			'ext_61',
			'ext_62',
			'ext_65',
			'ext_66',
			'ext_68',
			'ext_69',
			'ext_70',
			'ext_71',
			'ext_72',
			'ext_73',
			'ext_74',
			'ext_75',
			'ext_76',
			'ext_77',
			'ext_78',
			'ext_79',
			'ext_80',
			'ext_81',
			'ext_82',
			'ext_83',
			'ext_84',
			'ext_85',
			'ext_86',
			'ext_87',
			'ext_88',
			'ext_89',
			'ext_90',
			'ext_91',
			'ext_92',
			'ext_93',
			'ext_94',
			'ext_95',
			'ext_96',
			'ext_97',
			'ext_98',
			'ext_99',
			'ext_100',
			'ext_101',
			'ext_102',
			'ext_103',
			'ext_104',
			'ext_105',
			'ext_106',
			'ext_107',
			'ext_108',
			'ext_109',
			'ext_110',
			'ext_111',
			'ext_112',
			'ext_113',
			'ext_114',
			'ext_115',
			'ext_116',
			'ext_117',
			'ext_118',
			'ext_119',
			'ext_120',
			'ext_121',
			'ext_122',
			'ext_123',
			'ext_124',
			'ext_125',
			'ext_126',
			'ext_127',
			'ext_128',
			'ext_129',
			'ext_130',
			'ext_131',
			'ext_132',
			'ext_133',
			'ext_134',
			'ext_135',
			'ext_136',
			'ext_137',
			'ext_138',
			'ext_139',
			'ext_140',
			'ext_141',
			'ext_142',
			'ext_143',
			'ext_144',
			'ext_145',
			'ext_146',
			'ext_147',
			'ext_148',
			'ext_149',
			'ext_150',
			'ext_151',
			'ext_152',
			'ext_153',
			'ext_154',
			'ext_155',
			'ext_156',
			'ext_157',
			'ext_158',
			'ext_159',
			'ext_160',
			'ext_161',
			'ext_162',
			'ext_163',
			'ext_164',
			'ext_165',
			'ext_166',
			'ext_167',
			'ext_168',
			'ext_169',
			'ext_170',
			'ext_171',
			'ext_172',
			'ext_173',
			'ext_174',
			'ext_175',
			'ext_176',
			'ext_177',
			'ext_178',
			'ext_179',
			'ext_180',
			'ext_181',
			'ext_182',
			'ext_183',
			'ext_184',
			'ext_185',
			'ext_186',
			'ext_187',
			'ext_188',
			'ext_189',
			'ext_190',
			'ext_192',
			'ext_193',
			'ext_194',
			'ext_195',
			'ext_196',
			'ext_197',
			'ext_198',
			'ext_199',
			'ext_200',
			'ext_201',
			'ext_202',
			'ext_203',
			'ext_204',
			'ext_205',
			'ext_206',
			'ext_207',
			'ext_208',
			'ext_209',
			'ext_210',
			'ext_211',
			'ext_212',
			'ext_213',
			'ext_214',
			'ext_215',
			'ext_216',
			'ext_217',
			'ext_218',
			'ext_219',
			'ext_220',
			'ext_221',
			'ext_222',
			'ext_223',
			'ext_224',
			'ext_225',
			'ext_226',
			'ext_227',
			'ext_228',
			'ext_229',
			'ext_230',
			'ext_231',
			'ext_232',
			'ext_233',
			'ext_234',
			'ext_235',
			'ext_236',
			'ext_237',
			'ext_238',
			'ext_239',
			'ext_240',
			'ext_241',
			'ext_242',
			'ext_243',
			'ext_244',
			'ext_245',
			'ext_246',
			'ext_247',
			'ext_248',
			'ext_249',
			'ext_250',
			'ext_251',
			'ext_252',
			'ext_253',
			'ext_254',
			'ext_255',
			'ext_256',
			'ext_257',
			'ext_258',
			'ext_259',
			'ext_260',
			'ext_261',
			'ext_262',
			'ext_263',
			'ext_264',
			'ext_265',
			'ext_266',
			'ext_267',
			'ext_268',
			'ext_269',
			'ext_270',
			'ext_271',
			'ext_272',
			'ext_273',
			'ext_274',
			'ext_275',
			'ext_276',
			'ext_277',
			'ext_278',
			'ext_279',
			'ext_280',
			'ext_281',
			'ext_282',
			'ext_283',
			'ext_284',
			'ext_285',
			'ext_286',
			'ext_287',
			'ext_294',
			'ext_295',
			'ext_296',
			'ext_297',
			'ext_305',
			'ext_318',
			'ext_338',
			'ext_343',
			'nickname',
			'password',
			'last_login',
			'views',
			'groups',
			'access_code',
		), $sTableSchool);
				
				
		#### Nicht mehr benutzte Tabellen ####
		
		$aNotNeeded = array(
			'customer_db_23',
			'customer_db_22',
			'customer_db_21',
			'customer_db_12',
		);
		
		foreach($aNotNeeded as $sTable)
		{
			$aUpdates[$sTable]						= array();
			$aUpdates[$sTable][]					= $this->_getDropTableQuery($sTable);	
		}
		
		$aErrors									= array();
		
		
		foreach($aUpdates as $sTable => $aTableUpdates)
		{
			$bContinue = false;
			
			if(!Util::checkTableExists($sTable))
			{
				$aErrors[] = 'Table ' . $sTable . ' does not exist';
				
				continue;
			}
			
			$bSuccess = Util::backupTable($sTable, true);

			if($bSuccess)
			{
				foreach($aTableUpdates as $sUpdate)
				{
					if($bContinue)
					{
						continue;
					}
					
					$sError = false;
					
					try
					{
						$rRes = DB::executeQuery($sUpdate);
					}
					catch(DB_QueryFailedException $e)
					{
						$rRes = false;
						$sError = $e->getMessage();
					}
					catch(Exception $e)
					{
						$rRes = false;
						$sError = $e->getMessage();
					}
					
					if(!$rRes)
					{
						if($sError)
						{
							$aErrors[] = $sError;
						}
						else
						{
							$aErrors[] = $sUpdate;
						}
						
						if(strpos($sUpdate, 'RENAME TABLE') !== false)
						{
							$bContinue = true;
						}
					}
				}
			}
			else
			{
				$aErrors[] = 'Backup for table ' . $sTable . ' failed';
			}
		}


		if(!empty($aErrors))
		{
			$oMail = new WDMail();
			$oMail->subject = 'DB Structure';

			$sText = '';
			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('Y-m-d H:i:s')."\n\n";
			
			$sText .= '------------ERROR------------';
			$sText .= "\n\n";
			$sText .= print_r($aErrors, 1);

			$oMail->text = $sText;

			$oMail->send(array('m.durmaz@thebing.com'));	
		}

		return true;
	}
	
	protected function _createAddIndexQuery($sTable, $mIndexColumn, $sIndexName = false)
	{
		if(is_array($mIndexColumn))
		{	
			if(!$sIndexName)
			{
				$sIndexName = '`' . implode('_', $sIndexColumn) . '`';
			}
		}
		
		$sIndexColumn = '';
		$mIndexColumn = (array)$mIndexColumn;
		
		foreach($mIndexColumn as $sColumn)
		{
			$sIndexColumn .= '`' . $sColumn . '` , ';
		}
		
		$sIndexColumn = substr($sIndexColumn,0,-3);
		
		if(
			!$sIndexName
		)
		{
			$sIndexName = $sIndexColumn;
		}
		else
		{
			$sIndexName = '`' . $sIndexName . '`';
		}
		
		$sSql = 'ALTER TABLE `'.$sTable.'` ADD INDEX ' . $sIndexName . ' ( '.$sIndexColumn.' );';
		
		return $sSql;
	}
	
	protected function _getAddValidUntilColumnQuery($sTable)
	{
		$sSql = 'ALTER TABLE `' . $sTable . '` ADD `valid_until` DATE NOT NULL DEFAULT \'0000-00-00\' AFTER `active`;';
		
		return $sSql;
	}
	
	protected function _getDropTableQuery($sTable)
	{
		$sSql = 'DROP TABLE `' . $sTable . '`';

		return $sSql;
	}
	
	protected function _getRenameTableQuery($sTableOld, $sTableNew)
	{
		$sSql = 'RENAME TABLE `' . $sTableOld . '` TO `' . $sTableNew . '`';
		
		return $sSql;
	}
	
	protected function _addDropColumnQuerys(&$aUpdates, $aDropColumns, $sTable)
	{
		foreach($aDropColumns as $sColumn)
		{
			$sSql = 'ALTER TABLE `' . $sTable . '` DROP `' . $sColumn . '`';
			
			$aUpdates[] = $sSql;
		}
	}
}