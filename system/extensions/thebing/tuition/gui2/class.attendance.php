<?php

class Ext_Thebing_Tuition_Gui2_Attendance extends Ext_Thebing_Document_Gui2 {

	//damit ist nicht view/list gemeint, sondern der filter "übersicht"
	protected $_sAttendanceView;
	
	protected static $_iSortDirection = 1;
	
	protected static $_sAttendanceField;
	
	protected $_bFormatFlexValues = false;

	public function switchAjaxRequest($_VARS) {

		if($_VARS['task']== 'loadTable') {

			$oSchool		= Ext_Thebing_School::getSchoolFromSession();
			$aTransfer		= parent::_switchAjaxRequest($_VARS); 

			$aFilterDates	= $this->_getFilterDates();
			$sDateFrom		= $aFilterDates['from'];
			$sDateUntil		= $aFilterDates['until'];
			
			//Klassenfilter
			$aLabelItem = Ext_Gui2_Util::addLabelItem(array(), $this->t('Klasse'), '');
			$aLabelItem = array('id'=>'', 'name'=>reset($aLabelItem));

			$aTransfer['classes_filter'] = $oSchool->getClasses($sDateFrom, $sDateUntil, false);
			
			$aTransfer['classes_filter'] = array_merge(array($aLabelItem), $aTransfer['classes_filter']);

			$aTransfer['classes_filter'] = Ext_Thebing_Util::convertArrayForSelectOptions($aTransfer['classes_filter'],'name', 'id');		

			echo json_encode($aTransfer);
			$this->_oGui->save();

			die();

		}

		if($_VARS['task'] == 'save_inputs') {

			$aTransfer = array();

			$mBack = $this->_saveInputs((array)$_VARS);

			if($mBack === true)
			{
				// Tabelle neu laden nach dem Speichern
				$aTransfer				= array();
				$aTransfer['action']	= 'saveDialogCallback';
				$aTransfer['error']		= array();
				$aTransfer['data']		= array();
				$aTransfer['data']['bAfterSaveOption'] = false;	
			}
			else
			{
				$aTransfer			= array();
				$aTransfer['action']	= 'saveDialogCallback';
				$aTransfer['error'] = (array)$mBack;
				$aTransfer['data']	= array();
				$aTransfer['data']['action']	= 'saveDialogCallback';
				#$aTransfer['data']['id']		= 'ID_0';
			}

			echo json_encode($aTransfer);
			
			return;
			
			#die();
		}
		
		if($_VARS['task'] == 'request')
		{
			if($_VARS['action'] == 'student_login_release' || $_VARS['action'] == 'student_login_release_remove')
			{
                DB::begin('student_login_release');
                
				$aAllocations	= (array)$this->_oGui->decodeId($_VARS['id'], 'id');
                
                $aError         = array();
                
                if($_VARS['action'] == 'student_login_release')
                {
                    $oDateTime      = new DateTime();
                    $sDate          = $oDateTime->format('Y-m-d');   
                }
                else
                {
                    $sDate          = '0000-00-00';
                }
                
                foreach($aAllocations as $iAllocation)
                {
                    $iAllocation    = (int)$iAllocation;
                    $oAllocation	= Ext_Thebing_School_Tuition_Allocation::getInstance($iAllocation);
                    $oAttendance	= $oAllocation->getAttendance();

                    if($oAttendance)
                    {
                        $oAttendance->student_login_release = $sDate;
                        $mReturn		= $oAttendance->save();   

                        if($mReturn !== true && !$mReturn instanceof Ext_Thebing_Tuition_Attendance)
                        {
                            $aError[] = $mReturn;
                        }   
                    }
                }
				
				if(empty($aError))
				{
                    DB::commit('student_login_release');
                    
					$aTransfer['message']				= $this->t('Erfolgreich veröffentlicht.');
					$aTransfer['action']				= "releaseCallback";
				}
				else
				{
                    DB::rollback('student_login_release');
                    
					$aTransfer['action']				= 'showError';
					$aTransfer['error'][0]['message']	= array(
						$this->getTranslation('error_dialog_title'),
					);
					$aTransfer['error'][1]['message']	= array(
						$this->t('Fehler beim veröffentlichen!'),
					);
				}
				
				echo json_encode($aTransfer);

				return;
			}
		}

		if(
			$_VARS['task'] === 'requestAsUrl' &&
			$_VARS['action'] === 'report'
		) {
			$aDecodedData = $this->_oGui->decodeId($_VARS['id']);

			# #20135
			foreach ($aDecodedData as $decodedData) {
				$inquiries[] = Ext_TS_Inquiry::getInstance($decodedData['inquiry_id']);
			}

			$oService = new \TsTuition\Generator\AttendanceReport($inquiries, Closure::fromCallable([$this, 't']));
			$oService->render();

			return;
		}

		parent::switchAjaxRequest($_VARS);

	}

	/**
	 * Liste basiert auf Blockzuweisungen, aber hier müssen Anwesenheitseinträge gelöscht werden
	 *
	 * @param int $iRowId
	 * @return bool
	 */
	protected function deleteRow($iRowId) {

		$aAttendanceIds = (array)$this->_oGui->decodeId([$iRowId], 'attendance_id');
		foreach($aAttendanceIds as $iAttendanceId) {
			if(is_numeric($iAttendanceId)) {
				$oAttendance = Ext_Thebing_Tuition_Attendance::getInstance($iAttendanceId);
				$oAttendance->delete();
			}
		}

		return true;

	}
	
	protected function _prepareTableQueryData(&$aSql, &$sSql) {

		$sView = $this->getView();
		
		if($sView == 'edit') {
			$oWeek = new WDDate($aSql['filter_1_0'], WDDate::DB_DATE);

			$aWeek = $oWeek->getWeekLimits();

			$oStart = new WDDate($aWeek['start']);
			$oEnd   = new WDDate($aWeek['end']);

			$aSql['filter_course_period_from']  = $oStart->get(WDDate::DB_DATE);
			$aSql['filter_course_period_until'] = $oEnd->get(WDDate::DB_DATE);
		} else {
			$aSql['filter_course_period_from']  = $aSql['filter_1_0'];
			$aSql['filter_course_period_until'] = $aSql['filter_2_0'];	
		}

		$sAttendanceView = $this->getAttendanceView();

		$aSql['attendance_view'] = $sAttendanceView;

		$aSql['view'] = $sView;

		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iCritical	= (int)$oSchool->critical_attendance;
		
		$aSql['critical_config'] = $iCritical;
		
		$aFilter = array(
			'school_id' => $oSchool->id,
			'week_from' => $aSql['filter_course_period_from'],
			'week_until' => $aSql['filter_course_period_until'],
		);
		
		$oAllocation = new Ext_Thebing_School_Tuition_Allocation();
		$tmpTableName = $oAllocation->getAttendancePossibleAllocationsTmpTable($sAttendanceView, $aFilter);
		
		$aSql['allocation_ids_tmp_tbl'] = $tmpTableName;
		
//		$aIds = $oAllocation->getAttendancePossibleAllocations($sAttendanceView, $aFilter);
//		$aSql['allocation_ids'] = $aIds;
	}

	/**
	 * @inheritdoc
	 */
	protected function setQueryOrderByDataManualSort(&$sSql, &$aOrderBy, $oColumn) {

		$aAttendanceDays = array_flip(Ext_Thebing_Tuition_Attendance::DAY_MAPPING);

		// Logisch sortieren: Mit Wert, ohne Wert (aber Inputs vorhanden, Wert 0), keine Inputs vorhanden (Wert -1)
		// Selbe Abfrage findet auch in Ext_Thebing_Gui2_Format_School_Tuition_DayInput statt
		if(isset($aAttendanceDays[$oColumn->db_column])) {

			$iDay = $aAttendanceDays[$oColumn->db_column];
			$iDayAdd = $iDay - 1;

			$sSql .= "
				IF(
					(
						INSTR(GROUP_CONCAT(`ktbd`.`day`), $iDay) > 0 AND
						(`block_week` + INTERVAL {$iDayAdd} DAY) BETWEEN `journey_course_from` AND `journey_course_until`
					),
					IFNULL(`{$aOrderBy['db_column']}`, 0),
					-1
				) {$aOrderBy['order']}
			";

			return;
		}

		parent::setQueryOrderByDataManualSort($sSql, $aOrderBy, $oColumn);

	}

	public static function getWeeks() {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$aWeeks = Ext_Thebing_Util::getWeekOptions(WDDate::DB_DATE, 3, $oSchool->course_startday);

		return $aWeeks;
	}

	protected function _saveInputs(array $aVars) {

		$aIds	= (array)$aVars['id'];

		$oWeek = new WDDate($aVars['filter']['week_filter'], WDDate::DB_DATE);
		$oWeek->set(0, WDDate::HOUR);
		$oWeek->set(0, WDDate::MINUTE);
		$oWeek->set(0, WDDate::SECOND);
		
		$aDecodedGuiIds = (array)$this->_oGui->decodeId($aVars['id'], 'id');

		$aSaveFlex = $this->_prepareFlexSaveFields($aVars, $aDecodedGuiIds);
		
		// Flex Felder validieren
		$aFlexErrors = $this->validateFlexFields($aSaveFlex);

		if(empty($aFlexErrors)) {

			foreach((array)$aVars['comment'] as $iRowId => $sComment) {

				if(!in_array($iRowId,$aDecodedGuiIds)){
					continue;
				}

				$oAllocation	= Ext_Thebing_School_Tuition_Allocation::getInstance($iRowId);

				$aDays = array();
				foreach((array)$aVars['minutes'][$iRowId] as $iDay => $iValue) {
					$hours = $aVars['hours'][$iRowId][$iDay];

					if (empty($hours)) {
						$hours = 0;
					}

					$iMin = (int)$iValue + ($hours * 60);
					$aDays[$iDay] = $iMin;
				}

				foreach((array)$aVars['checkbox'][$iRowId] as $iDay=>$sValue) {
					if(
						!$sValue ||
						$sValue == 'null'
					) {
						$aVars['checkbox'][$iRowId][$iDay] = 0;
					} else {
						$aVars['checkbox'][$iRowId][$iDay] = 1;
					}
				}

				Ext_Thebing_Tuition_Attendance::getRepository()->saveAttendance($oAllocation, [
					'absence_per_day' => $aDays,
					'absence_complete_per_day' => (array)$aVars['checkbox'][$iRowId],
					'absence_excused_per_day' => (array)$aVars['excused'][$iRowId],
					'absence_reason_per_day' => (array)$aVars['absence_reason'][$iRowId],
					'score' => $aVars['score'][$iRowId],
					'comment' => $aVars['comment'][$iRowId]
				]);

			}

			// Flexibilität abspeichern
			$this->_saveFlexFields($aSaveFlex);

			return true;
		}
		else
		{
			$aErrors = $this->_manipulateFlexErrors($aFlexErrors);

			return $aErrors;
		}
	}

	public function prepareColumnListByRef(&$aColumnList) {

		parent::prepareColumnListByRef($aColumnList);

		$aNewArray = array();

		if(System::d('debugmode') == 2) {
			$oColumn = new Ext_Gui2_Head();
			$oColumn->db_column = 'attendance_id';
			$oColumn->title = 'ID';
			$oColumn->width = 50;
			$oColumn->sortable = false;
			$aNewArray[] = $oColumn;
		}

		$aFlexFields = Ext_TC_Flexibility::getSectionFieldData(array($this->_oGui->_aConfig['sSection']), true);

		$sAttendanceView	= $this->getAttendanceView();
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();

		foreach($aColumnList as $oColumn){

			if($sAttendanceView == 'inquiry'){
				//wenn schülerbezogen, dann lehrer und kurs anwesenheit ausblenden
				if(
					$oColumn->db_column != 'journey_course_teacher_attendance_period_original' &&
					$oColumn->db_column != 'journey_course_teacher_attendance_all_original' &&
					$oColumn->db_column != 'journey_course_attendance_period_original' &&
					$oColumn->db_column != 'journey_course_attendance_all_original' &&
					$oColumn->db_column != 'teacher_name_original' &&
					$oColumn->db_column != 'course_name_original' &&
					$oColumn->db_column != 'level_name' &&
					$oColumn->db_column != 'course_start_original'
				){
					$aNewArray[] = $oColumn;
				}
			}elseif($sAttendanceView == 'journey_course'){
				//wenn kursbezogen, dann lehrer ausblenden
				if(
					$oColumn->db_column != 'journey_course_teacher_attendance_period_original' &&
					$oColumn->db_column != 'journey_course_teacher_attendance_all_original' &&
					$oColumn->db_column != 'teacher_name_original'
				){
					$aNewArray[] = $oColumn;
				}
			}
			else{
				//wenn allgemeine ansicht oder view='edit', nichts ausblenden, alles anzeigen
				$aNewArray[] = $oColumn;
			}
			
			if(strpos($oColumn->db_column, 'flex_') !== false)
			{
				$iFlexFieldId = (int)str_replace('flex_', '', $oColumn->db_column);
				
				if(isset($aFlexFields[$iFlexFieldId]))
				{
					$oFlexField		= $aFlexFields[$iFlexFieldId];
					
					if($oFlexField->type == Ext_TC_Flexibility::TYPE_TEXT)
					{
						$oColumn->format = new Ext_Thebing_Gui2_Format_Input(103, 'form-control input-sm multiple_handle');
					}
					elseif($oFlexField->type == Ext_TC_Flexibility::TYPE_CHECKBOX)
					{
						$oColumn->format = new Ext_Thebing_Gui2_Format_Input(false, 'multiple_handle', 'checkbox');
					}
					elseif($oFlexField->type == Ext_TC_Flexibility::TYPE_SELECT)
					{
						$aFieldOptions								= Ext_TC_Flexibility::getOptions($iFlexFieldId, $sInterfaceLanguage);
						$aFieldOptions								= Ext_Thebing_Util::addEmptyItem($aFieldOptions);
						$oColumn->format							= new Ext_Thebing_Gui2_Format_Dropdown($aFieldOptions, array(
							'class' => 'form-control input-sm multiple_handle',
							'style'	=> 'width:103px;'
						));
					}
				}
				
			}
		}

		$aColumnList = $aNewArray;
	}
	
	/**
	 *
	 * Where-part für die IndexListe der Anwesenheit
	 * 
	 * @return array 
	 */
	public static function getListWhere(Ext_Gui2 $oGui=null){
		
		$aWhere			= array();
		
		$bIsAllSchools	= Ext_Thebing_System::isAllSchools();
		
		if(!$bIsAllSchools){
			$oSchool				= Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId				= (int)$oSchool->id;
			$aWhere['ts_i_j.school_id']	= (string)$iSchoolId;
		};
		
		return $aWhere;
	}
	
	/**
	 *
	 * Methode um festzustellen in welcher Ansicht wir uns gerade befinden (editieren/übersicht)
	 * 
	 * @return string 
	 */
	public function getView()
	{
		$sView = $this->_oGui->sView;
		
		return $sView;
	}

	/**
	 *
	 * Zeitraum Filter "von/bis" Werte bekommen, wenn per VARS was übermittelt wurde kommt dieser Wert auch zurück, ansonsten
	 * werden Standardzeiträume definiert
	 * 
	 * @return array 
	 */
	protected function _getFilterDates()
	{
		global $_VARS;
		
		$sDateFrom	= null;
		$sDateUntil	= null;
		
		$oSchool	= Ext_Thebing_School::getSchoolFromSession();

		if( //Attendance (Overview)
			isset($_VARS['filter']['search_time_from_1']) &&
			isset($_VARS['filter']['search_time_until_1'])
		) { //selektierte Datumsangaben

			$sDateFrom  = $_VARS['filter']['search_time_from_1'];
			$sDateUntil = $_VARS['filter']['search_time_until_1'];

			$sDateFrom  = Ext_Thebing_Format::ConvertDate($sDateFrom, $oSchool->id, true);
			$sDateUntil = Ext_Thebing_Format::ConvertDate($sDateUntil,$oSchool->id, true);

		}else if( //Attendace (Action)
			isset($_VARS['filter']['week_filter'])
		) {

			$sDateFrom = $_VARS['filter']['week_filter'];

			$oWeek = new WDDate($sDateFrom, WDDate::DB_DATE);
			$aWeek = $oWeek->getWeekLimits();

			$oStart = new WDDate($aWeek['start']);
			$oEnd   = new WDDate($aWeek['end']);

			$sDateFrom  = $oStart->get(WDDate::DB_DATE);//timestamp?
			$sDateUntil = $oEnd->get(WDDate::DB_DATE);


		} else { //default Datumsangaben

			$aFilterElements = $this->_oGui->getAllFilterElements();
			$aWeekFilters = array();

			foreach($aFilterElements as $iKey => &$oElement) {

				//overview
//				if( $oElement->element_type == 'timefilter' && $oElement->from_id == 'search_time_from_1' ) {
//
//					$sDateFrom  = $oElement->default_from;
//					$sDateUntil = $oElement->default_until;
//
//					$sDateFrom  = Ext_Thebing_Format::ConvertDate($sDateFrom, $oSchool->id, true);
//					$sDateUntil = Ext_Thebing_Format::ConvertDate($sDateUntil,$oSchool->id, true);
//
//					break;
//
//				} else
				// Register
				if (
					$oElement instanceof Ext_Gui2_Bar_Filter &&
					$oElement->id == 'week_filter'
				) {
					
					$sWeek = $oElement->value;

					if($sWeek != '') {
						$oWeek = new WDDate($sWeek, WDDate::DB_DATE);

						$aWeek = $oWeek->getWeekLimits();
						$oStart = new WDDate($aWeek['start']);
						$oEnd = new WDDate($aWeek['end']);

						$sDateFrom  = $oStart->get(WDDate::DB_DATE);//timestamp?
						$sDateUntil = $oEnd->get(WDDate::DB_DATE);
						break;
					}

				}

				// Overview besteht aus zwei Selects als Zeitraumfilter
				if (
					$oElement instanceof Ext_Gui2_Bar_Filter && (
						$oElement->id === 'week_from_filter' ||
						$oElement->id === 'week_until_filter'
					)
				) {
					$aWeekFilters[$oElement->id] = $oElement;
				}

			}

			// Werte von Overview-Filtern setzen
			if(count($aWeekFilters) === 2) {
				$sDateFrom = $aWeekFilters['week_from_filter']->value;
				$sDateUntil = $aWeekFilters['week_until_filter']->value;
			}
		}
		
		return array(
			'from'	=> $sDateFrom,
			'until'	=> $sDateUntil,
		);
	}
	
	/**
	 *
	 * Standard-Wert im Wochenselect in der Anwesenheit (aktuelle Woche)
	 * 
	 * @return string(db_date) 
	 */
	public static function getDefaultWeekFilterValue()
	{
		$aCurrentWeekData		= Ext_Thebing_Util::getWeekTimestamps();
		$oWeek = new WDDate($aCurrentWeekData['start']);
		$sCurrentWeekStart = $oWeek->get(WDDate::DB_DATE);
		
		return $sCurrentWeekStart;
	}
	
	public static function getDefaultWeekFilterFromValue()
	{
		$oDate = new WDDate();
		$oDate->sub(4, WDDate::WEEK);
		
		$aWeek = $oDate->getWeekLimits();
		
		$oWeek = new WDDate($aWeek['start']);

		$sCurrentWeekStart = $oWeek->get(WDDate::DB_DATE);

		return $sCurrentWeekStart; 
	}
	
	public static function getDefaultWeekFilterUntilValue()
	{
		$oDate = new WDDate();
		
		$aWeek = $oDate->getWeekLimits();
		
		$oWeek = new WDDate($aWeek['start']);

		$sCurrentWeekStart = $oWeek->get(WDDate::DB_DATE);

		return $sCurrentWeekStart; 
	}
	
	/**
	 *
	 * Lehrer-Filter Dropdown Optionen
	 * 
	 * @return array
	 */
	public static function getFilterSelectTeacherOptions(Ext_Gui2 $oGui)
	{
		$oSchool	= Ext_Thebing_Client::getFirstSchool();
		$aTeachers	= $oSchool->getTeacherList(true);
		$aTeachers	= Ext_Gui2_Util::addLabelItem($aTeachers, $oGui->t('Lehrer'));
		
		return $aTeachers;
	}
	
	/**
	 *
	 * Visum-Filter Dropdown Optionen
	 * 
	 * @return array
	 */
	public static function getVisaFilterOptions(Ext_Gui2 $oGui = null, $bForSelect = true)
	{
		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$aVisas = Ext_Thebing_Visum::getVisumStatusList($oSchool->id);
		
		if($oGui instanceof Ext_Gui2) {
			$sLabelNoVisa = $oGui->t('Kein Visum');
			$sLabelVisa = $oGui->t('Visum');
		} else {
			$sLabelNoVisa = L10N::t('Kein Visum', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
			$sLabelVisa = L10N::t('Visum', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
		}
		
		if($bForSelect) {
			$aVisas = Ext_Thebing_Util::addEmptyItem($aVisas, $sLabelNoVisa, 0);
			$aVisas = Ext_Gui2_Util::addLabelItem($aVisas, $sLabelVisa, 'xNullx');
		}

		return $aVisas;
	}

	/**
	 * Filter-Query für Visa manuell bauen wegen IS NULL und = (unterschiedliche Operatoren)
	 *
	 * @return array
	 */
	public static function getVisaFilterQuery() {

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$aVisas = Ext_Thebing_Visum::getVisumStatusList($oSchool->id);

		$aFilterQuery = array(
			0 => " `ts_j_t_v_d`.`status` IS NULL OR `ts_j_t_v_d`.`status` = 0 "
		);

		foreach($aVisas as $iVisa => $sVisa) {
			$aFilterQuery[$iVisa] = " `ts_j_t_v_d`.`status` = $iVisa ";
		}

		return $aFilterQuery;
	}

	/**
	 *
	 * Level-Filter Dropdown Optionen
	 * 
	 * @return array
	 */
	public static function getLevelFilterOptions(Ext_Gui2 $oGui)
	{
		$oSchool			= Ext_Thebing_Client::getFirstSchool();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$aLevels			= $oSchool->getLevelList(true, $sInterfaceLanguage, 1);
		$aLevels			= Ext_Gui2_Util::addLabelItem($aLevels, $oGui->t('Level'));
		
		return $aLevels;
	}
	
	public static function getDefaultTimeFilterFrom()
	{
		// Datum auf den "letzten" Montag stellen 
		// #2296
		$oDate = new WDDate();
		$oDate->set(1,  WDDate::WEEKDAY);
		$iLastMonth = $oDate->get(WDDate::TIMESTAMP);
		
		$sLastMonth = Ext_Thebing_Format::LocalDate($iLastMonth);

		return $sLastMonth;
	}
	
	public static function getDefaultTimeFilterUntil()
	{
		// Datum auf den "übernächsten" Freitag stellen 
		// #2296
		$oDate = new WDDate();
		$iDay = $oDate->get(WDDate::WEEKDAY);
		$oDate->set(5,  WDDate::WEEKDAY);
		if($iDay > 5){
			$oDate->add(2,  WDDate::WEEK);
		} else {
			$oDate->add(1,  WDDate::WEEK);
		}
		
		$iNextMonth = $oDate->get(WDDate::TIMESTAMP);
		
		$sNextMonth = Ext_Thebing_Format::LocalDate($iNextMonth);
		
		return $sNextMonth;
	}
	
	public static function getAttendanceViews(Ext_Gui2 $oGui)
	{
		$aAttendanceView = array(
			'journey_course_teacher'	=> $oGui->t('Allgemeine Übersicht'),
			'journey_course'			=> $oGui->t('Übersicht pro Kurs'),
			'inquiry'					=> $oGui->t('Übersicht pro Schüler'),
		);
		
		return $aAttendanceView;
	}

	public function getAttendanceView()
	{
		$sAttendanceView	= false;
		$sView				= $this->getView();//Übersicht oder Bearbeiten
		
		if($sView == 'list')
		{
			//Übersichtsfilter gibt es nur in der Anwesenheitsliste
			if(isset($this->_aFilter['attendance_view']))
			{
				$sAttendanceView = $this->_aFilter['attendance_view'];
			}
			else
			{
				//Default ist die Kursbezogene Ansicht
				$sAttendanceView = 'journey_course';
			}
		}
		else
		{
			$sAttendanceView = 'allocation';
		}
		
		return $sAttendanceView;
	}
	
	/**
	 * Filter Optionen ob Anwesenheit eingetragen wurde
	 * 
	 * @param Ext_Gui2 $oGui
	 * @return array 
	 */
	public static function getAttendanceFilterOptions(Ext_Gui2 $oGui) {
		$aAttendanceSavedOptions = array(
			'no' => $oGui->t('Nein'),
			'incomplete' => $oGui->t('Unvollständig'),
			'yes' => $oGui->t('Vollständig')
		);
		
		$aAttendanceSavedOptions = Ext_Gui2_Util::addLabelItem($aAttendanceSavedOptions, $oGui->t('Eingetragen'), 'xNullx');
		
		return $aAttendanceSavedOptions;
	}

	/**
	 * 
	 * 
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getCriticalOptions(Ext_Gui2 $oGui)
	{
		$aCritical	= array(
			'critical'		=>  $oGui->t('kritisch'),
			'not_critical'	=>  $oGui->t('nicht kritisch'),
		);
		
		$aCritical = Ext_Gui2_Util::addLabelItem($aCritical, $oGui->t('Anwesenheit'), 'xNullx');
		
		return $aCritical;
	}
	
	protected function _saveFlexFields($aSaveFlex)
	{
		foreach($aSaveFlex as $iAllocationId => $aSaveFlexFields)
		{
			$aSave = array(
				$iAllocationId => $aSaveFlexFields
			);

			$oDialog = new Ext_Gui2_Dialog();
			$this->saveEditDialogDataFlex($oDialog, $iAllocationId, $aSave);
		}
		
		return;
	}
	
	/**
	 * Bei der Anwsenheit sehen die VARS ganz anders aus wie die Vars aus einem Dialog für die Flexibilität, 
	 * die bringen wir hier auf ein gemeinsamen Nenner
	 * 
	 * @param array $aVars
	 * @param array $aDecodedGuiIds
	 * @return array 
	 */
	protected function _prepareFlexSaveFields(array $aVars, array $aDecodedGuiIds)
	{
		$aFlexibleSelectFields	= Ext_TC_Flexibility::getSectionFieldData(array('tuition_attendance_register'));
		$aSaveFlex				= array();
		
		foreach($aFlexibleSelectFields as $aFieldData)
		{
			$iFieldId	= $aFieldData['id'];
			$sColumn	= 'flex_' . $iFieldId;
			
			if(isset($aVars[$sColumn]))
			{
				foreach($aVars[$sColumn] as $iAllocationId => $mValue)
				{
					if(!in_array($iAllocationId, $aDecodedGuiIds))
					{
						continue;
					}
					
					#$aSaveFlex				= array();
					#$aSaveFlex[$iFieldId]	= $mValue;
					
					#Ext_TC_Flexibility::saveData($aSaveFlex, $iAllocationId);
					
					$aSaveFlex[$iAllocationId][$iFieldId] = $mValue;
				}
			}
		}
		
		return $aSaveFlex;
	}
	
	protected function _manipulateFlexErrors($aFlexErrors)
	{
		$aErrors			= array(
			$this->getTranslation('error_dialog_title')
		);

		foreach($aFlexErrors as $iAllocationId => $aError)
		{
			$oAllocation		= Ext_Thebing_School_Tuition_Allocation::getInstance($iAllocationId);
			$oJourneyCourse		= $oAllocation->getJourneyCourse();
			$oInquiry			= $oJourneyCourse->getInquiry();
			$oCustomer			= $oInquiry->getCustomer();		
			$sCustomerName		= $oCustomer->getName();
			
			$oBlock				= $oAllocation->getBlock();
			$oTempalte			= $oBlock->getTemplate();
			$sTemplateInfo		= $oTempalte->getNameAndTime();
			
			$oClass				= $oBlock->getClass();
			$sClassName			= $oClass->getName();
			
			foreach($aError as $aErrorData)
			{
				if(!empty($aErrorData['title']))
				{
					$aErrorData['message'] =  $aErrorData['title'] . ': ' . $aErrorData['message'];
				}
				
				$aErrorData['message'] .= ' ( ';

				$aErrorData['message'] .= $sCustomerName;

				$aErrorData['message'] .= ' / ';

				$aErrorData['message'] .= $sClassName;

				$aErrorData['message'] .= ' / ';

				$aErrorData['message'] .= $sTemplateInfo;	
				
				$aErrorData['message'] .= ' ) ';
				
				$aErrors[] = $aErrorData;
			}

		}
		
		return $aErrors;
	}
    
	public function requestReport($aVars) {

		$oEntity = $this->_getWDBasicObject($aVars['id']);
		
		$oDialog = new Ext_Gui2_Dialog();
		$oDialog->width = 1600;
		$oDialog->save_button = false;
		$oDialog->sDialogIDTag = 'ATTENDANCE_REPORT_';
		
		$oIframe = new Ext_Gui2_Html_Iframe();
		$oIframe->src = '/wdmvc/ts-tuition/interface/view?from='.$aVars['filter']['week_from_filter'].'&until='.$aVars['filter']['week_until_filter'];
		$oIframe->style = 'width: 100%; height: 100%; border: 0;';

		$oDialog->setElement($oIframe);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);
		
		$aTransfer['data']['title'] = $this->t('Anwesenheitsübersicht');
		
		$aTransfer['data']['no_scrolling'] = true;
		$aTransfer['data']['no_padding'] = true;
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		
		return $aTransfer;
	}

	static public function getCourseCategoriesFilterOptions(Ext_Gui2 $oGui) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aCategories = $oSchool->getCourseCategoriesList('select');

		$aCategories = Ext_Gui2_Util::addLabelItem($aCategories, $oGui->t('Kategorie'));

		return $aCategories;
	}

    public static function getCourses($short = false)
    {
        $oSchool = Ext_Thebing_School::getSchoolFromSession();
        $aCourses = $oSchool->getCourseList(true, false, false, $short);

        return $aCourses;
    }
	
}
