<?php

class Ext_Thebing_School_Tuition_Block_Students_Gui2 extends Ext_Thebing_Gui2_Data {

	/**
	 * @param array $aFilter
	 * @param array $aOrderBy
	 * @param array $aSelectedIds
	 * @param bool $bSkipLimit
	 * @return array
	 */
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false) {
		global $_VARS;

		if($_VARS['task'] == 'updateIcons') {
			$aResult = array();
			$aResult['data'] = array();
			$aResult['count'] = 0;
			$aResult['offset'] = 0;
			$aResult['end'] = 0;
			$aResult['show'] = 0;
			return $aResult;
		}

		$sSql = '';
		$aSql = array();
		$iLimit = 0;
		$aSqlParts = array();
		$iBlockId = (int)$_VARS['block_id'];
        $iRoomId = (int)$_VARS['room_id'];

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$sNameField = 'name_'.$sInterfaceLanguage;

		$this->setFilterValues($aFilter);
		
		$this->_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);

		$iWeek = $aFilter['week'];
		$dWeek = \Core\Helper\DateTime::createFromLocalTimestamp($iWeek);

		//$dWeek = new DateTime('@'.$iWeek);
		//$dWeek->setTimezone(new DateTimeZone(date_default_timezone_get())); // Kompatiblität: System arbeitet mit »lokalen« Timestamps
		$dLastWeek = clone $dWeek;
		$dLastWeek->sub(new DateInterval('P7D'));

		$iDay = (int)$_VARS['weekday'];
		if($iDay <= 0) {
			$iDay = $oSchool->course_startday;
		}

		$dSelectedDay = Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeek, $iDay, $oSchool->course_startday);

		$aWeek = Ext_Thebing_Util::getWeekTimestamps($iWeek, $oSchool->course_startday, true);
		$iCourseFrom = $aWeek['start'];
		$iCourseUntil = $aWeek['end'];

		$aLastWeek = Ext_Thebing_Util::getWeekTimestamps($dLastWeek->getTimestamp(), $oSchool->course_startday, true);
		$iLastWeekFrom = $aLastWeek['start'];
		$iLastWeekUntil = $aLastWeek['end'];
		
		$sTimeFrom = date('Y-m-d',$iCourseFrom);
		$sTimeUntil = date('Y-m-d',$iCourseUntil);
		
		$sTimeFromLastWeek = date('Y-m-d',$iLastWeekFrom);
		$sTimeUntilLastWeek	= date('Y-m-d',$iLastWeekUntil);

		$aSql['filter_week'] = $dWeek->format('Y-m-d');
		$aSql['course_from'] = $sTimeFrom;
		$aSql['course_until'] = $sTimeUntil;
		$aSql['block_id'] = $iBlockId;
		$aSql['room_id'] = $iRoomId;
		$aSql['lastweek_time_from'] = $sTimeFromLastWeek;
		$aSql['lastweek_time_until'] = $sTimeUntilLastWeek;
		$aSql['language_name'] = $sNameField;
		$aSql['current_date'] = $dSelectedDay->format('Y-m-d');

		$aSql['view'] = $this->_oGui->sView;
		#$aSql['without_invoice'] = (int)Ext_Thebing_System::getConfig('show_customer_without_invoice');

		if($this->_oGui->sView == 'unallocated') {
			/*
			$aSqlParts['where'] .= ' AND
				(
					(
						kic.from <= :course_until AND
						kic.until >= :course_from
					) OR
					(
						kihs.`inquiry_split_course_id` = kic.`id` AND
						kih.from <= :course_until AND
						kih.until >= :course_from
					)
				)
			';*/

			//Die auskommentierte Abfrage macht Probleme, falls Ferien und Kurs
			//gleichzeitig in eine Woche zutreffen (Ferien ab Samstag z.B.)
			$aSqlParts['where'] .= ' AND
				(
					(
						kic.from <= :course_until AND
						kic.until >= :course_from
					) OR (
						(
							`ts_ihs`.`journey_split_course_id` = `kic`.`id` OR (
								/* Bei journey_split_course_id = NULL wurde der Kurs lediglich verschoben, Ticket #.5215 */
								`ts_ihs`.`journey_course_id` = `kic`.`id` AND
								`ts_ihs`.`journey_split_course_id` IS NULL
							)
						) AND (
							:course_from < `ts_ih`.`until` AND
							:course_from >= `ts_ih`.`from`
						)
					)
				)
			';
			
			$aSqlParts['having'] = ' HAVING (course_lessons != allocation_lessons)';
			$aSqlParts['groupby'] = '
				inquiry_course_id,
				course_id';

			if(!empty($aFilter['courses'])) {
				$aSqlParts['where'] .= ' AND ktc_2.id IN (:courses)';
				$aSql['courses'] = (array)$aFilter['courses'];
			}			
			
		} else {

			$aSqlParts['where'] .= ' AND ktbic.block_id = :block_id';
			if($iRoomId > 0) {
                $aSqlParts['where'] .= ' AND ktbic.room_id = :room_id';
            }

			#$aSqlParts['where'] .= ' AND getRealDateFromTuitionWeek(ktb.week, :weekday, :course_startday) BETWEEN kic.from AND kic.until';
			$aSqlParts['groupby'] = '
				ki.id,
				kic.id';
			
			#$aSql['weekday'] = $iDay;
			#$aSql['course_startday'] = $oSchool->course_startday;
		}
		
		// Hier holen wir uns jetzt schon mal die JourneyIds die vom Zeitraum her passen, sonst dauert
		// der Query viel zu lang, weil in holidays&courses left join gemacht wird, kann mysql kein Zeitraum
		// Filter anwenden und fängt mit der Journey Tabelle an, und bei sehr vielen Buchungen ist das total lahm...
		$sSqlTemp = "
			(
				SELECT
					`journey_id`
				FROM
					`ts_inquiries_journeys_courses`
				WHERE
					`from` <= :course_until AND
					`until` >= :course_from
			) UNION ALL (
				SELECT
					`ts_ij`.`id` `journey_id`
				FROM
					`ts_inquiries_holidays` `ts_ih` INNER JOIN
					`ts_inquiries` `ts_i` ON
						`ts_i`.`id` = `ts_ih`.`inquiry_id` AND
						`ts_i`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_ij`.`active` = 1
				WHERE
					`ts_ih`.`active` = 1 AND
					(
						`ts_ih`.`from` <= :course_until AND
						`ts_ih`.`until` >= :course_from
					) OR (
						`ts_ih`.`from` <= :lastweek_time_until AND
						`ts_ih`.`until` >= :lastweek_time_from
					)
			)
		";
		
		$aJourneyIdsCourse = (array)DB::getQueryCol($sSqlTemp, $aSql);
		
		$aSql['journey_ids'] = $aJourneyIdsCourse;

		// Filter setzen, damit diese im WHERE-Teil des Querys eingebaut werden
		// Abfrage muss sein, da die ansonsten jedes Mal hinzugefügt werden würden
		if($this->_oGui->load_table_bar_data) {
			$this->setFilters();
		}

		// Filter in den Where Part einbauen
		$this->setQueryFilterDataByRef($aFilter, $aSqlParts, $aSql);

		// IDs mit filtern falls übergeben
		$this->setQueryIdDataByRef($aSelectedIds, $aSqlParts, $aSql);

		// WHERE an den SELECT anhängen
		$sSql .= $aSqlParts['where'];

		// Query um den GROUP BY Teil erweitern
		$this->setQueryGroupByDataByRef($sSql, $aSqlParts['groupby']);

		// HAVING an den SELECT anhängen
		$sSql .= $aSqlParts['having'];

		$aColumnList = $this->_oGui->getColumnList();

		if($aOrderBy === null) {
			// Sortiert alle Schüler die zu zu vielen Lektionen zugewiesen worden sind
			// ans Ende der Result-Liste
			$aSqlParts['orderby'] = str_replace('ORDER BY', 'ORDER BY (`course_lessons` - `allocation_lessons`) DESC,', $aSqlParts['orderby']);
		}

		// Query um den ORDER BY Teil erweitern und den Spalten die sortierung zuweisen
		$this->setQueryOrderByDataByRef($sSql, $aOrderBy, $aColumnList, $aSqlParts['orderby']);

		$iEnd = 0;

		if(!$bSkipLimit) {
			// LIMIT anhängen!
			$this->setQueryLimitDataByRef($iLimit, $iEnd, $sSql);
		}

		$aResult = $this->_getTableQueryData($sSql, $aSql, $iEnd, $iLimit);

		return $aResult;

	}

	public function initColumns() {

		$oSchool				= Ext_Thebing_School::getSchoolFromSession();
		$sLanguage				= $oSchool->getInterfaceLanguage();
		$aLevels				= $oSchool->getLevelList(true, $sLanguage, 'internal', true, true);

		$oGuiStudents			= &$this->_oGui;

		if(System::d('debugmode') == 2) {

			if($oGuiStudents->sView === 'allocated') {
				$oColumn = $oGuiStudents->createColumn();
				$oColumn->db_column = 'allocation_id';
				$oColumn->db_alias = '';
				$oColumn->title = 'KTBIC-ID';
				$oColumn->width	= Ext_TC_Util::getTableColumnWidth('id');
				$oGuiStudents->setColumn($oColumn);
			}

			$oColumn = $oGuiStudents->createColumn();
			$oColumn->db_column = 'inquiry_course_id';
			$oColumn->db_alias = '';
			$oColumn->title = 'IJC-ID';
			$oColumn->width	= Ext_TC_Util::getTableColumnWidth('id');
			$oGuiStudents->setColumn($oColumn);

			$oColumn = $oGuiStudents->createColumn();
			$oColumn->db_column = 'course_id';
			$oColumn->db_alias = '';
			$oColumn->title = 'KTC-ID';
			$oColumn->width	= Ext_TC_Util::getTableColumnWidth('id');
			$oGuiStudents->setColumn($oColumn);

			$oColumn = $oGuiStudents->createColumn();
			$oColumn->db_column = 'program_service_id';
			$oColumn->db_alias = '';
			$oColumn->title = 'PRGS-ID';
			$oColumn->width	= Ext_TC_Util::getTableColumnWidth('id');
			$oGuiStudents->setColumn($oColumn);
		}

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'customerNumber';
		$oColumn->db_alias					= '';
		$oColumn->title						= $this->t('K.Nr.');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('customer_number');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Kundennummer');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'short';
		$oColumn->db_alias					= 'kg';
		$oColumn->db_type					= 'varchar';
		$oColumn->select_column				= 'group_short';
		$oColumn->title						= '<i class="fa fa-users" alt="'.$this->t('Gruppe').'" title="'.$this->t('Gruppe').'"></i>';
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('group_short');
		$oColumn->width_resize				= false;
#		$oColumn->format					= new Ext_Thebing_Gui2_Format_ColumnTitle('group_name');
		$oColumn->css_class					= 'icon';
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'number';
		$oColumn->select_column				= 'group_number';
		$oColumn->db_alias					= 'kg';
		$oColumn->title						= $this->t('Gruppennummer');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize				= false;
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

        $oColumn							= $oGuiStudents->createColumn();
        $oColumn->db_column					= 'courselanguage_name';
        $oColumn->title						= $this->t('Kurssprache');
        $oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('name');
        $oColumn->default                   = false;
        $oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'checkin';
		$oColumn->title						= $this->t('Eingecheckt');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('datetime');
		$oColumn->default                   = false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Date_Time ();
		$oColumn->style						= new Ext_TS_Inquiry_Index_Gui2_Style_Checkin();
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->select_column = 'customer_name'; // Abhängigkeit im JS
		$oColumn->db_column = 'lastname'; 
		$oColumn->db_alias = 'cdb1';
		$oColumn->db_type					= 'varchar';
		$oColumn->title						= $this->t('Name');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('customer_name');
		$oColumn->width_resize				= true;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_School_Tuition_DivDraggable($this->_oGui->sView);
		$oColumn->mouseover_title			= $this->t('Name');
		$oColumn->css_overflow				= true;
		$oColumn->flexibility				= false; // Darf nicht flexiebel sein, da JS die Namen cashed
		$oColumn->order_settings = [
			'cdb1.lastname'=>'ASC', 
			'cdb1.firstname'=>'ASC'
		];
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->select_column = 'firstname';
		$oColumn->db_column = 'firstname';
		$oColumn->db_alias = 'cdb1';
		$oColumn->title = $this->t('Vorname');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_Thebing_Gui2_Format_School_Tuition_DivDraggable($this->_oGui->sView);
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->select_column = 'lastname';
		$oColumn->db_column = 'lastname_2';
		$oColumn->db_alias = 'cdb1';
		$oColumn->title = $this->t('Nachname');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_Thebing_Gui2_Format_School_Tuition_DivDraggable($this->_oGui->sView);
		$oColumn->order_settings = [
			'cdb1.lastname'=>'ASC'
		];
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'status_id';
		$oColumn->db_alias					= 'ki';
		$oColumn->title						= $this->t('Status');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize				= false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_CustomerStatus();
		$oColumn->mouseover_title			= $this->t('Schülerstatus');
		$oColumn->css_overflow				= true;
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

//		$oColumn = $oGuiStudents->createColumn();
//		$oColumn->db_column	= 'email';
//		$oColumn->title	= $this->t('E-Mail');
//		$oColumn->width	= Ext_Thebing_Util::getTableColumnWidth('email');
//		$oColumn->default = false;
//		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'gender';
		$oColumn->title						= $this->t('G');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('gender');
		$oColumn->width_resize				= false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Gender(true);
		$oColumn->mouseover_title			= $this->t('Geschlecht');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'age';
		$oColumn->title						= $this->t('Alter');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('age');
		$oColumn->width_resize				= false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Int();
		$oColumn->mouseover_title			= $this->t('Alter');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'mother_tongue_iso';
		$oColumn->title						= $this->t('MS');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('iso');
		$oColumn->width_resize				= false;
		$oColumn->format					= new Ext_Gui2_View_Format_Function('strtoupper');
		$oColumn->mouseover_title			= $this->t('Muttersprache');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->db_column	= 'nationality_iso';
		$oColumn->title	= $this->t('Nationalität');
		$oColumn->width	= Ext_Thebing_Util::getTableColumnWidth('iso');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Gui2_View_Format_Function('strtoupper');
		$oColumn->mouseover_title = $this->t('Nationalität');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'level';
		$oColumn->title						= $this->t('Level');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('short_name');
		$oColumn->sortable					= true;
		$oColumn->width_resize				= false;
		$oColumn->select_column			    = 'level_id';
		$oColumn->format				    = new Ext_Thebing_Gui2_Format_Dropdown($aLevels, array('class' => 'txt form-control input-sm levelSelect','style' => 'width:64px;'));
		$oColumn->mouseover_title			= $this->t('Level');
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'external_level';
		$oColumn->title						= $this->t('Level Ext');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('short_name');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Level Normal');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'course_type'; // Abhängigkeit im JS
		$oColumn->title						= $this->t('Kurs');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('short_name');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_School_Tuition_Coursename();
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Kurs');
		$oColumn->flexibility = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->title = $this->t('Gebuchte Kurse');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_Thebing_Gui2_Format_School_Tuition_BookedCourses();
		$oColumn->width_resize = false;
		$oColumn->mouseover_title = $this->t('Gebuchte Kurs');
		$oColumn->sortable = false;
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'course_category';
		$oColumn->title						= $this->t('Kurs Kategorie');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('customer_name');
		$oColumn->width_resize				= false;
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'course_closed';
		$oColumn->title						= $this->t('Geschlossener Unterricht');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('yes_no');
		$oColumn->width_resize				= false;
		$oColumn->default = false;
		$oColumn->format = new Ext_Gui2_View_Format_YesNo();
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'lesson_contingent_absolute';
		$oColumn->title						= $this->t('TGL');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Gebuchte Lektionen (Total)');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Float(2,false);
		$oColumn->default					= false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'lesson_contingent_remaining';
		$oColumn->title						= $this->t('TVL');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Verbleibende Lektionen (Total)');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Float(2,false);
		$oColumn->default					= false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'course_lessons';
		$oColumn->select_column				= 'booked_lessons';
		$oColumn->title						= $this->t('GL');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Gebuchte Lektionen');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Float(2,false);
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'remaining_lessons';
		$oColumn->title						= $this->t('VL');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Float(2,false);
		$oColumn->mouseover_title			= $this->t('Verbleibende Lektionen');
		$oColumn->style						= new Ext_Thebing_Gui2_Style_School_Tuition_Lessons();
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'allocation_lessons';
		$oColumn->title						= $this->t('ZL');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Zugewiesene Lektionen');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Float(2,false);
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'cancelled_lessons';
		$oColumn->title						= $this->t('AL');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Ausgefallene Lektionen');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Float(2,false);
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'state'; // Abhängigkeit im JS
		$oColumn->title						= $this->t('BS');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Status/Buchung');
		$oColumn->sortable					= false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_School_Tuition_State();
		$oColumn->flexibility = false; // JS prüft mit der Spalte, ob auf die Row ein Drag&Drop-Event darf
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'state_course';
		$oColumn->title						= $this->t('KS');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Status/Kurs');
		$oColumn->sortable					= false;
		$oColumn->format					= new Ext_Thebing_Gui2_Format_School_Tuition_State();
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'parallel_course';
		$oColumn->title						= $this->t('PC');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Parallelkurs');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_School_Tuition_Parallelcourse();
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);
		
		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'current_week';
		$oColumn->title						= $this->t('Woche');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('courseweek_from_until');
		$oColumn->width_resize				= false;
		//$oColumn->sortable					= false;
		$oColumn->mouseover_title			= $this->t('Woche');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_FromUntil('current_week','weeks_total');
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'all_weeks';
		$oColumn->title						= $this->t('WG');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('courseweek_from_until');
		$oColumn->width_resize				= false;
		//$oColumn->sortable					= false;
		$oColumn->mouseover_title			= $this->t('Wochen gesamt');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->select_column				= 'tuition_course_from';
		$oColumn->db_column					= 'from';
		$oColumn->db_alias					= 'tijcti';
		$oColumn->title						= $this->t('Start');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('date_short');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Start');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Date(true);
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->select_column				= 'tuition_course_until';
		$oColumn->db_column					= 'until';
		$oColumn->db_alias					= 'tijcti';
		$oColumn->title						= $this->t('Ende');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('date_short');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Ende');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Date(true);
		$oColumn->style						= new Ext_Thebing_Gui2_Style_School_Tuition_Until();
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'score';
		$oColumn->title						= $this->t('AS');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('courseweek_from_until');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Score der aktuellen Woche');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'score_last_week';
		$oColumn->title						= $this->t('LS');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('courseweek_from_until');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Score der letzten Woche');
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'comment';
		$oColumn->db_alias					= 'kic';
		$oColumn->title						= $this->t('Kommentar');
		$oColumn->width						= 140;
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Kommentar');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_ColumnTitle('comment', true, 20);
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->db_column = 'comment';
		$oColumn->db_alias = 'ts_ptr';
		$oColumn->select_column = 'placementtest_comment';
		$oColumn->title = $this->t('Einstufungstest Kommentar');
		$oColumn->width = 140;
		$oColumn->width_resize = false;
		$oColumn->mouseover_title = $this->t('Einstufungstest Kommentar');
		$oColumn->format = new Ext_Thebing_Gui2_Format_ColumnTitle('placementtest_comment', true, 20);
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->db_column = 'correct_answers';
		$oColumn->db_alias = 'ts_ptr';
		$oColumn->title = $this->t('Einstufungstest Richtige Antworten');
		$oColumn->width = 140;
		$oColumn->width_resize = false;
		$oColumn->mouseover_title = $this->t('Einstufungstest Richtige Antworten');
		$oColumn->format = new \Ts\Gui2\Format\TotalCorrectAnswers(); # Wert kommt über die Formatklasse
		$oColumn->sortable = false;
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);
		
		// Index Spalte
		$oClient = Ext_Thebing_System::getClient();
		$aInboxes = $oClient->getInboxList(true, false);

		$oColumn							= $oGuiStudents->createColumn();
		$oColumn->db_column					= 'inbox';
		$oColumn->select_column				= 'inbox';
		$oColumn->db_alias					= 'ki';
		$oColumn->title						= $this->t('Inbox');
		$oColumn->width						= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize				= false;
		$oColumn->mouseover_title			= $this->t('Inbox');
		$oColumn->format					= new Ext_Thebing_Gui2_Format_Select($aInboxes);		
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

		$oColumn = $oGuiStudents->createColumn();
		$oColumn->db_column = 'amount_open_original';
		$oColumn->select_column = 'amount_open_original';
		$oColumn->db_alias = 'ki';
		$oColumn->title = $this->t('Offener Betrag gesamt');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Amount();	
		$oColumn->style = new \Ts\Gui2\Style\Amount();
		$oColumn->default = false;
		$oGuiStudents->setColumn($oColumn);

	}

	/**
	 * Filter werden nicht in der GUI angezeigt; dienen nur für Query
	 */
	public function setFilters() {

		$oBar						= $this->_oGui->createBar();
		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= array('number','lastname','firstname', 'name', 'short', 'number');
		$oFilter->db_alias			= array('tc_c_n','cdb1','cdb1', 'kg', 'kg', 'kg');
		$oFilter->db_operator		= 'LIKE';
		$oFilter->id				= 'search';

		$oBar->setElement($oFilter);

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= 'id';
		$oFilter->db_alias			= 'ktc_2';
		$oFilter->db_operator		= '=';
		$oFilter->id				= 'course';

		$oBar->setElement($oFilter);

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= 'id';
		$oFilter->db_alias			= 'ktcc';
		$oFilter->db_operator		= '=';
		$oFilter->id				= 'course_category';

		$oBar->setElement($oFilter);

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= 'id';
		$oFilter->db_alias			= 'ktul';
		$oFilter->db_operator		= '=';
		$oFilter->id				= 'level';

		$oBar->setElement($oFilter);

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= 'state';
		$oFilter->db_alias			= 'titi';
		$oFilter->db_operator		= '&';
		$oFilter->id				= 'state';

		$oBar->setElement($oFilter);

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= 'state';
		$oFilter->db_alias			= 'tijcti';
		$oFilter->db_operator		= '&';
		$oFilter->id				= 'course_state';

		$oBar->setElement($oFilter);

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= 'inbox';
		$oFilter->db_alias			= 'ki';
		$oFilter->db_operator		= '=';
		$oFilter->id				= 'inbox';

		$oBar->setElement($oFilter);

		$oFilter = $oBar->createFilter();
		$oFilter->db_column = 'status_id';
		$oFilter->db_alias = 'ki';
		$oFilter->db_operator = '=';
		$oFilter->id = 'customer_status';
		$oBar->setElement($oFilter);

		$oFilter = $oBar->createFilter();
		$oFilter->db_column = 'courselanguage_id';
		$oFilter->db_alias = 'kic';
		$oFilter->db_operator = '=';
		$oFilter->id = 'levelgroup_filter';
		$oBar->setElement($oFilter);

		$aYesNo = Ext_TC_Util::getYesNoArray(false);
		$oFilter = $oBar->createFilter();
		$oFilter->id = 'checkin_filter';
		$oFilter->db_column='checkin';
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aYesNo);
		$oFilter->filter_query = [
			'no' => '
					`checkin` IS NULL ',
			'yes' => '
					`checkin` IS NOT NULL '
		];
		$oBar->setElement($oFilter);

		$this->_oGui->setBar($oBar);
	}

	public function manipulateTableDataResultsByRef(&$aResult)
	{
		global $_VARS;

		parent::manipulateTableDataResultsByRef($aResult);

		$iWeek = $_VARS['filter']['week'];
		$aWeek = Ext_Thebing_Util::getWeekTimestamps($iWeek);

		$sType = $this->_oGui->sView;

		$oWdDate	= new WDDate($iWeek);

		$iWeekDay	= (int)$_VARS['weekday'];
		if($iWeekDay <= 0){
			$iWeekDay = 1;
		}

		$oWdDate->set($iWeekDay, WDDate::WEEKDAY);

		foreach((array)$aResult['data'] as $iKey => $aResultRow) {

			$sIdAddon = $aResultRow['inquiry_course_id'].'_'.$aResultRow['program_service_id'].'_'.$aResultRow['id'];
			$aResult['data'][$iKey]['multiple_checkbox_id'] = 'checkbox_inquiry_'.$sType.'_'.$sIdAddon;

			$aResult['data'][$iKey]['crs_time_from']	= $aResultRow['tuition_course_from'];
			$aResult['data'][$iKey]['crs_time_to']		= $aResultRow['tuition_course_until'];
			$aResult['data'][$iKey]['current_week']		= $aResultRow['tuition_course_current_week'];
			$aResult['data'][$iKey]['weeks_total']		= $aResultRow['tuition_course_total_weeks'];

			$aTemp					= array();
			$aTemp['week_from']		= $aResultRow['tuition_inquiry_current_week'];
			$aTemp['week_until']	= $aResultRow['tuition_inquiry_total_weeks'];
			
			$oFormat = new Ext_Thebing_Gui2_Format_FromUntil('week_from', 'week_until'); 
			
			$sAllWeeks = $oFormat->format($aTemp, $aTemp, $aTemp);
			
			$aResult['data'][$iKey]['all_weeks']	= $sAllWeeks;
			
			$sState									= $aResultRow['tuition_inquiry_state'];
			$aResult['data'][$iKey]['state']		= $sState;
			
			$sCourseState							= $aResultRow['tuition_course_state'];
			$aResult['data'][$iKey]['state_course']	= $sCourseState;

			$aResult['data'][$iKey]['last_week'] = 0;
			// L-Bit prüfen
			if($sState & Ext_TS_Inquiry_TuitionIndex::STATE_LAST) {
				$aResult['data'][$iKey]['last_week'] = 1;
			}

			// Bei Ferien soll in der Wochenspalte die Ferienwoche (absolut und relativ) angezeigt werden
			if(
				$sState & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION &&
				!($sState & Ext_TS_Inquiry_TuitionIndex::STATE_NEW) &&
				!($sState & Ext_TS_Inquiry_TuitionIndex::STATE_CONTINUOUS) &&
				!($sState & Ext_TS_Inquiry_TuitionIndex::STATE_LAST)
			) {
				$aResult['data'][$iKey]['current_week'] = $aResultRow['holiday_current_week'];
				$aResult['data'][$iKey]['weeks_total'] = $aResultRow['holiday_weeks'];
			}

			if(
				$aResultRow['journey_course_state'] & \Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION &&
				\Carbon\Carbon::createFromTimestamp($aWeek['start']) > \Carbon\Carbon::parse($aResult['data'][$iKey]['lessons_catch_up_original_until'])
			) {
				// Durch Kursausfall verlängerte Kurswoche
				$aResult['data'][$iKey]['extended_due_cancellation'] = 1;
			} else {
				$aResult['data'][$iKey]['extended_due_cancellation'] = 0;
			}

			$iRemainingLessons = $aResultRow['course_lessons'] - $aResultRow['allocation_lessons'];

			$aResult['data'][$iKey]['remaining_lessons'] = $iRemainingLessons;

			if($iRemainingLessons > 0) {
				$aResult['data'][$iKey]['has_remaining'] = 1;
			} else {
				$aResult['data'][$iKey]['has_remaining'] = 0;
			}

		}

		return $aResult;

	}

	public function switchAjaxRequest($_VARS) {

		if($_VARS['task']=='changeStudentLevel') {

            $_VARS['id']        = (array)$_VARS['id'];
			$iSelectedId		= (int)reset($_VARS['id']);
			$iInquiryCourseId	= $this->_oGui->decodeId($iSelectedId,'inquiry_course_id');
			$iProgramServiceId	= $this->_oGui->decodeId($iSelectedId,'program_service_id');
			$iLevelId			= (int)$_VARS['level_id'];
			$iWeek				= $_VARS['week'];
			$iWeekDay			= $_VARS['weekday'];

			if(
				!empty($iInquiryCourseId) && 
				!empty($iProgramServiceId)
			) {

				$oInquiryCourse = new Ext_TS_Inquiry_Journey_Course($iInquiryCourseId);
				$oRequestProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($iProgramServiceId);

				$oLevel = Ext_Thebing_Tuition_Level::getInstance($iLevelId);

				// Über WDDate gehen, da hier ein gefährlicher lokaler Timestamp ankommt
				$oWeek = new WDDate($iWeek);
				$sDate = $oWeek->get(WDDate::DB_DATE);
				$dWeek = new DateTime($sDate);

				/* @var \Ext_Thebing_Tuition_LevelGroup $oLevelGroup */
				$oLevelGroup = $oInquiryCourse->getLevelGroup();

				$oInquiryCourse->saveProgress($dWeek, $oLevel, $oLevelGroup, $oRequestProgramService, $iWeekDay);

				// Alle Kurse der Buchung mit derselben Levelgruppe holen um das Level für die Woche auch bei diesen Kursen zu setzen
				$aProgressJourneyCourses = $this->_getJourneyCoursesByDateAndLevelgroup($oInquiryCourse, $oLevelGroup, $sDate);
				// Auch Leistungen vom ausgewählten Kurs prüfen
				$aProgressJourneyCourses[] = $oInquiryCourse;

				foreach($aProgressJourneyCourses as $oProgressJourneyCourse) {

					$aProgramServices = $oProgressJourneyCourse->getProgram()
						->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

					foreach($aProgramServices as $oProgramService) {
						/* @var \Ext_Thebing_Tuition_Course $oCourse*/
						$oCourse = $oProgramService->getService();

						if(
							// Wurde oben schon gespeichert
							(int)$oRequestProgramService->getId() === (int)$oProgramService->getId() ||
							// Anstellungen haben kein Level
							$oCourse->isEmployment()
						) {
							continue;
						}

						// Wenn die Programmleistung einen eigenen Zeitraum hat dann dürfen - vermutlich - nur Kurse in
						// derselben Woche angepasst werden (siehe _getJourneyCoursesByDateAndLevelgroup())
						if($oProgramService->hasDates()) {
							$dWeekEnd = (clone $dWeek)->modify('+6 days');
							if(!\Core\Helper\DateTime::checkDateRangeOverlap($oProgramService->getFrom(), $oProgramService->getUntil(), $dWeek, $dWeekEnd)) {
								continue;
							}
						}

						$oProgramCourseLevelGroup = $oProgressJourneyCourse->getLevelgroup();
						if($oLevelGroup->id == $oProgramCourseLevelGroup->id) {
							$oProgressJourneyCourse->saveProgress($dWeek, $oLevel, $oLevelGroup, $oProgramService, $iWeekDay);
						}
					}
				}

			}

			//Die Liste muss neu geladen werden, wegen Levelgruppen
			$aTransfer = array();
			$aTransfer['action'] = 'changeStudentLevelCallback';
			$aTransfer['data'] = array();
			$aTransfer['data']['selectedRows'] = (array)$_VARS['selected_ids'];

			echo json_encode($aTransfer);
		}
		elseif($_VARS['task']=='openDialog' && $_VARS['action']=='move_student_question')
		{
			$bCheck = $this->_checkIfLastWeek($_VARS['block_id']);
			if(!$bCheck)
			{
                $aTransfer = parent::_switchAjaxRequest($_VARS);
                $aTransfer['data']['buttons']= array(
                    array('label' => L10N::t('Ja')),
                    array('label' => L10N::t('Nein')),
                );
			}
			else
			{
				//letzte Woche, Frage nicht einblenden, Aktion sofort durchführen
				$aTransfer['action']				= 'executeAction';
				$aTransfer['all_weeks']				= 0;
				$aTransfer['data']['additional']	= $_VARS['additional'];
			}
			echo json_encode($aTransfer);
			$this->_oGui->save();
			die();
		}
		else
		{
			parent::switchAjaxRequest($_VARS);
		}
	}

	/**
	 * Funktion liefert alle Kurse der Buchung, die in einer Woche liegen und dieselbe Levelgruppe haben
	 * 
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @param Ext_Thebing_Tuition_LevelGroup $oLevelGroup
	 * @param string $sDate
	 * @return array
	 */
	protected function _getJourneyCoursesByDateAndLevelgroup(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, Ext_Thebing_Tuition_LevelGroup $oLevelGroup, $sDate) {
		
		$oInquiry = $oJourneyCourse->getInquiry();
		
		// Alle Kurs holen, die in diese Woche fallen
		$aJourneyCourses = $oInquiry->getCourses();
		
		$aReturn = array();
		foreach($aJourneyCourses as $oTempJourneyCourse) {
			// Der Kurs aus dem Parameter muss nicht erneut berücksichtigt werden
			if($oTempJourneyCourse->id == $oJourneyCourse->id) {
				continue;
			}
			
			$oDate = new WDDate($sDate, WDDate::DB_DATE);
			$oDate2 = new WDDate($sDate, WDDate::DB_DATE);
			$oDate2->add(6, WDDate::DAY);

			$oDateFrom	= new WDDate($oJourneyCourse->from, WDDate::DB_DATE);
			$oDateUntil = new WDDate($oJourneyCourse->until, WDDate::DB_DATE);

			$iCompare = WDDate::comparePeriod($oDate, $oDate2, $oDateFrom, $oDateUntil);			
			
			// Nur Kurse die sich überschneiden
			if(
				$iCompare == WDDate::PERIOD_AFTER ||
				$iCompare == WDDate::PERIOD_BEFORE
			) {
				continue;
			}
			
			$oCourse = $oTempJourneyCourse->getCourse();
			$oTempLevelGroup = $oCourse->getLevelgroup();
			
			// Nur Kurse mit derselben Levelgruppe werden berücksichtigt
			if($oTempLevelGroup->id == $oLevelGroup->id) {
				$aReturn[$oTempJourneyCourse->id] = $oTempJourneyCourse;
			}			
		}
		
		return $aReturn;
	}

	/**
	 * @TODO Kann man das hier auf $oClass->getLastDate() umstellen?
	 *
	 * @param $iBlockId
	 * @return bool
	 */
	protected function _checkIfLastWeek($iBlockId)
	{
		$oBlock		= Ext_Thebing_School_Tuition_Block::getInstance((int)$iBlockId);
		$oClass		= $oBlock->getClass();

		$iStartWeek	= $oClass->start_week_timestamp;
		$iAllWeeks	= (int)$oClass->weeks;

		$oWdDate	= new WDDate($oBlock->week, WDDate::DB_DATE);
		$iWeekDiff	= $oWdDate->getDiff(WDDate::WEEK, $iStartWeek, WDDate::TIMESTAMP);

		$iCurrentWeek = $iWeekDiff + 1;
		if($iCurrentWeek<$iAllWeeks)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true)
	{
		if ($sIconAction === 'communication' && !isset($this->aIconData[$sIconKey = self::getIconKey($sIconAction, $sAdditional)])) {
			/* @var Ext_Gui2_Dialog $oDialog */
			$oDialog = Factory::executeStatic(Ext_TC_Communication::class, 'createDialogObject', [$this->_oGui, [], $sAdditional]);
			$this->aIconData[$sIconKey]['dialog_data'] = $oDialog;
		}

		return parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
	}

	public function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false)
	{
		global $_VARS;

		if ($sIconAction === 'communication') {
			return parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
		}

		$aData					= array();
		$aData['id']			= 'ID_0';
		$aData['title']			= $this->t('Schülerzuweisung');
		$aData['width']			= '500';
		$aData['height']		= '300';
		$sHtml					= '';
		if($_VARS['additional']=='move')
		{
			$sHtml					.= $this->t('Wollen Sie den/die Schüler für alle Folgewochen übernehmen?');
		}
		elseif($_VARS['additional']=='delete_block')
		{
			$sHtml					.= $this->t('Wollen Sie diesen Block für alle Folgewochen löschen?');
		}
		else
		{
			$sHtml					.= $this->t('Wollen Sie den/die Schüler für alle Folgewochen löschen?');
		}

		$aData['html']			= $sHtml;

		return $aData;
	}

	public function getTranslations($sL10NDescription)
	{
		$aTranslations = parent::getTranslations($sL10NDescription);
		
		$aTranslations['error_no_students'] = $this->t('Bitte wählen Sie mindestens einen Schüler aus!');
		$aTranslations['confirm_delete_block'] = $this->t('Möchten Sie diesen Block wirklich löschen?');
		$aTranslations['confirm_delete_student'] = $this->t('Möchten Sie diesen Schüler wirklich löschen?');
		$aTranslations['action_not_allowed'] = $this->t('Diese Aktion ist nicht zulässig!');
		$aTranslations['move_allocated_student_alert'] = $this->t('Bitte beachten Sie, dass das Niveau automatisch angepasst wird.');
		$aTranslations['confirm_clear_students'] = $this->t('Möchten Sie wirklich alle Schüler aus dieser Klasse in dieser Woche löschen?');
		$aTranslations['error_no_students'] = $this->t('Bitte wählen Sie mindestens einen Schüler aus!');
		$aTranslations['pls_wait'] = $this->t('Eine andere Anfrage wird gerade bearbeitet! Bitte warten Sie einen Moment.');
		
		return $aTranslations;
	}

}
