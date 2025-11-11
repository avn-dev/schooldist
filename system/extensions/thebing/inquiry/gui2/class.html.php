<?php


class Ext_Thebing_Inquiry_Gui2_Html {

	static public $sL10NDescription = '';
	static public $oCalendarFormat;

	/**
	 * Erzeugt einen "+" Button zum hinzufügen neuer Kurse, Unterkünfte oder Versicherungen
	 *
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param string $sTab
	 * @param bool $bReadOnly
	 * @return Ext_Gui2_Html_Div
	 */
	static public function getAddButton(Ext_Gui2_Dialog $oDialogData, $sTab, $bReadOnly = false) {

		if(
			$sTab == 'course' ||
			$sTab == 'course_guide'
		){
			$sLabel = L10N::t('Weiterer Kurs', self::$sL10NDescription);
		} else if(
			$sTab == 'accommodation' ||
			$sTab == 'accommodation_guide'
		){
			$sLabel = L10N::t('Weitere Unterkunft', self::$sL10NDescription);
		} else if($sTab == 'transfer'){
			$sLabel = L10N::t('Weiterer Transfer', self::$sL10NDescription);
		} else if($sTab == 'insurance'){
			$sLabel = L10N::t('Weitere Versicherung', self::$sL10NDescription);
		} elseif($sTab === 'email') {
			$sLabel = L10N::t('E-Mail hinzufügen', self::$sL10NDescription);
		} elseif($sTab === 'activity') {
			$sLabel = L10N::t('Weitere Aktivität', self::$sL10NDescription);
		}else {
			$sLabel = L10N::t('Hinzufügen', self::$sL10NDescription);
		}
		
		$oButtonRow = new Ext_Gui2_Html_Div();
		$oButtonRow->class = 'GUIDialogRow form-group';
		
		$oButtonContainer = new Ext_Gui2_Html_Div();
		$oButtonContainer->class = 'col-sm-12';#'col-sm-offset-3 col-sm-10';
		
		$oButton = new Ext_Gui2_Html_Button();
		$oButton->id = 'add_new_'.$sTab;
		$oButton->class = 'btn btn-default btn-sm';
		
		$oImg = Ext_Gui2_Html::getIconObject(Ext_Thebing_Util::getIcon('add'));
		
		$oButton->setElement($oImg);
		
		$oButton->setElement('&nbsp;'.$sLabel);
		
		$oButtonContainer->setElement($oButton);
		$oButtonRow->setElement($oButtonContainer);

		return $oButtonRow;
	}

	/**
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aCategories
	 * @param array $aCourses
	 * @param array $aLevels
	 * @param array $aInquiryCourse
	 * @param string $sSavePrefix
	 * @return mixed
	 */
	static public function getCourseBodyHtml($oDialogData, $aCategories, $aCourses, $aLevels, $aInquiryCourse = array(), $sSavePrefix = 'course', $bGroup=false) {

		$interfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		/* @var $oGui \Ext_TC_Gui2 */
		$oGui = $oDialogData->getDataObject()->getGui();
		
		$bSetCourseDataHidden = false;

		$bDisabled = $aInquiryCourse['disabled'];

		if(isset($aInquiryCourse['group_id'])) {
			$iInquiryId = (int)$aInquiryCourse['group_id'];
		} else {
            //neuer Eintrag
            if(isset($aInquiryCourse['inquiry_id'])) {
                $iInquiryId					= (int)$aInquiryCourse['inquiry_id'];
            // bestehender Eintrag hat kein inquiry_id
            } else {
                $oJourney = Ext_TS_Inquiry_Journey::getInstance((int)$aInquiryCourse['journey_id']);		
                $oInquiry = $oJourney->getInquiry();
                $iInquiryId					= (int)$oInquiry->id;
            }
			
			
			#$oInquiry			= Ext_TS_Inquiry::getInstance($iInquiryId);
			$iGroupId			= (int)$oInquiry->group_id;
			if($iGroupId>0){
				$oInquiryGroup	= Ext_Thebing_Inquiry_Group::getInstance($iGroupId);
				if($oInquiryGroup->course_data == 'only_time'){
					$bSetCourseDataHidden = true;
				}
			}
		}
		
		$oFloatFormat = new Ext_Thebing_Gui2_Format_Float(2, false);

		/* @var \TsTuition\Dto\CourseLessons $courseLessons */
		$courseLessons = $aInquiryCourse['course_lessons'] ?? new \TsTuition\Dto\CourseLessons([], \TsTuition\Enums\LessonsUnit::PER_WEEK);

		$iInquiryCourseId	= (int)$aInquiryCourse['id'];
		$iCourseId			= (int)$aInquiryCourse['course_id'];
		$iCourselanguageId = (int)$aInquiryCourse['courselanguage_id'];
		$iLevelId			= (int)$aInquiryCourse['level_id'];
		$iProgramId			= (int)$aInquiryCourse['program_id'];
		$iWeeks				= (int)$aInquiryCourse['weeks'];

		if (!$bGroup) {
			$aAdditionalServices = (array)$aInquiryCourse['additionalservices'];
		} else {
			$groupCourse = Ext_Thebing_Inquiry_Group_Course::getInstance($aInquiryCourse['id']);
			$aAdditionalServices = [];
			foreach ($groupCourse->additionalservices as $additionalserviceData) {
				$aAdditionalServices[] = $additionalserviceData['additionalservice_id'];
			}
		}

		$iUnits				= $oFloatFormat->format($aInquiryCourse['units']);
		$aLessons			= \Illuminate\Support\Arr::wrap($aInquiryCourse['lessons']);
		$bFixLessons		= (bool)$aInquiryCourse['lessons_fix'];
		$sFrom				= self::$oCalendarFormat->format($aInquiryCourse['from']);
		$sUntil				= self::$oCalendarFormat->format($aInquiryCourse['until']);
		$sComment			= $aInquiryCourse['comment'];
		$bFlexibleAllocation = (bool)$aInquiryCourse['flexible_allocation'];

		$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);

		$aPrograms = [];
		if($iCourseId > 0) {
			$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);

			$aPrograms = $oCourse->getPrograms()->mapWithKeys(function(\TsTuition\Entity\Course\Program $oProgram) {
					return [$oProgram->getId() => $oProgram->getName()];
				})
				->toArray()
			;
		}

		$oDiv = $oDialogData->create('div');
		$oDiv->id = $sSavePrefix . '_' . $iInquiryCourseId;
		$oDiv->class = 'box-body'; // class wird komplett benutzt, um ID rauszuziehen

		$oDivRow = $oDialogData->create('div');
		$oDivRow->class = 'grid grid-cols-1 lg:grid-cols-2';
		
		$oDivColLeft = $oDialogData->create('div');
		
		$oDivColRight = $oDialogData->create('div');
		
        $sLabel = L10N::t('Kategorie', self::$sL10NDescription);
        $sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][category_id]';
        $sName	= $sSavePrefix . '['.$iInquiryCourseId.'][category_id]';
        $sClass	= $sSavePrefix.'_category_select txt';
        // no_save_data bringt hier nichts da das Speichern in einer eigenen Klasse passiert
        $oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aCategories, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_course_category'));
        $oDivColLeft->setElement($oRow);

		$sLabel = L10N::t('Kurs', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][course_id]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][course_id]';
		$sClass	= 'courseSelect txt';
		
		// Hiddenfeld für Kurs damit immer mitgeschickt wird
		$sIdHidden = str_replace('course_id', 'course_id_hidden', $sId);

		$oHidden			= $oDialogData->create('input');
		$oHidden->type		= 'hidden';
		$oHidden->name		= $sName;
		$oHidden->id		= $sIdHidden;
		$oHidden->value		= $iCourseId;
		$oHidden->class		= 'courseSelectHidden';
		$oDivColLeft->setElement($oHidden);

		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aCourses, 'default_value'=> $iCourseId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_course'));
		$oDivColLeft->setElement($oRow);
		
		// Kurssprache
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][courselanguage_id]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][courselanguage_id]';
		$sClass	= 'courseLanguageSelect txt';
		$oRow = $oDialogData->createRow(
			L10N::t('Kurssprache', self::$sL10NDescription), 
			'select', 
			[
				'select_options' => Ext_Thebing_Tuition_LevelGroup::getInstance()->getArrayList(true, 'name_'.$interfaceLanguage), 
				'default_value'=> $iCourselanguageId, 
				'id' => $sId, 
				'name' => $sName, 
				'class' => $sClass, 
				'disabled' => $bDisabled, 
				'row_class' => 'row_course_languages',
				'info_icon_key' => 'inquiry_course_courselanguage'
			]
		);
		$oDivColLeft->setElement($oRow);

		// Level
		$sLabel = L10N::t('Level', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][level_id]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][level_id]';
		$sClass	= $sSavePrefix.'_level_select txt';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aLevels, 'default_value'=> $iLevelId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_course_level'));
		$oDivColLeft->setElement($oRow);

		$sLabel = L10N::t('Anzahl der Einheiten', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][units]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][units]';
		$oRow = $oDialogData->createMultiRow($sLabel, [
			'row_class' => 'row_units',
			'items' => [
				[
					'input' => 'input',
					'value'=> $iUnits,
					'id' => $sId,
					'name' => $sName,
					'class' => 'units lessons-input',
					'disabled' => $bDisabled,
					'format' => new Ext_Thebing_Gui2_Format_Float(),
					'info_icon_key' => 'inquiry_course_units'
				],
				[
					'input' => 'select',
					'default_value'=> $iUnits,
					'id' => str_replace('[units]', '[units_dummy]', $sId),
					'name' => str_replace('[units]', '[units_dummy]', $sName),
					'class' => 'units lessons-select',
					'disabled' => $bDisabled,
					'select_options' => array_combine($courseLessons->getLessons(), $courseLessons->getLessons()),
					'text_after' => ' <span class="lessons_unit">'.$courseLessons->getUnit()->getLabelText($oGui->getLanguageObject()).'</span>',
					'style' => 'display: none',
				]
			]
		]);
		$oDivColLeft->setElement($oRow);

		$sLabel = L10N::t('Programm', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][program_id]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][program_id]';
		$sClass	= $sSavePrefix.'_program_select txt';
		$sRowClass ='row_program';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aPrograms, 'default_value'=> $iProgramId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'row_class' => $sRowClass));
		$oDivColLeft->setElement($oRow);

		$oPeriodContainer = new Ext_Gui2_Html_Div();
		$oPeriodContainer->class = 'course_period_container';

		$oCourseInfoElement = new Ext_Gui2_Html_Div();
		$oCourseInfoElement->class = 'course_info_container';
		
		$oPeriodContainer->setElement($oDialogData->createRow(L10N::t('Verfügbarkeit', self::$sL10NDescription), $oCourseInfoElement, ['info_icon_key' => 'inquiry_course_availability']));

		// Wochen
		$aWeekRow = [
			'items' => [],
			'row_class' => 'row_weeks',
		];
		
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][weeks]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][weeks]';

		$oRefreshI = Ext_Gui2_Html::getIconObject('fa-refresh');
		
		$oRefreshImg = new Ext_Gui2_Html_Button();
		$oRefreshImg->title = L10N::t('Enddatum neu berechnen', self::$sL10NDescription);
		$oRefreshImg->onclick="return false;";
		$oRefreshImg->class = 'recalculate_course_enddate btn btn-default btn-sm';
		$oRefreshImg->id = str_replace('[weeks]', '[refresh]', $sId);

		$oRefreshImg->setElement($oRefreshI);

		$aWeekRow['items'][] = [
			'input'=>'input',
			'value'=>$iWeeks, 
			'id' => $sId, 
			'name' => $sName, 
			'class' => 'courseWeeks',
			'style' => 'width:40px;',
			'input_div_addon' => $oRefreshImg,
			'disabled' => $bDisabled,
			'info_icon_key' => 'inquiry_course_weeks'
		];
		$oDivWeeks = $oDialogData->createMultiRow($oGui->t('Wochenanzahl'), $aWeekRow);
		$oPeriodContainer->setElement($oDivWeeks);
		
		// Zeitraum
		$aPeriodRow = [
			'items' => []
		];
		
		// Von
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][from]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][from]';
		$sIdCalendar	= $sSavePrefix . 'calendar['.$iInquiryId.']['.$iInquiryCourseId.'][from]';
		$sClass	= 'calculateCourseUntil txt';
		if($sSavePrefix == 'course_guide') {
			$sClass .= ' calculateCourseUntilGuide';
		} else {
			$sClass .= ' calculateCourseUntilNormal';
		}

		$aPeriodRow['items'][] = [
			'input'=>'calendar',
			'value'=>$sFrom, 
			'id' => $sId, 
			'name' => $sName, 
			'calendar_id' => $sIdCalendar, 
			'class' => $sClass,
			'disabled' => $bDisabled,
			#'text_before' => '&nbsp;&nbsp;'.$oGui->t('Von'),
			'info_icon_key' => 'inquiry_course_period'
		];
		
		// Bis
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][until]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][until]';
		$sIdCalendar	= $sSavePrefix . 'calendar['.$iInquiryId.']['.$iInquiryCourseId.'][until]';
		$sClass	= 'calculateCourseTo txt';
		if($sSavePrefix == 'course_guide'){
			$sClass .= ' calculateCourseToGuide';
		}else{
			$sClass .= ' calculateCourseToNormal';
		}

		$textAfter = '';
		if ($oJourneyCourse->state & \Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION) {
			$message = L10N::t('Dieser Kurs wurde aufgrund von Klassenausfall automatisch verlängert (Vorheriges Enddatum: %s)', self::$sL10NDescription);
			$message = sprintf($message, (new Ext_Thebing_Gui2_Format_Date())->format($oJourneyCourse->lessons_catch_up_original_until));

			$textAfter = sprintf(
				//'<span class="label label-danger no-floating" title="%s"><i class="fas fa-arrows-alt-h"></i></span>',
				'<i class="fa fa-exclamation-circle" style="color: %s" title="%s"></i>',
				Ext_Gui2_Util::getColor('red_font'),
				$message,
			);
		}

		$aPeriodRow['items'][] = [
			'input'=>'calendar',
			'value'=>$sUntil, 
			'id' => $sId, 
			'name' => $sName, 
			'calendar_id' => $sIdCalendar, 
			'class' => $sClass,
			'disabled' => $bDisabled, 
			'element_class' => 'row_until',
			'text_before' => '<span class="row_until">&nbsp;'.$oGui->t('bis').'</span>',
			'text_after' => $textAfter
		];
		
		$oDivPeriod = $oDialogData->createMultiRow(L10N::t('Zeitraum', self::$sL10NDescription), $aPeriodRow);

		if($bSetCourseDataHidden) {
			
			$sIdHidden		= str_replace('weeks','weeks_hidden',$sId);
			$sNameHidden	= str_replace('weeks','weeks_hidden',$sName);
			$oHidden		= $oDialogData->createSaveField('hidden', array('value'=>$iWeeks, 'id' => $sIdHidden, 'name' => $sNameHidden, 'disabled' => $bDisabled));
			$oDivPeriod->setElement($oHidden);

			$sIdHidden		= str_replace('from','from_hidden',$sId);
			$sNameHidden	= str_replace('from','from_hidden',$sName);
			$oHidden		= $oDialogData->createSaveField('hidden', array('value'=>$sFrom, 'id' => $sIdHidden, 'name' => $sNameHidden, 'disabled' => $bDisabled));
			$oDivPeriod->setElement($oHidden);
			
			$sIdHidden		= str_replace('until','until_hidden',$sId);
			$sNameHidden	= str_replace('until','until_hidden',$sName);
			$oHidden		= $oDialogData->createSaveField('hidden', array('value'=>$sUntil, 'id' => $sIdHidden, 'name' => $sNameHidden, 'disabled' => $bDisabled));
			$oDivPeriod->setElement($oHidden);
			
		}

		$oPeriodContainer->setElement($oDivPeriod);
		$oDivColLeft->setElement($oPeriodContainer);

		// Flexible Zuweisung - Checkbox
		$sLabel = L10N::t('Flexible Zuweisung', self::$sL10NDescription);
		$sId = $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][flexible_allocation]';
		$sName = $sSavePrefix . '['.$iInquiryCourseId.'][flexible_allocation]';
		$oRow = $oDialogData->createRow($sLabel, 'checkbox', array('default_value' => $bFlexibleAllocation, 'id' => $sId, 'name' => $sName, 'row_class' => 'row_flex_allocation', 'info_icon_key' => 'inquiry_course_flex_allocation'));
		$oDivColRight->setElement($oRow);

		$sLabel = L10N::t('Kommentar', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][comment]';
		$sName	= $sSavePrefix . '['.$iInquiryCourseId.'][comment]';
		$sClass	= 'courseComment txt form-control autoheight';
		$oRow = $oDialogData->createRow($sLabel, 'textarea', array('value'=>$sComment, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_course_comment'));
		$oDivColRight->setElement($oRow);

		$sId = $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][additionalservices]';
		$sName = $sSavePrefix . '['.$iInquiryCourseId.'][additionalservices][]';
		$oRow = $oDialogData->createMultiRow($oGui->t('Zusatzleistungen'), [
			'items' => [
				[
					'input' => 'select',
					'multiple' => 3,
					'style' => 'width: 100%',
					'jquery_multiple' => 1,
					'searchable' => 1,
					'id' => $sId,
					'name' => $sName,
					'class' => 'additionalservices_select',
					'multi_rows' => true,
					'default_value' => $aAdditionalServices,
					'select_options' => array_flip($aAdditionalServices),
					'info_icon_key' => 'inquiry_course_additionalservices'
				]
			]
		]);
		$oDivColRight->setElement($oRow);

		if (
			isset($oCourse) &&
			$oCourse->automatic_renewal &&
			$aInquiryCourse['automatic_renewal_origin'] === null
		) {
			// Anderer Name, damit GUI-Validate nicht nervt. Reihenfolge hinter Kommentar ist wichtig!
			$oDivColRight->setElement($oDialogData->createRow($oGui->t('Kündigung'), 'checkbox', [
				'default_value' => !!$aInquiryCourse['automatic_renewal_cancellation'],
				'id' => $sSavePrefix . '['.$iInquiryId.']['.$iInquiryCourseId.'][automatic_renewal_cancel]',
				'name' => $sSavePrefix . '['.$iInquiryCourseId.'][automatic_renewal_cancel]',
				'info_icon_key' => 'inquiry_course_automatic_renewal_cancellation'
			]));
		}
		
		// Individuelle Felder pro Kursbuchung müssen individuell behandelt werden
		if(!isset($aInquiryCourse['group_id'])) {
			$oData = $oDialogData->oGui->getDataObject(); /** @var Ext_TS_Inquiry_Index_Gui2_Data $oData */
			$oDivColRight->setElement($oData->getFlexEditDataHTML($oDialogData, ['student_record_journey_course'], $oJourneyCourse, 0, 1, $sSavePrefix.'['.$iInquiryCourseId.'][flex]'));
		}

		$oDivRow->setElement($oDivColLeft);
		$oDivRow->setElement($oDivColRight);
		
		$oDiv->setElement($oDivRow);
		
		return $oDiv;
	}

    static public $_iLowestTransferKey = 0;
    
	static public function getTransferBodyHtml($oDialogData, $aTransfer, $oTransfer, $bDisabled, $iTransferType = 0){
		
		$iTransferId = $oTransfer->id;
		if($oTransfer instanceof Ext_Thebing_Inquiry_Group_Transfer) {
			$iInquiryId = (int)$oTransfer->group_id;
		}
		else {
			$oJourney = Ext_TS_Inquiry_Journey::getInstance((int)$oTransfer->journey_id);		
			$oInquiry = $oJourney->getInquiry();
			$iInquiryId	= (int)$oInquiry->id;
			// TODO für Gruppen einbauen
			// Bezahlungen prüfen
			$aPayments = $oTransfer->getJoinedObjectChilds('accounting_payments_active');
			if(count($aPayments) > 0){
				// Das sollte NICHT geändert werden #1870
				$bDisabled = true;
			}
		}	

		$iStartId				= (int) $oTransfer->start;
		$iStartAdditional		= (int) $oTransfer->start_additional;
		$iEndId					= (int) $oTransfer->end;
		$iEndAdditional			= (int) $oTransfer->end_additional;
		$sDate					= self::$oCalendarFormat->format($oTransfer->transfer_date);
		$sTime					= substr($oTransfer->transfer_time, 0 , 5);
		$sPickup				= substr($oTransfer->pickup, 0 , 5);
		$sAirline				= $oTransfer->airline;
		$sFlightnumber			= $oTransfer->flightnumber;
		$sComment				= $oTransfer->comment;

		$aStartTerminals = [];
		$aEndTerminals = [];

		// Hier müssen ALLE Terminals reingeschrieben werden, damit der Wert überhaupt gesetzt werden kann
		// Vom JS wird dann die Abhängigkeit zwischen Location und Terminal aufgelöst (updateAdditionalTransferSelect)
		$aTransferTerminals = Ext_TS_Transfer_Location_Terminal::getGroupedTerminals();
		foreach($aTransferTerminals as $aTransferTerminals2) {
			$aStartTerminals += $aTransferTerminals2;
			$aEndTerminals += $aTransferTerminals2;
		}

		// Nur Individuelle Transfers haben Terminals
		/*if($oTransfer->start_type == 'location'){
			$aStartTerminals		= Ext_Thebing_Data_Airport::getTerminals($iStartId, true);
		}
		if($oTransfer->end_type == 'location'){
			$aEndTerminals			= Ext_Thebing_Data_Airport::getTerminals($iEndId, true);
		}
        
        if(!empty($aStartTerminals)){
            $aStartTerminals = Ext_TC_Util::addEmptyItem($aStartTerminals);
        }

        if(!empty($aEndTerminals)){
            $aEndTerminals = Ext_TC_Util::addEmptyItem($aEndTerminals);
        }*/

		// Select IDs zusammensetzen
		$iStartId	= $oTransfer->start_type . '_' . $iStartId;
		$iEndId		= $oTransfer->end_type . '_' . $iEndId;

		// is umschreiben bei An-Abreise damit daten mit gesendet werden
		if(
			$iTransferId == 0
		){
			$iTransferId = self::$_iLowestTransferKey;;
            self::$_iLowestTransferKey--;
		}


		$oDivTransfer	= $oDialogData->create('div');
		$oDivTransfer->id = 'transfer_' . $iTransferId;
		$oDivTransfer->class = 'box-body'; // class wird komplett benutzt, um ID rauszuziehen

		$sLabel = L10N::t('Anreiseort', self::$sL10NDescription);
		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][start]';
		$sName	= 'transfer['.$iTransferId.'][start]';
		$sClass	= 'txt additional_transfer_from';
		if($iTransferType == 1){
			$sClass.= ' airports_arrival';
		} else if($iTransferType == 2){
			$sClass.= ' airports_departure';
		} else {
			$sClass.= ' airports_individual';
		}
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aTransfer, 'default_value'=> $iStartId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_start'));
		$oDivTransfer->setElement($oRow);

		// Terminal etc.
		$sLabel = L10N::t('Von Zusatz', self::$sL10NDescription);
		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][start_additional]';
		$sName	= 'transfer['.$iTransferId.'][start_additional]';
		$sClass	= 'txt';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aStartTerminals, 'default_value'=> $iStartAdditional, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_start_additional'));
		$oRow->style = 'display: none;';
		$oDivTransfer->setElement($oRow);


		$sLabel = L10N::t('Ankunftsort', self::$sL10NDescription);
		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][end]';
		$sName	= 'transfer['.$iTransferId.'][end]';
		$sClass	= 'txt additional_transfer_end';
		if($iTransferType == 1){
			$sClass.= ' airports_arrival';
		} else if($iTransferType == 2){
			$sClass.= ' airports_departure';
		} else {
			$sClass.= ' airports_individual';
		} 
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aTransfer, 'default_value'=> $iEndId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_end'));
		$oDivTransfer->setElement($oRow);

		// Terminal etc.
		$sLabel = L10N::t('Nach Zusatz', self::$sL10NDescription);
		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][end_additional]';
		$sName	= 'transfer['.$iTransferId.'][end_additional]';
		$sClass	= 'txt';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aEndTerminals, 'default_value'=> $iEndAdditional, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_end_attitional'));
		$oRow->style = 'display: none;';
		$oDivTransfer->setElement($oRow);





		if(
			$iTransferType == 1 ||
			$iTransferType == 2
		){
			// Airline
			$sLabel = L10N::t('Fluglinie', self::$sL10NDescription);
			$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][airline]';
			$sName	= 'transfer['.$iTransferId.'][airline]';
			$sClass	= 'txt';
			$oRow					= $oDialogData->createRow( $sLabel, 'input', array('id' => $sId, 'value' => $sAirline, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_airline'));
			$oDivTransfer			->setElement($oRow);

			// Flugnummer
			$sLabel = L10N::t('Flugnummer', self::$sL10NDescription);
			$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][flightnumber]';
			$sName	= 'transfer['.$iTransferId.'][flightnumber]';
			$sClass	= 'txt';
			$oRow					= $oDialogData->createRow( $sLabel, 'input', array('id' => $sId, 'value' => $sFlightnumber, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_flightnumber'));
			$oDivTransfer			->setElement($oRow);
		}

		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][transfer_date]';
		$sName	= 'transfer['.$iTransferId.'][transfer_date]';
		$sIdCalendar	= 'transfercalendar['.$iInquiryId.']['.$iTransferId.'][transfer_date]';
		if($iTransferType == 1){
			$sClass = ' input_arrival_date input_transfer_date ';
			$sLabel = 'Anreiseuhrzeit';
			$bShowArrivalTime = true;
		}elseif($iTransferType == 2){
			$sClass = ' input_departure_date input_transfer_date ';
			$sLabel = 'Abreiseuhrzeit';
			$bShowArrivalTime = true;
		}else{
			$sLabel = 'Zeit';
			$bShowArrivalTime = false;
			$sClass = ' input_transfer_date ';
		}
		
		$sId2	= 'transfer['.$iInquiryId.']['.$iTransferId.'][transfer_time]';
		$sName2	= 'transfer['.$iTransferId.'][transfer_time]';
		$oRow = Ext_Thebing_Gui2_Util::getDateTimeRow($oDialogData,
														array(	'value_1' => $sDate,
																'name_1' => $sName,
																'id_1' => $sId,
																'class_1' => $sClass,
																'class_2' => 'input_transfer_time',
																'disabled_1' => $bDisabled,
																'calendar_id' => $sIdCalendar,
																'value_2' => $sTime,
																'name_2'=> $sName2,
																'id_2' => $sId2,
																'disabled_2' => $bDisabled,
																'show_time' => $bShowArrivalTime),
													L10N::t('Datum', self::$sL10NDescription),
													L10N::t($sLabel, self::$sL10NDescription)
													);
		$oDivTransfer->setElement($oRow);
		

		// Abholung
		$sLabel = L10N::t('Abholung', self::$sL10NDescription);
		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][pickup]';
		$sName	= 'transfer['.$iTransferId.'][pickup]';
		$sClass	= 'txt input_transfer_pickup_time';
		$oRow					= $oDialogData->createRow( $sLabel, 'input', array('id' => $sId, 'value' => $sPickup, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_pickup'));
		$oDivTransfer			->setElement($oRow);
		

		$sLabel = L10N::t('Kommentar', self::$sL10NDescription);
		$sId	= 'transfer['.$iInquiryId.']['.$iTransferId.'][comment]';
		$sName	= 'transfer['.$iTransferId.'][comment]';
		$sClass	= 'units txt autoheight';
		$oRow					= $oDialogData->createRow( $sLabel, 'textarea', array('id' => $sId, 'value' => $sComment, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_transfer_'.$iTransferType.'_comment'));
		$oDivTransfer			->setElement($oRow); 

		// Gibt an welcher TransferTyp es ist
		$oHidden		= $oDialogData->create('input');
		$oHidden->type	= 'hidden';
		$oHidden->name	= 'transfer['.$iTransferId.'][transfer_type]';
		$oHidden->value	= (int) $iTransferType;
		$oDivTransfer->setElement($oHidden);

		// aktiv feld (wird ggf bei arr o. dep. per js umgeschrieben
		$oHidden		= $oDialogData->create('input');
		$oHidden->type	= 'hidden';
		$oHidden->name	= 'transfer['.$iTransferId.'][active]';
		$oHidden->value	= (int) 1;
		$oDivTransfer->setElement($oHidden);

		return $oDivTransfer;
	}

	static public function getAccommodationBodyHtml($oDialogData, $aAccommodations, $aRoomtypes, $aMeals, $aInquiryAccommodation = array(), $sSavePrefix = 'accommodation', $bGroup=false) {

		$oGui = $oDialogData->getDataObject()->getGui();
		
		$bDisabled = $aInquiryAccommodation['disabled'];

		if(isset($aInquiryAccommodation['group_id'])){
			$iInquiryId					= (int)$aInquiryAccommodation['group_id'];
		}else{
            // Neuer Eintrag
            if(isset($aInquiryAccommodation['inquiry_id'])){
                $iInquiryId					= (int)$aInquiryAccommodation['inquiry_id'];
            // bestehender Eintrag hat kein inquiry_id
            } else {
                $oJourney                   = Ext_TS_Inquiry_Journey::getInstance((int)$aInquiryAccommodation['journey_id']);		
                $oInquiry                   = $oJourney->getInquiry();
                $iInquiryId					= (int)$oInquiry->id;
            }
			
		}		
		
		$iInquiryAccommodationId	= (int)$aInquiryAccommodation['id'];
		$iAccommodationId			= (int)$aInquiryAccommodation['accommodation_id'];
		$iRoomtypeId				= (int)$aInquiryAccommodation['roomtype_id'];
		$iMealId					= (int)$aInquiryAccommodation['meal_id'];
		$iWeeks						= (int)$aInquiryAccommodation['weeks'];
		$sComment					= (string)$aInquiryAccommodation['comment'];

		if (!$bGroup) {
			$aAdditionalServices = (array)$aInquiryAccommodation['additionalservices'];
		} else {
			$groupAccommodation = Ext_Thebing_Inquiry_Group_Accommodation::getInstance($aInquiryAccommodation['id']);
			$aAdditionalServices = [];
			foreach ($groupAccommodation->additionalservices as $additionalserviceArray) {
				$aAdditionalServices[] = $additionalserviceArray['additionalservice_id'];
			}
		}

		$sFrom						= self::$oCalendarFormat->format($aInquiryAccommodation['from']);
		$sUntil						= self::$oCalendarFormat->format($aInquiryAccommodation['until']);
		$sFromTime					= substr($aInquiryAccommodation['from_time'], 0 , 5);
		$sUntilTime					= substr($aInquiryAccommodation['until_time'], 0 , 5);

		$oDiv = $oDialogData->create('div');
		$oDiv->id = $sSavePrefix . '_' . $iInquiryAccommodationId;
		$oDiv->class = 'box-body'; // class wird komplett benutzt, um ID rauszuziehen

		$oDivRow = $oDialogData->create('div');
		$oDivRow->class = 'grid grid-cols-1 lg:grid-cols-2';
		
		$oDivColLeft = $oDialogData->create('div');
		
		$oDivColRight = $oDialogData->create('div');
		
		$sLabel = L10N::t('Unterkunft', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][accommodation_id]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][accommodation_id]';
		$sClass	= 'accommodationSelect txt';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aAccommodations, 'default_value'=> $iAccommodationId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_accommodation_id'));
		$oDivColLeft->setElement($oRow);

		$sLabel = L10N::t('Raumart', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][roomtype_id]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][roomtype_id]';
		$sClass	= 'RoomtypeSelect txt';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aRoomtypes, 'default_value'=> $iRoomtypeId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_accommodation_roomtype'));
		$oDivColLeft->setElement($oRow);

		$sLabel = L10N::t('Verpflegung', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][meal_id]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][meal_id]';
		$sClass	= 'MealtypeSelect txt';
		$oRow = $oDialogData->createRow($sLabel, 'select', array('select_options'=>$aMeals, 'default_value'=> $iMealId, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_accommodation_meal'));
		$oDivColLeft->setElement($oRow);

		

		$sLabel = L10N::t('Wochenanzahl', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][weeks]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][weeks]';
		$sClass	= 'accommodationWeeks txt';

		
		$oRefreshI = Ext_Gui2_Html::getIconObject('fa-refresh');
		
		$oRefreshImg = new Ext_Gui2_Html_Button();
		$oRefreshImg->title = L10N::t('Enddatum neu berechnen', self::$sL10NDescription);
		$oRefreshImg->onclick="return false;";
		$oRefreshImg->class = 'recalculate_accommodation_enddate btn btn-default btn-sm';
		$oRefreshImg->id = str_replace('[weeks]', '[refresh]', $sId);

		$oRefreshImg->setElement($oRefreshI);
		
		$oRow = $oDialogData->createRow($sLabel, 'input', array('value'=>$iWeeks, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'style' => '', 'input_div_addon' => $oRefreshImg, 'disabled' => $bDisabled, 'info_icon_key' => 'inquiry_accommodation_weeks'));
		$oDivColLeft->setElement($oRow);

		// Von	
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][from]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][from]';
		$sIdCalendar	= $sSavePrefix . 'calendar['.$iInquiryId.']['.$iInquiryAccommodationId.'][from]';
		$sClass	= 'calculateAccUntil txt';

		$sId2	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][from_time]';
		$sName2	= $sSavePrefix . '['.$iInquiryAccommodationId.'][from_time]';
		$oRow = Ext_Thebing_Gui2_Util::getDateTimeRow($oDialogData,
			[
				'value_1' => $sFrom,
				'name_1' => $sName,
				'id_1' => $sId,
				'class_1' => $sClass,
				'disabled_1' => $bDisabled,
				'calendar_id' => $sIdCalendar,
				'value_2' => $sFromTime,
				'name_2'=> $sName2,
				'id_2' => $sId2,
				'disabled_2' => $bDisabled
			],
			L10N::t('Von', self::$sL10NDescription),
			L10N::t('Einzugszeit', self::$sL10NDescription)
		);
		$oDivColLeft->setElement($oRow);

		// Bis		
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][until]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][until]';
		$sIdCalendar	= $sSavePrefix . 'calendar['.$iInquiryId.']['.$iInquiryAccommodationId.'][until]';
		$sClass	= 'calculateAccTo txt';

		$sId2	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][until_time]';
		$sName2	= $sSavePrefix . '['.$iInquiryAccommodationId.'][until_time]';
		$oRow = Ext_Thebing_Gui2_Util::getDateTimeRow($oDialogData,
														array(	'value_1' => $sUntil,
																'name_1' => $sName,
																'id_1' => $sId,
																'class_1' => $sClass,
																'disabled_1' => $bDisabled,
																'calendar_id' => $sIdCalendar,
																'value_2' => $sUntilTime,
																'name_2'=> $sName2,
																'id_2' => $sId2,
																'disabled_2' => $bDisabled),
													L10N::t('Bis', self::$sL10NDescription),
													L10N::t('Auszugszeit', self::$sL10NDescription)
													);
		$oDivColLeft->setElement($oRow);

		$sLabel = L10N::t('Kommentar', self::$sL10NDescription);
		$sId	= $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][comment]';
		$sName	= $sSavePrefix . '['.$iInquiryAccommodationId.'][comment]';
		$sClass	= 'accommodationComment txt form-control autoheight';
		$oRow = $oDialogData->createRow($sLabel, 'textarea', array('value'=>$sComment, 'id' => $sId, 'name' => $sName, 'class' => $sClass, 'info_icon_key' => 'inquiry_accommodation_comment'));
		$oDivColRight->setElement($oRow);


		$sId = $sSavePrefix . '['.$iInquiryId.']['.$iInquiryAccommodationId.'][additionalservices]';
		$sName = $sSavePrefix . '['.$iInquiryAccommodationId.'][additionalservices][]';
		$oRow = $oDialogData->createMultiRow($oGui->t('Zusatzleistungen'), [
			'items' => [
				[
					'input' => 'select',
					'multiple' => 3,
					'style' => 'width: 100%',
					'jquery_multiple' => 1,
					'searchable' => 1,
					'id' => $sId,
					'name' => $sName,
					'class' => 'additionalservices_select',
					'multi_rows' => true,
					'default_value' => $aAdditionalServices,
					'select_options' => array_flip($aAdditionalServices),
					'info_icon_key' => 'inquiry_accommodation_additionalservices'
				]
			]
		]);
		$oDivColRight->setElement($oRow);


		$oDivRow->setElement($oDivColLeft);
		$oDivRow->setElement($oDivColRight);
		
		$oDiv->setElement($oDivRow);
		
		return $oDiv;
	}

	static public function getInsuranceBodyHtml($oDialogData, $aInsurances, $iInquiryID, $bReadOnly) {

		$aInsurancesDD = Ext_Thebing_Insurances_Gui2_Insurance::getInsurancesListForInbox(true);

		$aTemp = $aTypes = [];
		foreach((array)$aInsurancesDD as $aInsurance) {
			$aTemp[$aInsurance['id']] = $aInsurance['title'];
			$aTypes[$aInsurance['id']] = $aInsurance['payment'];
		}

		$i = $n = 0;

		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryID);
		$oJourney = $oInquiry->getJourney();

		$aInsurances = $oJourney->getJoinedObjectChilds('insurances');

		if(empty($aInsurances)) {
			$aInsurances = [
				Ext_TS_Inquiry_Journey_Insurance::getInstance()
			];
		}
		
		$oDivContainer = $oDialogData->create('div');
		
		// TODO: Redundant mit oberen Teil!
		foreach((array)$aInsurances as $oJourneyInsurance) {

			$oBackDiv = $oDialogData->create('div');
			$oBackDiv->id = 'insurance_container_'.$n;
			$oBackDiv->class = 'insurance_container box';

			$oBackDiv->setElement('<div class="box-separator"></div>');

			$oMainDiv = $oDialogData->create('div');
			$oMainDiv->class = 'box-body';

			if($oJourneyInsurance->exist()) {
				$sTitle = $oJourneyInsurance->getNameForEditData();
			} else {
				$sTitle = L10N::t('Neue Versicherung', self::$sL10NDescription);
			}
			
			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialogData;
			$oTitle->sTitle = $sTitle;
			$oTitle->sTab = 'insurance';
			$oTitle->iInqiryId = (int)$iInquiryID;
			$oTitle->iDialogRowId = (int)0;
			$oTitle->sL10NDescription = self::$sL10NDescription;
			$oTitle->iVisible = (int)$oJourneyInsurance->visible;

			$oDiv = $oTitle->getTitelRowHtml();
			$oBackDiv->setElement($oDiv);

			$oHidden = $oDialogData->create('input');
			$oHidden->type = 'hidden';
			$oHidden->name = 'insurance['.$iInquiryID.'][update]['.$n.']';
			$oHidden->value = $oJourneyInsurance->id;
			$oHidden->class = 'insurance_hiddens';
			$oMainDiv->setElement($oHidden);

			$sLabel = L10N::t('Versicherung', self::$sL10NDescription);
			$sID = 'insurance['.$iInquiryID.'][id]['.$n.']';
			$sName = 'insurance['.$iInquiryID.'][id]['.$n.']';
			$oRow = $oDialogData->createRow($sLabel, 'select', ['default_value' => $oJourneyInsurance->insurance_id, 'select_options' => $aTemp, 'id' => $sID, 'name' => $sName, 'class' => 'insurance_ids', 'info_icon_key' => 'inquiry_insurance_id']);
			$oMainDiv->setElement($oRow);

//			if($aTypes[$aInsurance['insurance_id']] == 3) {
//				$oDateFrom = new WDDate($aInsurance['from'].' 00:00:00', WDDate::DB_TIMESTAMP);
//				$oDateUntil = new WDDate($aInsurance['until'].' 00:00:00', WDDate::DB_TIMESTAMP);
//				$iWeeks = 1;
//				while(true) {
//					$oDateFrom->add(1, WDDate::WEEK);
//					if($oDateFrom->get(WDDate::TIMESTAMP) > $oDateUntil->get(WDDate::TIMESTAMP) || $iWeeks > 1000) {
//						break;
//					}
//					$iWeeks++;
//				}
//				$aInsurance['weeks'] = $iWeeks;
//			} else {
//				$aInsurance['weeks'] = '';
//			}

			$sLabel = L10N::t('Wochen', self::$sL10NDescription);
			$sID = 'insurance['.$iInquiryID.'][weeks]['.$n.']';
			$sName = 'insurance['.$iInquiryID.'][weeks]['.$n.']';

			$oRefreshImg = new Ext_Gui2_Html_Button();
			$oRefreshImg->title = L10N::t('Enddatum neu berechnen', self::$sL10NDescription);
			$oRefreshImg->onclick="return false;";
			$oRefreshImg->class = 'recalculate_insurance_enddate btn btn-default btn-sm';
			$oRefreshImg->id = str_replace('[weeks]', '[refresh]', $sID);
			$oRefreshImg->setElement(Ext_Gui2_Html::getIconObject('fa-refresh'));

			$oRow = $oDialogData->createRow($sLabel, 'input', ['value' => $oJourneyInsurance->weeks, 'id' => $sID, 'name' => $sName, 'class' => 'insurance_weeks', 'style' => '', 'input_div_addon' => $oRefreshImg, 'info_icon_key' => 'inquiry_insurance_weeks']);
			$oMainDiv->setElement($oRow);

			$oDate = new WDDate($oJourneyInsurance->from, WDDate::DB_DATE);
			$sLabel = L10N::t('Von', self::$sL10NDescription);
			$sID = 'insurance['.$iInquiryID.'][from]['.$n.']';
			$sName = 'insurance['.$iInquiryID.'][from]['.$n.']';
			$oRow = $oDialogData->createRow($sLabel, 'calendar', ['value' => Ext_Thebing_Format::LocalDate($oDate->get(WDDate::TIMESTAMP), 0), 'id' => $sID, 'name' => $sName, 'calendar_id' => 'calendar_id_' . $i++, 'class' => 'insurance_froms', 'info_icon_key' => 'inquiry_insurance_from']);
			$oMainDiv->setElement($oRow);

			$oDate = new WDDate($oJourneyInsurance->until, WDDate::DB_DATE);
			$sLabel = L10N::t('Bis', self::$sL10NDescription);
			$sID = 'insurance['.$iInquiryID.'][until]['.$n.']';
			$sName = 'insurance['.$iInquiryID.'][until]['.$n.']';
			$oRow = $oDialogData->createRow($sLabel, 'calendar', ['value' => Ext_Thebing_Format::LocalDate($oDate->get(WDDate::TIMESTAMP), 0), 'id' => $sID, 'name' => $sName, 'calendar_id' => 'calendar_id_' . $i++, 'class' => 'insurance_untils', 'info_icon_key' => 'inquiry_insurance_until']);
			$oMainDiv->setElement($oRow);

			$oBackDiv->setElement($oMainDiv);

			$oDivContainer->setElement($oBackDiv);
			
			$n++;

		}

		$z = count((array)$aInsurances);
		if($z > 0) {
			$z++;
		}

		$oHidden = $oDialogData->create('input');
		$oHidden->type = 'hidden';
		$oHidden->value = $z;
		$oHidden->id = 'insurances_blocks_count';

		$oDivContainer->setElement($oHidden);

		$oDivContainer->setElement(Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialogData, 'insurance', $bReadOnly));

		return $oDivContainer;
	}

	static public function getActivityBodyHtml($oDialogData, $iInquiryID, $bReadOnly) {

		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryID);

		$oJourney = $oInquiry->getJourney();

		$aActivities = $oJourney->getJoinedObjectChilds('activities');

		$oSchool = self::getSelectedSchool($oInquiry, $oDialogData);

		$oDivContainer = $oDialogData->create('div');
		
		if (empty($aActivities)) {
			$aActivities = [Ext_TS_Inquiry_Journey_Activity::getInstance()];
		}

		foreach((array)$aActivities as $oJourneyActivity) {

			$oBackDiv = $oDialogData->create('div');
			$oBackDiv->class = 'activity_container box';

			$oBackDiv->setElement('<div class="box-separator"></div>');

			$iJourneyActivityId = $oJourneyActivity->id;

			$oMainDiv = $oDialogData->create('div');
			$oMainDiv->id = 'activity_'.$iJourneyActivityId;
			$oMainDiv->class = 'box-body';

			if($oJourneyActivity->exist()) {
				$sTitle = $oJourneyActivity->getNameForEditData();
			} else {
				$sTitle = L10N::t('Neue Aktivität', self::$sL10NDescription);
			}

			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialogData;
			$oTitle->sTitle = $sTitle;
			$oTitle->sTab = 'activity';
			$oTitle->iInqiryId = (int)$iInquiryID;
			$oTitle->iDialogRowId = (int)$iJourneyActivityId;
			$oTitle->sL10NDescription = self::$sL10NDescription;
			$oTitle->iVisible = (int)$oJourneyActivity->visible;

			$oDiv = $oTitle->getTitelRowHtml();
			$oBackDiv->setElement($oDiv);

			// IDs werden für das Löschen benötigt
			$oHidden = $oDialogData->create('input');
			$oHidden->type = 'hidden';
			$oHidden->name = 'activity['.$iJourneyActivityId.'][update]';
			$oHidden->value = $oJourneyActivity->id;
			$oHidden->class = 'activity_hiddens';
			$oMainDiv->setElement($oHidden);

			$oActivityRepository = \TsActivities\Entity\Activity::getRepository();
			$aActivityOptions = $oActivityRepository->getSelectOptions($oSchool);
			$aActivityOptions = Ext_Thebing_Util::addEmptyItem($aActivityOptions);

			// Gelöschter oder deaktivierter Eintrag
			if(!in_array($oJourneyActivity->activity_id, $aActivityOptions)) {
				$oActivity = \TsActivities\Entity\Activity::getInstance($oJourneyActivity->activity_id);
				$aActivityOptions[$oActivity->id] = $oActivity->getName();
			}

			$sLabel = L10N::t('Aktivität', self::$sL10NDescription);
			$sID = 'activity['.$iInquiryID.']['.$iJourneyActivityId.'][activity_id]';
			$sName = 'activity['.$iJourneyActivityId.'][activity_id]';
			$oRow = $oDialogData->createRow(
				$sLabel,
				'select',
				[
					'default_value' => $oJourneyActivity->activity_id,
					'select_options' => $aActivityOptions,
					'id' => $sID,
					'name' => $sName,
					'class' => 'activity_ids',
					'info_icon_key' => 'inquiry_activity_id'
				]
			);

			$oMainDiv->setElement($oRow);

			$sLabel = L10N::t('Anzahl der Blöcke', self::$sL10NDescription);
			$sID = 'activity['.$iInquiryID.']['.$iJourneyActivityId.'][blocks]';
			$sName = 'activity['.$iJourneyActivityId.'][blocks]';

			$oRow = $oDialogData->createRow($sLabel,
				'input', [
					'value' => $oJourneyActivity->blocks,
					'id' => $sID,
					'name' => $sName,
					'class' => 'activity_blocks',
					'info_icon_key' => 'inquiry_activity_blocks',
					'row_class' => 'row_activity_blocks'
				]
			);

			$oActivity = $oJourneyActivity->getActivity();
			if($oActivity->billing_period == "payment_per_week") {
				$oRow->style = 'display: none;';
			}

			$oMainDiv->setElement($oRow);

			$sLabel = L10N::t('Wochen', self::$sL10NDescription);
			$sID = 'activity['.$iInquiryID.']['.$iJourneyActivityId.'][weeks]';
			$sName = 'activity['.$iJourneyActivityId.'][weeks]';

			$oRefreshImg = new Ext_Gui2_Html_Button();
			$oRefreshImg->title = L10N::t('Enddatum neu berechnen', self::$sL10NDescription);
			$oRefreshImg->onclick="return false;";
			$oRefreshImg->class = 'recalculate_activity_enddate btn btn-default btn-sm';
			$oRefreshImg->id = str_replace('[weeks]', '[refresh]', $sID);
			$oRefreshImg->setElement(Ext_Gui2_Html::getIconObject('fa-refresh'));

			$oRow = $oDialogData->createRow(
				$sLabel,
				'input', [
					'value' => $oJourneyActivity->weeks,
					'id' => $sID,
					'name' => $sName,
					'class' => 'activity_weeks',
					'style' => '',
					'input_div_addon' => $oRefreshImg,
					'info_icon_key' => 'inquiry_activity_weeks'
				]);
			$oMainDiv->setElement($oRow);

			$oDate = new WDDate($oJourneyActivity->from, WDDate::DB_DATE);
			$sLabel = L10N::t('Von', self::$sL10NDescription);
			$sID = 'activity['.$iInquiryID.']['.$iJourneyActivityId.'][from]';
			$sName = 'activity['.$iJourneyActivityId.'][from]';

			$oRow = $oDialogData->createRow(
				$sLabel,
				'calendar', [
					'value' => Ext_Thebing_Format::LocalDate($oDate->get(WDDate::TIMESTAMP), 0),
					'id' => $sID,
					'name' => $sName,
					'class' => 'activity_froms',
					'info_icon_key' => 'inquiry_activity_from'
				]);
			$oMainDiv->setElement($oRow);

			$oDate = new WDDate($oJourneyActivity->until, WDDate::DB_DATE);
			$sLabel = L10N::t('Bis', self::$sL10NDescription);
			$sID = 'activity['.$iInquiryID.']['.$iJourneyActivityId.'][until]';
			$sName = 'activity['.$iJourneyActivityId.'][until]';

			$oRow = $oDialogData->createRow(
				$sLabel,
				'calendar', [
					'value' => Ext_Thebing_Format::LocalDate($oDate->get(WDDate::TIMESTAMP), 0),
					'id' => $sID,
					'name' => $sName,
					'class' => 'activity_tills',
					'info_icon_key' => 'inquiry_activity_until'
				]);

			$oMainDiv->setElement($oRow);

			$sLabel = L10N::t('Kommentar', self::$sL10NDescription);
			$sId	= 'activity['.$iInquiryID.']['.$iJourneyActivityId.'][comment]';
			$sName	= 'activity['.$iJourneyActivityId.'][comment]';
			$sClass	= 'activityComment txt form-control autoheight';
			$oRow	= $oDialogData->createRow(
				$sLabel,
				'textarea',
				[
					'id' => $sId,
					'value' => $oJourneyActivity->comment,
					'name' => $sName,
					'class' => $sClass,
					'info_icon_key' => 'inquiry_activity_comment'
				]);

			$oMainDiv->setElement($oRow);

			$oBackDiv->setElement($oMainDiv);

			$oDivContainer->setElement($oBackDiv);
			
		}

		$oAddDiv = Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialogData, 'activity', $bReadOnly);
		$oDivContainer->setElement($oAddDiv);

		return $oDivContainer;
	}


	static public function getCourseTabHTML(Ext_Gui2_Dialog $oDialogData, $aSelectedIds, $bReadOnly = false, $bGroup = false, $sPrefix = '') {

		global $_VARS;

		$iInquiryId = reset($aSelectedIds);

		$sSavePrefix = 'course';
		$sContainerClass = 'InquiryCourseContainer';

		// Guides
		if(!empty($sPrefix)){
			$sSavePrefix = $sPrefix;
			$sContainerClass .= ' InquiryCourseGuideContainer';
		}

		if($bGroup) {
			$oGroup = new Ext_Thebing_Inquiry_Group($iInquiryId);
			if($sSavePrefix == 'course_guide') {
				$aJourneyCourses = $oGroup->getCourses('guide', true);
			} else {
				$aJourneyCourses = $oGroup->getCourses('all', true);
			}

			$oSchool = self::getSelectedSchool($oGroup, $oDialogData);

		} else {
			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
			if($_VARS['task'] != 'openDialog') {
				// hier nie aus dem cache die Kurse laden, da nach dem Speichern sonst die Änderungen nicht sichtbar sind
				$bFromCache = false;
			} else {
				$bFromCache = true;
			}
			/** @var Ext_TS_Inquiry_Journey_Course[]|Ext_Thebing_Inquiry_Group_Course[] $aJourneyCourses */
			$aJourneyCourses = $oInquiry->getCourses(true, true, $bFromCache, false);
			$oSchool = self::getSelectedSchool($oInquiry, $oDialogData);
		}

        $aCategories = $oSchool->getCourseCategoriesList('select');
        $aCategories = Ext_Thebing_Util::addEmptyItem($aCategories);

		$aCourses = $oSchool->getCourseList(true, false, false, false, false);
        $aCourses = Ext_Thebing_Util::addEmptyItem($aCourses, L10N::t('Kein Kurs', self::$sL10NDescription));

		$aLevels = $oSchool->getCourseLevelList();
		$aLevels = Ext_Thebing_Util::addEmptyItem($aLevels);

		$oDivContent = $oDialogData->create('div');
		
		$aDisabledCourses = [];
		
//		$aSchoolHolidayCourses = [];
//		$aStudentHolidayCourses = [];

		// Bisherige Kurse auf Bezahlungen prüfen
		$bShowPayments = false;
		$oUl = new Ext_Gui2_Html_Ul();
		foreach($aJourneyCourses as $iKey => $oJourneyCourse) {

//			$aRelatedCourses = $oJourneyCourse->getRelatedServices(null, 'school');
//			if(count($aRelatedCourses) > 1) {
//				$aSchoolHolidayCourses[] = $oJourneyCourse->getCourseName();
//			}
//
//			$aRelatedCourses = $oJourneyCourse->getRelatedServices(null, 'student');
//			if(count($aRelatedCourses) > 1) {
//				$aStudentHolidayCourses[] = $oJourneyCourse->getCourseName();
//			}

			$aInquiryCourse = $oJourneyCourse->getData();

			// Prüfen ob Bezahlungen vorliegen, Falls ja darf nicht editierbar sein
			if(!isset($aInquiryCourse['group_id'])) {
				// TODO für Gruppen einbauen
				// Bezahlungen prüfen
				$aPayments = $oJourneyCourse->checkPaymentStatus();
				if(count($aPayments) > 0) {
					//@todo: Andere Lösung überlegen
					$aDisabledCourses[$iKey] = false;
				}
				foreach((array)$aPayments as $oPayment) {
					if($oPayment->comment == '') {
						$oFormat = new Ext_Thebing_Gui2_Format_Date();
						$oPayment->comment = $oFormat->format($oPayment->timepoint);
					}
					$oLi = new Ext_Gui2_Html_Li();
					$oLi->setElement($oPayment->comment);
					$oUl->setElement($oLi);
					$bShowPayments = true;
				}
			} else {
				// Gruppen
				$aDisabledCourses[$iKey] = false;
			}

		}

		// Hinweis auf Feriensplittung (war früher mal im JS)
//		if(
//			!empty($aSchoolHolidayCourses) ||
//			!empty($aStudentHolidayCourses)
//		) {
//
//			$aMessages = [];
//			if(!empty($aSchoolHolidayCourses)) {
//				$aMessages[] = sprintf(
//					L10N::t('Folgende Kurse wurden durch Schulferien geteilt: %s', self::$sL10NDescription),
//					join(', ', $aSchoolHolidayCourses)
//				);
//			}
//			if(!empty($aStudentHolidayCourses)) {
//				$aMessages[] = sprintf(
//					L10N::t('Folgende Kurse wurden durch Schülerferien geteilt: %s', self::$sL10NDescription),
//					join(', ', $aStudentHolidayCourses)
//				);
//			}
//
//			$oDivContent->setElement($oDialogData->createNotification(
//				L10N::t('Teilung durch Ferien', self::$sL10NDescription),
//				join('<br>', $aMessages),
//				'info'
//			));
//
//		}

		if($bShowPayments) {
			$oDivContent = self::addAccordionPayments($oDivContent, $oUl->generateHTML(), 'course');
		}

		// Bisherige Kurse
		foreach($aJourneyCourses as $iKey=>$oJourneyCourse) {

			$journeyCourseCourseOptions = $aCourses;

			$aInquiryCourse = $oJourneyCourse->getData();

			$aInquiryCourse['course_lessons'] = $oJourneyCourse->getCourse()->getLessons();
			$aInquiryCourse['disabled'] = $aDisabledCourses[$iKey];
			if($bGroup !== true) {
				// JoinTable muss explizit neu eingelesen werden
				$aInquiryCourse['additionalservices'] = $oJourneyCourse->getJoinTableData('additionalservices');
			}
			
			$oDivContainer = $oDialogData->create('div');
			$oDivContainer->class = $sContainerClass.' InquiryContainer box';

			$oDivContainer->setElement('<div class="box-separator"></div>');

			/*
			 * Schule muss angegeben werden, sonst funktioniert "all schools" Ansicht nicht! (#9529)
			 */
			$oCourse = new Ext_Thebing_Course_Util($oSchool);
			$oCourse->setCourse($aInquiryCourse['course_id']);

			// Prüfen ob Kurs durch schülerferien gesplittet wurde, dann darf er nicht editierbar sein
			/*$aStudentCourseHolidayInfos = Ext_Thebing_Inquiry_Holidays::_getHolidaySplittings(
				(int)$aInquiryCourse['id'],
				'both',
				'course'
			);

			$bTempReadonly = $bReadOnly;
			if(count($aStudentCourseHolidayInfos) > 0 ) {
				$bTempReadonly = true;
			}*/

			if($oCourse->getField("per_unit") == 1) {
				$iWeeksUnits = $aInquiryCourse['units'];
			} else {
				$iWeeksUnits = $aInquiryCourse['weeks'];
			}

			if(!array_key_exists($aInquiryCourse['course_id'], $journeyCourseCourseOptions)) {
				$oCourse = Ext_Thebing_Tuition_Course::getInstance($aInquiryCourse['course_id']);
				$journeyCourseCourseOptions[$oCourse->id] = $oCourse->getName();
			}

			$sCourseTitel = Ext_TS_Inquiry_Journey_Course::getCourseNameForEditData(
				$journeyCourseCourseOptions[$aInquiryCourse['course_id']],
				$aLevels[$aInquiryCourse['level_id']],
				$aInquiryCourse['from'],
				$aInquiryCourse['until'],
				$iWeeksUnits,
				$oSchool->id,
				(bool)($oCourse->getField("per_unit") == 1)
			);

			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialogData;
			$oTitle->sTitle = $sCourseTitel;
			$oTitle->sTab = $sSavePrefix;
			$oTitle->iDialogRowId = (int)$aInquiryCourse['id'];
			$oTitle->iVisible = (int)$aInquiryCourse['visible'];
			$oTitle->sL10NDescription = self::$sL10NDescription;
			$oTitle->bReadOnly = $bReadOnly;

			$oDiv = $oTitle->getTitelRowHtml();
			$oDivContainer->setElement($oDiv);

			if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
				$aMessages = self::getServiceHolidayNotifications($oJourneyCourse);
				if(!empty($aMessages)) {
					$oDivContainer->setElement($oDialogData->createNotification(
						L10N::t('Teilung durch Ferien', self::$sL10NDescription),
						join('<br>', $aMessages),
						'info',
						['dismissible' => false]
					));
				}
			}

			$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getCourseBodyHtml($oDialogData, $aCategories, $journeyCourseCourseOptions, $aLevels, $aInquiryCourse, $sSavePrefix, $bGroup);
			$oDivContainer->setElement($oDiv);

			$oDivContent->setElement($oDivContainer);

		}

		$oDivContainer = $oDialogData->create('div');
		$oDivContainer->class = $sContainerClass.' InquiryContainer box';
		if(empty($aJourneyCourses)) {

			$oDivContainer->setElement('<div class="box-separator"></div>');

			// Neuer Kurs
			$sCourseTitel = L10N::t('Neuer Kurs', self::$sL10NDescription);

			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialogData;
			$oTitle->sTitle = $sCourseTitel;
			$oTitle->sTab = $sSavePrefix;
			$oTitle->iInqiryId = (int)$iInquiryId;
			$oTitle->iDialogRowId = (int)0;
			$oTitle->sL10NDescription = self::$sL10NDescription;
			$oTitle->bReadOnly = $bReadOnly;

			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance();

			$aCourseData = $oInquiryCourse->getData();

			$aCourseData['inquiry_id'] = $iInquiryId;
			if($bGroup) {
				$aCourseData['group_id'] = $iInquiryId;
			}

			$oDiv = $oTitle->getTitelRowHtml();
			$oDivContainer->setElement($oDiv);
			$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getCourseBodyHtml($oDialogData, $aCategories, $aCourses, $aLevels, $aCourseData, $sSavePrefix, $bGroup);
			$oDivContainer->setElement($oDiv);

			$oDivContent->setElement($oDivContainer);

		}

		// Plus
		$oAddDiv = Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialogData, $sSavePrefix, $bReadOnly);
		$oDivContent->setElement($oAddDiv);

		$sHTML = $oDivContent->generateHTML($bReadOnly);

		return $sHTML;

	}


	static public function getAccommodationTabHTML(Ext_Gui2_Dialog $oDialogData, $aSelectedIds, $bReadOnly = false, $bGroup = false, $sPrefix = '') {

		$iInquiryId = reset($aSelectedIds);

		$sSavePrefix = 'accommodation';
		$sContainerClass = 'InquiryAccommodationContainer';

		// Guides
		if(!empty($sPrefix)){
			$sSavePrefix = $sPrefix;
			$sContainerClass = 'InquiryAccommodationGuideContainer';
		}

		$oSchool = null;

		if($bGroup) {
			$oGroup = new Ext_Thebing_Inquiry_Group($iInquiryId);
			if($sSavePrefix == 'accommodation_guide') {
				$aJourneyAccommodations	= $oGroup->getAccommodations('guide', true);
			} else {
				$aJourneyAccommodations	= $oGroup->getAccommodations('all', true);
			}
			$oSchool = self::getSelectedSchool($oGroup, $oDialogData);
		} else {
			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
			$aJourneyAccommodations = $oInquiry->getAccommodations(true, true);
			$oSchool = self::getSelectedSchool($oInquiry, $oDialogData);
		}

		$sLanguage = $oSchool->getInterfaceLanguage();

		$aAccommodations = $oSchool->getAccommodationList();
		$aAccommodations = Ext_Thebing_Util::addEmptyItem($aAccommodations, L10N::t('Keine Unterkunft', self::$sL10NDescription));

		$aMeals = $oSchool->getMealList();
		$aMeals = Ext_Thebing_Util::addEmptyItem($aMeals, L10N::t('Keine Mahlzeit', self::$sL10NDescription));

		$aRoomtypes = $oSchool->getRoomtypeList();
		$aRoomtypes = Ext_Thebing_Util::addEmptyItem($aRoomtypes, L10N::t('Kein Raumtyp', self::$sL10NDescription));

		$oDivContent = $oDialogData->create('div');

		$aDisabledAccommodations = [];
		
		// Info Div
		$oErrorDialog = new Ext_Gui2_Dialog();
		$oHint = $oErrorDialog->createNotification(L10N::t('Kursinformation', self::$sL10NDescription), '', 'info', array('row_class' => 'accommodation_course_info', 'row_style' => 'display: none;'));
		$oDivContent->setElement($oHint->generateHTML());

		// Bisherige Unterkünfte auf Bezahlungen prüfen
		$bShowPayments = false;
		$oUl = new Ext_Gui2_Html_Ul();
		$aHolidayInfo = [];

		foreach($aJourneyAccommodations as $iKey => $oJourneyAccommodation) {

			$aInquiryAccommodation = $oJourneyAccommodation->getData();

//			$oHolidaySplit = Ext_TS_Inquiry_Holiday_Splitting::getRepository()->findOneBy(['journey_accommodation_id' => $oJourneyAccommodation->id]);
//			if($oHolidaySplit !== null) {
//				$aHolidayInfo[$oJourneyAccommodation->id] = $oJourneyAccommodation->getAccommodationCategoryWithRoomTypeAndMeal();
//			}

			// Prüfen ob Bezahlungen vorliegen, Falls ja darf nicht editierbar sein
			if(!isset($aInquiryAccommodation['group_id'])) {

				// TODO für Gruppen einbauen
				// Bezahlungen prüfen
				$aPayments = $oJourneyAccommodation->checkPaymentStatus();
				if(count($aPayments) > 0) {
					//@todo: andere Lösung überlegen
					$aDisabledAccommodations[$iKey] = false;
				}
				foreach((array)$aPayments as $oPayment) {
					if($oPayment->comment == '') {
						$oFormat = new Ext_Thebing_Gui2_Format_Date();
						$oPayment->comment = $oFormat->format($oPayment->timepoint);
					}
					$oLi = new Ext_Gui2_Html_Li();
					$oLi->setElement($oPayment->comment);
					$oUl->setElement($oLi);
					$bShowPayments = true;
				}

			} else {
				// Gruppen
				$aDisabledAccommodations[$iKey] = false;
			}

			if(!array_key_exists($oJourneyAccommodation->accommodation_id, $aAccommodations)) {
				$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($oJourneyAccommodation->accommodation_id);
				$aAccommodations[$oAccommodationCategory->id] = $oAccommodationCategory->getName($sLanguage);
			}

			if(!array_key_exists($oJourneyAccommodation->roomtype_id, $aRoomtypes)) {
				$oRoomType= Ext_Thebing_Accommodation_Roomtype::getInstance($oJourneyAccommodation->roomtype_id);
				$aRoomtypes[$oRoomType->id] = $oRoomType->getName($sLanguage);
			}

			if(!array_key_exists($oJourneyAccommodation->meal_id, $aMeals)) {
				$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($oJourneyAccommodation->meal_id);
				$aMeals[$oMeal->id] = $oMeal->getName($sLanguage, false);
			}

		}

		// Hinweis für Unterkünfte, die durch Schülerferien geteilt wurden
//		if(!empty($aHolidayInfo)) {
//			$oDivContent->setElement($oDialogData->createNotification(
//				L10N::t('Teilung durch Ferien', self::$sL10NDescription),
//				sprintf(L10N::t('Folgende Unterkünfte wurden durch Schülerferien geteilt: %s', self::$sL10NDescription), join(', ', $aHolidayInfo)),
//				'info'
//			));
//		}

		if($bShowPayments) {
			$oDivContent = self::addAccordionPayments($oDivContent, $oUl->generateHTML(), 'accommodation');
		}

		// Bisherige Unterkünfte
		foreach($aJourneyAccommodations as $iKey=>$oJourneyAccommodation) {

			$aInquiryAccommodation = $oJourneyAccommodation->getData();

			$aInquiryAccommodation['disabled'] = $aDisabledAccommodations[$iKey];
			
			if($bGroup !== true) {
				// JoinTable muss explizit neu eingelesen werden
				$aInquiryAccommodation['additionalservices'] = $oJourneyAccommodation->getJoinTableData('additionalservices');			
			}
			
			// Prüfen ob Unterkunft durch schülerferien gesplittet wurde, dann darf er nicht editierbar sein
			$bTempReadonly = $bReadOnly;
			if(isset($aHolidayInfo[$oJourneyAccommodation->id])) {
				$bTempReadonly = true;
			}

			$oDivContainer = $oDialogData->create('div');
			$oDivContainer->class = $sContainerClass . ' InquiryContainer box';

			/*
			 * Schule muss angegeben werden, sonst funktioniert "all schools" Ansicht nicht! (#9529)
			 */
			$oCourse = new Ext_Thebing_Course_Util($oSchool);
			$oCourse->setCourse($aInquiryAccommodation['course_id']);

			$sAccoTitel = Ext_TS_Inquiry_Journey_Accommodation::getAccommodationNameForEditData($aAccommodations[$aInquiryAccommodation['accommodation_id']], $aRoomtypes[$aInquiryAccommodation['roomtype_id']], $aMeals[$aInquiryAccommodation['meal_id']], $aInquiryAccommodation['from'], $aInquiryAccommodation['until'], $aInquiryAccommodation['weeks'], $oSchool->id);

			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialogData;
			$oTitle->sTitle = $sAccoTitel;
			$oTitle->sTab = $sSavePrefix;
			$oTitle->iDialogRowId = (int)$aInquiryAccommodation['id'];
			$oTitle->iVisible = (int)$aInquiryAccommodation['visible'];
			$oTitle->sL10NDescription = self::$sL10NDescription;
			$oTitle->bReadOnly = $bTempReadonly;

			$oDiv = $oTitle->getTitelRowHtml();
			$oDivContainer->setElement($oDiv);

			if($oJourneyAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
				$aMessages = self::getServiceHolidayNotifications($oJourneyAccommodation);
				if(!empty($aMessages)) {
					$oDivContainer->setElement($oDialogData->createNotification(
						L10N::t('Teilung durch Ferien', self::$sL10NDescription),
						join('<br>', $aMessages),
						'info',
						['dismissible' => false]
					));
				}
			}

			$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getAccommodationBodyHtml($oDialogData, $aAccommodations, $aRoomtypes, $aMeals, $aInquiryAccommodation, $sSavePrefix, $bGroup);
			$oDivContainer->setElement($oDiv);

			$oDivContent->setElement($oDivContainer);

		}

		// Neue Unterkunft
		if(empty($aJourneyAccommodations)) {

			$oDivContainer = $oDialogData->create('div');
			$oDivContainer->class = $sContainerClass.' InquiryContainer box';

			$oDivContainer->setElement('<div class="box-separator"></div>');

			$sAccoTitel = L10N::t('Neue Unterkunft', self::$sL10NDescription);

			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialogData;
			$oTitle->sTitle = $sAccoTitel;
			$oTitle->sTab = $sSavePrefix;
			$oTitle->iInqiryId = (int)$iInquiryId;
			$oTitle->iDialogRowId = (int)0;
			$oTitle->iVisible = (int)1;
			$oTitle->sL10NDescription = self::$sL10NDescription;
			$oTitle->bReadOnly = $bReadOnly;

			$oDiv = $oTitle->getTitelRowHtml();
			$oDivContainer->setElement($oDiv);
			$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getAccommodationBodyHtml($oDialogData, $aAccommodations, $aRoomtypes, $aMeals, array('inquiry_id'=>$iInquiryId), $sSavePrefix, $bGroup);
			$oDivContainer->setElement($oDiv);

			$oDivContent->setElement($oDivContainer);

		}

		// Plus
		$oAddDiv = Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialogData, $sSavePrefix, $bReadOnly);
		$oDivContent->setElement($oAddDiv);

		$sHTML = $oDivContent->generateHTML($bReadOnly);
		return $sHTML;

	}

	static public function getIndividualMatchingTabHtml($oDialogData, $aSelectedIds = array()){

		$oDivContent = $oDialogData->create('div');

		// Zuweisungsinformationen
		$oDivInfo = Ext_Thebing_Gui2_Util::getInfoRow(
			$oDialogData,
			L10N::t('Zuweisungsinformationen', self::$sL10NDescription),
			'textarea',
			array(
				//'id' => 'test',
				'style' => 'display: none;',
				'class' => 'matching_info',
				'editable' => false
			),
			''
		);

		$oDivContent->setElement($oDivInfo);
		$sHTML = $oDivContent->generateHTML();
		return $sHTML;
	}

	// Versicherungs Tab
	static public function getInsuranceTabHtml($oDialogData, $aInsurances,  $aSelectedIds = array(), $bReadOnly = false) {

		$iInquiryID		= reset($aSelectedIds);

		$oDivConatiner = $oDialogData->create('div');

		$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getInsuranceBodyHtml($oDialogData, $aInsurances, (int)$iInquiryID, $bReadOnly);
		$oDivConatiner->setElement($oDiv);

		$sHTML = $oDivConatiner->generateHTML($bReadOnly);

		return $sHTML;

	}

	static public function getActivityTabHtml($oDialogData, $aSelectedIds = array(), $bReadOnly = false) {

		$iInquiryID	= reset($aSelectedIds);

		$oDivConatiner = $oDialogData->create('div');

		$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getActivityBodyHtml($oDialogData, (int)$iInquiryID, $bReadOnly);
		$oDivConatiner->setElement($oDiv);

		$sHTML = $oDivConatiner->generateHTML($bReadOnly);

		return $sHTML;
	}

	public static function getSponsoringTabHtml(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog, array $aSelectedIds = [], $bReadOnly = false) {

		$iInquiryId = reset($aSelectedIds);
		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

		$oDivContainer = $oDialog->create('div');

		/** @var TsSponsoring\Entity\InquiryGuarantee[] $aGurantees */
		$aGurantees = $oInquiry->getJoinedObjectChilds('sponsoring_guarantees', true);

		if(empty($aGurantees)) {
			$aGurantees = [new TsSponsoring\Entity\InquiryGuarantee()];
		}

		foreach($aGurantees as $oGurantee) {

			$oDiv = $oDialog->create('div');
			$oDiv->id = 'sponsoring_guarantee_'.$oGurantee->id;
			$oDiv->class = 'sponsoring_guarantee_container box';

			$oDiv->setElement('<div class="box-separator"></div>');

			$sIdPrefix = 'sponsoring_gurantee['.$iInquiryId.']['.$oGurantee->id.']';
			$sNamePrefix = 'sponsoring_gurantee['.$oGurantee->id.']';

			$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
			$oTitle->oDialogData = $oDialog;
			$oTitle->sTab = 'sponsoring_gurantee';
			$oTitle->iInqiryId = $iInquiryId;
			$oTitle->iDialogRowId = $oGurantee->id;
			$oTitle->sL10NDescription = self::$sL10NDescription;

			// IDs werden für das Löschen benötigt
			$oHidden = $oDialog->create('input');
			$oHidden->type = 'hidden';
			$oHidden->name = 'sponsoring_guarantee['.$oGurantee->id.'][update]';
			$oHidden->value = $oGurantee->id;
			$oHidden->class = 'sponsoring_guarantee_hiddens';
			$oDiv->setElement($oHidden);

			$oDivBody = $oDialog->create('div');
			$oDivBody->class = 'box-body';
			
			if($oGurantee->exist()) {
				$oTitle->sTitle = vsprintf('%s %s - %s', [
					L10N::t('Finanzgarantie', self::$sL10NDescription),
					Ext_Thebing_Format::LocalDate($oGurantee->from),
					Ext_Thebing_Format::LocalDate($oGurantee->until)
				]);
			} else {
				$oTitle->sTitle = L10N::t('Neue Finanzgarantie', self::$sL10NDescription);
			}

			$oDiv->setElement($oTitle->getTitelRowHtml());

			$oRow = $oDialog->createRow(L10N::t('Nummer', self::$sL10NDescription), 'input', [
				'id' => $sIdPrefix.'[number]',
				'name' => $sNamePrefix.'[number]',
				'value' => $oGurantee->number,
				'info_icon_key' => 'inquiry_sponsoring_number'
			]);
			$oDivBody->setElement($oRow);

			$oRow = $oDialog->createRow(L10N::t('Gültig ab', self::$sL10NDescription), 'calendar', [
				'id' => $sIdPrefix.'[from]',
				'name' => $sNamePrefix.'[from]',
				'value' => Ext_Thebing_Format::LocalDate($oGurantee->from),
				'format' => Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date'),
				'info_icon_key' => 'inquiry_sponsoring_from'
			]);
			$oDivBody->setElement($oRow);

			$oRow = $oDialog->createRow(L10N::t('Gültig bis', self::$sL10NDescription), 'calendar', [
				'id' => $sIdPrefix.'[until]',
				'name' => $sNamePrefix.'[until]',
				'value' => Ext_Thebing_Format::LocalDate($oGurantee->until),
				'format' => Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date'),
				'info_icon_key' => 'inquiry_sponsoring_until'
			]);
			$oDivBody->setElement($oRow);

			$oUpload = new Ext_Gui2_Dialog_Upload(
				$oGui,
				$oGui->t('Dokument'),
				$oDialog,
				'',
				'',
				Ext_Thebing_School::getFirstSchool()->getSchoolFileDir().'/inquiry_sponsoring/', // Wird überschrieben
				!$oInquiry->exist(),
				[
					'id' => $sIdPrefix.'[path]',
					'name' => $sNamePrefix.'[path]',
				]
			);
			$oUpload->bNoCache = true;
			$oDivBody->setElement($oUpload);

			$oDiv->setElement($oDivBody);
			
			$oDivContainer->setElement($oDiv);

		}

		$oDivContainer->setElement(\Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialog, 'sponsoring_gurantee'));

		$sHTML = $oDivContainer->generateHTML($bReadOnly);

		return $sHTML;
	}

	static public function getIndividualTransferTabHtml($oDialogData, $aSelectedIds = array(), $bGroup = false, $bReadOnly = false){

		$iInquiryId	= reset($aSelectedIds);

		if($bGroup) {
			$oGroup	= new Ext_Thebing_Inquiry_Group($iInquiryId);
			//$oSchool = self::getSelectedSchool($oGroup, $oDialogData);

			// Buchungsbezogene An/Abreise Orte
			$aTransferArrival		= $oGroup->getTransferLocations(1);
			$aTransferDeparture		= $oGroup->getTransferLocations(2);
			$aTransferIndividual	= $oGroup->getTransferLocations(0);
			$aAdditionalTransfer	= $oGroup->getTransfers('additional', true);
			$mArrivalTransfer		= $oGroup->getTransfers('arrival', true);
			$mDepartureTransfer		= $oGroup->getTransfers('departure', true);
		} else {
			$oInquiry				= Ext_TS_Inquiry::getInstance($iInquiryId);
			$oJourney				= $oInquiry->getJourney();
			$iJourneyId				= $oJourney->id;
			//$oSchool = self::getSelectedSchool($oInquiry, $oDialogData);
			// Buchungsbezogene An/Abreise Orte
			$aTransferArrival		= $oInquiry->getTransferLocations('arrival');
			$aTransferDeparture		= $oInquiry->getTransferLocations('departure');
			$aTransferIndividual	= $oInquiry->getTransferLocations();

			$aAdditionalTransfer	= $oInquiry->getTransfers('additional', true);
			$mArrivalTransfer		= $oInquiry->getTransfers('arrival', true);
			$mDepartureTransfer		= $oInquiry->getTransfers('departure', true);
		}

		$oDivContent = $oDialogData->create('div');

		// Wenn Transfere bezahlt worden sind ====================================================
		$bShowPayments = false;
		if(is_object($oInquiry)){
			$aTransfers = $oInquiry->getTransfers('', true);

			$oUl = new Ext_Gui2_Html_Ul();
			foreach((array)$aTransfers as $oTransfer){
				$aPaymentsTamp = $oTransfer->getJoinedObjectChilds('accounting_payments_active');
				if(count($aPaymentsTamp) > 0){
					$oLi = new Ext_Gui2_Html_Li();
					$oLi->setElement($oTransfer->getName());
					$oUl->setElement($oLi);
					$bShowPayments = true;
				}
			}
		}
		
		if($bShowPayments){
			$oDivContent = self::addAccordionPayments($oDivContent, $oUl->generateHTML(), 'transfer');
		}
		
		// ==========================================================================================
		
		## START Anreise
			$oTransfer = null;
			if(
				$mArrivalTransfer instanceof Ext_TS_Inquiry_Journey_Transfer ||
				$mArrivalTransfer instanceof Ext_Thebing_Inquiry_Group_Transfer
			){
				$oTransfer = $mArrivalTransfer;
			}else{
				// Neuer Transfer
				if($bGroup){
					$oTransfer = new Ext_Thebing_Inquiry_Group_Transfer(0);
					$oTransfer->group_id = $oGroup->id;
				}else{
					$oTransfer = new Ext_TS_Inquiry_Journey_Transfer(0);
					$oTransfer->journey_id = $iJourneyId;
				}
			}

			$oDivArrival = $oDialogData->create('div');
			$oDivArrival->id = 'div_arrival_data';

			$oH3 = $oDialogData->create('h4');
			$oH3->setElement(L10N::t('Anreise', self::$sL10NDescription));
			$oDivArrival->setElement($oH3);

			$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getTransferBodyHtml($oDialogData, $aTransferArrival, $oTransfer, $bReadOnly, 1);

			$oDivArrival->setElement($oDiv);
			$oDivContent->setElement($oDivArrival);
		## ENDE

		## START Abreise
			$oTransfer = null;
			if(
				$mDepartureTransfer instanceof Ext_TS_Inquiry_Journey_Transfer ||
				$mDepartureTransfer instanceof Ext_Thebing_Inquiry_Group_Transfer
			){
				$oTransfer = $mDepartureTransfer;
			}else{
				// Neuer Transfer
				if($bGroup){
					$oTransfer = new Ext_Thebing_Inquiry_Group_Transfer(0);
					$oTransfer->group_id = $oGroup->id;
				}else{
					$oTransfer = new Ext_TS_Inquiry_Journey_Transfer(0);
					$oTransfer->journey_id = $iJourneyId;
				}
			}

			$oDivDeparture = $oDialogData->create('div');
			$oDivDeparture->id = 'div_departure_data';

			$oH3 = $oDialogData->create('h4');
			$oH3->setElement(L10N::t('Abreise', self::$sL10NDescription));
			$oDivDeparture->setElement($oH3);

			$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getTransferBodyHtml($oDialogData, $aTransferDeparture, $oTransfer, $bReadOnly, 2);
			
			$oDivDeparture->setElement($oDiv);
			$oDivContent->setElement($oDivDeparture);
		## ENDE

		## START Individueller Transfer
			$oH3 = $oDialogData->create('h4');
			$oH3->setElement(L10N::t('Individueller Transfer', self::$sL10NDescription));
			$oDivContent->setElement($oH3);


			// Gebuchter Transfer
			foreach((array)$aAdditionalTransfer as $oTransfer){

				$oDivContainer = $oDialogData->create('div');
				$oDivContainer->class = 'InquiryTransferContainer InquiryContainer box';

				$sTitel = $oTransfer->getName(self::$oCalendarFormat);

				$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
				$oTitle->oDialogData		= $oDialogData;
				$oTitle->sTitle				= $sTitel;
				$oTitle->sTab				= 'transfer';
				$oTitle->iInqiryId			= (int)$iInquiryId;
				$oTitle->iDialogRowId		= (int)0;
				$oTitle->iVisible			= (int)1;
				$oTitle->sL10NDescription	= self::$sL10NDescription;
				$oTitle->bReadOnly			= $bReadOnly;
			
				$oDivTitle		= $oTitle->getTitelRowHtml();
				$oDivContainer->setElement($oDivTitle);

				$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getTransferBodyHtml($oDialogData, $aTransferIndividual, $oTransfer, $bReadOnly);
				$oDivContainer->setElement($oDiv);


				$oDivContent->setElement($oDivContainer);
			}


			if(empty($aAdditionalTransfer)){
				// Neuer Transfer
				if($bGroup){
					$oTransfer = new Ext_Thebing_Inquiry_Group_Transfer(0);
					$oTransfer->group_id = $oGroup->id;
				}else{
					$oTransfer = new Ext_TS_Inquiry_Journey_Transfer(0);
					$oTransfer->journey_id = $iJourneyId;
				}

				$oDivContainer = $oDialogData->create('div');
				$oDivContainer->class = 'InquiryTransferContainer InquiryContainer box';

				$oDivContainer->setElement('<div class="box-separator"></div>');

				$sTitel			= L10N::t('Neuer Transfer', self::$sL10NDescription);
				
				$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
				$oTitle->oDialogData		= $oDialogData;
				$oTitle->sTitle				= $sTitel;
				$oTitle->sTab				= 'transfer';
				$oTitle->iInqiryId			= (int)$iInquiryId;
				$oTitle->iDialogRowId		= (int)0;
				$oTitle->iVisible			= (int)1;
				$oTitle->sL10NDescription	= self::$sL10NDescription;
				$oTitle->bReadOnly			= $bReadOnly;		
				
				$oDivTitle		= $oTitle->getTitelRowHtml();
				$oDivContainer->setElement($oDivTitle);

				$oDiv = Ext_Thebing_Inquiry_Gui2_Html::getTransferBodyHtml($oDialogData, $aTransferIndividual, $oTransfer, $bReadOnly);

				$oDivContainer->setElement($oDiv);

				$oDivContent->setElement($oDivContainer);
			}

			// Add Button
			$oAddDiv = Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialogData, 'transfer', $bReadOnly);
			$oDivContent->setElement($oAddDiv);
		## ENDE

		$sHTML = $oDivContent->generateHTML($bReadOnly);
		return $sHTML;
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document[] $aDocuments
	 * @param string $sDescription
	 * @param int $iSchool
	 * @param int $iCurrency
	 * @param string $sHistoryType
	 * @return string
	 */
	public static function getHistoryHTML($aDocuments, $sDescription, $iSchool, $iCurrency, $sHistoryType = 'inquiry') {

		$oSchool		= new Ext_Thebing_School($iSchool);

		$sHistoryHtml		= "<div id='dialog_document_history' style='overflow: auto;'>";

		switch($sHistoryType)
		{
			case 'insurance':
			{
				$sHistoryHtml .= '
					<table class="table tblDocumentTable highlightRows">
						<colgroup>
							<col width="60px" />
							<col width="200px" />
							<col width="120px" />
							<col width="auto" />
							<col width="140px" />
							<col width="50px" />
						</colgroup>
				';
				$sHistoryHtml .= '
					<tr>
						<th>'.L10N::t('Version', $sDescription).'</th>
						<th>'.L10N::t('Typ', $sDescription).'</th>
						<th>'.L10N::t('Datum/Uhrzeit', $sDescription).'</th>
						<th>'.L10N::t('Kommentar', $sDescription).'</th>
						<th>'.L10N::t('User', $sDescription).'</th>
						<th>'.L10N::t('Aktion', $sDescription).'</th>
					</tr>
				';

				break;
			}
			case 'additional_document':
			{
				$sHistoryHtml .= '
					<table class="table tblDocumentTable highlightRows">
						<colgroup>
							<col width="200px" />
							<col width="65px" />
							<col width="60px" />
							<col width="140px" />
							<col width="auto" />
							<col width="140px" />
							<col width="50px" />
						</colgroup>
				';
				$sHistoryHtml .= '
					<tr>
						<th>'.L10N::t('Typ', $sDescription).'</th>
						<th>'.L10N::t('Nummer', $sDescription).'</th>
						<th>'.L10N::t('Version', $sDescription).'</th>
						<th>'.L10N::t('Datum/Uhrzeit', $sDescription).'</th>
						<th>'.L10N::t('Kommentar', $sDescription).'</th>
						<th>'.L10N::t('User', $sDescription).'</th>
						<th>'.L10N::t('Aktion', $sDescription).'</th>
					</tr>
				';

				break;
			}
			default:
			{
				$sHistoryHtml .= '
					<table class="table tblDocumentTable highlightRows">
						<colgroup>
							<col width="85px" />
							<col width="60px" />
							<col width="200px" />
							<col width="120px" />
							<col width="100px" />
							<col width="auto" />
							<col width="140px" />
							<col width="50px" />
						</colgroup>
				';
				$sHistoryHtml .= '
					<tr>
						<th>'.L10N::t('Nummer', $sDescription).'</th>
						<th>'.L10N::t('Version', $sDescription).'</th>
						<th>'.L10N::t('Typ', $sDescription).'</th>
						<th>'.L10N::t('Datum/Uhrzeit', $sDescription).'</th>
						<th>'.L10N::t('Betrag', $sDescription).'</th>
						<th>'.L10N::t('Kommentar', $sDescription).'</th>
						<th>'.L10N::t('User', $sDescription).'</th>
						<th>'.L10N::t('Aktion', $sDescription).'</th>
					</tr>
				';

				break;
			}
		}

		if(!is_array($aDocuments)) {
			$aDocuments = array();
		}
		
		$bLastDocumentView = 'gross';
		foreach($aDocuments as $oDocument) {
			if($oDocument->type != 'storno') {
				if(
					strpos($oDocument->type, 'netto') !== false
				) {
					$bLastDocumentView = 'net';
				} elseif(
					$oDocument->type == 'creditnote'
				) {
					$bLastDocumentView = 'creditnote';
				} else {
					$bLastDocumentView = 'gross';
				}
			}
		}
	
		foreach($aDocuments as $oDocument) {

			$sNumber = $oDocument->document_number;
			$aVersions = $oDocument->getAllVersions(false, 'DESC', false);

			foreach($aVersions as $oVersion) {

				$oVersionHistory = new Ext_Thebing_Inquiry_Document_Version_History($oVersion);
				
				$oVersionHistory->setL10NDescription($sDescription);
				$oVersionHistory->setSchool($oSchool);
				$oVersionHistory->setCurrency($iCurrency);
				$oVersionHistory->setHistoryType($sHistoryType);
				$oVersionHistory->setLastDocumentView($bLastDocumentView);

				// Wenn Dokument auf anderem basiert: Selektierte Rechnung in Klammern anzeigen
				if(!empty($oVersion->invoice_select_id)) {
					$oSelectedDocument = $oVersion->getSelectedInvoiceDocument();
					if($oSelectedDocument) {
						$sNumber .= ' ('.$oSelectedDocument->document_number.')';
					}
				}

				$sHistoryHtml .= $oVersionHistory->generate($sNumber);
				$sNumber = '';

			}

		}

		$sHistoryHtml		.= '
			</table>
			';

		$oTemp = new Ext_Gui2_Bar_Legend(new Ext_Thebing_Gui2());
		$oTemp->addTitle(L10N::t('Legende', $sDescription));
		$oTemp->addInfo(L10N::t('gelöschte Dokumente', $sDescription), Ext_Thebing_Util::getColor('storno'));
		
		$sHistoryHtml .= '</div><div class="clearfix" style="height: 100%; position: relative; border-top:1px solid #CCCCCC; bottom:0px;margin-left:-10px; margin-right:-10px;">'.$oTemp.'</div>';

		return $sHistoryHtml;

	}

	// HTML für den Unerrichts/Anwesenheits Tab
	static public function getTuitionTabHtml($aSelectedIds = array()) {

		$sTutionHtml = '';

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= (int)reset($aSelectedIds);

		$dFrom			= Ext_Thebing_Format::ConvertDate($_VARS['filter']['search_time_from_1']);
		$dUntil			= Ext_Thebing_Format::ConvertDate($_VARS['filter']['search_time_until_1']);

		$oProgressReport = new Ext_Thebing_Tuition_ProgressReport($iSelectedId, $dFrom, $dUntil);
		$oProgressReport->setTranslationPart(self::$sL10NDescription);
		
		$sTutionHtml .= (string)$oProgressReport->getDialogHtml();

		return $sTutionHtml;
	}

	/**
	 * HTML für das FerienTab im StudentRecord
	 *
	 * @param $oDialogData
	 * @param array $aSelectedIds
	 * @param array $aHolidays
	 * @param int $bReadOnly
	 * @return string
	 */
	public static function getHolidayTabHtml($oDialogData, $aSelectedIds = array(), $aHolidays = array(), $bReadOnly=0) {

		$aBack = array();
		$aBack['html'] = '';

		// Warnung, da für neue Kunden keine Ferien gebucht werden können
		/** @var Ext_Gui2_Dialog $oDialogData */
		$oHint = $oDialogData->createNotification(L10N::t('Achtung'), L10N::t('Ferien können nur für gespeicherte Kurse/Unterkünfte gebucht werden', self::$sL10NDescription), 'hint', array('row_class' => 'holiday_info', 'row_style' => 'display: none;'));
		$aBack['html'] .= $oHint->generateHTML();

		// Hinweis
		/** @var Ext_Gui2_Dialog $oDialogData */
		$oHint = $oDialogData->createNotification(L10N::t('Achtung'), L10N::t('Vorhandene Unterkunftszuweisungen werden gelöscht wenn Ferien eingetragen werden.', self::$sL10NDescription), 'hint');
		$aBack['html'] .= $oHint->generateHTML();

		$aBack['html'] .= '<div>';

		// add row for a existing holiday entry
		$iHolidayCount = 0;
		foreach((array)$aHolidays as $oHoliday) {
			$iHolidayCount++;
			$bDeleteEnabled = count($aHolidays) == $iHolidayCount;
			$aBack['html'] .= self::getHolidayTabBodyHtml($oDialogData, $oHoliday, false, $bDeleteEnabled, $bReadOnly);
		}

		// add row for a new holiday entry
		$aBack['html'] .= self::getHolidayTabBodyHtml($oDialogData, null, true, false, $bReadOnly);

		return $aBack['html'];

	}

	public static function getHolidayTabBodyHtml(Ext_Gui2_Dialog $oDialogData, Ext_TS_Inquiry_Holiday $oHoliday = null, $bEditMode, $bDeleteEnabled, $bReadOnly = false) {

		if($oHoliday === null) {
			$aHoliday = [];
			$aHoliday['id'] = 'new';
			$aHoliday['weeks'] = '';
			$aHoliday['from'] = '';
			$aHoliday['until'] = '';
		} else {
			$aHoliday = $oHoliday->getData();
		}

		$aHoliday['courses'] = [];
		$aHoliday['accommodations'] = [];

		if($oHoliday instanceof Ext_TS_Inquiry_Holiday) {
			foreach($oHoliday->getSplittings() as $oSplitting) {
				if($oSplitting->getType() === 'course') {
					/** @var Ext_TS_Inquiry_Journey_Course $oJourneyCourse */
					$oJourneyCourse = $oSplitting->getJoinedObject('new_course');
					if($oJourneyCourse->active) {
						$aHoliday['courses'][] = $oJourneyCourse->getInfo();
					}
				} else {
					/** @var Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation */
					$oJourneyAccommodation = $oSplitting->getJoinedObject('new_accommodation');
					if($oJourneyAccommodation->active) {
						$aHoliday['accommodations'][] = $oJourneyAccommodation->getAccommodationCategoryWithRoomTypeAndMeal();
					}
				}
			}
		}

		$aNoEdit = array();

		// gibt an ob Ferien editiert werden dürfen
		$aFieldTypes = array();
		if(!$bEditMode) {
			$aFieldTypes['input']					= 'input';
			$aFieldTypes['calendar']				= 'calendar';
			$aFieldTypes['select']					= 'select';
			$aFieldTypes['checkbox']				= 'checkbox';
			$aNoEdit = array(
					'readonly' => true, 
					'class' => 'readonly'
			);
		}else {
			$aFieldTypes['input']					= 'input';
			$aFieldTypes['calendar']				= 'calendar';
			$aFieldTypes['select']					= 'select';
			$aFieldTypes['checkbox']				= 'checkbox';
			// Ferien die einmal angelegt worden sind können nicht abgeändert werden momentan!
			
		}

		$i = 0;

		// Außeres Div
		$oBackDiv					= new Ext_Gui2_Html_Div();
		$oBackDiv->class			= 'holidayTab box';

		// headline Div
//		$oHeadlineDiv				= new Ext_Gui2_Html_Div();
		//$oHeadlineDiv->style		= 'cursor: pointer; background-color: #ccc;';
		//$oHeadlineDiv->class		= 'accordion headv holidays';

		$oBackDiv->setElement('<div class="box-separator"></div>');

		// body Div
		$oInnerDiv					= new Ext_Gui2_Html_Div();
		//$oInnerDiv->style			= 'display: none;';
		$oInnerDiv->class = 'box-body';

		if($aHoliday['id'] == 'new') {
			$sTitle = L10N::t('Ferien eintragen', self::$sL10NDescription);
		} else {
			$sTitle = vsprintf('%s: %s – %s', [
				L10N::t($aHoliday['type'] === 'school' ? 'Schulferien' : 'Schülerferien', self::$sL10NDescription),
				Ext_Thebing_Format::LocalDate($aHoliday['from']),
				Ext_Thebing_Format::LocalDate($aHoliday['until'])
			]);
		}

		$oTitle = new Ext_Thebing_Inquiry_Gui2_Html_PositionTitle();
		$oTitle->oDialogData = $oDialogData;
		$oTitle->sTitle = $sTitle;
		$oTitle->sTab = 'holiday';
		$oTitle->sL10NDescription = self::$sL10NDescription;
		$oTitle->sDeleteButtonId = 'holiday_delete_btn_' . $aHoliday['id']; // Sonderbehandlung für alten Schrott
		$oTitle->bReadOnly = $bReadOnly;

		if($aHoliday['id'] == 'new') {
			// Bei neuen Ferien Button sperren, da ansonsten auch ein direkter Request mit ID new abgefeuert wird
			$oTitle->bDeleteButtonReadOnly = true;
		}

		$oDiv = $oTitle->getTitelRowHtml();

		## START Head definieren
		/*$oDiv						= new Ext_Gui2_Html_Div();
		if($aHoliday['id'] == 'new') {
			if($bReadOnly == 0) {
				$oDiv				->setElement(L10N::t('Ferien für den Kunden buchen', self::$sL10NDescription));
			} else {
				$oDiv				->setElement(L10N::t('Es wurden keine Ferien für den Kunden gebucht', self::$sL10NDescription));
			}
		} else {
			$oDiv					->setElement(L10N::t('Ferien', self::$sL10NDescription) . ': '. $aHoliday['from'] . ' - ' . $aHoliday['until']);
		}
		$oDiv->id					= 'holidays[new][headline]';
		$oDiv->class				= 'accordion';
		$oDiv->style				= 'padding: 3px; margin-top: 2px; line-height: 20px; height:21px';

		// Löschen von Ferien
		if (($aHoliday['id'] != 'new') && $bDeleteEnabled) {
			
			
			$oDivDeleteMain		= new Ext_Gui2_Html_Div();
			$oDivDeleteMain->style = 'float: right;';
			
			$oDivDelete				= new Ext_Gui2_Html_Div();
			$oDivDelete->class		= 'holiday_block_remover';
			$oDivDelete->style		= 'float: right; margin-top: -3px'; # background: none repeat scroll 0px 0px rgb(204, 204, 204);	
			
			$DivBarLink				= new Ext_Gui2_Html_Div();
			$DivBarLink->class		= 'guiBarElement guiBarLink holiday_delete w150';
			$DivBarLink->id			= 'holiday_delete_btn_' . $aHoliday['id'];
			
			$DivToolbarIcon			= new Ext_Gui2_Html_Div();
			$DivToolbarIcon->class	= 'divToolbarIcon';
			
			$oImg = $oDialogData->create('image');
			$oImg->src		= Ext_Thebing_Util::getIcon('delete');
			$oImg->alt		= L10N::t('Löschen');
			$oImg->style	= 'cursor:pointer; float:left; margin-top: 3px;';

			$DivToolbarIcon->setElement($oImg);
			
			$oDivLabel = $oDialogData->create('div');
			$oDivLabel->class = 'divToolbarLabel';
			$oDivLabel->setElement( '&nbsp;' . L10N::t('Ferien löschen', self::$sL10NDescription));

			$DivBarLink->setElement($DivToolbarIcon);
			$DivBarLink->setElement($oDivLabel);
			$oDivDelete->setElement($DivBarLink);
			$oDivDeleteMain->setElement($oDivDelete);
			
			
			$oDiv					->setElement($oDivDeleteMain);
		}*/

		// Hiddenfelder im Head
		$oHidden		= $oDialogData->create('input');
		$oHidden->type	= 'hidden';
		$oHidden->name	= 'holidays[' . $aHoliday['id'] . '][id]';
		$oHidden->id	= 'holidays[' . $aHoliday['id'] . '][id]';
		$oHidden->value	= $aHoliday['id'];
		$oHidden->class = 'holiday_id';
		$oBackDiv->setElement($oHidden);

		$oBackDiv->setElement($oDiv);
		##ENDE

		## START Body definieren

		$oRow = $oDialogData->createRow(L10N::t('Type', self::$sL10NDescription), 'select', [
			'id' => 'holidays[' . $aHoliday['id'] . '][type]',
			'select_options' => [
				'school' => L10N::t('Schulferien', self::$sL10NDescription),
				'student' => L10N::t('Schülerferien', self::$sL10NDescription)
			],
			'default_value' => $aHoliday['id'] == 'new' ? 'student' : $aHoliday['type'],
			'info_icon_key' => 'inquiry_holidays_type',
			'disabled' => true
		]);
		$oInnerDiv->setElement($oRow);

		$sLabel					= L10N::t('Wochenanzahl', self::$sL10NDescription);
		$sID					= 'holidays[' . $aHoliday['id'] . '][weeks]';
		$sName					= 'holidays[' . $aHoliday['id'] . '][weeks]';
		
		
		if($aHoliday['id'] == 'new'){
			$oRefreshImg = new Ext_Gui2_Html_Button();
			//$oRefreshImg->style = 'padding: 0px; width: 16px; height: 16px; border: none; margin-left:2px; margin-top:2px; float:right; display: block; cursor: pointer;';
			$oRefreshImg->title = L10N::t('Enddatum neu berechnen', self::$sL10NDescription);
			$oRefreshImg->onclick="return false;";
			$oRefreshImg->class = 'recalculate_holiday_enddate btn btn-default btn-sm';
			$oRefreshImg->setElement('<i class="fa fa-refresh"></i>');
			$oRefreshImg->id = str_replace('[weeks]', '[refresh]', $sID);
		}

		$aOptions				= array('value' => $aHoliday['weeks'], 'id' => $sID, 'name' => $sName, 'class' => '', 'input_div_addon' => $oRefreshImg, 'info_icon_key' => 'inquiry_holidays_weeks');

		$oDiv					= $oDialogData->createRow($sLabel, $aFieldTypes['input'], array_merge($aOptions, $aNoEdit) );
		$oInnerDiv				->setElement($oDiv);

		$sLabel					= L10N::t('Von', self::$sL10NDescription);
		$sID					= 'holidays[' . $aHoliday['id'] . '][from]';
		$sName					= 'holidays[' . $aHoliday['id'] . '][from]';
		$aOptions				= array('value' => Ext_Thebing_Format::LocalDate($aHoliday['from']), 'id' => $sID, 'name' => $sName, 'calendar_id' => 'holidayscalendar[' . $aHoliday['id'] . '][from]' . $i++, 'class' => 'holiday_from txt', 'info_icon_key' => 'inquiry_holidays_from');
		$oDiv					= $oDialogData->createRow( $sLabel,	$aFieldTypes['calendar'], array_merge($aOptions, $aNoEdit)	);
		$oInnerDiv				->setElement($oDiv);

		$sLabel					= L10N::t('Bis', self::$sL10NDescription);
		$sID					= 'holidays[' . $aHoliday['id'] . '][until]';
		$sName					= 'holidays[' . $aHoliday['id'] . '][until]';
		$aOptions				= array('value' => Ext_Thebing_Format::LocalDate($aHoliday['until']), 'id' => $sID, 'name' => $sName, 'calendar_id' => 'holidayscalendar[' . $aHoliday['id'] . '][until]' . $i++, 'class' => 'holiday_until txt', 'info_icon_key' => 'inquiry_holidays_until');
		$oDiv					= $oDialogData->createRow( $sLabel,	$aFieldTypes['calendar'], array_merge($aOptions, $aNoEdit)	);
		$oInnerDiv				->setElement($oDiv);

		if ($aHoliday['id'] == 'new'){
			$sLabel					= L10N::t('Kurse', self::$sL10NDescription);
			$sID					= 'holidays[' . $aHoliday['id'] . '][course_ids][]';
			$sName					= 'holidays[' . $aHoliday['id'] . '][course_ids]';
			$aOptions				= array(
												'value' => '', 
												'id' => $sID, 
												'name' => $sName, 
												'multiple' => 3,
												//'jquery_multiple' => 1, // TODO möglich machen
												'select_options' => array(), 
												'class' => 'holiday_course_ids txt',
												'info_icon_key' => 'inquiry_holidays_courses'
											);
			$oDiv					= $oDialogData->createRow( 
																$sLabel,	
																$aFieldTypes['select'], 
																array_merge($aOptions, $aNoEdit)
															);
			$oInnerDiv				->setElement($oDiv);


			$sLabel					= L10N::t('Nachfolgende Kurse verschieben', self::$sL10NDescription);
			$sID					= 'holidays[' . $aHoliday['id'] . '][move_following_courses]';
			$sName					= 'holidays[' . $aHoliday['id'] . '][move_following_courses]';
			$aOptions				= array( 'id' => $sID, 'name' => $sName, 'class' => 'move_following_courses', 'info_icon' => false);
			$oDiv					= $oDialogData->createRow( $sLabel,	$aFieldTypes['checkbox'], array_merge($aOptions, $aNoEdit) );
			$oDiv					->style = 'display: none;';
			$oInnerDiv				->setElement($oDiv);

			$sLabel					= L10N::t('Nachfolgende Kurse', self::$sL10NDescription);
			$sID					= 'holidays[' . $aHoliday['id'] . '][following_courses][]';
			$sName					= 'holidays[' . $aHoliday['id'] . '][following_courses]';
			$aOptions				= array('value' => '', 'id' => $sID, 'name' => $sName, 'multiple' => 5, 'select_options' => array(), 'info_icon' => false);
			$oDiv					= $oDialogData->createRow( $sLabel,	$aFieldTypes['select'], array_merge($aOptions, $aNoEdit) );
			$oDiv->id				= 'following_courses_section_' . $aHoliday['id'];
			$oDiv					->style = 'display: none;';
			$oInnerDiv				->setElement($oDiv);

		} else {

			if(!empty($aHoliday['courses'])) {
				$oInputDiv = $oDialogData->create('div');
				$oInputDiv->style = 'padding-top: 10px;';
				$oInputDiv->setElement(implode(', ', $aHoliday['courses']));

				$oDiv = $oDialogData->createRow(L10N::t('Kurse', self::$sL10NDescription), $oInputDiv, ['info_icon' => false]);
				$oInnerDiv->setElement($oDiv);
			}

		}

		if ($aHoliday['id'] == 'new') {

			$sLabel					= L10N::t('Unterkünfte', self::$sL10NDescription);
			$sID					= 'holidays[' . $aHoliday['id'] . '][accommodation_ids][]';
			$sName					= 'holidays[' . $aHoliday['id'] . '][accommodation_ids]';
			$aOptions				= array(
											'value' => '', 
											'id' => $sID, 
											'name' => $sName, 
											'multiple' => 3,
											//'jquery_multiple' => 1, // TODO möglich machen
											'select_options' => array(), 
											'class' => 'holiday_accommodation_ids txt',
											'info_icon_key' => 'inquiry_holidays_accommodations'
											);
			$oDiv					= $oDialogData->createRow( 
																$sLabel,	
																$aFieldTypes['select'], 
																array_merge($aOptions, $aNoEdit) 
															);
			$oInnerDiv				->setElement($oDiv);
/*
			$sLabel					= L10N::t('Neue Unterkunft für Ferienzeitraum anhängen', self::$sL10NDescription);
			$sID					= 'holidays[' . $aHoliday['id'] . '][move_following_accommodations]';
			$sName					= 'holidays[' . $aHoliday['id'] . '][move_following_accommodations]';
			$aOptions				= array( 'id' => $sID, 'name' => $sName, 'class' => 'move_following_accommodations');
			$oDiv					= $oDialogData->createRow( $sLabel,	$aFieldTypes['checkbox'], array_merge($aOptions, $aNoEdit) );
			$oDiv					->style = 'display: none;';
			$oInnerDiv				->setElement($oDiv);
*/
			$sLabel					= L10N::t('Nachfolgende Unterkünfte', self::$sL10NDescription);
			$sID					= 'holidays[' . $aHoliday['id'] . '][following_accommodations][]';
			$sName					= 'holidays[' . $aHoliday['id'] . '][following_accommodations]';
			$aOptions				= array('value' => '', 'id' => $sID, 'name' => $sName, 'multiple' => 5, 'select_options' => array(), 'info_icon' => false);
			$oDiv					= $oDialogData->createRow( $sLabel,	$aFieldTypes['select'], array_merge($aOptions, $aNoEdit) );
			$oDiv->id				= 'following_accommodations_section_' . $aHoliday['id'];
			$oDiv					->style = 'display: none;';
			$oInnerDiv				->setElement($oDiv);

		} else {

			if(!empty($aHoliday['accommodations'])) {
				$oInputDiv = $oDialogData->create('div');
				$oInputDiv->style = 'padding-top: 10px;';
				$oInputDiv->setElement(implode(', ', $aHoliday['accommodations']));

				$oDiv = $oDialogData->createRow(L10N::t('Unterkünfte', self::$sL10NDescription), $oInputDiv);
				$oInnerDiv->setElement($oDiv);
			}

		}

		$oBackDiv->setElement($oInnerDiv);

		## ENDE
		return $oBackDiv->generateHTML($bReadOnly);

	}

	/**
	 * @param $oDivContent
	 * @param string $sList
	 * @param string $sType
	 * @return mixed
	 */
	public static function addAccordionPayments($oDivContent, $sList, $sType) {

		$box = new Ext_Gui2_Dialog_Box(L10N::t('Anbieterbezahlungen', self::$sL10NDescription), true);
		$box->setElement($sList);
		
		$oDivContent->setElement($box->generateHtml());

		return $oDivContent;
	}

	/**
	 * Erste Schule aus dem Schul-Select holen (für Gruppen-Dialog)
	 *
	 * @param Ext_TS_Inquiry|Ext_Thebing_Inquiry_Group $oObject
	 * @param Ext_Gui2_Dialog $oDialog
	 * @return Ext_Thebing_School
	 */
	protected static function getSelectedSchool($oObject, Ext_Gui2_Dialog $oDialog) {

		if($oObject->exist()) {
			$oSchool = $oObject->getSchool();
			if(
				!empty($oSchool) &&
				$oSchool->exist()
			) {
				return $oSchool;
			}
		}

		if(!Ext_Thebing_System::isAllSchools()) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}

		if(
			empty($oSchool) ||
			!$oSchool->exist()
		) {
			throw new RuntimeException('Could not find school for inquiry dialog!');
		}

		return $oSchool;

	}

	/**
	 * Manueller wiederholbarer Bereich: E-Mails
	 *
	 * @see \Ext_Thebing_Inquiry_Gui2::setEditDialogDataContactEmails()
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_Tab $oTab
	 */
	public static function setContactEmailContainer(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog, $oTab) {

		$oDiv = $oDialog->create('div');
		$oDiv->id = 'container_contact_email';

		$oRemoveIcon = new Ext_Gui2_Html_Button();
		$oRemoveIcon->title = $oGui->t('Löschen');
		$oRemoveIcon->class = 'remove_icon inputDivAddonIcon btn btn-default btn-sm';
		
		$sRemoveI = Ext_Gui2_Html::getIconObject('fa-minus-circle');
		$oRemoveIcon->setElement($sRemoveI);

		// Feld dient nur als Template, geht alles über JS (wie bei den Leistungen)
		$oDiv->setElement($oDialog->createRow($oGui->t('E-Mail'), 'input', array(
			'id' => '',
			'name' => 'contact_email',
			'input_div_addon' => $oRemoveIcon,
			'info_icon_key' => 'inquiry_contact_email',
		)));

		$oTab->setElement($oDiv);
		$oTab->setElement(\Ext_Thebing_Inquiry_Gui2_Html::getAddButton($oDialog, 'email'));

	}

	/**
	 * Pro Leistungsblock Notification für Ferien
	 *
	 * @param Ext_TS_Inquiry_Journey_Service $oJourneyService
	 * @return array
	 */
	private static function getServiceHolidayNotifications(Ext_TS_Inquiry_Journey_Service $oJourneyService) {

		$aMessages = [];

		foreach($oJourneyService->getHolidaySplittings() as $aSplitting) {

			$sLabel = L10N::t($aSplitting['type'] === 'student' ? 'Schülerferien' : 'Schulferien', self::$sL10NDescription);
			$sSplitMessage = 'Diese Leistung wurde durch %s mit <em>%s</em> geteilt (%s).';

			if(
				$oJourneyService->id == $aSplitting['journey_'.$oJourneyService->getKey().'_id'] &&
				!empty($aSplitting['journey_split_'.$oJourneyService->getKey().'_id'])
			) {
				$aMessages[] = vsprintf(L10N::t($sSplitMessage, self::$sL10NDescription), [
					$sLabel,
					self::getServiceHolidayLabel($oJourneyService, $aSplitting['journey_split_'.$oJourneyService->getKey().'_id']),
					L10N::t('linker Teil', self::$sL10NDescription)
				]);
			} elseif($oJourneyService->id == $aSplitting['journey_split_'.$oJourneyService->getKey().'_id']) {
				$aMessages[] = vsprintf(L10N::t($sSplitMessage, self::$sL10NDescription), [
					$sLabel,
					self::getServiceHolidayLabel($oJourneyService, $aSplitting['journey_'.$oJourneyService->getKey().'_id']),
					L10N::t('rechter Teil', self::$sL10NDescription)
				]);
			} else {
				$aMessages[] = vsprintf(L10N::t('Diese Leistung wurde durch %s verschoben.', self::$sL10NDescription), [
					$sLabel
				]);
			}

		}

		return $aMessages;

	}

	/**
	 * Label für Ferien-Notification
	 *
	 * @param Ext_TS_Inquiry_Journey_Service $oJourneyService
	 * @param int $iOtherServiceId
	 * @return string
	 */
	private static function getServiceHolidayLabel(Ext_TS_Inquiry_Journey_Service $oJourneyService, $iOtherServiceId) {

		if($oJourneyService instanceof Ext_TS_Inquiry_Journey_Course) {
			$oService = Ext_TS_Inquiry_Journey_Course::getInstance($iOtherServiceId);
			return vsprintf('%s (%s – %s)', [
				$oService->getCourse()->getName(),
				Ext_Thebing_Format::LocalDate($oService->from),
				Ext_Thebing_Format::LocalDate($oService->until)
			]);
		} elseif($oJourneyService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
			$oService = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iOtherServiceId);
			return vsprintf('%s (%s – %s)', [
				$oService->getAccommodationCategoryWithRoomTypeAndMeal(),
				Ext_Thebing_Format::LocalDate($oService->from),
				Ext_Thebing_Format::LocalDate($oService->until)
			]);
		} else {
			throw new InvalidArgumentException('Unknown service type');
		}

	}

}
