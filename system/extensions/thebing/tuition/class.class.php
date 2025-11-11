<?php

/**
 * @property $id
 * @property $changed
 * @property $created
 * @property $active
 * @property $user_id
 * @property $creator_id
 * @property $school_id
 * @property $color_id
 * @property $name
 * @property $start_week
 * @property $weeks
 * @property $level_increase
 * @property $internal_comment
 * @property $online_bookable_as_course
 * @property $bookable_only_in_full
 * @property $teacher_can_add_students
 * @property $lesson_duration
 * @property $courselanguage_id
 * @property int $start_week_timestamp DEPRECATED
 * @property int $confirmed
 * @method static \Ext_Thebing_Tuition_ClassRepository getRepository()
 */
class Ext_Thebing_Tuition_Class extends Ext_Thebing_Basic {
	use \FileManager\Traits\FileManagerTrait;

    // Tabellenname
    protected $_sTable = 'kolumbus_tuition_classes';

    // Tabellenalias
    protected $_sTableAlias	= 'ktcl';

	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\ClassPlaceholder::class;

    // Format
    protected $_aFormat = array(
        'name' => array(
            'required' => true
        ),
        'color_id' => array(
            'validate'	=> 'INT'
        ),
        'start_week'	=> array(
            'validate'	=> 'DATE'
        ),
        'weeks'	=> array(
            'validate'	=> 'INT'
        ),
        'level_increase' => array(
            'validate'	=> 'INT'
        ),
		'lesson_duration' => array(
			'validate'=>'FLOAT_NOTNEGATIVE'
		),
    );

    // joined tables
    protected $_aJoinTables = array(
        'courses'=>array(
            'table'=>'kolumbus_tuition_classes_courses',
            'class' => 'Ext_Thebing_Tuition_Course',
            'foreign_key_field'=>'course_id',
            'primary_key_field'=>'class_id',
            'on_delete' => 'no_action'
        ),
    );

    // aktuelle Woche für die Klasse
    protected $_iCurrentWeek;

    // aktuelles Level für die Klasse
    protected $_iCurrentLevel;

    // Blöcke zum Speichern
    protected $_aSaveBlocks = null;

    // Die Blöcke im Cache nachdem die aktuelle Woche gespeichert wird
    protected $_aSavedBlocks = array();

    // Flag für Änderungen
    protected $_bChanged;
    protected $_aChangedFields = [];

    // Flag für Levelerhöhung
    protected $_bHaveToMakeLevelIncrease = false;

    // Wird zurückgeliefert nach dem Speichern falls die Fehler ignoriert werden können
    public $bCanIgnoreErrors;

    // Flag für die Erlaubnis mit Fehlern zu speichern (falls Fehler ignoriert werden können => siehe $bCanIgnoreErrors)
    protected $_saveWithErrors;

    // Meldungen für das Speichern in die Folgewochen
    public $aAlerts = [];

    public $aErrorPlaceholder = array();

    /**
     * Es werden Daten vor dem Speichern benötigt, die auch nicht nach dem Speichern überschrieben werden dürfen, deshalb
     * müssen wir statt $_aOriginalData einen anderen Array dafür bereit stellen
     *
     * @var array
     */
    protected $_aOldData = array();

    /**
     * Überprüfung vornehmen ob es Abweichungen zwischen Blocklevel & Schülerlevel gibt
     *
     * @var bool
     */
    public $bCheckDifferentLevels = 1;

    /**
     * Flag setzen falls nur Info angezeigt werden soll ohne "ignorieren" Checkbox
     *
     * @var type
     */
    public $bShowSkipErrors = true;

    /**
     * Falls Levelunterschiede zwischen Block & Schüler existieren, wird nach dem Speichern dieses Array befüllt
     *
     * @var array
     */
    public $aDifferentLevels = array();

    /**
     * Schüler die Ihr dummes Level behalten wollen landen hier, also wird hier keine Überschreibung von
     * BlockLevel => SchülerLevel gemacht, wenn ein Kunde in dieser Liste auftaucht
     *
     * @var array
     */
    public $aLevelBlackList = array();

	protected $_aAttributes = [
		'bookable_only_in_full' => [
			'type' => 'int'
		],
	];

	public function isConfirmed(): bool
	{
		return (bool)$this->confirmed;
	}

	/**
     * Calculate the current week
     *
     * @param int $iWeekTimestamp
     * @return int
     */
    public function getCurrentWeek($mWeek)
    {
        if(is_numeric($mWeek)){
            $sPart = WDDate::TIMESTAMP;
        }else{
            $sPart = WDDate::DB_DATE;
        }

        $oDate = new WDDate($mWeek, $sPart);

        $iDiffWeeks = $oDate->getDiff(WDDate::WEEK, $this->start_week_timestamp, WDDate::TIMESTAMP);

        $iDiffWeeks += 1;

        return $iDiffWeeks;
    }


    public function getListQueryData($oGui=null)
    {
        $aQueryData			= array();

        $sFormat			= $this->_formatSelect();

        $aQueryData['data'] = array();

        $sTableAlias = $this->_sTableAlias;

        if(empty($sTableAlias))
        {
            $sTableAlias = $this->_sTable;
        }

        $sAliasString = '';
        $sAliasName = '';
        if(!empty($sTableAlias))
        {
            $sAliasString .= '`'.$sTableAlias.'`.';
            $sAliasName .= '`'.$sTableAlias.'`';
        }

        $sClientConfigFilter	= Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');
        $sClientConfigFilterSub = Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki2`');

		$maxStudents = "COALESCE(MIN(IF(`ktc`.`maximum_students` > 0, `ktc`.`maximum_students`, NULL)), 0)";

		$minStudents = "COALESCE(MAX(IF(`ktc`.`minimum_students` > 0, `ktc`.`minimum_students`, NULL)), 0)";

        $aQueryData['sql'] = "
				SELECT
					".$sAliasString."*,
					GROUP_CONCAT(DISTINCT `ktc`.`id`) `course_ids`,
					GROUP_CONCAT(DISTINCT `ktc`.`category_id`) `course_category_ids`,
					GROUP_CONCAT(DISTINCT `tc_c_n`.`number`) `customerNumber`,
					IF(
					    `ktb`.`week`>".$sAliasName.".`start_week`,
						CAST(
							(
								DATEDIFF(`ktb`.`week`,".$sAliasName.".`start_week`) / 7
							)+1 AS UNSIGNED
						),
						1
					) `current_week`,

					IF(
						`ktb`.`week`>".$sAliasName.".`start_week`,
						CAST(
							(
								DATEDIFF(`ktb`.`week`,".$sAliasName.".`start_week`) / 7
							)+1 AS UNSIGNED
						),
						1
					) `weeks_count`,
					".$sAliasName.".`weeks` `weeks_all`,
					GROUP_CONCAT(DISTINCT `ktb`.`description` ORDER BY `ktb`.`id`) `block_content`,
					GROUP_CONCAT(
						DISTINCT IF(`kt`.`id` IS NOT NULL, CONCAT_WS('_', `kt`.`id`, `kt`.`firstname`, `kt`.`lastname`, `ktb`.`id`), `ktb`.`teacher_id`)
					) `teachers`,
					GROUP_CONCAT(
						DISTINCT IF(`kt_substitute`.`id` IS NOT NULL, CONCAT_WS('_', `kt_substitute`.`id`, `kt_substitute`.`firstname`, `kt_substitute`.`lastname`, `ktb`.`id`), `kt_substitute`.`id`)
					) `sub_teachers`,
					GROUP_CONCAT(DISTINCT CONCAT_WS('_',`ktbtr`.`room_id`, `ktb`.`id`) ORDER BY `ktb`.`id`) `rooms`,
					`ktco`.`title` `color_name`,
					DATE_ADD(`ktcl`.`start_week`, INTERVAL ((`ktcl`.`weeks`*7)-1) DAY) `end_week`,
					`ktco`.`code` `color_code`,
					`ktul_start`.`name_short` `start_level`,
					`ktul`.`name_short` `current_level`,
					GROUP_CONCAT(
						DISTINCT CONCAT(
							`ktb`.`id`,'_',`ktbd`.`day`,'_',`ktt`.`from`,'_',`ktt`.`until`
						) 
						ORDER BY `ktb`.`id`
					) `days`,
					IFNULL(
						GROUP_CONCAT(
							DISTINCT CONCAT(
								`cdb1`.`lastname`,', ',`cdb1`.`firstname`
							) SEPARATOR '#'
						),''
					) `students`,
					GROUP_CONCAT(DISTINCT CONCAT(`ktb`.`teacher_id`, '_', `ktb`.`state` + 0)) `block_state`,
					GROUP_CONCAT(DISTINCT `kic`.`courselanguage_id`) `course_language_ids`,
					".$maxStudents." `maximum_students`,
					".$minStudents." `minimum_students`,
					COALESCE(`students`.`count_students`, 0) `count_students`,
					IF( 
						".$maxStudents." > 0 AND COALESCE(`students`.`count_students`, 0) > ".$maxStudents.",
						1,
						0
					) `overbooked`,
					IF (
						".$maxStudents." > 0 AND COALESCE(`students`.`count_students`, 0) >= ".$maxStudents.",
						1,
						0
					) `fully_booked`
					{FORMAT}
				FROM 
					`{TABLE}` ".$sAliasName." INNER JOIN
					`kolumbus_tuition_blocks` `ktb` ON
						`ktb`.`class_id` = ".$sAliasName.".`id`	LEFT JOIN
					(
						SELECT
							`ktb2`.`class_id`,
							COUNT(DISTINCT `cdb1_2`.`id`) `count_students`
						FROM
							`kolumbus_tuition_blocks_inquiries_courses` `ktbic2` INNER JOIN
							`kolumbus_tuition_blocks` `ktb2` ON
								`ktb2`.`id` = `ktbic2`.`block_id` AND
								`ktb2`.`active` = 1 INNER JOIN
							`ts_inquiries_journeys_courses` `kic2` ON
								`kic2`.`id` = `ktbic2`.`inquiry_course_id` AND
								`kic2`.`active` = 1 AND
								`kic2`.`visible` = 1 INNER JOIN
							`ts_inquiries_journeys` `ts_i_j2` ON
								`ts_i_j2`.`id` = `kic2`.`journey_id` AND
								`ts_i_j2`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
								`ts_i_j2`.`active` = 1 INNER JOIN
							`ts_inquiries` `ki2` ON
								`ki2`.`id` = `ts_i_j2`.`inquiry_id` AND
								`ki2`.`active` = 1 AND
								`ki2`.`canceled` <= 0 INNER JOIN
							`ts_inquiries_to_contacts` `ts_i_to_c2` ON
								`ts_i_to_c2`.`inquiry_id` = `ki2`.`id` AND
								`ts_i_to_c2`.`type` = 'traveller' INNER JOIN
							`tc_contacts` `cdb1_2` ON
								`cdb1_2`.`id` = `ts_i_to_c2`.`contact_id` AND
								`cdb1_2`.`active` = 1								
						WHERE
							`ktbic2`.`active` = 1 AND
							`ktb2`.`week` = :filter_week_0
							".$sClientConfigFilterSub."
							GROUP BY
						`ktb2`.`class_id`
					) `students` ON
						`ktb`.`class_id` = `students`.`class_id` LEFT JOIN
					`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON
						`ktb`.`id` = `ktbtr`.`block_id` LEFT JOIN
					`ts_teachers` `kt` ON
						`kt`.`id` = `ktb`.`teacher_id` AND
						`kt`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
						`ktbst`.`block_id` = `ktb`.`id` AND
						`ktbst`.`active` = 1 LEFT JOIN
					`ts_teachers` `kt_substitute` ON
						`kt_substitute`.`id` = `ktbst`.`teacher_id` AND
						`kt_substitute`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_colors` `ktco` ON
						".$sAliasName.".`color_id` = `ktco`.`id` AND
						`ktco`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
						`ktbic`.`block_id` = `ktb`.`id` AND
						`ktbic`.`active` = 1 LEFT JOIN
					`kolumbus_classroom` `kc` ON
						`ktbic`.`room_id` = `kc`.`id` AND
						`kc`.`active` = 1 LEFT JOIN
					(
						`ts_inquiries_journeys_courses` `kic` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `kic`.`journey_id` AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
							`ts_i_j`.`active` = 1
					) ON
						`ktbic`.`inquiry_course_id` = `kic`.`id` AND
						`kic`.`active` = 1 AND
						`kic`.`visible` = 1 LEFT JOIN
					`ts_inquiries` `ki` ON
						`ki`.`id` = `ts_i_j`.`inquiry_id` AND
						`ki`.`active` = 1 AND 
						`ki`.`canceled` <= 0
						".$sClientConfigFilter." LEFT JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
					`tc_contacts` `cdb1` ON
						`cdb1`.`id` = `ts_i_to_c`.`contact_id` AND
						`cdb1`.`active` = 1 LEFT JOIN
					`ts_tuition_levels` `ktul` ON
						`ktul`.`id` = `ktb`.`level_id` AND
						`ktul`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks` `ktb_start` ON
						`ktb_start`.`class_id` = ".$sAliasName.".`id` AND
						`ktb_start`.`week` = ".$sAliasName.".`start_week` AND
						`ktb_start`.`active` = 1 LEFT JOIN
					`ts_tuition_levels` `ktul_start` ON
						`ktul_start`.`id` = `ktb_start`.`level_id` AND
						`ktul_start`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks_days` `ktbd` ON
						`ktbd`.`block_id` = `ktb`.`id` LEFT JOIN
					`kolumbus_tuition_classes_courses` `ktclc` ON
						`ktclc`.`class_id` = ".$sAliasName.".`id` LEFT JOIN
					/*`kolumbus_tuition_classes_courses` `ktclc_filter` ON
						`ktclc_filter`.`class_id` = ".$sAliasName.".`id` LEFT JOIN*/
					`kolumbus_tuition_courses` `ktc` ON
						`ktc`.`id` = `ktclc`.`course_id` AND
						`ktc`.`active` = 1 LEFT JOIN
					/*`kolumbus_tuition_courses` `ktc_filter` ON
						`ktc_filter`.`id` = `ktclc_filter`.`course_id` AND
						`ktc_filter`.`active` = 1 LEFT JOIN
					`ts_tuition_courses_to_courselanguages` `ts_tctc_filter` ON
						`ts_tctc_filter`.`course_id` = `ktc_filter`.`id` LEFT JOIN*/
					`kolumbus_tuition_templates` `ktt` ON
						`ktt`.`id` = `ktb`.`template_id` LEFT JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id`	
				GROUP BY
					".$sAliasName.".`id`
		";

        $aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
        $aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

        return $aQueryData;
    }

    public function __set($sName, $mValue)
    {
        if('start_week'==$sName && is_numeric($mValue))
        {
            $oWdDate	= $this->_getWdDate($mValue, WDDate::TIMESTAMP);
            $dDate		= $oWdDate->get(WDDate::DB_DATE);

            $this->_aData['start_week'] = $dDate;
        }
        elseif('current_level'==$sName)
        {
            return $this->_iCurrentLevel = $mValue;
        }
        elseif('blocks'==$sName)
        {
            return $this;
        }
        elseif('ignore_errors'==$sName)
        {
            $this->_saveWithErrors = $mValue;
        }
        elseif($sName == 'weeks')
        {
            $this->_aOldData['weeks'] = $this->weeks;

            parent::__set($sName, $mValue);
        }
        elseif($sName == 'level_increase')
        {
            $this->_aOldData['level_increase'] = $this->level_increase;

            parent::__set($sName, $mValue);
        }
        elseif($sName == 'check_different_levels')
        {
            $this->bCheckDifferentLevels = $mValue;
        }
        else
        {
            parent::__set($sName, $mValue);
        }
    }

    public function __get($sName)
    {
        Ext_Gui2_Index_Registry::set($this);

        if($sName === 'start_week_timestamp') {
            $dDate = $this->_aData['start_week'];
            if(empty($dDate)) {
                return $dDate;
            }
            $oWdDate = $this->_getWdDate($dDate, WDDate::DB_DATE);
            return $oWdDate->get(WDDate::TIMESTAMP);
        } elseif('current_week'==$sName) {
            return $this->_iCurrentWeek;
        } elseif('blocks'==$sName) {
            return null;
        } elseif('current_level'==$sName) {
            return $this->_iCurrentLevel;
        } elseif('ignore_errors'==$sName) {
            return $this->_saveWithErrors;
        } elseif($sName == 'check_different_levels') {
            return $this->bCheckDifferentLevels;
        } else {
            try {
                $mValue = parent::__get($sName);
            } catch(Exception $e) {

                $sText = $_SERVER['HTTP_HOST']."\n\n";
                $sText .= Util::getBacktrace()."\n\n";
                $sText .= print_r($_SERVER,1)."\n\n";
                $sText .= print_r($_POST,1)."\n\n";
                $sText .= $sName."\n\n";
                $sText .= $e->getMessage()."\n\n";

                Ext_Thebing_Util::reportError('Classes GET Error', $sText);

                $mValue = null;
            }

            return $mValue;
        }
    }

    /**
     * @param int|string|bool|\DateTimeInterface $mWeekStart
     * @param bool $bOnlyIds
     * @param string $sWeekFilterType
     * @param DateTime[] $aDateRange
     * @return Ext_Thebing_School_Tuition_Block[]|int[]
     */
    public function getBlocks($mWeekStart = false, $bOnlyIds = false, $sWeekFilterType = '=', $aDateRange=null) {
        $iId = $this->id;
        if(empty($iId)) {
            return array();
        }

        $aSql = array(
            'class_id' => $iId,
        );

        $sWhereAddon = '';

        $oWdDate		= $this->_getWdDate();
        $sDateFilter	= false;

        if(is_numeric($mWeekStart)) {
            $oWdDate->set($mWeekStart, WDDate::TIMESTAMP);
            $sDateFilter = $oWdDate->get(WDDate::DB_DATE);
        } else if($oWdDate->isDate($mWeekStart, WDDate::DB_DATE)) {
            $sDateFilter = $mWeekStart;
        } else if ($mWeekStart instanceof \DateTimeInterface) {
			$sDateFilter = $mWeekStart->format('Y-m-d');
		}

        if($sDateFilter) {
            $sWhereAddon .= ' AND `week` '.$sWeekFilterType.' :week_start';
            $aSql['week_start']	 = $sDateFilter;
        }

        if(!empty($aDateRange)) {
            $sWhereAddon .= " AND `week` BETWEEN :from AND :until ";
            $aSql['from'] = $aDateRange[0]->format('Y-m-d');
            $aSql['until'] = $aDateRange[1]->format('Y-m-d');
        }

        $sSelect = "*";
        if($bOnlyIds) {
            $sSelect = "`id`";
        }

        $sSql = "
			SELECT
				".$sSelect."
			FROM
				`kolumbus_tuition_blocks`
			WHERE
				`class_id`	= :class_id AND
				`active`	= 1
				".$sWhereAddon."
			ORDER BY
				`created` ASC, `id` ASC
		";

        if($bOnlyIds) {
            return DB::getQueryCol($sSql, $aSql);
        } else {
            $aResult = DB::getPreparedQueryData($sSql, $aSql);
            $aResult2 = [];
            foreach($aResult as $aBlock) {
                $aResult2[$aBlock['id']] = Ext_Thebing_School_Tuition_Block::getObjectFromArray($aBlock);
            }

            return $aResult2;
        }
    }

	public function getBlocksWeeksIds(\DateTime $weekStart) {
		
        if(!$this->exist()) {
            return [];
        }

        $aSql = [
            'class_id' => $this->id,
			'week_start' => $weekStart->format('Y-m-d')
		];

        $sSql = "
			SELECT
				`ktb`.`id` `block_id`,
				`ktbtr`.`room_id` `room_id`
			FROM
				`kolumbus_tuition_blocks` `ktb` JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON
					`ktb`.`id` = `ktbtr`.`block_id`
			WHERE
				`ktb`.`class_id` = :class_id AND
				`ktb`.`active` = 1 AND 
				`ktb`.`week` = :week_start
			ORDER BY
				`ktb`.`created` ASC, 
				`ktb`.`id` ASC, 
				`ktbtr`.`room_id` ASC
		";

		return DB::getQueryRows($sSql, $aSql);
	}
	
    public function delete($bLog = true)
    {
        $aSavedBlockIds = $this->getBlocks(false,true);
        if(!empty($aSavedBlockIds))
        {
            $bSuccess = $this->clearBlockTables($aSavedBlockIds, true);
            if(!$bSuccess)
            {
                return array(
                    'TEACHER_PAYMENTS_EXISTS'
                );
            }
        }

        $aSql = array(
            'table' => $this->_sTable,
            'id'	=> $this->id,
        );

        $sSql = "
			DELETE FROM
				`kolumbus_tuition_classes_courses`
			WHERE
				`class_id` = :id
		";

        DB::executePreparedQuery($sSql, $aSql);

        $sSql = "
				UPDATE
					#table
				SET
					`active` = 0
				WHERE
					`id` = :id
		";

        $bSuccess = DB::executePreparedQuery($sSql, $aSql);

        // Log entry
        if($bSuccess)
        {
            $this->log(Ext_Thebing_Log::DELETED, $this->_aData);
        }

        if($bSuccess) {
        	\System::wd()->executeHook('ts_tuition_class_delete', $this);
		}

        return (bool)$bSuccess;
    }

    /**
     *
     * @param <array> $aBlockIds
     * @return bool
     */
    public function clearBlockTables($aBlockIds, $bCheckPayments = false)
    {
        $aBlockIds = (array)$aBlockIds;

        /** @var Ext_Thebing_School_Tuition_Block[] $aBlocks */
        $aBlocks = array_map(function($iBlockId) {
            return Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
        }, $aBlockIds);

        if($bCheckPayments) {
            foreach($aBlocks as $oBlock) {
                if(!empty($oBlock->getPayments())) {
                    return false;
                }
            }
        }

        $aSql = array(
            'block_ids' => $aBlockIds
        );

        foreach($aBlocks as $oBlock) {
            $oBlock->delete(true, false);
        }

        //custom templates entfernen

        $sSql = "
			UPDATE
				`kolumbus_tuition_templates` `ktt` JOIN
				`kolumbus_tuition_blocks` `ktb`
					ON `ktb`.`template_id` = `ktt`.`id`
			SET
				`ktt`.`active` = 0
			WHERE
				`ktt`.`custom` = 1 AND
				`ktb`.`id` IN(:block_ids)
		";

        DB::executePreparedQuery($sSql, $aSql);

        return true;
    }

    public function setCurrentWeek($iCurrentWeek)
    {
        $this->_iCurrentWeek = $iCurrentWeek;
    }

    public function setSaveBlocks(array $aSaveBlocks) {
        $this->_aSaveBlocks = $aSaveBlocks;
    }

    /**
     *
     * @param <bool> $bLog
     * @return Ext_Thebing_Tuition_Class => if success / <array>/false => if failed / true => if success
     */
    public function save($bLog = true) {

        DB::begin('save_class');

        $iWeeksBefore			= (int)$this->countWeeks();
        $bIsExtended			= $this->isExtended();
        $iIdBefore				= (int)$this->_aOriginalData['id'];

		$fOriginalLessonDuration = floatval($this->getOriginalData('lesson_duration'));

		$confirmed = false;
		if (
			!$this->exist() &&
			self::confirmClassesOnCreation()
		) {
			$this->confirmed = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
			$confirmed = true;
		}

        try
        {
            $mReturn = parent::save($bLog);

            if(
                $mReturn === true ||
                (
                    is_object($mReturn) &&
                    $mReturn instanceof Ext_Thebing_Tuition_Class
                )
            ) {
                $bMainObjectSaved = true;
            } else {
                $bMainObjectSaved = false;
            }

            if($bMainObjectSaved) {

                $iCurrentWeek = $this->_iCurrentWeek;

                if($this->_aSaveBlocks !== null) {

                    if(
						!empty($iCurrentWeek) && 
						is_numeric($iCurrentWeek)
					) {
						
						// Zu jedem existierenden Block die zugewiesenen Schüler holen (Blockweise wg. Flexibilität)
						$nonFlexibleInquiryCourses = [];
						foreach($this->_aSaveBlocks as &$aSaveBlockData) {
							
							// Neue Block überspringen
							if(empty($aSaveBlockData['block_id'])) {
								continue;
							}

							$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($aSaveBlockData['block_id']);
							$blockInquiryCourses = $oBlock->getInquiriesCourses();

							if(!empty($blockInquiryCourses)) {
								foreach($blockInquiryCourses as $blockInquiryCourse) {
									if($blockInquiryCourse['flexible_allocation'] == 0) {
										$nonFlexibleInquiryCourses[$blockInquiryCourse['inquiry_course']] = $blockInquiryCourse;
									}
								}
								if(empty($aSaveBlockData['inquiries_courses'])) {
									$aSaveBlockData['inquiries_courses'] = $blockInquiryCourses;
								}
							}

						}

						// Jedem neuen Block alle Schüler ohne Flexibilität zuweisen
						if(!empty($nonFlexibleInquiryCourses)) {
							foreach($this->_aSaveBlocks as &$aSaveBlockData) {

								// Neuer Block und Schüler noch nicht gesetzt
								if(
									!empty($aSaveBlockData['block_id']) ||
									!empty($aSaveBlockData['inquiries_courses'])
								) {
									continue;
								}

								$aSaveBlockData['inquiries_courses'] = $nonFlexibleInquiryCourses;

							}
						}

						try {
							[$mSuccess, $aChangedFields] = $this->saveBlocksForWeek($this->_aSaveBlocks, $iCurrentWeek);
						} catch (\TsTuition\Exception\SaveBlocksForWeekErrorException $e) {
							$mSuccess = $e->getErrors();
						}

                        if(is_array($mSuccess) && !empty($mSuccess))
                        {
                            DB::rollback('save_class');

                            return $mSuccess;
                        }
                        else
                        {
                            $this->bCanIgnoreErrors = false;

                            //aktuelle Woche wurde erfolgreich gespeichert
                            $aErrors	= array();
                            $iStartWeek = $this->start_week_timestamp;
                            $iWeeks		= (int)$this->weeks;
                            //nach dem Speichern der aktuellen Woche, werden erfolgreich gespeicherte Blöcke zwischengelagert
                            $aCurrentlySavedBlocks = $this->_aSavedBlocks;

                            if($bIsExtended) {

                                $oWdDate = $this->_getWdDate($iStartWeek, WDDate::TIMESTAMP);

                                //verlängern
                                //Die erste Folgewoche festhalten und nicht ab der aktuellen Woche anfangen, da sie schon eh gespeichert wurde
                                if(0 < $iWeeksBefore) {
                                    $iWeek	= $this->calcWeek($oWdDate, 'add', $iWeeksBefore);
                                } else {
                                    //neu Anlegen wie eine Verlängerung behandeln
                                    $iWeek	= $this->calcWeek($oWdDate);
                                    $iWeeks	-= 1;
                                }

                                $aSaveBlocks = $this->prepareBlockSaveDataArray($aCurrentlySavedBlocks);

                                for($iWeeksBefore; $iWeeksBefore < $iWeeks ; $iWeeksBefore++) {

									try {
										[$mSuccessClone, $aCloneChangedFields] = $this->saveBlocksForWeek($aSaveBlocks, $iWeek, true,true);
									} catch (\TsTuition\Exception\SaveBlocksForWeekErrorException $e) {
										$mSuccessClone = $e->getErrors();
									}

                                    if(is_array($mSuccessClone)) {
                                        foreach($mSuccessClone as $sKey => $aError)
                                        {
                                            foreach($aError as $sError)
                                            {
                                                $sErrorKey = 'week_' . $iWeeksBefore;
                                                $sErrorKey .= '#' . $sKey;
                                                $sErrorKey .= '#' . $sError;

                                                $this->aErrorPlaceholder[$sErrorKey] = $iWeeksBefore;
                                                $aErrors[$sErrorKey]	= 'COPY_WEEK_ERROR';
                                            }
                                        }

                                        break;
                                    }
                                    elseif(is_string($mSuccessClone))
                                    {
                                        $this->aErrorPlaceholder['week_'.$iWeeksBefore] = $iWeeksBefore;
                                        $aErrors['week_'.$iWeeksBefore]	= $mSuccessClone;
                                        break;
                                    }

                                    $iWeek = $this->calcWeek($oWdDate);
                                }
                            }
                            elseif($iWeeksBefore > $iWeeks)
                            {
                                $oWdDate			= $this->_getWdDate($iStartWeek, WDDate::TIMESTAMP);
                                $iWeek				= $this->calcWeek($oWdDate,'add',$iWeeksBefore-1);
                                $aDeleteBlockIds	= array();

                                //verkürzen
                                for(; $iWeeksBefore > $iWeeks; $iWeeksBefore--) {

                                    $aDeleteBlocks = $this->getBlocks($iWeek, false);
                                    $aDeleteKeys = array_map(function($oBlock) {
                                        return $oBlock->id;
                                    }, $aDeleteBlocks);

                                    $bHasPayments = false;
                                    foreach($aDeleteBlocks as $oDeleteBlock) {
                                        if(!empty($oDeleteBlock->getPayments())) {
                                            $bHasPayments = true;
                                            break;
                                        }
                                    }

                                    if($bHasPayments) {
                                        //Zahlungen existieren noch, verkürzen nicht möglich
                                        $this->aErrorPlaceholder['week_'.$iWeeksBefore] = $iWeeksBefore;
                                        $aErrors['week_'.$iWeeksBefore]	= 'WEEK_REDUCE_ERROR';
                                    } elseif(
                                        is_array($aDeleteKeys) &&
                                        !empty($aDeleteKeys)
                                    ) {
                                        $aDeleteBlockIds = array_merge($aDeleteBlockIds, $aDeleteKeys);
                                    }

                                    $iWeek = $this->calcWeek($oWdDate,'sub');
                                }

                                if(!empty($aErrors))
                                {
                                    $this->bCanIgnoreErrors = false;
                                }
                                else
                                {
                                    $this->clearBlockTables($aDeleteBlockIds);
                                }
                            }

                            //Levelerhöhung
                            if(empty($aErrors)) {

                                // Levelerhöhung abspeichern wenn nötig
                                $mErrorLevelIncrease = $this->_saveLevelIncrease();

                                if(
                                    $mErrorLevelIncrease === true ||
                                    $this->bCanIgnoreErrors === true
                                ) {

                                    $iWeekNum = $this->getCurrentWeek($iCurrentWeek);

                                    if(
                                        0 < $iIdBefore &&
                                        $iWeekNum < $iWeeks
                                    ) {
                                        $this->_bChanged = $mSuccess;
										$this->_aChangedFields = $aChangedFields;
                                    } else {
                                        //in der letzten Woche und beim neu Anlegen nicht die Frage einblenden ob die Änderungen
                                        //in die Folgewochen übernommen werden sollen
                                        $this->_bChanged = false;
										$this->_aChangedFields = [];
                                    }

                                } else {

                                    DB::rollback('save_class');

                                    $this->bShowSkipErrors = false; // Einfache Hintmeldung anzeigen ohne "ignorieren" Checkbox

                                    $this->aDifferentLevels = $mErrorLevelIncrease;// Array befüllen um in Tab die Unterschiede zu zeigen

                                    return array('DIFFERENT_LEVELS');
                                }

                            } else {
                                DB::rollback('save_class');

                                return $aErrors;
                            }
                        }
                    } else {
                        DB::rollback('save_class');

                        return array('SAVE_CLASS_ERROR');
                    }

                }

            } else {
                DB::rollback('save_class');

				$this->bCanIgnoreErrors = false;

                return $mReturn;
            }

        } catch(Exception $oException) {

            DB::rollback('save_class');

            Ext_Thebing_Log::error('SAVE_CLASS_EXCEPTION', array(
                'message' => $oException->getMessage(),
            ));

            $this->bCanIgnoreErrors = false;

            return array('SAVE_CLASS_EXCEPTION');
        }

        // Bei $this->_aSaveBlocks === null bleibt die Transaktion einfach offen #13158
        if(DB::getLastTransactionPoint() === 'save_class') {
            DB::commit('save_class');
        }

		if (bccomp($this->lesson_duration, $fOriginalLessonDuration) !== 0) {
			\Core\Entity\ParallelProcessing\Stack::getRepository()
				->writeToStack(
					'ts-tuition/lesson-duration',
					[
						'class_id' => $this->id,
						'original_lesson_duration' => $fOriginalLessonDuration
					],
					1
				);
		}

        System::wd()->executeHook('ts_class_save', $this);

		if ($confirmed) {
			\TsTuition\Events\ClassConfirmed::dispatch($this);
		}

		return $this;
    }

    /**
     *
     * @param <array> $aBlocks
     * @param <int> $iWeek
     * @param <bool> $bAllowAllocated
     * @param <bool> $bDontSetFlags
     * @param <array> $aInquiriesCourses
     * @return <array> => if failed / <bool> => if success, true==changed,false==not changed
     */
    public function saveBlocksForWeek($aBlocks, $iWeek, $bAllowAllocated = false, $bDontSetFlags = false, $overwriteExistingAllocation=true) {
		
        $aErrorsAll			= array();
        $aAlerts			= array();
        $aBlockIdsOrigin	= (array)$this->getBlocks($iWeek,true);
        $aBlockIdsNew		= array();

        $oWdDate			= $this->_getWdDate($iWeek, WDDate::TIMESTAMP);
        $sWeek				= $oWdDate->get(WDDate::DB_DATE);

		$aChangedFields = [];

        foreach($aBlocks as $aBlockSaveData) {
            $iBlockId		= (int)$aBlockSaveData['block_id'];
            $aBlockIdsNew[]	= $iBlockId;
        }
        $aDeleteBlockIds = array_diff($aBlockIdsOrigin,$aBlockIdsNew);
        //gelöschte Blöcke
        if(!empty($aDeleteBlockIds)) {
            //wenn Blöcke gelöscht, dann ist definitiv eine Änderung passiert
            $bChanged = true;
        } else {
            $bChanged = false;
        }
        $bCanIgnoreErrors = true;

        // Blöcke nur entfernen, wenn keine Zahlungen existieren
        $bHasPayments = false;
        foreach($aDeleteBlockIds as $iDeleteBlockId) {
            $oDeleteBlock = Ext_Thebing_School_Tuition_Block::getInstance($iDeleteBlockId);
            if(!empty($oDeleteBlock->getPayments())) {
                $bHasPayments = true;
                break;
            }
        }

        if(!$bHasPayments) {

            // Blöcke sammeln, da man die nacher für die Prüfungen braucht
            $aClassBlocks = array();

            foreach($aBlocks as $iBlockKey => $aBlockSaveData) {

                $aErrors		= array();
                $iBlockId		= (int)$aBlockSaveData['block_id'];
                $oBlock			= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

				$oOriginalTemplate = $oBlock->getTemplate();
				$fOriginalLessons = $oOriginalTemplate->lessons;
				unset($oOriginalTemplate);

                if(!empty($aBlockSaveData['days'])) {
                    $oBlock->days = (array)$aBlockSaveData['days'];
                }

				if(isset($aBlockSaveData['rooms'])) {
					$oBlock->setRoomIds($aBlockSaveData['rooms']);
				}

                $iTemplateId = (int)$aBlockSaveData['template'];
                $oTuitionTemplate = false;

                if(
					!empty($oBlock->checkIfChanged('rooms')) ||
					!empty($oBlock->checkIfChanged('days'))
				) {
                    $oBlock->bUpdateAllocations = true;
                }

                if(
					!empty($aBlockSaveData['from']) && 
					!empty($aBlockSaveData['until'])
				) {

                    //wenn from&until Parameter vorhanden, dann handelt es sich um eine individuelle Vorlage,
                    //weil die im Normalfall disabled sind
                    $oTuitionTemplate = Ext_Thebing_Tuition_Template::getInstance($iTemplateId);
                    $oTuitionTemplate->from = $aBlockSaveData['from'];
                    $oTuitionTemplate->until = $aBlockSaveData['until'];
                    $fLessons = Ext_Thebing_Format::convertFloat($aBlockSaveData['lessons']);
                    $oTuitionTemplate->lessons = $fLessons;

                    $sFrom = substr($oTuitionTemplate->from, 0, 5);
                    $sUntil = substr($oTuitionTemplate->until, 0, 5);

                    $oTuitionTemplate->name = L10N::t('Individuell',\Ext_Thebing_Tuition_Class_Gui2::TRANSLATION_PATH).' ('.$sFrom.' - '.$sUntil.')';

                    if(0>=$iTemplateId)
                    {
                        $oTuitionTemplate->school_id = $this->school_id;
                        //das ist der Flag für individuelle Vorlagen
                        $oTuitionTemplate->custom = 1;
                    }

                    try
                    {
                        if(!$bDontSetFlags)
                        {
                            if(!$bChanged)
                            {
                                //überprüfen ob in einer individuellen Vorlage eine Änderung existiert
                                $bChanged = $oTuitionTemplate->checkIfChanged();
								if ($bChanged) {
									// Flag für checkExistingBlocks setzen
									$oBlock->bCheckAllocations = true;
								}
                            }
                        }

                        $mValidateTemplate = $oTuitionTemplate->validate(false);

                        if(true===$mValidateTemplate)
                        {
                            $oTuitionTemplate->save();
                        }
                        else
                        {
                            $aErrorTemp = array();
                            $sField		= 'template';
                            foreach($mValidateTemplate as $mKey => $aTemp)
                            {
                                if(is_array($aTemp))
                                {
                                    foreach($aTemp as $sErrorValidate)
                                    {
                                        $aErrorTemp[] = $sErrorValidate;
                                    }
                                }
                                else
                                {
                                    $aErrorTemp[] = $aTemp;
                                }

                                //das ist der Db-Alias für Vorlagen, falls eine Standard Fehlermeldung zurückkommt, genau das richtige Feld markieren wo was schief läuft
                                if(strpos($mKey, 'ktt') !== false)
                                {
                                    //alias ausschneiden
                                    $sField = substr($mKey,4);
                                }
                            }
                            $aErrors['[blocks][ktcl]['.$iBlockKey.']['.$sField.']'] = $aErrorTemp;
                            $bCanIgnoreErrors = false;
                        }

                        $iTemplateId = $oTuitionTemplate->id;
                    } catch(Exception $oException) {

                        if(System::d('debugmode')) {
                            __pout($oException->getMessage());
                            __pout($oException->getFile());
                            __pout($oException->getLine());
                        }

                        $aErrors['[blocks][ktcl]['.$iBlockKey.'][template]'] = 'TEMPLATE_SAVE_ERROR';
                        $bCanIgnoreErrors = false;
                    }
                }

                if(empty($aErrors) && $iTemplateId > 0) {
                    $oBlock->template_id = $iTemplateId;
                }

				// Untermenge von $oBlock->bUpdateAllocations, siehe checkExistingBlocks()
				if (!empty($oBlock->checkIfChanged('template_id'))) {
					$oBlock->bCheckAllocations = true;
				}

				$oTemplate = $oBlock->getTemplate();

				/* 
				 * Wichtig: Bei Änderung müssen die Zuweisungen des Blocks wegen lesson_duration aktualisiert werden
				 * Wird in der save() und updateAllocations() vom Block benötigt, da Original-Wert nachher weg ist
				 */
				if($fOriginalLessons != $oTemplate->lessons) {
					$oBlock->bUpdateAllocations = true;
				}

                $aClassBlocks[] = $oBlock;
                $aBlocks[$iBlockKey]['object'] = $oBlock;
            }

            // Schleife geteilt wegen $aClassBlocks
            foreach($aBlocks as $iBlockKey => $aBlockSaveData) {
				
                $oBlock = $aBlockSaveData['object']; /** @var Ext_Thebing_School_Tuition_Block $oBlock */

                if(
                    empty($aErrors) ||
                    $this->_saveWithErrors == 1
                ) {

					if(isset($aBlockSaveData['teacher_id'])) {
						$oBlock->teacher_id = $aBlockSaveData['teacher_id'];
					}

                    if(isset($aBlockSaveData['parent_id'])) {
                        $oBlock->parent_id = $aBlockSaveData['parent_id'];
                    }

					if (
						!isset($aBlockSaveData['copy_from']) ||
						(empty($oBlock->description) && !empty($aBlockSaveData['description']))
					) {
						// Inhalt setzen wenn man nicht im Kopiervorgang aus der Vorwoche ist oder noch kein Inhalt vorhanden ist
						$oBlock->description = $aBlockSaveData['description'];
					}

					if (
						!isset($aBlockSaveData['copy_from']) ||
						(empty($oBlock->description_student) && !empty($aBlockSaveData['description_student']))
					) {
						// Inhalt setzen wenn man nicht im Kopiervorgang aus der Vorwoche ist oder noch kein Inhalt vorhanden ist
						$oBlock->description_student = $aBlockSaveData['description_student'];
					}

                    $oBlock->state = null;
					$oBlock->week = $sWeek;

					// Neuer Block
                    if((int)$aBlockSaveData['block_id'] <= 0) {
						
                        $oBlock->class_id		= $this->id;
                        $oBlock->school_id		= $this->school_id;
						
						// Darf während Schulferien angelegt werden?
						$firstCourse = reset($this->getJoinTableObjects('courses'));

						if($firstCourse->schoolholiday_scheduling == 1) {
							
							$school = \Ext_Thebing_School::getInstance($this->school_id);
							
							$blockPeriods = $oBlock->createPeriodCollection();

							$blockStartDate = $blockPeriods->reduce(fn($carry, $p) => $carry === null || $p->start() < $carry ? $p->start() : $carry, null);
							$blockEndDate = $blockPeriods->reduce(fn($carry, $p) => $carry === null || $p->end() > $carry ? $p->end() : $carry, null);
							
							$schoolHolidays = $school->getSchoolHolidays($blockStartDate, $blockEndDate);
							
							if(!empty($schoolHolidays)) {
								continue;
							}
							
						}
						
                    }

					/*if (
						$oBlock->exist() &&
						!empty($oBlock->checkIfChanged('week'))
					) {
						// Wenn der Block in eine andere Woche verschoben wird müssen die Anwesenheiten umgeschrieben werden
						$oBlock->bUpdateAllocations = true;
					}*/

                    try
                    {
                        $iSchoolId	= $this->school_id;
                        $aRoomIds	= $oBlock->getRoomIds();
                        $iTeacherId	= $oBlock->teacher_id;
                        $aDays		= $oBlock->days;
                        $iBlock		= $oBlock->id;

                        $iLevel		= $this->current_level;
                        //wird benötigt für copyweekdata
                        if(isset($aBlockSaveData['level_id']))
                        {
                            $iLevel				= $aBlockSaveData['level_id'];
                            $oBlock->level_id	= $iLevel;
                        }
                        //$aCourses	= $this->courses;

//						if(!$bAllowAllocated)
//						{
                        // Muss für $oBlock->state immer ausgeführt werden (also eben auch für Folgewochen)
                        // Die Variable heißt eigentlich $bNoCheck und muss deswegen auf false stehen…
                        $bCheckExtra = false;
//						}
//						else
//						{
//							$bCheckExtra = true;
//						}

                        $oClone			= clone($oBlock);

						// Achtung: Jeder Rückgabewert/Fehler aus dieser Methode, der hier drunter nicht gemappt ist, existiert einfach nicht
                        $mReturnCheck	= $oClone->checkExistingBlocks($iLevel, $bCheckExtra, $aDeleteBlockIds, $aClassBlocks);

                        // Zuweisungen nur bei neuen Blöcken neu speichern
                        // @TODO Früher (< März 2016) wurde das immer gemacht. Keine Ahnung, was die Änderung für Auswirkungen hat… #9866
                        $bSaveInquiryCourses = !$oBlock->exist();

                        if(true!==$mReturnCheck) {

                            if(in_array('course_not_available',$mReturnCheck)) {
                                #$aErrors['ktcl.courses'] = 'COURSE_NOT_AVAILABLE';
                                //
                                //Klasse soll trotzdem weitergehen, nur die Schüler nicht übernehmen
                                $bSaveInquiryCourses = false;
                            }
							
                            if(
                                in_array('room_allocated', $mReturnCheck) &&
                                !empty($aRoomIds)
                            ) {
                                $oBlock->setRoomIds([]);

                                $roomNames = collect($aRoomIds)
                                    ->map(function($iRoomId) {
                                        $oRoom = Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
                                        return $oRoom->name;
                                    })
                                    ->implode(', ');

                                if(!$bAllowAllocated) {
                                    $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][rooms]'] = $roomNames;
                                    $aErrors['[blocks][ktcl]['.$iBlockKey.'][rooms]'][] = 'ROOM_ALLOCATED';
                                } else {
                                    $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][rooms]'] = $roomNames;
                                    $aAlerts['[blocks][ktcl]['.$iBlockKey.'][rooms]']	= 'ROOM_ALLOCATED_IN_FUTURE';
                                }
								
                            }

                            if(in_array('room_not_valid', $mReturnCheck)) {
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][rooms]'][] = 'ROOM_INVALID';
                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][rooms]'] = $aBlockSaveData['rooms'];
                            }
							
							if(in_array('room_multiple_incompatibility', $mReturnCheck)) {
								$aErrors['[blocks][ktcl]['.$iBlockKey.'][rooms]'][] = 'ROOM_MULTIPLE_INCOMPATIBILITY';
								//$this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][rooms]'] = $aBlockSaveData['rooms'];
							}
							
                            if(in_array('teacher_not_valid', $mReturnCheck)) {
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'TEACHER_INVALID';
                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];
                            }
							
                            if(
								in_array('teacher_allocated',$mReturnCheck) && 
								0<$iTeacherId
							) {
								
                                $oBlock->teacher_id = 0;
                                if(!$bAllowAllocated) {
                                    $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];
                                    $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'TEACHER_ALLOCATED';
                                } else {
                                    $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];
                                    $aAlerts['[blocks][ktcl]['.$iBlockKey.'][teacher_id]']	= 'TEACHER_ALLOCATED_IN_FUTURE';
                                }
                            }
							
                            if(in_array('payments_exists',$mReturnCheck)) {
								
                                if(!$bAllowAllocated) {
                                    $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'TEACHER_PAYMENTS_EXISTS';
                                } else {
                                    $iCurrentWeek = $this->getCurrentWeek($iWeek);
                                    $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $iCurrentWeek;
                                    $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'TEACHER_PAYMENTS_EXISTS_IN_FUTURE';
                                    $aAlerts['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = 'TEACHER_PAYMENTS_EXISTS_IN_FUTURE';
                                }
                            }
							
                            if(
								in_array('teacher_worktime',$mReturnCheck) && 
								0<$iTeacherId
							) {
                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'INVALID_TEACHER_WORKTIME';
                                $aAlerts['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = 'INVALID_TEACHER_WORKTIME';

                                $oBlock->state |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_AVAILABILITY;
                            }
                            if(in_array('teacher_holiday',$mReturnCheck) && 0 < $iTeacherId) {
                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'TEACHER_HAS_ABSENCE';
                                $aAlerts['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = 'TEACHER_HAS_ABSENCE';

                                $oBlock->state |= Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE;
                            }
                            if(in_array('teacher_course_category',$mReturnCheck) && 0<$iTeacherId)
                            {
                                $aFlipError = array_flip($mReturnCheck);

                                $aExplode = explode('_', $aFlipError['teacher_course_category']);
                                $iCategoryId = (int) array_pop($aExplode);

                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];
                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id][course_category]'] = $iCategoryId;
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'INVALID_TEACHER_COURSE_CATEGORY';
                                $aAlerts['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = 'INVALID_TEACHER_COURSE_CATEGORY';

                                $oBlock->state |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION;
                            }

                            if(
                                in_array('teacher_level',$mReturnCheck) &&
                                0 < $iTeacherId &&
                                0 < $iLevel
                            ) {
                                $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'] = $aBlockSaveData['teacher_id'];

                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][teacher_id]'][] = 'INVALID_TEACHER_LEVEL';

                                $oBlock->state |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION;
                            }

                            if(
                            in_array('course_not_valid', $mReturnCheck)
                            ) {
                                //es können mehrere Kurse nicht gültig sein, deshalb für diesen speziellen
                                //Fall noch einmal alle Fehler durchgehen
                                //@todo: allgemein wäre es besser immer alle Fehler durchzugehen, anstatt
                                //immer in_array zu überprüfen...
                                foreach($mReturnCheck as $sKey => $sError)
                                {
                                    if($sError == 'course_not_valid')
                                    {
                                        $aKey		= explode('_', $sKey);
                                        $iCourseId	= (int)$aKey[1];

                                        $aErrors['ktcl.courses'][] = 'INVALID_COURSE';

                                        $this->aErrorPlaceholder['ktcl.courses']['invalid'][] = $iCourseId;
                                    }
                                }

                            }
                            if(in_array('attendance_exists', $mReturnCheck))
                            {
                                if(!$bAllowAllocated)
                                {
                                    $aErrors['[blocks][ktcl]['.$iBlockKey.'][template]'][] = 'ATTENDANCE_EXISTS';
                                }
                                else
                                {
                                    $iCurrentWeek = $this->getCurrentWeek($iWeek);
                                    $this->aErrorPlaceholder['[blocks][ktcl]['.$iBlockKey.'][template]'] = $iCurrentWeek;
                                    $aErrors['[blocks][ktcl]['.$iBlockKey.'][template]'][] = 'ATTENDANCE_EXISTS_IN_FUTURE';
                                }
                            }

							if(in_array('attendance_exists_for_days', $mReturnCheck)) {
								$bCanIgnoreErrors = false;
								$aErrors['[blocks][ktcl]['.$iBlockKey.'][days]'][] = 'ATTENDANCE_EXISTS_FOR_DAYS';
							}

                            if(in_array('block_overlapping', $mReturnCheck)) {
                                $bCanIgnoreErrors = false;
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][days]'][] = 'BLOCK_OVERLAPPING';
                            }

							if(in_array('students_overlapping', $mReturnCheck)) {
								$bCanIgnoreErrors = false;
								$aErrors['[blocks][ktcl]['.$iBlockKey.'][template]'][] = 'STUDENTS_OVERLAPPING';
							}

                            if(in_array('no_online_room_allocated_students', $mReturnCheck)) {
                                // Wenn Räume gelöscht wurden in denen noch Schüler zugewiesen waren
                                $bCanIgnoreErrors = false;
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][rooms]'][] = 'NO_ONLINE_ROOM_ALLOCATED_STUDENTS';
                            }

                            if(in_array('no_offline_room_allocated_students', $mReturnCheck)) {
                                // Wenn Räume gelöscht wurden in denen noch Schüler zugewiesen waren
                                $bCanIgnoreErrors = false;
                                $aErrors['[blocks][ktcl]['.$iBlockKey.'][rooms]'][] = 'NO_OFFLINE_ROOM_ALLOCATED_STUDENTS';
                            }
                        }

						if(
                            empty($aErrors) ||
                            1 == $this->_saveWithErrors
                        ) {

                            if(!$bDontSetFlags)
                            {
                                if(!$bChanged)
                                {
									$aBlockChangedFields = $oBlock->checkIfChanged();

									if (!empty($aBlockChangedFields)) {
										$aChangedFields[$oBlock->id] = $aBlockChangedFields;
										$bChanged = true;
									} else {
										$bChanged = false;
									}
                                }

                                $iCurrentLevel	  = $this->current_level;
                                $oBlock->level_id = $iCurrentLevel;

                                if(!empty($iCurrentLevel))
                                {
									if (!empty($oBlock->checkIfChanged('level_id'))) {
										$bLevelChanged = true;
									} else {
										$bLevelChanged = false;
									}

                                    if(!$this->_bHaveToMakeLevelIncrease)
                                    {
                                        $this->_bHaveToMakeLevelIncrease = $bLevelChanged;
                                    }
                                }
                            }

							if (!empty($oBlock->checkIfChanged('teacher_id'))) {
								$bChangedTeacherId = true;
							} else {
								$bChangedTeacherId = false;
							}

                            $oBlock->save();

                            // Schüler in neu erzeugten Blöcken zuweisen
                            if($bSaveInquiryCourses) {
                                // Schüler übernehmen
                                $aInquiriesCourses = $aBlockSaveData['inquiries_courses'];

                                if(
                                    is_array($aInquiriesCourses) &&
                                    !empty($aInquiriesCourses)
                                ) {
                                    foreach($aInquiriesCourses as $aBlocksInquiriesData) {

                                        if(!isset($aBlocksInquiriesData['program_service_id'])) {
                                        	// Falls eine Stelle vergessen wurde
                                        	throw new \RuntimeException('Missing key "program_service_id" in $aBlocksInquiriesData!');
										}

                                        $oBlock->addInquiryCourse($aBlocksInquiriesData['inquiry_course'], $aBlocksInquiriesData['program_service_id'], (int)$aBlocksInquiriesData['room_id'], $overwriteExistingAllocation);
                                    }
                                }
                            }

                            // Wenn Tage oder Lektionsanzahl verändert wurde, müssen die Zuweisungen dennoch aktualisiert werden
                            // Die Lektionsdauer wird zwar schon in addInquiryCourse() oben berechnet, aber nur bei neuen Blöcken (und nicht immer)
                            if($oBlock->exist()) {
                                $oBlock->updateAllocations();
                            }

                            // Im Inquiry-Index gibt es Spalten, die sich auf den Block beziehen, bspw. Name des letzten Lehrers.
                            if($bChangedTeacherId) {
                                $aStudents = $oBlock->getStudents();
                                foreach($aStudents as $aStudent) {
                                    Ext_Gui2_Index_Stack::add('ts_inquiry', (int)$aStudent['inquiry_id'], 1);
                                }
                            }

                            if(!$bDontSetFlags)
                            {
                                $this->_aSavedBlocks[] = $oBlock;
                            }
                        }
                        else
                        {
                            if(
								is_array($mReturnCheck) &&
								(
									in_array('course_not_available',$mReturnCheck) ||
									(in_array('room_allocated',$mReturnCheck) && !$bAllowAllocated) ||
									(in_array('teacher_allocated',$mReturnCheck) && !$bAllowAllocated) ||
									in_array('payments_exists',$mReturnCheck) ||
									in_array('course_not_valid', $mReturnCheck) ||
									in_array('room_not_valid', $mReturnCheck) ||
									in_array('teacher_not_valid', $mReturnCheck) ||
									in_array('attendance_exists', $mReturnCheck) ||
									in_array('room_multiple_incompatibility', $mReturnCheck)
								)
                            ) {
                                $bCanIgnoreErrors = false;
                            }
                        }

                    }
                    catch(Exception $oException)
                    {
                    	$aErrors['[blocks][ktcl]['.$iBlockKey.'][blockRow]'] = 'BLOCK_SAVE_ERROR';
                        $bCanIgnoreErrors = false;
                    }
                }

                if(!empty($aErrors))
                {
                    $aErrorsAll = array_merge($aErrors,$aErrorsAll);
                }
            }
        }
        else
        {
            if($iWeek == $this->_iCurrentWeek)
            {
                //aktuelle Woche
                $aErrorsAll = array(
                    'TEACHER_PAYMENTS_EXISTS'
                );
                $bCanIgnoreErrors = false;
            }
            else
            {
                $bCanIgnoreErrors = false;

				// TODO hier war vorher ein return mit dem Fehler. Warum nicht in $aErrorsAll?
				throw new \TsTuition\Exception\SaveBlocksForWeekErrorException($iWeek, ['TEACHER_PAYMENTS_EXISTS_IN_FUTURE']);
            }
        }

        // Nur überschreiben, wenn nicht gesetzt
        if($this->bCanIgnoreErrors === null) {
            $this->bCanIgnoreErrors = $bCanIgnoreErrors;
        }

		// Vorher wurde aAlerts mit jedem neuen Block immer wieder überschrieben, obwohl die Warnungen alle die jeweilige Block-ID haben sollten #17157
        $this->aAlerts = array_merge($this->aAlerts, $aAlerts);

        if(
            empty($aErrorsAll) ||
            (
                $bCanIgnoreErrors &&
                $this->_saveWithErrors == 1
            )
        ) {
            if(!empty($aDeleteBlockIds)) {
                $this->clearBlockTables($aDeleteBlockIds);
            }

            return [$bChanged, $aChangedFields];
        }

		throw new \TsTuition\Exception\SaveBlocksForWeekErrorException($iWeek, $aErrorsAll);
    }

    public function isChanged($sField = '') {
        return $this->_bChanged;
    }

	public function getChangedFields() {
		return $this->_aChangedFields;
	}

    protected function _saveLevelIncrease() {

        $aErrors			= array();
        $aLevelIncreases	= $this->getLevelIncreases();

        // Die berechneten Levelerhöhgen in die Blöcke und Zuweisungen abspeichern
        foreach($aLevelIncreases as $dWeek => $aLevelData) {

            $aWeekBlackList = array();

            if(isset($this->aLevelBlackList[$dWeek])) {
                $aWeekBlackList = $this->aLevelBlackList[$dWeek];
            }

            foreach($aLevelData as $iBlockId => $iLevel) {

                $oBlock				= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

                $oBlock->level_id	= $iLevel;

                $oBlock->save();

                $this->bCanIgnoreErrors;

                $mError				= $oBlock->saveProgressForAllocations($this->bCheckDifferentLevels, $aWeekBlackList);

                if(is_array($mError)) {

                    if(!isset($aErrors[$dWeek])) {
                        $aErrors[$dWeek] = array();
                    }

                    // Da hier die Keys der Fehler Buchung+Levelgruppe sind, kann hier nichts doppelt vorkommen
                    // da array_merge bei assoziativen arrays gruppiert
                    $aErrors[$dWeek]		= array_merge($aErrors[$dWeek], $mError);
                }

            }
        }

        if(empty($aErrors)) {
            return true;
        } else {
            return $aErrors;
        }
    }

    /**
     * Berrechnete Levelerhöhungen für alle Blöcke einer Klasse
     *
     * @param int $iCurrentWeek
     * @param int $iCurrentLevel
     * @param int $iIncreaseCounter
     * @return array
     */
    public function getLevelIncreases()
    {
        $aLevelIncreases		= array();

        $iCurrentWeek			= false;// Der Zeitpunkt ab dem die Levelerhöhung stattfinden soll
        $iCurrentLevel			= false;// Hmm der war mal für irgendwas gut, lass ich mal vorsichtshalber drin, vielleicht fällts mir irgendwann wieder ein :)
        $iIncreaseCounter		= 0;// Der Zähler ab wann eine Stufe höher gesetzt werden muss
        $bChangedLevelIncrease	= $this->hasChangedLevelIncrease();
        $bIsExtended			= $this->isExtended();

        //Intervaländerung hat Vorrang
        if($bChangedLevelIncrease)
        {
            $iCurrentWeek = $this->start_week_timestamp;
        }
        //Leveländerung in aktueller Woche
        elseif($this->isLevelIncreaseNeeded())
        {
            $iCurrentWeek = $this->_iCurrentWeek;
        }
        //Levelerhöhung für verlängerte Laufzeiten
        elseif($bIsExtended)
        {
            $oWdDate	= $this->_getWdDate($this->start_week_timestamp);

            // TODO Das wurde an den anderen Stellen auf $this->countWeeks() umgestellt (#5200, #12011)
            $iOldWeeks	= $this->getOldData('weeks');

            $iLastWeek	= $this->calcWeek($oWdDate, 'add', $iOldWeeks - 1);

            $iCount		= $this->getLastLevelCount($iLastWeek);

            if(false !== $iCount)
            {
                $iCurrentWeek			= $iLastWeek;
                $iIncreaseCounter		= $iCount;
            } else {
                $iCurrentWeek = $this->start_week_timestamp;
            }
        }
        else
        {
            return $aLevelIncreases;
        }

        $iLevelIncrease = (int)$this->level_increase;
        $iStartWeek		= (int)$this->start_week_timestamp;
        $iWeeks			= (int)$this->weeks;
        $sCompare		= '>';

        $oWdDate		= $this->_getWdDate($iCurrentWeek, WDDate::TIMESTAMP);
        $dCurrentWeek	= $oWdDate->get(WDDate::DB_DATE);

        if(!$iCurrentLevel)
        {
            $iCurrentLevel	= (int)$this->current_level;
            //manuelle Änderung übernehmen wir die aktuelle Woche mit
            $sCompare		= '>=';
        }

        if(0==$iLevelIncrease)
        {
            //da der IncreaseCounter mit 0 anfängt, sorgen wir dafür dass nie $iLevelIncrease==$iIncreaseCounter ist,
            //und für jede Woche wird dann das gleiche Level eingetragen
            $iLevelIncrease = -1;
        }

        $aBlocks = $this->getBlocks($dCurrentWeek, false, $sCompare);

        $aWeekGroupedBlocks = array();

        foreach($aBlocks as $oBlockData)
        {
            if(!isset($aWeekGroupedBlocks[$oBlockData->week]))
            {
                $aWeekGroupedBlocks[$oBlockData->week] = array();
            }

            $aWeekGroupedBlocks[$oBlockData->week][] = $oBlockData->id;
        }

        $oWeek = new WDDate($iCurrentWeek);
        $oLimit = new WDDate($iCurrentWeek);
        $oLimit->add($iWeeks, WDDate::WEEK);
        $oLimit->sub(1, WDDate::SECOND);

        $oSchool			= Ext_Thebing_School::getInstance($this->school_id);
        $sInterfaceLanguage	= $oSchool->getInterfaceLanguage();
        $aLevels			= (array)$oSchool->getLevelList(true, $sInterfaceLanguage, 1, false);

        if(!array_key_exists($iCurrentLevel, $aLevels)) {
            return $aLevelIncreases;
        }

        end($aLevels);
        $iLastLevel	= (int)key($aLevels);

        self::moveArrayPos($iCurrentLevel, $aLevels);

        do {

            $dCurrentWeek = $oWeek->get(WDDate::DB_DATE);

            if($iIncreaseCounter==$iLevelIncrease) {
                //raise level
                next($aLevels);
                $iIncreaseCounter = 0;
            }

            $iLevel = key($aLevels);

            if(
                !is_numeric($iLevel) ||
                is_null($iLevel) ||
                $iLevel == 0
            ) {
                //falls keine Levels mehr vorhanden, speichern wir das letzte Level
                $iLevel = $iLastLevel;
            }

            if(isset($aWeekGroupedBlocks[$dCurrentWeek]))
            {
                if(!isset($aLevelIncreases[$dCurrentWeek]))
                {
                    $aLevelIncreases[$dCurrentWeek] = array();
                }

                foreach($aWeekGroupedBlocks[$dCurrentWeek] as $iBlockId)
                {
                    $aLevelIncreases[$dCurrentWeek][$iBlockId] = $iLevel;
                }
            }

            $iIncreaseCounter++;

            $oWeek->add(1, WDDate::WEEK);

        } while($oWeek->compare($oLimit) < 0);


        return $aLevelIncreases;
    }

    /**
     *
     * @param <int> $iWeek
     * @return <array>
     */
    public function getClonedBlocks($iWeek)
    {
        $oWdDate	= $this->_getWdDate($iWeek, WDDate::TIMESTAMP);
        $dWeek		= $oWdDate->get(WDDate::DB_DATE);

        $sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_blocks`
			WHERE
				`class_id`	= :class_id AND
				`week`		= :week AND
				`parent_id`	!= 0 AND
				`active` = 1
		";

        $aSql = array(
            'week'		=> $dWeek,
            'class_id'	=> $this->id
        );

        $aResult		= DB::getPreparedQueryData($sSql, $aSql);
        $aClonedBlocks	= array();

        foreach($aResult as $aRowData)
        {
            $aClonedBlocks[$aRowData['parent_id']] = $aRowData['id'];
        }

        return $aClonedBlocks;
    }

    public function getCopyWeekData($mWeek, $iSchoolId=false)
    {
        $aCopyWeekData = array();

        if(!$iSchoolId)
        {
            $iSchoolId = Ext_Thebing_School::getSchoolFromSession()->id;
        }

        if(!is_numeric($mWeek))
        {
            $sPart = WDDate::DB_DATE;
        }
        else
        {
            $sPart = WDDate::TIMESTAMP;
        }
        $oWdDate	= $this->_getWdDate($mWeek,$sPart);
        $iWeek		= $oWdDate->get(WDDate::TIMESTAMP);
        $dWeek		= $oWdDate->get(WDDate::DB_DATE);

        $this->calcWeek($oWdDate);
        $dNextWeek	= $oWdDate->get(WDDate::DB_DATE);

        $sTableAlias = $this->_sTableAlias;

        if(empty($sTableAlias))
        {
            $sTableAlias = $this->_sTable;
        }

        $sAliasString = '';
        $sAliasName = '';
        if(!empty($sTableAlias))
        {
            $sAliasString .= '`'.$sTableAlias.'`.';
            $sAliasName .= '`'.$sTableAlias.'`';
        }


        $sSql = "
			SELECT
				".$sTableAlias.".`id` `class_id`,
				".$sTableAlias.".`name` `class_name`,
				".$sTableAlias.".`start_week` `class_start_week`,
				".$sTableAlias.".`weeks` `class_weeks`,
				".$sTableAlias.".`level_increase` `class_level_increase`,
				`ktb`.`week` `block_week`,
				CONCAT(
					IF(
						`ktb`.`week`>".$sAliasName.".`start_week`,
						CAST(
							(
								DATEDIFF(`ktb`.`week`,".$sAliasName.".`start_week`) / 7
							)+1 AS UNSIGNED
						),
						1
					),
					'/',
					".$sAliasName.".`weeks`
				)`current_week_string`,
				`ktul`.`id` `current_level`,
				`ktb`.`id` `block_id`,
				`ktt`.`name` `block_name`,
				`kt`.`id` `block_teacher_id`,
				TIME_TO_SEC(`ktt`.`from`) `block_from`,
				TIME_TO_SEC(`ktt`.`until`) `block_until`,
				GROUP_CONCAT(DISTINCT `ktbtr`.`room_id`) `block_room_ids`,
				GROUP_CONCAT(`ktbd`.`day`) `block_days`,
				`ktb`.`description` `block_description`
			FROM
				#table ".$sAliasName." INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`class_id` = ".$sAliasName.".`id` LEFT JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON
					`ktb`.`id` = `ktbtr`.`block_id` LEFT JOIN
				`kolumbus_classroom` `kc` ON
					`kc`.`id` = `ktbtr`.`room_id` AND
					`kc`.`active` = 1 LEFT JOIN
				`ts_tuition_levels` `ktul` ON
					`ktul`.`id` = `ktb`.`level_id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` LEFT JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = `ktb`.`teacher_id` AND
					`kt`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id`
			WHERE
				".$sAliasName.".`active` = 1 AND
				`ktb`.`active` = 1 AND
				".$sAliasName.".`school_id` = :school_id AND
				(
					`ktb`.`week` = :week OR
					`ktb`.`week` = :next_week
				)
			GROUP BY
				`ktb`.`id`
			ORDER BY
				".$sTableAlias.".`name`
		";

        $aSql = array(
            'table'		=> $this->_sTable,
            'week'		=> $dWeek,
            'next_week' => $dNextWeek,
            'school_id'	=> $iSchoolId,
        );

        $aResult	= DB::getPreparedQueryData($sSql, $aSql);

        $aTemp		= array();
        foreach($aResult as $aData)
        {
            if(!isset($aTemp[$aData['class_id']][$aData['block_week']]))
            {
                $aTemp[$aData['class_id']][$aData['block_week']] = array(
                    'class_name'	=> $aData['class_name'],
                    'current_week'	=> $aData['current_week_string'],
                    'current_level'	=> $aData['current_level'],
                    'class_start_week'	=> $aData['class_start_week'],
                    'class_weeks'		=> $aData['class_weeks'],
                    'class_level_increase'	=> $aData['class_level_increase'],
                );
            }

            $aTemp[$aData['class_id']][$aData['block_week']]['blocks'][$aData['block_id']] = array(
                'block_name'			=> $aData['block_name'],
                'block_room_ids'		=> $aData['block_room_ids'],
                'block_teacher_id'		=> $aData['block_teacher_id'],
                'block_week'			=> $aData['block_week'],
                'block_from'			=> $aData['block_from'],
                'block_until'			=> $aData['block_until'],
                'block_days'			=> $aData['block_days'],
                'block_description'		=> $aData['block_description'],
            );
        }

        foreach($aTemp as $iClassId => $aData)
        {
            if(2==count($aData))
            {
                $sKey = 'both';
                $aData = $aData[$dNextWeek];
            }
            else
            {
                $dKeyWeek = key($aData);
                if($dWeek==$dKeyWeek)
                {
                    $sKey = 'current';
                }
                else
                {
                    $sKey = 'next';
                }

                $aData = current($aData);
            }

            $this->start_week		= $aData['class_start_week'];
            $this->weeks			= $aData['class_weeks'] + 1;
            $this->level_increase	= $aData['class_level_increase'];
            unset(
                $aData['class_start_week'],
                $aData['class_weeks'],
                $aData['class_level_increase']
            );

            $aCopyWeekData[$sKey][$iClassId] = $aData;

            if('next'==$sKey || 'both'==$sKey)
            {
                foreach($aData['blocks'] as $iBlockId => $aBlockData)
                {
                    $aCopyWeekData['blocks_next_week'][$iBlockId] = $aBlockData;
                }
            }
            else
            {
                $iLastLevel = $aData['current_level'];

                $aCopyWeekData[$sKey][$iClassId]['current_level'] = $this->getPossibleLevel($iWeek, $iLastLevel, $iClassId);
            }
        }

        return $aCopyWeekData;
    }

    /**
     * @todo: recursion
     * @param <int> $iParamFilterWeek
     * @param <array> $aBlocks
     * @return <bool>
     */
    public function copyDataFromWeek($iParamFilterWeek, $aBlocks, $aFields = [])
    {
        $iStartWeek			= $this->start_week_timestamp;
        $iWeeks				= (int)$this->weeks;

        if(!is_numeric($iParamFilterWeek) || !is_numeric($iStartWeek))
        {
            return false;
        }

        $oSchool			= Ext_Thebing_School::getSchoolFromSession();
        $aTuitionTemplates	= $oSchool->getTuitionTemplates(true);

        foreach($aBlocks as $iKey => $aBlockData)
        {
            if(isset($aBlockData['template']))
            {
                $iTemplateId = $aBlockData['template'];
                if(!array_key_exists($iTemplateId,$aTuitionTemplates))
                {
                    //custom template nicht kopieren, nur die daten des custom templates
                    $aBlocks[$iKey]['template'] = 0;
                }
            }
        }

        $iCurrentWeek		= $this->getCurrentWeek($iParamFilterWeek);
        $oWdDate			= $this->_getWdDate($iParamFilterWeek, WDDate::TIMESTAMP);

        //kopieren ab erster Folgewoche anfangen
        $iNextWeek			= $iCurrentWeek + 1;

        $aErrors			= array();

        for($iNextWeek;$iNextWeek<=$iWeeks;$iNextWeek++)
        {
            $this->calcWeek($oWdDate);
            $iParamFilterWeek = $oWdDate->get(WDDate::TIMESTAMP);

            $aSaveBlocks	= $aBlocks;
            //alle geklonten Blöcke für diese Woche rausfinden, um festzustellen welche Daten wohin gehören
            $aClonedBlocks	= $this->getClonedBlocks($iParamFilterWeek);

            $iLevel			= 0;
            if(!empty($aClonedBlocks))
            {
                $iFirstBlock	= reset($aClonedBlocks);
                $oBlock			= Ext_Thebing_School_Tuition_Block::getInstance($iFirstBlock);
                $iLevel			= $oBlock->level_id;
            }

            foreach($aSaveBlocks as $iKey => $aSaveBlockData) {

                $iBlockId	= $aSaveBlockData['block_id'];
                $oBlock		= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
                $iParentId	= $oBlock->parent_id;

                if(array_key_exists($iBlockId,$aClonedBlocks)) {
                    //Block wurde von diesem Block kopiert, ID austauschen und Daten übernehmen
                    $aSaveBlocks[$iKey]['block_id'] = $aClonedBlocks[$iBlockId];
                } elseif(array_key_exists($iParentId, $aClonedBlocks)) {
                    //parent stimmt überein, ID austauschen und Daten übernehmen
                    $aSaveBlocks[$iKey]['block_id'] = $aClonedBlocks[$iParentId];
                } else {
                    //Block existiert nicht, parent_id setzen und neu anlegen
                    $aSaveBlocks[$iKey]['block_id']		= 0;
                    $aSaveBlocks[$iKey]['parent_id']	= $iBlockId;
                    $aSaveBlocks[$iKey]['level_id']		= $iLevel;
                }

				// Damit man Räume entfernen kann, muss hier sichergestellt werden, dass der Wert immer übermittelt wird.
				if(!isset($aSaveBlocks[$iKey]['rooms'])) {
					$aSaveBlocks[$iKey]['rooms'] = [];
				}
				
				// Block aus dem kopiert wird mitsenden
				$aSaveBlocks[$iKey]['copy_from'] = $iBlockId;
				// Wenn nur bestimmte Felder kopiert werden sollen dann die restlichen ignorieren
				if ($aSaveBlocks[$iKey]['block_id'] > 0 && !empty($aFields)) {
					// TODO evtl. noch mehr entfernen?
					// Felder entfernen die nicht überschrieben werden sollen
					foreach (['days', 'rooms', 'teacher_id', 'description'] as $sCopyField) {
						if (!in_array($sCopyField, $aFields)) {
							unset($aSaveBlocks[$iKey][$sCopyField]);
						}
					}
				}
            }

			try {
				[$mReturn, $aChangedFields] = $this->saveBlocksForWeek($aSaveBlocks, $iParamFilterWeek, true, true);
			} catch (\TsTuition\Exception\SaveBlocksForWeekErrorException $e) {
				$mReturn = $e->getErrors();
			}

            if(is_array($mReturn))
            {
                $aErrors = array_merge($aErrors, $mReturn);
            }
        }

        if(!empty($aErrors))
        {
            return $aErrors;
        }
        else
        {
            return true;
        }
    }

    public function getPossibleLevel($iLastWeek, $iLastLevel, $iClassId)
    {
        $iLevelIncrease		= $this->level_increase;
        if($iLevelIncrease==0){
            return $iLastLevel;
        }

        $oSchool			= Ext_Thebing_School::getSchoolFromSession();
        $aLevels			= (array)$oSchool->getLevelList(true,$sInterfaceLanguage,1,false);
        self::moveArrayPos($iLastLevel, $aLevels);

        $iCountLastLevels	= $this->getLastLevelCount($iLastWeek, $iLastLevel, $iClassId);

        if(false!==$iCountLastLevels)
        {
            $iCountLastLevels++;//lastWeek

            $iLevelIncrease		= (int)$this->level_increase;

            if($iCountLastLevels>=$iLevelIncrease){
                next($aLevels);
            }
            $iLevel = key($aLevels);
            if(empty($iLevel)){
                $iLevel = $iLastLevel;
            }

            return $iLevel;
        }

        return false;
    }

    public function clearStudents($iRoomId, $iWeekStart,$sOperator='=') {

        $aBlocks = $this->getBlocks($iWeekStart, false, $sOperator);
        $aBlockIds = array_map(function($oBlock) {
            return (int)$oBlock->id;
        }, $aBlocks);

        foreach($aBlocks as $oBlock) {
            if(!empty($oBlock->getPayments())) {
                return [
                    'success' => 0,
                    'error' => 'payment_exists'
                ];
            }
        }

		try {
			$aAllocations = Ext_Thebing_School_Tuition_Allocation::getRepository()->findByBlocksAndRoom($aBlockIds, $iRoomId);

			// Prüfen, ob alle Zuweisungen gelöscht werden dürfen
			$deleteAllocationsErrors = [];
			foreach ($aAllocations as $allocation) {
				$deleteAllocationsErrors = array_merge($deleteAllocationsErrors, $allocation->validateDelete());
			}
			if (!empty($deleteAllocationsErrors)) {
				return [
					'success' => 0,
					'error' => $deleteAllocationsErrors
				];
			}

			foreach($aAllocations as $oAllocation) {
				$oAllocation->delete();
			}

            $aBlockRoomIds = array_map(function($iBlockId) use($iRoomId) {
                return [
                    'block_id' => $iBlockId,
                    'room_id' => $iRoomId
                ];
            }, $aBlockIds);

            return array(
                'success'	=> 1,
                'block_ids'	=> array_values($aBlockRoomIds),
            );
        }
        catch(DB_QueryFailedException $e)
        {
            return array(
                'success'	=> 0,
                'error'		=> 'query_failed'
            );
        }
    }

    public static function getClassesForWeek($mWeek,$iSchoolId=false)
    {
        if(is_numeric($mWeek))
        {
            $oWdDate	= new WDDate($mWeek, WDDate::TIMESTAMP);
            $dWeek		= $oWdDate->get(WDDate::DB_DATE);
        }
        else
        {
            $dWeek		= $mWeek;
        }

        if(!$iSchoolId)
        {
            $iSchoolId = Ext_Thebing_School::getSchoolFromSession()->id;
        }

        $sSql = "
			SELECT
				`ktb`.`class_id`
			FROM
				`kolumbus_tuition_blocks` `ktb`
			WHERE
				`ktb`.`active` = 1 AND
				`ktb`.`week` = :week AND
				`ktb`.`school_id` = :school_id
			GROUP BY
				`ktb`.`class_id`
		";

        $aSql = array(
            'week'		=> $dWeek,
            'school_id'	=> $iSchoolId
        );

        $aResult	= DB::getQueryCol($sSql, $aSql);
        $aClasses	= array();

        foreach($aResult as $iClassId)
        {
            $aClasses[] = Ext_Thebing_Tuition_Class::getInstance($iClassId);
        }

        return $aClasses;
    }

    /**
     * Letzte Woche mit Klassen
     *
     * @param DateTime $dWeek
     * @param Ext_Thebing_School $oSchool
     * @return DateTime|null
     */
    public static function searchLastWeekWithClasses(DateTime $dWeek, Ext_Thebing_School $oSchool) {

        $sSql = "
			SELECT
				`ktb`.`week`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id`
			WHERE
				`ktb`.`active` = 1 AND
				`ktb`.`school_id` = :school_id AND
				`ktcl`.`active` = 1 AND
				`ktb`.`week` < :week
			ORDER BY
				`ktb`.`week` DESC
			LIMIT
				1
		";

        $sWeek = DB::getQueryOne($sSql, [
            'week' => $dWeek->format('Y-m-d'),
            'school_id' => $oSchool->id
        ]);

        if(empty($sWeek)) {
            return null;
        }

        return new DateTime($sWeek);

    }

    public function getLastLevelCount($iLastWeek, $iLastLevel=false, $iClassId=false)
    {
        if(!$iLastLevel)
        {
            $aBlocksEnd	= $this->getBlocks($iLastWeek);
            if(!empty($aBlocksEnd))
            {
                $oBlockEndOne = reset($aBlocksEnd);
                $iLastLevel = $oBlockEndOne->level_id;
            }
            else
            {
                $iLastLevel = false;
            }
        }

        if(!$iClassId)
        {
            $iClassId = $this->id;
        }

        if($iLastLevel)
        {
            $oWdDate		= $this->_getWdDate($iLastWeek, WDDate::TIMESTAMP);
            $dLastWeek		= $oWdDate->get(WDDate::DB_DATE);

            //rausfinden ab wann wider eine Levelerhöhung erfolgen muss
            $sSql = "
						SELECT
							`week`
						FROM
							`kolumbus_tuition_blocks`
						WHERE
							`active`	= 1 AND
							`week`		!= :last_week AND
							`level_id`	= :level_id AND
							`class_id`	= :class_id
						GROUP BY
							`week`
			";

            $aSql = array(
                'last_week'	=> $dLastWeek,
                'level_id'	=> $iLastLevel,
                'class_id'	=> $iClassId,
            );

            $aResult	= DB::getPreparedQueryData($sSql, $aSql);
            $iCount		= count($aResult);

            return $iCount;
        }
        else
        {
            return false;
        }
    }

    public static function moveArrayPos($mValue, &$aSearch)
    {
        reset($aSearch);

        $bMatch = false;
        $mKey	= key($aSearch);

        while(false===$bMatch && !empty($mKey))
        {
            $mKey = key($aSearch);

            if($mKey==$mValue)
            {
                $bMatch = true;
            }
            else
            {
                next($aSearch);
            }
        }

        return;
    }

    public function prepareBlockSaveDataArray($aBlocks, $bForCopy=false) {

        $oSchool			= Ext_Thebing_School::getSchoolFromSession();
        //alle nicht individuelle Vorlagen
        $aTuitionTemplates	= $oSchool->getTuitionTemplates(true);

        $aBlockSaveData = array();

        foreach((array)$aBlocks as $iBlockId => $aBlockData) {

            if(
                is_object($aBlockData) &&
                $aBlockData instanceof Ext_Thebing_School_Tuition_Block
            ) {
                $oBlock		= $aBlockData;
                $iBlockId	= $oBlock->id;
            } else {

                if(!is_array($aBlockData)) {
                    $iBlockId = $aBlockData;
                }

                $oBlock	= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
            }

            $iTemplateId		= $oBlock->template_id;
            $aInquiriesCourses	= $oBlock->getInquiriesCourses();
            $sFrom				= null;
            $sUntil				= null;
            $fLessons			= null;

            if(is_array($aBlockData)) {
                $aRoomIds	= explode(",", $aBlockData['block_room_ids']);
                $iTeacherId	= $aBlockData['block_teacher_id'];
            } else {
                $aRoomIds	= $oBlock->getRoomIds();
                $iTeacherId	= $oBlock->teacher_id;
            }

            if(!array_key_exists($iTemplateId,$aTuitionTemplates)) {
                $iTemplateId	= 0;
                $sFrom			= $oBlock->getAdditionalData('from');
                $sUntil			= $oBlock->getAdditionalData('until');
                $fLessons		= $oBlock->lessons;
            }

            if(!$bForCopy) {
                if($oBlock->parent_id==0) {
                    $iParentId	= $iBlockId;
                } else {
                    $iParentId	= $oBlock->parent_id;
                }

                $iBlockId	= 0;
            } else {
                $iParentId = $oBlock->parent_id;
            }

            $aBlockSaveData[] = array(
                'parent_id'		=> $iParentId,
                'block_id'		=> $iBlockId,
                'template'		=> $iTemplateId,
                'rooms'		    => $aRoomIds,
                'teacher_id'	=> $iTeacherId,
                'days'			=> $oBlock->days,
                'from'			=> $sFrom,
                'until'			=> $sUntil,
                'lessons'		=> $fLessons,
                'description' => $oBlock->description,
                'description_student' => $oBlock->description_student,
                'inquiries_courses'	=> $aInquiriesCourses,
				'original' => [
					'block_id' => $oBlock->id,
					'template' => $oBlock->template_id
				]
            );

        }

        return $aBlockSaveData;
    }

    protected function _getWdDate($mDate = null, $sPart = null, $sFormat = null)
    {
        $oWdDate = new WDDate($mDate, $sPart, $sFormat);

        return $oWdDate;
    }

    public function calcWeek(&$oWdDate, $sMethod='add', $iWeeks=1)
    {
        if($sMethod=='add')
        {
            $oWdDate->add($iWeeks, WDDate::WEEK);
        }
        else
        {
            $oWdDate->sub($iWeeks, WDDate::WEEK);
        }

        return $oWdDate->get(WDDate::TIMESTAMP);
    }

    public function getColor()
    {
        $oColor = Ext_Thebing_Tuition_Color::getInstance($this->color_id);
        return $oColor->code;
    }

    public function validate($bThrowExceptions = false)
    {
        $aErrors = parent::validate($bThrowExceptions);

        if($aErrors === true)
        {
            $aErrors = array();
        }

        $aIncomatibleAllocations = $this->checkIncompatibleAllocations();

        if(!empty($aIncomatibleAllocations)) {

            $aErrors['ktcl.courses'][] = 'INCOMPATIBLE_COURSES';

            $sPlaceHolder	= '';
            $oFormatUser	= new Ext_Thebing_Gui2_Format_CustomerName();
            $oDummy			= null;
            $oFormatDate	= new Ext_Thebing_Gui2_Format_Date();

            foreach($aIncomatibleAllocations as $aData) {
                $sName	= $oFormatUser->format(null, $oDummy, $aData);
                $aWeek	= Ext_Thebing_Util::getWeekTimestamps($aData['week']);
                $sFrom	= $oFormatDate->formatByValue($aWeek['start']);
                $sUntil	= $oFormatDate->formatByValue($aWeek['end']);

                $sPlaceHolder .= $sName.' ('.$sFrom.' - '.$sUntil.'); ';
            }

            $sPlaceHolder = substr($sPlaceHolder, 0, -2);
            $this->aErrorPlaceholder['ktcl.courses']['incompatible'] = $sPlaceHolder;
        }

        //Falls die Klasse aus einer anderen Schule kommt, nicht speichern
        $oSchool = Ext_Thebing_School::getSchoolFromSession();

        $iSchoolId = (int)$oSchool->id;
        if($this->school_id != $iSchoolId) {
            $aErrors[] = 'OTHER_SCHOOL_CLASS_NOT_CHANGABLE';
        }

		// Darf nicht einfach mehr verändert werden, da Tuition-Index und Zuweisungen nicht aktualisiert werden #12330
		if(
			$this->exist() &&
			$this->lesson_duration != $this->getOriginalData('lesson_duration')
		) {
			$process = \Core\Entity\ParallelProcessing\Stack::query()
				->where('type', 'ts-tuition/lesson-duration')
				->whereJsonContains('data->class_id', $this->id)
				->first();

			if ($process) {
				// Im Hintergrund existiert ein Prozess der bereits eine kürzliche Anpassung der Lektionsdauer verarbeitet
				$aErrors['ktcl.lesson_duration'] = 'LESSON_DURATION_EDITED_CURRENTLY';
			} else if (
				// Checkbox "Fehler ignorieren und speichern" nicht angehakt
				$this->_saveWithErrors !== 1 &&
				static::getRepository()->hasTuitionAllocation($this)
			) {
				$aErrors['ktcl.lesson_duration'] = 'LESSON_DURATION_BY_EXISTING_ALLOCATIONS';
				// Fehler kann als Hint-Meldung ignoriert werden
				$this->bCanIgnoreErrors = true;
			}
		}

        if(empty($aErrors)) {
            $aErrors = true;
        }

        return $aErrors;
    }

    public function checkIncompatibleAllocations()
    {
        $aCourses	= (array)$this->courses;
        $iClassId	= (int)$this->id;

        if($iClassId <= 0)
        {
            return;
        }

        $sSql = "
			SELECT
				`cdb1`.`lastname`,
				`cdb1`.`firstname`,
				`ktb`.`week`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `kic` ON
					`kic`.`id` = `ktbic`.`inquiry_course_id` AND
					`kic`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kic`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ts_i_j`.`inquiry_id` AND
					`ki`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `cdb1` ON
					`cdb1`.`id` = `ts_i_to_c`.`contact_id` AND
					`cdb1`.`active` = 1
			WHERE
				`ktbic`.`active` = 1 AND
				`ktb`.`class_id` = :class_id AND
				`ktbic`.`course_id` NOT IN(:course_ids)
			GROUP BY
				`cdb1`.`lastname`,
				`cdb1`.`firstname`,
				`ktb`.`week`
		";

        $aSql = array(
            'class_id'		=> $iClassId,
            'course_ids'	=> $aCourses,
        );

        $aResult = DB::getPreparedQueryData($sSql, $aSql);

        return $aResult;
    }

	/**
	 * @deprecated
	 *
	 * @param $iInquiryId
	 * @param $dWeek
	 * @return array
	 */
    protected function _getClassesByInquiryAndWeek($iInquiryId, $dWeek)
    {
        $sSql = "
			SELECT
				`ktcl`.`id` `class_id`,
				`ktcl`.`name` `class_name`,
				GROUP_CONCAT(
					DISTINCT CONCAT(
						`ktb`.`id`,'_',`ktbd`.`day`,'_',`ktt`.`from`,'_',`ktt`.`until`
					) ORDER BY `ktb`.`id`
				)     `days`,
				`ksb`.`title` `building`,
				`kc`.`name` `classroom`,
				GROUP_CONCAT(
					DISTINCT CONCAT(
						`kt`.`id`,
						'_',
						`kt`.`firstname`,
						'_',
						`kt`.`lastname`,
					    '_',
				    	`ktb`.`id`
					)
				) `teachers`,
				GROUP_CONCAT(
					DISTINCT CONCAT(
						`kt_substitute`.`id`,
						'_',
						`kt_substitute`.`firstname`,
						'_',
						`kt_substitute`.`lastname`,
				    	'_',
				    	`ktb`.`id`
					)
				) `sub_teachers`
			FROM
				#table #table_alias INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`class_id` = #table_alias.`id` AND
					`ktb`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` AND
					`ktt`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`block_id` = `ktb`.`id` AND
					`ktbic`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `kic` ON
					`kic`.`id` = `ktbic`.`inquiry_course_id` AND
					`kic`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kic`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ts_i_j`.`inquiry_id` AND
					`ki`.`active` = 1 LEFT JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = `ktb`.`teacher_id` AND
					`kt`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
					`ktbst`.`block_id` = `ktb`.`id` AND
					`ktbst`.`active` = 1 LEFT JOIN
				`ts_teachers` `kt_substitute` ON
					`kt_substitute`.`id` = `ktbst`.`teacher_id` AND
					`kt_substitute`.`active` = 1 LEFT JOIN
				`kolumbus_classroom` `kc` ON
					`kc`.`id` = `ktbic`.`room_id` AND
					`kc`.`active` = 1 LEFT JOIN
				`kolumbus_school_floors` `ksf` ON
					`ksf`.`id` = `kc`.`floor_id` AND
					`ksf`.`active` = 1 LEFT JOIN
				`kolumbus_school_buildings` `ksb` ON
					`ksb`.`id` = `ksf`.`building_id` AND
					`ksb`.`active` = 1
			WHERE
				`ki`.`id` = :inquiry_id AND
				`ktb`.`week` = :week AND
				#table_alias.`active` = 1
			GROUP BY
				`ktcl`.`id`,
				`ktb`.`id`
			ORDER BY
				`ktbd`.`day`,
				`ktt`.`from`
		";

        $aSql = array(
            'table'			=> $this->_sTable,
            'table_alias'	=> $this->_sTableAlias,
            'inquiry_id'	=> (int)$iInquiryId,
            'week'			=> $dWeek
        );

        $aResult = DB::getPreparedQueryData($sSql, $aSql);

        return $aResult;
    }

    protected function _getClassWeeksByInquiry($iInquiryId)
    {
        $sSql = "
			SELECT
				`ktb`.`week`
			FROM
				#table #table_alias INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`class_id` = #table_alias.`id` AND
					`ktb`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` AND
					`ktt`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`block_id` = `ktb`.`id` AND
					`ktbic`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `kic` ON
					`kic`.`id` = `ktbic`.`inquiry_course_id` AND
					`kic`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kic`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ts_i_j`.`inquiry_id` AND
					`ki`.`active` = 1
			WHERE
				`ki`.`id` = :inquiry_id AND
				#table_alias.`active` = 1
			GROUP BY
				`ktb`.`week`
		";

        $aSql = array(
            'table'			=> $this->_sTable,
            'table_alias'	=> $this->_sTableAlias,
            'inquiry_id'	=> (int)$iInquiryId,
        );

        $aResult = (array)DB::getQueryCol($sSql, $aSql);

        return $aResult;
    }

	/**
	 * TODO Entfernen
	 * Ext_Thebing_School_Tuition_Allocation_Result kann genau das gleiche und noch viel mehr.
	 *
	 * @deprecated
	 * @param $iInquiryId
	 * @param $dWeek
	 * @return array
	 */
    public static function getClassesByInquiryAndWeek($iInquiryId, $dWeek)
    {
        $oSelf		= new self();
        $aResult	= $oSelf->_getClassesByInquiryAndWeek($iInquiryId, $dWeek);

        return $aResult;
    }

    public static function getClassWeeksByInquiry($iInquiryId)
    {
        $oSelf		= new self();
        $aResult	= $oSelf->_getClassWeeksByInquiry($iInquiryId);

        return $aResult;
    }

    /**
     * Prüft ob Lehrer das Level, Kurskategorie und Kurssprache unterrichten kann aller angelegter Blöcke
     */
    public function checkLevel($aBlockTeacherAvailability=null) {

        $iLevel		= (int)$this->current_level;
        $aBlocks	= (array)$this->_aSaveBlocks;
        $aCourses	= (array)$this->courses;
        $aBack		= array();

        foreach($aBlocks as $iKey => $aBlock){

            $oBlock	= Ext_Thebing_School_Tuition_Block::getInstance((int)$aBlock['block_id']);

			$class = $oBlock->getClass();
			
			if(
				$aBlockTeacherAvailability !== null &&
				!empty($aBlockTeacherAvailability[$oBlock->id])
			) {
				$aTeachers = $aBlockTeacherAvailability[$oBlock->id];
			} else {
				$aTeachers = $oBlock->getAvailableTeachers($this->_iCurrentWeek, true);
			}

            foreach($aTeachers as $iTeacherId => $sTeacherName) {

                // Wenn der Lehrer diesen Block
                $bTeacherError = false;
                $oTeacher = Ext_Thebing_Teacher::getInstance((int)$iTeacherId);

                $bLevelFound = false;
                if($iLevel > 0) {
                    foreach($oTeacher->levels as $iCurrentLevel){
                        if($iCurrentLevel == $iLevel){
                            $bLevelFound = true;
                            break;
                        }
                    }
                } else {
                    // Ist kein level gewählt so gibt es auch keine Level Fehler
                    $bLevelFound = true;
                }

                $bAllCoursesFound = true;
				$bAllCourseLanguageFound = true;
                foreach($aCourses as $iCourseCategoryId) {
					
                    $oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$iCourseCategoryId);
					
                    $bCourseFound = false;
                    foreach($oTeacher->course_categories as $iCurrentCategory){
                        if($iCurrentCategory == $oCourse->category_id){
                            $bCourseFound = true;
                            break;
                        }
                    }

                    // Kein passenden Kurs beim Lehrer gefunden -> Fehler
                    if(!$bCourseFound) {
                        $bAllCoursesFound = false;
                        break;
                    }
					
					// Ist ein optionales Feld
					// TODO Sollte das nicht auch auf die Kurse gehen?
					if(!empty($class->courselanguage_id)) {
						$bCourseLanguageFound = false;
						foreach($oTeacher->course_languages as $iCurrentCourseLanguageId) {
							if($iCurrentCourseLanguageId == $class->courselanguage_id) {
								$bCourseLanguageFound = true;
								break;
							}
						}
					} else {
						$bCourseLanguageFound = true;
					}

                    // Kein passenden Kurs beim Lehrer gefunden -> Fehler
                    if(!$bCourseLanguageFound) {
                        $bAllCourseLanguageFound = false;
                        break;
                    }
					
                }

                if(
                    $bLevelFound == false ||
                    $bAllCoursesFound == false ||
                    $bAllCourseLanguageFound == false
                ) {
                    $bTeacherError = true;
                }

                $aTeacherCheck = array();
                $aTeacherCheck['teacher_id'] = (int)$oTeacher->id;
                $aTeacherCheck['check'] = (int)$bTeacherError;
                $aBack[$iKey][] = $aTeacherCheck;

            }

        }

        return $aBack;
    }

    /**
     * Alle gespeicherten Blöcke über saveBlockForWeek bekommen
     *
     * @return Ext_Thebing_School_Tuition_Block[]
     */
    public function getSavedBlocks()
    {
        return $this->_aSavedBlocks;
    }

    /**
     * Überprüfen ob sich die Levelerhöhung in der Klasse verändert hat
     *
     * @return bool
     */
    public function hasChangedLevelIncrease()
    {
        $iOldLevelIncrease		= (int)$this->getOldData('level_increase');

        $iLevelIncrease			= (int)$this->level_increase;

        if($iOldLevelIncrease != $iLevelIncrease)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Anzahl gespeicherter Wochen dieser Klasse
     *
     * Hier muss mit einer Differenz gearbeitet werden, da Blöcke auch gelöscht werden können
     * und dies die Anzahl der Wochen verfälscht (alte Implementierung). Wiederum dürfen aber auch
     * gelöschte Blöcke nicht beachtet werden, da die Verlängerung dann nicht gut funktioniert, wenn
     * wieder verkürzt wurde.
     *
     * Zusätzlich muss die Startwoche der Klasse verwendet werden, da auch am Anfang der Klasse
     * die Blöcke gelöscht werden können und dies wieder ein falsches Ergebnis liefern würde.
     * Die Startwoche kann allerdings auch nicht verändert werden, sonst würde das hier auch nicht
     * funktionieren.
     *
     * @return int
     */
    public function countWeeks() {

        if($this->id == 0) {
            return 0;
        }

        $sSql = "
			SELECT
				MAX(`week`) `max`
			FROM
				`kolumbus_tuition_blocks`
			WHERE
				`class_id` = :id AND
				`active` = 1
		";

        $aData = DB::getQueryRow($sSql, $this->getData());

        if($aData === null) {
            return 0;
        }

        $dMin = new DateTime($this->start_week);
        $dMax = new DateTime($aData['max']);

        $oDiff = $dMax->diff($dMin);
        if($oDiff->days % 7 !== 0) {
            // Datum muss immer ein Montag sein
            throw new RuntimeException(__METHOD__.': Diff between dates has remainder!');
        }

        $iWeeks = $oDiff->days / 7 + 1;

        return $iWeeks;

    }

    /**
     * Überprüfen ob die Laufzeit der Klasse verlängert wurde
     *
     * @return bool
     */
    public function isExtended() {

        $iExistingBlockWeeks	= (int)$this->countWeeks();

        $iWeeks					= (int)$this->weeks;

        if($iWeeks > $iExistingBlockWeeks) {
            return true;
        } else {
            return false;
        }

    }

    /**
     *
     * @param string $sKey
     * @return mixed
     */
    public function getOldData($sKey)
    {
        if(isset($this->_aOldData[$sKey]))
        {
            $mOldData = $this->_aOldData[$sKey];

            return $mOldData;
        }
        else
        {
            return false;
        }
    }

    /**
     * Überprüfen ob Levelerhöhung vorgenommen werden muss
     *
     * @return bool
     */
    public function isLevelIncreaseNeeded()
    {
        return $this->_bHaveToMakeLevelIncrease;
    }

    /**
     * Liefert alle Templates dieser Klasse
     *
     * @return array
     */
    public function getTuitionTemplates() {
        $oSchool = $this->getSchool();

        $aClassTemplates = array();
        if($this->id > 0) {
            // alle Templates die speziell zu dieser Klasse angelegt wurden
            $aClassTemplates	= $oSchool->getTuitionTemplates(false, true, $this);
        }

        return $aClassTemplates;
    }

    /**
     * Gibt das Ende der Klasse als DateTime-Objekt zurück
     *
     * @return DateTime
     */
    public function getLastDate() {

        $dDate = new DateTime($this->start_week);
        $dDate->modify('+'.$this->weeks.' weeks');
        $dDate->modify('- 1 days');

        return $dDate;

    }

	/**
	 * Die Klasse ist aktuell nur editierbar wenn es nur einen Block gibt
	 *
	 * @param Ext_Thebing_Teacher $teacher
	 * @return bool
	 */
	public function isEditableByTeacher(Ext_Thebing_Teacher $teacher): bool
	{
		if (!$teacher->hasAccessRight(Ext_Thebing_Teacher::ACCESS_CLASS_SCHEDULING_EDIT)) {
			return false;
		}

		if (
			$this->weeks > 1 ||
			count($this->courses) > 1
		) {
			return false;
		}

		$blocks = $this->getBlocks();
		if (count($blocks) > 1) {
			return false;
		}

		return true;
	}

	public function getCourseLanguage(): Ext_Thebing_Tuition_LevelGroup
	{
		if ($this->courselanguage_id > 0) {
			return Ext_Thebing_Tuition_LevelGroup::getInstance($this->courselanguage_id);
		}

		// Wenn eine Klasse mit mehreren Kursen mehrere Sprachen hat, sollte $courselanguage_id oben gesetzt sein
		/** @var Ext_Thebing_Tuition_Course[] $courses */
		$courses = $this->getJoinTableObjects('courses');
		foreach ($courses as $course) {
			foreach ($course->getCourseLanguages() as $language) {
				return $language;
			}
		}

		throw new DomainException('No language for class');
	}

	public function getBookableCourse(): ?Ext_Thebing_Tuition_Course
	{
		if ($this->online_bookable_as_course > 0) {
			return Ext_Thebing_Tuition_Course::getInstance($this->online_bookable_as_course);
		}

		return null;
	}

	/**
	 * Klassen werden beim Erstellen bestätigt
	 *
	 * @return bool
	 */
	public static function confirmClassesOnCreation(): bool
	{
		return \System::d('ts_tuition_class_confirm', 0) == 0;
	}

	/**
	 * Klassen werden bestätigt, wenn einer der zugehörigen Kurse die min_students erreicht.
	 *
	 * @return bool
	 */
	public static function confirmClassesOnMinStudentsReached(): bool
	{
		return \System::d('ts_tuition_class_confirm', 0) == 2;
	}

	/**
	 * Bestätigt die Klasse
	 * @return void
	 */
	public function confirm(): void
	{
		if (empty($this->confirmed)) {
			$this->confirmed = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
			$this->save();
			\TsTuition\Events\ClassConfirmed::dispatch($this);
		}
	}

	/**
	 * Ersetzt depricated Version aus wdbasic
	 *
	 * @return Ext_Thebing_School
	 * @throws Exception
	 */
	public function getSchool(): Ext_Thebing_School
	{
		return \Ext_Thebing_School::getInstance($this->school_id);
	}

	public function checkConfirmAfterAddingStudent() {
		if (
			!$this->isConfirmed() &&
			\Ext_Thebing_Tuition_Class::confirmClassesOnMinStudentsReached()
		) {
			$confirmClass = false;
			$studentsPerCourse = [];
			$blocks = $this->getBlocks();
			foreach ($blocks as $block) {
				foreach ($block->getAllocations() as $allocation) {
					$courseId = $allocation->getCourse()->id;
					$studentsPerCourse[$courseId] = empty($studentsPerCourse[$courseId]) ? 1 : $studentsPerCourse[$courseId] + 1;
				}
			}
			// Min Anzahl in einem Kurs erreicht, dann wird Klasse bestätigt
			$courses = $this->getJoinTableObjects('courses');
			foreach ($courses as $course) {
				if ($course->minimum_students <= $studentsPerCourse[$course->id]) {
					$confirmClass = true;
				}
			}
			if ($confirmClass) {
				$this->confirm();
			}
		}
	}

}
