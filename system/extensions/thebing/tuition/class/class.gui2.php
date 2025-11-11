<?php

use TcExternalApps\Service\AppService;
use TsTuition\Service\BlockCancellationService;
use TsTuition\Entity\Block\Unit;

/**
 * @property Ext_Thebing_Tuition_Class $oWDBasic
 */
class Ext_Thebing_Tuition_Class_Gui2 extends Ext_Thebing_Gui2_Data
{
	const TRANSLATION_PATH = 'Thebing » Tuition » Classes';

	protected $_aColors;

	protected $_iCurrentWeekStart;

	protected $_aCourses;

	protected $_aLevels;

	protected $_aWeeks;

	protected $_aRooms;

	protected $_aTeachers;

	protected $_aBlocksNextWeek;

	public function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {
		global $_VARS;

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= (int)reset($aSelectedIds);

		if(!empty($_VARS['additional'])) {

			$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $_VARS['additional']);
			return $aData;

		} else {
			$aData = parent::getEditDialogData($aSelectedIds, $aSaveData);
		}

		$iCurrentWeek	= false;
		$iCurrentLevel	= false;
		$aBlocks		= array();
		
		if(!$this->oWDBasic)
		{
			$this->_getWDBasicObject($aSelectedIds);
		}
		
		$oTuitionClass		= $this->oWDBasic;
		
		$iStartWeek			= $oTuitionClass->start_week_timestamp;
		$iParamFilterWeek	= $_VARS['filter']['week_filter'];

		if(is_numeric($iParamFilterWeek))
		{
			if(is_numeric($iStartWeek))
			{
				$iCurrentWeek		= $oTuitionClass->getCurrentWeek($iParamFilterWeek);
			}

			$aBlocks			= $oTuitionClass->getBlocks($iParamFilterWeek);

			if(!empty($aBlocks))
			{
				$oBlockFirst = reset($aBlocks);
				$iCurrentLevel = $oBlockFirst->level_id;
			}
		}

		foreach((array)$aData as $iKey => $aSaveInfos)
		{
			if($aSaveInfos['db_column'] === 'current_week') {
				$aData[$iKey]['value'] = $iCurrentWeek;
			}
			elseif($aSaveInfos['db_column'] == 'current_level')
			{
				$aData[$iKey]['value'] = $iCurrentLevel;
			}
			elseif($aSaveInfos['db_column'] == 'blocks')
			{
				//da alle Blöcke die gleiche db_column haben entfernen wir die leer generierten Daten,
				//damit das füllen der Daten leichter ist
				unset($aData[$iKey]);
			}
			elseif($aSaveInfos['db_column'] == 'ignore_errors')
			{
				$aData[$iKey]['value'] = null;
			}
			elseif($aSaveInfos['db_column'] == 'start_week') {
				
				if($iSelectedId != 0) {
					// Muss Timestamp sein, da Werte irgendwo aus Dialog-Vorbereitung kommen
					$aData[$iKey]['value'] = $iStartWeek;
				} elseif(!empty($iParamFilterWeek)) {
					//wenn neues Objekt, dann setze die Startwoche aus dem Wert des selektierten Dropdown-Filter
					$aData[$iKey]['value'] = $iParamFilterWeek;
				// Reload Dialog
				} elseif(!empty($aData[$iKey]['value'])) {
					$startWeek = \Carbon\Carbon::parse($aData[$iKey]['value']);
					$aData[$iKey]['value'] = $startWeek->getTimestamp();
				}

			}
		}

		if(!empty($aBlocks))
		{
			$iKey = count($aData);

			$sHashAddon		= '['.$this->_oGui->hash.']';
			$sSelectedId	= 'ID_'.$iSelectedId;
			$sIdAddon		= '['.$sSelectedId.']';

			foreach($aBlocks as $iBlockKey => $oBlock)
			{
				$sBlockId		= '['.$iBlockKey.']';
				$sIdNameAddon	= 'save'.$sHashAddon.$sIdAddon.'[blocks][ktcl]'.$sBlockId;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[block_id]';
				$aData[$iKey]['value']		= $oBlock->id;
				$aData[$iKey]['required']	= 0;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[days]';
				$aData[$iKey]['value']		= $oBlock->days;
				$aData[$iKey]['required']	= 1;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[template]';
				$aData[$iKey]['value']		= $oBlock->template_id;
				$aData[$iKey]['required']	= 0;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[from]';
				$aData[$iKey]['required']	= 1;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[until]';
				$aData[$iKey]['required']	= 1;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[lessons]';
				$aData[$iKey]['required']	= 1;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[rooms]';
				$aData[$iKey]['value']		= $oBlock->getRoomIds();
				$aData[$iKey]['required']	= 0;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias']	= 'ktcl';
				$aData[$iKey]['db_column']	= 'blocks';
				$aData[$iKey]['id']			= $sIdNameAddon.'[teacher_id]';
				$aData[$iKey]['value']		= $oBlock->teacher_id;
				$aData[$iKey]['required']	= 0;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias'] = 'ktcl';
				$aData[$iKey]['db_column'] = 'blocks';
				$aData[$iKey]['id'] = $sIdNameAddon.'[description]';
				$aData[$iKey]['value'] = $oBlock->description;
				$aData[$iKey]['required'] = 0;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;

				$aData[$iKey]['db_alias'] = 'ktcl';
				$aData[$iKey]['db_column'] = 'blocks';
				$aData[$iKey]['id'] = $sIdNameAddon.'[description_student]';
				$aData[$iKey]['value'] = $oBlock->description_student;
				$aData[$iKey]['required'] = 0;
				$aData[$iKey]['select_options'] = array();
				$aData[$iKey]['force_options_reload'] = 0;
				$iKey++;
			}
		}

		return $aData;
	}

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = [], $sAdditional = false): array {
		
		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		
		if($sIconAction == 'daily_comments') {

			// Aufruf über die Planung
			if($this->request->has('block_id')) {
				
				$blockIds = (array)$this->request->input('block_id');
				$blockId = reset($blockIds);

				$block = Ext_Thebing_School_Tuition_Block::getInstance($blockId);

				$class = $block->getClass();
			
				$blocks = $class->getBlocks($block->week);
			
			// Aufruf über die Klassenliste
			} else {
				
				$class = $this->getWDBasicObject($aSelectedIds);
								
				$weekFilter = $this->request->input('filter')['week_filter']??null;

				$oWdDate = new WDDate($weekFilter, WDDate::TIMESTAMP);
				
				$blocks = $class->getBlocks($oWdDate->get(WDDate::DB_DATE));
				
				$block = reset($blocks);
				$blockId = $block->id;
				
			}

			$classDays = [];
			foreach($blocks as $block) {
				$classDays = array_merge($classDays, $block->days);
			}
			
			$classDays = array_unique($classDays);
			
			$localeDays = \Ext_TC_Util::getLocaleDays($sLanguage, 'wide');
			
			$smarty = new \SmartyWrapper();

			$days = array_intersect_key($localeDays, array_flip($classDays));
			
			$absenceReasons = \TsTuition\Entity\AbsenceReason::getOptions(false);
			$absenceReasons = \Util::addEmptyItem($absenceReasons);
	
			$smarty->assign('blockId', $blockId);
			$smarty->assign('days', $days);
			$smarty->assign('blocks', $blocks);
			$smarty->assign('absenceReasons', $absenceReasons);

			$sTemplatePath = 'system/bundles/TsTuition/Resources/views/daily_comments.tpl';
			$aData['html'] = $smarty->fetch($sTemplatePath);
					
		}
		
		return $aData;
	}
	
	/**
	 * @global type $_VARS
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param type $aSelectedIds
	 * @param type $sAdditional
	 * @return type
	 */
	public function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional=false) {
		global $_VARS;
		
		$aSelectedIds = (array)$aSelectedIds;
		
		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);	
		}
		
		$oTuitionClass = $this->oWDBasic;

		$iParamFilterWeek = $_VARS['filter']['week_filter'];
		$aBlocks = $oTuitionClass->getBlocks($iParamFilterWeek);
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aDays = Ext_Thebing_Util::getDays('%A', null, $oSchool->course_startday);
		$aTuitionTemplates = $oSchool->getTuitionTemplates(true);

		$aTimes = $oSchool->getClassTimesOptions('format', 5);

		if(!empty($_VARS['additional'])) {
			$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $_VARS['additional']);
			return $aData;
		} else {
			$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds);
		}

		$sBlockHtml = '';

		if(empty($aBlocks)) {
			$aBlocks = [new Ext_Thebing_School_Tuition_Block()];
		}

		foreach($aBlocks as $iKey => $oBlock) {

			$sHashAddon = '['.$this->_oGui->hash.']';
			$sIdAddon	= '['.$aData['id'].']';
			$sBlockId	= '['.$iKey.']';

			$sIdNameAddon	= 'save'.$sHashAddon.$sIdAddon.'[blocks][ktcl]'.$sBlockId;

			$iTemplateId	= $oBlock->template_id;
			$aTuitionTemplatesWithCustom = $aTuitionTemplates;
			if(array_key_exists($iTemplateId,$aTuitionTemplates)){
				//kein custom vorhanden
				$aTuitionTemplatesWithCustom = Ext_Thebing_Util::addEmptyItem($aTuitionTemplatesWithCustom, $this->t('Individuell'));
			} else {
				//custom vorhanden
				$aTuitionTemplatesWithCustom = Ext_Thebing_Util::addEmptyItem($aTuitionTemplatesWithCustom, $this->t('Individuell'), $iTemplateId);
			}

			$aRooms = $oBlock->getAvailableRooms($iParamFilterWeek, true);

			// Add virtual room
			$aRooms[-1] = $this->t('Virtueller Raum');

			$aTeachers		= $oBlock->getAvailableTeachers($iParamFilterWeek,true);
			$sEmptyLabel	= Ext_Thebing_L10N::getEmptySelectLabel('teacher');
			$aTeachers		= Ext_Thebing_Util::addEmptyItem($aTeachers, $sEmptyLabel);

			$oDivBlockContent = $oDialogData->create('div');
			$oDivBlockContent->class	= 'block-content';

			$oDivRow		= $oDialogData->create('div');
			$oDivRow->class	= 'GUIDialogRow block_title';
			$oDivRow->id	= $sIdNameAddon.'[blockRow]';

			$oDivTitle				= $oDialogData->create('div');
			$oDivTitle->style		= 'float:left;';
			$oDivTitle->class = 'block-title';
			$sName = $oBlock->name;

			if(empty($sName)){
				$sName = $this->t('Neuer Block');
			}

			$oDivTitle->setElement($sName);
			$oDivRow->setElement($oDivTitle);

			if ($oDialogData->bReadOnly === false) {
				$oDivAction = $oDialogData->create('div');
				$oDivAction->style = 'float:right;';
				$oDivAction->class = 'block_action';
				$oDivRow->setElement($oDivAction);
			}

			$oDivBlockContent->setElement($oDivRow);

			$oDivBlockContent->setElement($oDialogData->createSaveField('hidden', array(
				'db_column'			=> 'blocks',
				'db_alias'			=> 'ktcl',
				'name'				=> 'save[blocks]'.$sBlockId.'[block_id]',
				'id'				=> $sIdNameAddon.'[block_id]',
				'class'				=> 'hidden_block',
			)));

			$divBlockContentRow = new Ext_Gui2_Html_Div;
			$divBlockContentRow->class = 'grid grid-cols-1 sm:grid-cols-2';
			
			$divBlockContentLeft = new Ext_Gui2_Html_Div;
			#$divBlockContentLeft->class = 'col-md-6';
			
			$divBlockContentRight = new Ext_Gui2_Html_Div;
			#$divBlockContentRight->class = 'col-md-6';
			
			$divBlockContentRow->setElement($divBlockContentLeft);
			$divBlockContentRow->setElement($divBlockContentRight);
			
			$oDivBlockContent->setElement($divBlockContentRow);
						
			$divBlockContentLeft->setElement($this->getWeekdaySelect($oDialogData, $sBlockId, $sIdNameAddon));
			
			$divBlockContentLeft->setElement(
				$oDialogData->createMultiRow($this->t('Planung'), [
					'db_alias' => 'ktcl',
					'items' => [
						[
							'input'=>'select',
							'db_column'			=> 'blocks',
							'name'				=> 'save[blocks]'.$sBlockId.'[template]',
							'id'				=> $sIdNameAddon.'[template]',
							'select_options'	=> $aTuitionTemplatesWithCustom,
							'class'				=> 'template',
							'required'			=> 0,
							'readonly'			=> $oDialogData->bReadOnly,
							'style' => 'max-width: 250px;'
						],
						[
							'input'=>'select',
							'text_before' => '&nbsp;'.$this->t('Von'),
							'db_column'			=> 'blocks',
							'name'				=> 'save[blocks]'.$sBlockId.'[from]',
							'id'				=> $sIdNameAddon.'[from]',
							'class'				=> 'template_field_from',
							'select_options'	=> $aTimes,
							'required'			=> 1,
							'readonly'			=> $oDialogData->bReadOnly,
						],
						[
							'input'=>'select',
							'text_before' => '&nbsp;'.$this->t('Bis'),
							'db_column'			=> 'blocks',
							'name'				=> 'save[blocks]'.$sBlockId.'[until]',
							'id'				=> $sIdNameAddon.'[until]',
							'class'				=> 'template_field_until',
							'select_options'	=> $aTimes,
							'required'			=> 1,
							'readonly'			=> $oDialogData->bReadOnly,
						],
						[
							'input'=>'input',
							'text_before' => '&nbsp;'.$this->t('Lektionen'),
							'db_column'			=> 'blocks',
							'db_alias'			=> 'ktcl',
							'name'				=> 'save[blocks]'.$sBlockId.'[lessons]',
							'id'				=> $sIdNameAddon.'[lessons]',
							'class'				=> 'template_field_lessons text-right',
							'style' => 'width: 80px;',
							'required'			=> 1,
							'readonly'			=> $oDialogData->bReadOnly,
							'text_after'		=> $this->t('/Tag')
						]
					]					
				])
			);
			
			$divBlockContentLeft->setElement($oDialogData->createRow($this->t('Klassenzimmer'),'select',array(
				'db_column'			=> 'blocks',
				'db_alias'			=> 'ktcl',
				'name'				=> 'save[blocks]'.$sBlockId.'[rooms]',
				'id'				=> $sIdNameAddon.'[rooms]',
				'class'				=> 'rooms',
				'select_options'	=> $aRooms,
                'multiple'          => 3,
                'jquery_multiple'   => 1,
                'searchable'        => 1,
				'readonly'			=> $oDialogData->bReadOnly,
				'style'				=> 'width:100%;',
			)));
			
			$divBlockContentLeft->setElement($oDialogData->createRow($this->t('Lehrer'),'select',array(
				'db_column'			=> 'blocks',
				'db_alias'			=> 'ktcl',
				'name'				=> 'save[blocks]'.$sBlockId.'[teacher_id]',
				'id'				=> $sIdNameAddon.'[teacher_id]',
				'class'				=> 'teacher',
				'select_options'	=> $aTeachers,
				'readonly'			=> $oDialogData->bReadOnly,
			)));

			$divBlockContentRight->setElement($oDialogData->createRow($this->t('Inhalt'), 'textarea', array(
				'db_column' => 'blocks',
				'db_alias' => 'ktcl',
				'name' => 'save[blocks]'.$sBlockId.'[description]',
				'id' => $sIdNameAddon.'[description]',
				'class' => 'description autoheight',
				'readonly' => $oDialogData->bReadOnly,
			)));

			if (\TcExternalApps\Service\AppService::hasApp(\TsStudentApp\Handler\ExternalApp::APP_NAME)) {
				$divBlockContentRight->setElement($oDialogData->createRow($this->t('Inhalt (Schüler)'), 'html', array(
					'db_column' => 'blocks',
					'db_alias' => 'ktcl',
					'name' => 'save[blocks]'.$sBlockId.'[description_student]',
					'id' => $sIdNameAddon.'[description_student]',
					'class' => 'description_student',
					'readonly' => $oDialogData->bReadOnly,
				)));
			} else {
				$divBlockContentRight->setElement($oDialogData->createSaveField('hidden', array(
					'db_column' => 'blocks',
					'db_alias' => 'ktcl',
					'name' => 'save[blocks]'.$sBlockId.'[description_student]',
					'id' => $sIdNameAddon.'[description_student]'
				)));				
			}

			$sBlockHtml .= $oDivBlockContent->generateHtml();
		}

		$aData['tabs'][0]['html'] .= $sBlockHtml;

		return $aData;
	}

	protected function getWeekdaySelect(Ext_Gui2_Dialog $oDialogData, $sBlockId, $sIdNameAddon) {

		$functiosSpan = new Ext_Gui2_Html_Span();
		$functiosSpan->class = 'functions';
		
		$btnWeekdays = new Ext_Gui2_Html_Button;
		$btnWeekdays->class = 'btn btn-sm btn-default weekdays';
		$btnWeekdays->setElement($this->t('Wochentage'));
		$btnWeekdays->setDataAttribute('days', [1,2,3,4,5]);

		$btnWeekend = new Ext_Gui2_Html_Button;
		$btnWeekend->class = 'btn btn-sm btn-default weekend';
		$btnWeekend->setElement($this->t('Wochenende'));
		$btnWeekend->setDataAttribute('days', [6,7]);

		$functiosSpan->setElement($btnWeekdays);
		$functiosSpan->setElement($btnWeekend);
		
		$daySelect = new Ext_Gui2_Html_Div;
		$daySelect->class = 'day-select';
		$daySelect->setElement($functiosSpan);

		$localeDays = Ext_Thebing_Util::getLocaleDays();

		$daysSpan = new Ext_Gui2_Html_Span();
		$daysSpan->class = 'days';
		
		$hiddenSelect = new Ext_Gui2_Html_Select();
		$hiddenSelect->class = 'required day-input';
		$hiddenSelect->name = 'save[blocks]'.$sBlockId.'[days][]';
		$hiddenSelect->id = $sIdNameAddon.'[days]';
		$hiddenSelect->style = 'display: none;';
		$hiddenSelect->required = 'required';
		$hiddenSelect->multiple = 'multiple';
		
		foreach($localeDays as $dayKey=>$localeDay) {

			$btnDay = new Ext_Gui2_Html_Button;
			$btnDay->class = 'btn btn-sm btn-default day-'.$dayKey;
			$btnDay->setDataAttribute('day', $dayKey);
			$btnDay->setDataAttribute('days', [$dayKey]);
			$btnDay->setElement($localeDay);

			$daysSpan->setElement($btnDay);
			
			$hiddenSelect->addOption($dayKey, $localeDay);
			
		}

		$daysSpan->setElement($hiddenSelect);
		
		$daySelect->setElement($daysSpan);

//			$divBlockContentLeft->setElement($oDialogData->createRow($this->t('Tage'),'select',array(
//				'db_column'			=> 'blocks',
//				'db_alias'			=> 'ktcl',
//				'name'				=> '',
//				'id'				=> $sIdNameAddon.'[days]',
//				'select_options'	=> $aDays,
//				'multiple'			=> 5,
//				'jquery_multiple'	=> 1,
//				'required'			=> 1,
//				'readonly'			=> $oDialogData->bReadOnly,
//				'style'				=> 'width:100%;',
//			)));
			
		
		return $oDialogData->createRow($this->t('Tage'), $daySelect, [
			'info_icon_key' => 'days',
			'required' => 1,
		]);
	}


	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess=true) {
		global $_VARS;

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$this->oWDBasic->check_different_levels = 1;

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if(
			empty($sAdditional) &&
			(
				'new'==$sIconAction ||
				'edit'==$sIconAction
			)
		) {
			$aTemplateInfos		= array();

			$aClassTemplates = $this->oWDBasic->getTuitionTemplates();

			$oSchool = $this->oWDBasic->getSchool();

			// Beim Neuanlegen einer Klasse ist noch keine Schule zugewiesen
			if(!$oSchool) {
				$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			}
			
			// Templates mit custom = 0
			$aTuitionTemplatesCustom  = $oSchool->getTuitionTemplates(false, false);
			// Sowohl die Templates der Klasse als auch die mit custom=0 müssen vorhanden sein, damit die Uhrzeiten richtig geladen werden
			$aTuitionTemplates = array_merge($aClassTemplates, $aTuitionTemplatesCustom);

			$oFormatFloat	= new Ext_Thebing_Gui2_Format_Float(4, false);
			$oFormatTime	= new Ext_Thebing_Gui2_Format_Time();

			foreach((array)$aTuitionTemplates as $aTemplateData)
			{
				// Gleichzeitig option[value] im Select
				$sTimeFrom	= $this->executeFormat($oFormatTime, $aTemplateData['from']);
				$sTimeUntil = $this->executeFormat($oFormatTime, $aTemplateData['until']);

				$aTemplateInfos[$aTemplateData['id']]['from']		= $sTimeFrom;
				$aTemplateInfos[$aTemplateData['id']]['until']		= $sTimeUntil;

				$fLessons = $this->executeFormat($oFormatFloat, $aTemplateData['lessons']);
				$aTemplateInfos[$aTemplateData['id']]['lessons'] = $fLessons;
			}

			$aData['templates']			= $aTemplateInfos;
			$aData['add_icon_src']		= Ext_Thebing_Util::getIcon('add');
			$aData['remove_icon_src']	= Ext_Thebing_Util::getIcon('delete');
			
			$aData['class_id'] = reset($aSelectedIds);
			
		}
		elseif($sIconAction === 'copy') {

			if($sAdditional == 'replace_data') {
				$aData['id']	= 'ID_xXx'; // Fake ID muss vorhanden sein um Fehler anzeigen zu können
			}else{
				$aData['id']	= 'ID_0'; // Damit sich der Dialog nach dem speichern schließt
			}

			$sDataHtml		= $this->getCopyDialogHtml($sAdditional);

			if (isset($_VARS['containers'])) {
				// Container welche geöffnet waren wieder öffnen
				$aData['containers'] = $_VARS['containers'];
			}

			$aData['tabs'][0]['html'] .= $sDataHtml;

		}

		if($this->oWDBasic) {

			$aDifferentLevels = $this->oWDBasic->aDifferentLevels;

			if(!empty($aDifferentLevels)) {

				$aDifferentLevels = $this->_getDateFormatHeader($aDifferentLevels);

				$aData['different_level_html'] = $this->_getDifferentLevelTableHTml($aDifferentLevels, $aData['id']);
			}
		}

		return $aData;
	}

	public function switchAjaxRequest($_VARS) {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		if(isset($_VARS['block_id'])) {
			$aSelectedIds	= (array)$_VARS['block_id'];
			$iSelectedId	= (int)reset($aSelectedIds);
			$oTuitionBlock	= Ext_Thebing_School_Tuition_Block::getInstance($iSelectedId);
			$oTuitionClass	= $oTuitionBlock->getJoinedObject('class');

			$_VARS['id'] = array($oTuitionClass->id);
		}

		//Verfügbare Raum und Lehrer Dropdowns aktualisieren
		if($_VARS['task'] == 'reloadAvailable') {

			$aTransfer = parent::_switchAjaxRequest($_VARS);
			$aTransfer['action'] = 'reloadAvailable';

			$aBlocks	= $_VARS['save']['blocks'];
			$iWeek		= $_VARS['filter']['week_filter'];
			
			$aBlockTeacherAvailability = [];
			foreach((array)$aBlocks as $iBlockKey => $aBlockData) {

				$iBlockId		= $aBlockData['block_id'];
				$oBlock			= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
				$oBlock->school_id = $oSchool->id;

				$iTemplateId	= $aBlockData['template'];
				
				if(empty($aBlockData['from']) || empty($aBlockData['until'])) {
					$oTemplate = Ext_Thebing_Tuition_Template::getInstance($iTemplateId);
					$sFrom = $oTemplate->from;
					$sUntil = $oTemplate->until;
				} else {
					//individuelle Vorlage, Daten aus $_VARS übergeben
					$sFrom	= $aBlockData['from'];
					$sUntil = $aBlockData['until'];
				}

				$aDays = $aBlockData['days'];

				//Klassenkurse in dieser Woche nicht filtern, um den User die schnelle Änderung innerhalb der Klasse zu erleichtern
				//siehe @todo
				$aAvailableRooms = $oBlock->getAvailableRooms($iWeek, true, $sFrom, $sUntil, $aDays);
				// Add virtual room
				$aAvailableRooms[-1] = $this->t('Virtueller Raum');

				$aAvailableTeachers = $oBlock->getAvailableTeachers($iWeek, true, $sFrom, $sUntil, $aDays);
				$aBlockTeacherAvailability[$oBlock->id] = $aAvailableTeachers;

				//@todo:erneut filtern für aktuelle Änderungen

				//Daten für die Dropdowns
				if(!empty($aAvailableRooms)) {
					$aTransfer['rooms'][]	= array(
						'keys'		=> array_keys($aAvailableRooms),
						'values'	=> array_values($aAvailableRooms),
						#'selected'	=> $oBlock->room_id,
						'selected'	=> $aBlockData['rooms'],
					);
				}
				if(!empty($aAvailableTeachers)) {
					$aTransfer['teachers'][]	= array(
						'keys'		=> array_keys($aAvailableTeachers),
						'values'	=> array_values($aAvailableTeachers),
						#'selected'	=> $oBlock->teacher_id,
						'selected'	=>  $aBlockData['teacher_id'],
					);
				}
				$aTransfer['blocks'][]	= (string)$iBlockKey;
			}

            $this->saveEditDialogData((array)$_VARS['id'], $_VARS['save'], false);
			
            $aCheckResult = $this->oWDBasic->checkLevel($aBlockTeacherAvailability);
			
			$aSelectedIds	= (array)$_VARS['id'];
			$iSelectedId	= (int)reset($aSelectedIds);
			$aTransfer['data']['id'] = $_VARS['dialog_id'];
			$aTransfer['data']['level_check'] = $aCheckResult;

			echo json_encode($aTransfer);
			$this->_oGui->save();
			#die();
		}
		//Daten in die Folgewochen kopieren
		elseif('copyBlockChanges'==$_VARS['task'])
		{
			DB::begin('copy_block_changes');

			$aSelectedIds		= (array)$_VARS['id'];
			$iSelectedId		= (int)reset($aSelectedIds);
			$oTuitionClass		= Ext_Thebing_Tuition_Class::getInstance($iSelectedId);

			// Warnung mit Checkbox hier immer nachstellen #6236
			// Zu diesem Task hier kommt man ohnehin nur, wenn kein Fehler aufgetreten ist
			// Ohne das hier würden Änderungen nicht in die Folgewochen kopiert werden, trotz bestätiger Warnung
			$oTuitionClass->ignore_errors = 1;

			$iParamFilterWeek	= $_VARS['filter']['week_filter'];
			$aBlocks			= $_VARS['save']['blocks'];
			$aChangedFields		= $_VARS['changed_fields'] ?? [];

			foreach($aBlocks as $iKey => $aBlockData)
			{
				$iBlockId		= $aBlockData['block_id'];
				$oTuitionBlock	= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
				$aBlocks[$iKey]['inquiries_courses'] = $oTuitionBlock->getInquiriesCourses();
			}
			
			$mReturn = $oTuitionClass->copyDataFromWeek($iParamFilterWeek, $aBlocks, $aChangedFields);

			$aTransfer = parent::_switchAjaxRequest($_VARS);

			$this->oWDBasic		= $oTuitionClass;

			if(is_array($mReturn))
			{
				$aTransfer['action']	= 'saveDialogCallback';
				$aTransfer['dialog_id'] = 'ID_'.$iSelectedId;
				
				$aErrors				= array(
					0 => $this->getTranslation('error_dialog_title'),
				);
				
				foreach($mReturn as $sErrorKey => $mError)
				{
					$aError = (array)$mError;
					
					foreach($aError as $sError)
					{
						$sErrorMessage = $this->_getErrorMessage($sError, $sErrorKey);
						
						$aErrors[] = array(
							'input' => array(
								'dbcolumn' => $sErrorKey
							),
							'message' => $sErrorMessage,
						);
					}
				}
				
				$aTransfer['error']		= $aErrors;
				
				DB::rollback('copy_block_changes');
				
			} else {
				
				$aTransfer['action']	= 'showSuccess';
				$aTransfer['dialog_id'] = 'ID_'.$iSelectedId;
				$aTransfer['success_title']	= $this->t('Die Daten wurden erfolgreich in die Folgewochen kopiert.');
				$aTransfer['message'] = [];

				//Hinweise zu Folgewochen
				if(0<count($oTuitionClass->aAlerts)) {

					$aAlerts = $oTuitionClass->aAlerts;

					foreach($aAlerts as $sKey => $sAlert) {
						$sMessage				= $this->_getErrorMessage($sAlert, $sKey);
						$aTransfer['message'][] = $sMessage;
					}

				}
				
				DB::commit('copy_block_changes');
			}

			echo json_encode($aTransfer);
			$this->_oGui->save();

		} elseif($_VARS['task']=='saveDialog' && $_VARS['action']=='copy') {
			
			$sTransfer = $this->saveCopyDialogData($_VARS['save']);

			echo json_encode($sTransfer);
			$this->_oGui->save();

		} elseif($_VARS['task']=='loadTable') {
			
			$aTransfer	= parent::_switchAjaxRequest($_VARS);
			$aWeek		= Ext_Thebing_Util::getWeekTimestamps();
			$aTransfer['data']['default_week'] = $aWeek['start'];
			echo json_encode($aTransfer);
			$this->_oGui->save();
			
		} elseif($_VARS['task']=='checkLevel') {

			$this->saveEditDialogData((array)$_VARS['id'], $_VARS['save'], false);
			$aCheckResult = $this->oWDBasic->checkLevel();

			$iSelectedId = $this->_getFirstSelectedId();

			$aTransfer['action'] = 'showLevelCheck';
			$aTransfer['data']['level_check'] = $aCheckResult;
			$aTransfer['data']['id'] = 'ID_' . $iSelectedId;
			echo json_encode($aTransfer);

		} elseif (
			$_VARS['task'] === 'requestAsUrl' &&
			$_VARS['action'] === 'report_class'
		) {

			$classIds = (array)$_VARS['id'];
			$service = new \TsTuition\Generator\ClassAttendanceReport($classIds, $this->t(...));
			$service->render();

		} elseif (
			$_VARS['task'] === 'request' &&
			$_VARS['action'] === 'confirm_class'
		) {
			$transfer = [];
			$classIds = (array)$_VARS['id'];

			$class = Ext_Thebing_Tuition_Class::getInstance(reset($classIds));

			if (
				$class->exist() &&
				!$class->isConfirmed()
			) {
				$class->confirm();
			} else {
				$saveResult = [
					'' => ['CLASS_ALREADY_CONFIRMED']
				];
			}
			$this->oWDBasic = $class;
			if (is_array($saveResult))
			{
				$errors = [];
				foreach ($saveResult as $errorKey => $error)
				{
					foreach ($error as $errorString)
					{
						$errors[] = $this->_getErrorMessage($errorString, $errorKey);
					}
				}
				$transfer['action']	= 'showError';
				$transfer['error'] = $errors;
			} else {
				$transfer['action']	= 'showSuccessAndReloadTable';
				$transfer['success'] = $this->t('Die Klasse wurde bestätigt.');
			}
			echo json_encode($transfer);
		} else {
			parent::switchAjaxRequest($_VARS);
		}
		
	}
	
	public function getTranslations($sL10NDescription)
	{
		$aData = parent::getTranslations($sL10NDescription);

		$aData['tuition_class_new_block']			= L10N::t('Neuer Block', $sL10NDescription);
		$aData['tuition_class_add_block']			= L10N::t('Block hinzufügen', $sL10NDescription);
		$aData['tuition_class_remove_block']		= L10N::t('Block löschen', $sL10NDescription);
		$aData['tuition_class_confirm_text']		= L10N::t('Wollen Sie die Änderungen für alle folgenden Wochen übernehmen?', $sL10NDescription);
		$aData['tuition_copy_week_success']			= L10N::t('Die Woche wurde erfolgreich kopiert.', $sL10NDescription);
		$aData['tuition_copy_week_error']			= L10N::t('Die Woche konnte nicht kopiert werden.', $sL10NDescription);
		$aData['tuition_template_time_missing'] = L10N::t('nicht verfügbare Zeit', $sL10NDescription);

		// Wird hier überschrieben. Da ein anderer Text gewünscht wurde
		$aData['delete_question']					= L10N::t('Möchten Sie diese Klassen für alle Wochen endültig löschen? Falls ja, wird diese Klasse für alle Wochen gelöscht. Falls diese Klasse nur für eine Woche oder ab sofort nicht mehr verfügbar sein soll, dann kürzen Sie bitte die Dauer des Kurses entsprechend ab..', $sL10NDescription);

		return $aData;
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		global $_VARS;

		$transfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
				
		if($sAction == 'daily_comments') {
				
			$persister = WDBasic_Persister::getInstance();
			
			$attendanceRepository = \Ext_Thebing_Tuition_Attendance::getRepository();

			$blockId = (int)$this->request->input('block_id');
			$absenceReasons = (array)$this->request->input('absence_reason');

			$block = Ext_Thebing_School_Tuition_Block::getInstance($blockId);

			$class = $block->getClass();
			$blocks = $class->getBlocks($block->week);

			$changedState = [];
			$warning = false;

			$attendanceObjectCache = [];
			
			$coursesWithAutoExtension = \Ext_Thebing_Tuition_Course::query()
				->where('catch_up_on_cancelled_lessons', 1)
				->pluck('id');

			foreach($blocks as $block) {
				$allocations = $block->getAllocations();
				foreach($block->days as $day) {
					if(!empty($aData[$block->id][$day])) {
						$unit = $block->getUnit($day);
						
						// Kommentar wurde geändert
						if($unit->comment != $aData[$block->id][$day]['comment']) {
							$unit->comment = $aData[$block->id][$day]['comment'];						
							$persister->attach($unit);
						}

						if (isset($aData[$block->id][$day]['state'])) {
							if ((bool)$aData[$block->id][$day]['state']) {
								if (!$unit->hasState(Unit::STATE_CANCELLED)) {
									$unit->addState(Unit::STATE_CANCELLED, $aData[$block->id][$day]['state_comment']);
									if ($coursesWithAutoExtension->intersect($class->courses)->isNotEmpty()) {
										$warning = true;
									}
								}
							} else if ($unit->hasState(Unit::STATE_CANCELLED)) {
								$unit->removeState(Unit::STATE_CANCELLED);
							}

							if ($unit->state != $unit->getOriginalData('state')) {
								$changedState[$block->id]['block'] = $block;
								$changedState[$block->id]['units'][] = $unit;
							}
						}
					}
					
					foreach($allocations as $allocation) {
						if(!empty($absenceReasons[$allocation->id][$day])) {
							/*
							 * Da die neu erstellten Attendance-Objekte nicht direkt gespeichert werden, können Sie auch nicht per Query ermittelt werden.
							 * Daher hier der lokale Cache.
							 */
							if(isset($attendanceObjectCache[$allocation->id])) {
								$attendance = $attendanceObjectCache[$allocation->id];
							} else {
								$attendance = $attendanceRepository->getOrCreateAttendanceObject($allocation);
								$attendanceObjectCache[$allocation->id] = $attendance;
							}
							$absenceReason = $attendance->absence_reasons??[];
							$absenceReason[$day] = (int)$absenceReasons[$allocation->id][$day];
							$attendance->absence_reasons = $absenceReason;
							$persister->attach($attendance);
						}
					}
					
				}
			}

			if (
				$warning &&
				(!isset($_VARS['ignore_errors']) || $_VARS['ignore_errors'] != 1)
			) {
				$transfer = [];
				$transfer['action'] = 'saveDialogCallback';
				$transfer['data']['action'] = 'daily_comments';
				$transfer['data']['show_skip_errors_checkbox'] = 1;
				$transfer['data']['id'] = $_VARS['dialog_id'];
				$transfer['error'] = [[
					'message' => $this->t('Sie sind dabei eine Unterrichtseinheit ausfallen zu lassen. Dadurch werden die Kursbuchungen der zugewiesenen Schüler gegebenenfalls verlängert.'),
					'type' => 'hint'
				]];
				return $transfer;
			}

			foreach($changedState as $blockArray) {
				foreach($blockArray['units'] as $unit) {
					$persister->attach($unit);
				}
			}
			$persister->save();

			foreach($changedState as $blockArray) {

				// TODO zu langsam? ->lazyUpdate()
				(new BlockCancellationService($blockArray['block']))->update();

				$cancelledUnits = array_filter($blockArray['units'], fn ($unit) => $unit->isCancelled());
				foreach ($cancelledUnits as $unit) {
					\TsTuition\Events\BlockCanceled::dispatch($unit, \TsTuition\Enums\ActionSource::SCHEDULER, \Access_Backend::getInstance()->getUser());
				}
			}

			$transfer = [];
			$transfer['action'] = 'saveDialogCallback';
			$transfer['error'] = [];
			$transfer['data'] = $this->prepareOpenDialog($sAction, $aSelectedIds, 0, $sAdditional, false);
		}
		
		return $transfer;
	}
	
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		if(
			is_array($sAction) &&
			!empty($sAction['additional'])
		) {

			$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		} else {

			$aSelectedIds	= (array)$aSelectedIds;
			$iSelectedId	= (int)reset($aSelectedIds);

			$iCurrentFilter	= $_VARS['filter']['week_filter'];
			$aBlocks		= $aSaveData['blocks'];
			$aErrors		= array();

			if($this->oWDBasic === null) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			// Damit man Räume entfernen kann, muss hier sichergestellt werden, dass der Wert immer übermittelt wird.
			array_walk($aBlocks, function(&$block){
				if(!isset($block['rooms'])) {
					$block['rooms'] = [];
				}
			});
			
			$this->oWDBasic->setCurrentWeek($iCurrentFilter);
			$this->oWDBasic->current_level = $aSaveData['current_level'];
			$this->oWDBasic->setSaveBlocks($aBlocks);

			if($_VARS['ignore_errors']==1) {
				$aSaveData['ignore_errors']['ktcl'] = 1;
				// #5876 - Wenn die Checkbox aktiviert wurde muss die Variable auf false gesetzt werden, damit der Tab "Niveauänderungen"
				// angezeigt wird (s. Ext_Thebing_Tuition_Class::save())
				$this->oWDBasic->bCanIgnoreErrors = false;
			}

			if(
				isset($_VARS['save']) &&
				isset($_VARS['save']['level_black_list'])
			) {
				$this->oWDBasic->aLevelBlackList = (array)$_VARS['save']['level_black_list'];
			}

			$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction);

			$aErrors = $aTransfer['error'];

			if(empty($aErrors) && $this->oWDBasic->isChanged()) {
				//Fragen ob aktuelle Änderungen übernommen werden sollen
				$aTransfer['return_confirm'] = 1;
				if (!empty($aChangedFields = $this->oWDBasic->getChangedFields())) {
					$aTransfer['changed_fields'] = array_values(
						array_unique(\Illuminate\Support\Arr::flatten($aChangedFields))
					);
				}
			}

			if(!empty($aErrors) && $this->oWDBasic->bCanIgnoreErrors) {
				//"Fehler beim Speichern" Meldung entfernen
				unset($aErrors[0]);
				$aHints = array();
				$iCounter = 0;

				foreach($aErrors as $aErrorData) {
					$aHints[$iCounter] = $aErrorData;
					$aHints[$iCounter]['type'] = 'hint';
					$iCounter++;
				}

				//Checkbox Fehler ignorieren einblenden
				$aTransfer['error'] = $aHints;

				if($this->oWDBasic->bShowSkipErrors) {
					$aTransfer['data']['show_skip_errors_checkbox'] = 1;
				}
			}

			$aAlerts = (array)$this->oWDBasic->aAlerts;
			if(count($aAlerts) > 0) {

				// Hinweise zu Folgewochen
				array_walk($aAlerts, function(&$sError, $sField) {
					$sError = $this->_getErrorMessage($sError, $sField, null);
				});

				$aTransfer['success_title'] = L10N::t('Erfolgreich gespeichert');
				$aTransfer['success_message'] = join('<br>', $aAlerts);

			}
		}

		return $aTransfer;
	}

	public function saveCopyDialogData($aSaveData)
	{
		global $_VARS;

		ini_set('memory_limit', '1G');
		
		DB::begin('copy_week');
		
		$iCheckDifferentLevels = true;
		
		if(isset($aSaveData['check_different_levels']))
		{
			$iCheckDifferentLevels = (int)$aSaveData['check_different_levels'];
			
			unset($aSaveData['check_different_levels']);
		}


		$aLevelBlackList = array();
		
		if(isset($aSaveData['level_black_list']))
		{
			$aLevelBlackList = (array)$aSaveData['level_black_list'];
		}

		$iCurrentWeek	= $_VARS['filter']['week_filter'];

		$oWdDate		= new WDDate($iCurrentWeek, WDDate::TIMESTAMP);

		if('replace_data'==$_VARS['additional'])
		{
			$oWdDate->sub(1,WDDate::WEEK);
			$iCurrentWeek = $oWdDate->get(WDDate::TIMESTAMP);
		}

		$oWdDate->add(1,WDDate::WEEK);
		$iNextWeek = $oWdDate->get(WDDate::TIMESTAMP);
		$dNextWeek	= $oWdDate->get(WDDate::DB_DATE);

		$aNextWeekSaveData	= array();
		$aNextWeekSaveData += (array)$aSaveData['both'];
		$aNextWeekSaveData += (array)$aSaveData['next'];

		$aErrors  = array(); // Dialog-Fehler
		$aAlerts = array(); // Vorbereitung für $aHints
		$aHints = array(); // Dialog-Warnungen

		$this->_resetBlocksData($aSaveData, ['teacher', 'rooms', 'description']);
		
		$aLevelChanges		= array();

		// Klassen, die in der nächsten Woche bereits Blöcke haben
		foreach($aNextWeekSaveData as $iClassId => $aClassData)
		{
			if(
				isset($aClassData['changes_to_next_week']) ||
				isset($aClassData['changes_to_all_weeks'])
			) {
				/** @var Ext_Thebing_Tuition_Class $oTuitionClass */
				$oTuitionClass = $this->_getWDBasicObject($iClassId);
				$oTuitionClass->current_level	= $aClassData['current_level'];
				$oTuitionClass->setCurrentWeek($iNextWeek);

				if($_VARS['ignore_errors']) {
					$oTuitionClass->ignore_errors = 1;
				}

				$oTuitionClass->weeks = $oTuitionClass->weeks;
				$oTuitionClass->level_increase = $oTuitionClass->level_increase;

				$sClassName		= $oTuitionClass->name;

				$aBlockSaveData = array();
				$iCounter = 0;

				foreach((array)$aClassData['blocks'] as $iBlockId => $aBlockData) {

					$aRoomIds = array_filter(explode(",", (string)$aBlockData['block_room_ids']));
					
					$aBlockSaveData[$iCounter] = array(
						'block_id'		=> $iBlockId,
						'rooms'		    => $aRoomIds,
						'teacher_id'	=> $aBlockData['block_teacher_id'],
						'description'	=> $aBlockData['block_description'],
					);
					
					#if(1==(int)$aClassData['changes_to_all_weeks']) {
//						$oTuitionBlock = Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
//						$aBlockSaveData[$iCounter]['inquiries_courses'] = $oTuitionBlock->getInquiriesCourses();
					#}

					$iCounter++;
				}

				$oTuitionClass->setSaveBlocks($aBlockSaveData);
				$oTuitionClass->aLevelBlackList = $aLevelBlackList;
				$oTuitionClass->check_different_levels = $iCheckDifferentLevels;
				$mSuccess = $oTuitionClass->save();

				if(is_array($mSuccess))
				{
					if(true===$oTuitionClass->bCanIgnoreErrors)
					{
						$oTuitionClass->ignore_errors = 1;
						$mSuccess = $oTuitionClass->save();
					}
					
					$aDifferentLevels = $oTuitionClass->aDifferentLevels;

					if(!empty($aDifferentLevels))
					{
						if(!isset($aHints['level_changes']))
						{
							$aHints['level_changes'] = array();
						}
						
						foreach($aDifferentLevels as $sWeek => $aDifferentLevelData)
						{
							if(!isset($aHints['level_changes'][$sWeek]))
							{
								$aHints['level_changes'][$sWeek] = array();
							}
							
							$aHints['level_changes'][$sWeek] = array_merge($aHints['level_changes'][$sWeek], $aDifferentLevelData);
						}
					}
				}

				if(true===$mSuccess || is_object($mSuccess))
				{
					if(1==(int)$aClassData['changes_to_all_weeks'])
					{
						$oTuitionClass->copyDataFromWeek($iNextWeek, $aBlockSaveData);
					}
				}
				else
				{
					$mSuccess = (array)$mSuccess;

					foreach($mSuccess as $sErrorPlaceHolder => $mError)
					{
						if($mError == 'DIFFERENT_LEVELS') {
							continue;
						}

						//Wird benutzt für die Fehler, da die Fehler im Objekt festgehalten werden
						$this->oWDBasic = $oTuitionClass;
						$mError = (array)$mError;

						foreach($mError as $sError) {
							$sErrorMessage = $this->_getErrorMessage('SAVE_CLASS_ERROR', $sErrorPlaceHolder, $sClassName);
							$sErrorMessage .= ' '.$this->_getErrorMessage($sError, $sErrorPlaceHolder);
							$aErrors[] = $sErrorMessage;
						}
					}
				}
			}
		}

		// Klassen, die in der nächsten Woche noch keine Blöcke haben
		if(isset($aSaveData['current']))
		{
			foreach((array)$aSaveData['current'] as $iClassId => $aClassData)
			{
				if(isset($aClassData['changes_to_next_week']))
				{
					$oTuitionClass = $this->_getWDBasicObject($iClassId);
					$oTuitionClass->current_level = $aClassData['current_level'];

					if($_VARS['ignore_errors']) {
						$oTuitionClass->ignore_errors = 1;
					}

					$sClassName		= $oTuitionClass->name;

					$aBlockSaveData = $oTuitionClass->prepareBlockSaveDataArray($aClassData['blocks']);

					try {
						// Beim Kopieren in die nächste Woche, bestehende Zuweisungen NICHT löschen
						[$mSuccessClone, $aCloneChangedFields] = $oTuitionClass->saveBlocksForWeek($aBlockSaveData, $iNextWeek, true, false, false);
					} catch (\TsTuition\Exception\SaveBlocksForWeekErrorException $e) {
						$mSuccessClone = $e->getErrors();
					}

					if(!is_array($mSuccessClone)) {

						$iWeeks = $oTuitionClass->weeks;
						$oTuitionClass->weeks = $iWeeks + 1;
						$oTuitionClass->save();

						$aSavedBlocks	= $oTuitionClass->getSavedBlocks();

						foreach($aSavedBlocks as $oBlock) {
							$sBlockWeek = $oBlock->week;
							
							if(isset($aLevelBlackList[$sBlockWeek])) {
								$aLevelBlackListByWeek = $aLevelBlackList[$sBlockWeek];
							} else {
								$aLevelBlackListByWeek = array();
							}
							
							$mReturn = $oBlock->saveProgressForAllocations($iCheckDifferentLevels, $aLevelBlackListByWeek);

							if(is_array($mReturn)) {
								
								if(!isset($aHints['level_changes'])) {
									$aHints['level_changes'] = array();
								}
								
								if(!isset($aHints['level_changes'][$sBlockWeek])) {
									$aHints['level_changes'][$sBlockWeek] = array();
								}
								
								$aHints['level_changes'][$sBlockWeek] = array_merge($aHints['level_changes'][$sBlockWeek], $mReturn);
							}
						}
						
					}
					else
					{
						foreach($mSuccessClone as $sErrorPlaceHolder => $mError)
						{
							//Wird benutzt für die Fehler, da die Fehler im Objekt festgehalten werden
							$this->oWDBasic = $oTuitionClass;
							$mError = (array)$mError;
							
							foreach($mError as $sError) {

								// Fehlermeldung muss bereits hier geholt werden, da $oTuitionClass intern Platzhalter hat
								$sErrorMessage = ' '.$this->_getErrorMessage($sError, $sErrorPlaceHolder);

								if(
									$oTuitionClass->bCanIgnoreErrors &&
									in_array($sError, $oTuitionClass->aAlerts)
								) {
									// Warnungen sammeln, da $oTuitionClass immer wieder überschrieben wird
									$aAlerts[$sErrorPlaceHolder] = $sErrorMessage;
								} else {
									// Nur als Fehler betrachten, wenn dieser nicht ignoriert werden kann
									$sErrorMessage = $this->_getErrorMessage('SAVE_CLASS_ERROR', $sErrorPlaceHolder, $sClassName).$sErrorMessage;

									$aErrors[] = $sErrorMessage;
								}
							}

						}
					}
				}
			}
		}

		$aTransfer	= array();

		// Gesammelte Warnungen in Dialog-Hinweise konvertieren
		if(
			!empty($aAlerts) &&
			$_VARS['ignore_errors'] != 1
		) {
			foreach($aAlerts as $sKey => $sAlert) {
				//$sMessage = $this->_getErrorMessage($sAlert, $sKey);
				$aHints[] = array('message' => $sAlert, 'type' => 'hint');
			}
		}
		
		if(
			empty($aErrors) &&
			empty($aHints)
		) {
			DB::commit('copy_week');
			
			$aTransfer['data']['id']							= 'ID_0';
	
			if('replace_data'!=$_VARS['additional']) {
				
				$aTransfer['data']['action']						= 'copyCallback';
				$aTransfer['data']['options']['close_after_save']	= true;
			} else {
				$aTransfer['data']['action']						= 'replaceDataCallback';
				$aTransfer['success_message'] = $this->t('Vertauschen von Raum und Lehrern erfolgreich gespeichert.');
				$aTransfer['data']['id']							= 'ID_xXx'; // Fake ID muss vorhanden sein um Fehler anzeigen zu können
				$aTransfer['data']['options']['close_after_save']	= true;
			}

			$aTransfer['action']			= 'saveDialogCallback';
			$aTransfer['error']				= array();

		} else {

			DB::rollback('copy_week');

			// Bei "Räume und Lehrer ändern" muss die spezielle Dialog-ID übergeben werden
			if($_VARS['additional'] === 'replace_data') {
				$aTransfer['data']['id']	= 'ID_xXx';
			} else {
				$aTransfer['data']['id']	= 'ID_0';
			}

			$aErrorsAll = array();
			$iShowSkipErrors = 0;

			// Bei nur Hinweisen die Checkbox anzeigen
			if(
				empty($aErrors) &&
				!empty($aHints)
			) {
				$iShowSkipErrors = 1;
			}

			if(!empty($aErrors)) {
				// Fehler beim speichern nur hinzufügen, falls Fehler vorhanden
				$aErrorsAll = array($this->t('Fehler beim Speichern'));
				$aErrorsAll = array_merge($aErrorsAll, $aErrors);
			}
			
			if(
				empty($aErrorsAll) &&
				isset($aHints['level_changes'])
			) {
				// Die Leveländerungen nur anzeiegen wenn keine "richtigen" Fehler vorhanden sind
				
				$aFormattedLevelChanges = $this->_getDateFormatHeader($aHints['level_changes']);
				
				// Leveländerungen Tab-HTML generieren
				$sHtmlLevelChanges = $this->_getDifferentLevelTableHTml($aFormattedLevelChanges, $aTransfer['data']['id'], 'block_week', false);

				$aTransfer['data']['different_level_html']	= $sHtmlLevelChanges;

				// im Fehler-Array werden die Informationen nicht benötigt, sie dienen nur um die HTML-Tabelle anzuzeigen
				unset($aHints['level_changes']);
				
				if(empty($aHints)) {
					// Wenn keine anderen Warnungen vorhanden sind, dann auch keine Checkbox anzeigen
					$iShowSkipErrors = 0;
				} else {
					$iShowSkipErrors = 1;
				}

				// Warnung hinzufügen
				$aHints[] = array(
					'message'	=> $this->_getErrorMessage('DIFFERENT_LEVELS'),
					'type'		=> 'hint',
				);
				
				$aTransfer['data']['show_skip_errors_checkbox'] = $iShowSkipErrors;
			}

			// Alle Fehler & Warnungen zusammenfügen
			$aErrorsAll = array_merge($aErrorsAll, $aHints);

			$aTransfer['data']['show_skip_errors_checkbox'] = $iShowSkipErrors;
			$aTransfer['error']	= $aErrorsAll;
			$aTransfer['action'] = 'saveDialogCallback';
			
		}

		return $aTransfer;

	}

	public function prepareDialogData() {

		$oSchool				= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId				= $oSchool->id;

		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		
		$aColors	= Ext_Thebing_Tuition_Color::getColorsForSchool($iSchoolId, true);
		$aColors	= Ext_Thebing_Util::addEmptyItem($aColors);
		$this->_aColors = $aColors;
		
		$aCurrentWeekData		= Ext_Thebing_Util::getWeekTimestamps();
		$iCurrentWeekStart		= $aCurrentWeekData['start'];
		$this->_iCurrentWeekStart = $iCurrentWeekStart;
		
		$oCourseList = new Ext_Thebing_Tuition_Course_List();
		$oCourseList->bForSelect	= true;
		$oCourseList->sLanguage		= $sInterfaceLanguage;
		$oCourseList->iSchoolId		= $oSchool->id;
		$oCourseList->bShort		= false;
		$oCourseList->iCombinedCourses = 0;
		$oCourseList->bOnlyForCombinationCourses = null;

		$this->_aCourses = $oCourseList->getList();
		
		// Kurse in Kurzform
		$oCourseList->bShort = true;
		$this->_aCoursesShort = $oCourseList->getList();

		$aLevels				= $oSchool->getLevelList(true,$sInterfaceLanguage,1);
		$this->_aLevels			= $aLevels;

		$this->_aWeeks = Ext_Thebing_Util::getWeekOptions(null, 3, $oSchool->course_startday);

	}

	public function getWeeks() {
		return $this->_aWeeks;
	}

	public function getCurrentWeekStart() {
		return $this->_iCurrentWeekStart;
	}

	private function setLevelIncreaseField(Ext_Gui2_Dialog &$oDialog, &$oTab) {

		$oDiv = $oDialog->create('div');

		$oInput = $oDialog->createSaveField('input', array(
			'db_column'			=> 'level_increase',
			'db_alias'			=> 'ktcl',
			'required'			=> 0,
			'style'				=> 'width:50px;display: inline-block;',
			'label'				=> 'Levelerhöhung',//damit das Label im Dialog festgehalten, für die Fehlermeldungen
		));

		$sInputHtml = $oInput->generateHTML();

		$sText = $this->t('alle %s Wochen');

		$sText = str_replace('%s', $sInputHtml, $sText);

		$oDiv->setElement($sText);

		$oTab->setElement($oDialog->createRow($this->t('Levelerhöhung'), $oDiv));

	}

	public function getDialogDailyComments() {
		
		$oDialog = $this->_oGui->createDialog($this->t('Tägliche Kommentare von Klasse "{name}"'));
		$oDialog->bSmallLabels = true;

		return $oDialog;
	}
	
	public function getDialogEdit() {
		return $this->getDialog(false);
	}
	
	public function getDialogNew() {
		return $this->getDialog(true);
	}
	
	public function getDialog($bNew=false) {

		$oDialog = $this->_oGui->createDialog($this->t('Klasse "{name}" editieren'),$this->t('Neue Klasse anlegen'));
		$oDialog->width = 1300;
		$oDialog->bSmallLabels = true;

		$oTab = $oDialog->createTab($this->t('Daten'));

		$oTab->setElement($oDialog->createSaveField('hidden', array(
			'db_column' => 'ignore_errors',
			'db_alias'	=> 'ktcl',
		)));
		
		$oDivContainer = new Ext_Gui2_Html_Div;
		$oDivContainer->class = 'dialog_columns_container clearfix row';
		
		$oDivContainerLeft = new Ext_Gui2_Html_Div;
		$oDivContainerLeft->class = '';
		$oBoxLeft = new Ext_Gui2_Dialog_Box();
		$oDivContainerLeft->setElement($oBoxLeft);
		$oDivContainer->setElement($oDivContainerLeft);
		
		$oDivContainerRight = new Ext_Gui2_Html_Div;
		$oDivContainerLeft->class = '';
		$oBoxRight = new Ext_Gui2_Dialog_Box();
		$oDivContainerRight->setElement($oBoxRight);
		$oDivContainer->setElement($oDivContainerRight);
		
		$oBoxLeft->setElement($oDialog->createRow($this->t('Name'), 'input', array(
			'db_column'			=> 'name',
			'db_alias'			=> 'ktcl',
			'required'			=> 1,
		)));

		$oBoxLeft->setElement($oDialog->createRow($this->t('Farbe'), 'select', array(
			'db_column'			=> 'color_id',
			'db_alias'			=> 'ktcl',
			'required'			=> 0,
			'select_options'	=> $this->_aColors,
		)));

		if($bNew) {
		
			$oBoxLeft->setElement($oDialog->createSaveField('hidden', array(
				'db_column'			=> 'start_week',
				'db_alias'			=> 'ktcl',
				'required'			=> 1,
				'value'				=> $this->_iCurrentWeekStart,
			)));

			$oBoxLeft->setElement($oDialog->createRow($this->t('Wochen'), 'input', array(
				'db_column'			=> 'weeks',
				'db_alias'			=> 'ktcl',
				'required'			=> 1,
			)));

		} else {
				
			$oBoxLeft->setElement($oDialog->createRow($this->t('Startwoche'), 'select', array(
				  'db_column'			=> 'start_week',
				  'db_alias'			=> 'ktcl',
				  'required'			=> 1,
				  'select_options'	=> $this->_aWeeks,
				  'readonly'			=> 'readonly',
				  'skip_value_handling' => true // Kann eh nicht verändert werden und als Wert wird ein Timestamp benötigt
			  )));

			  // Anzahl Wochen bei edit anders aufgebaut, Inhalt für current_week wird in editDialogData gesetzt
			  $oDiv = $oDialog->create('div');
			  $oInput = $oDialog->createSaveField('input', array(
				  'db_column' => 'current_week',
				  'db_alias'	=> 'ktcl',
				  'style'		=> 'width:50px;display: inline-block;',
				  #'readonly'	=> true,
			  ));
			  $oInput->bReadOnly = true;

			  $oDiv->setElement($oInput);
			  $oDiv->setElement('<span> / </span>');
			  $oInput = $oDialog->createSaveField('input', array(
				  'db_column' => 'weeks',
				  'db_alias'	=> 'ktcl',
				  'style'		=> 'width:50px;display: inline-block;',
				  'required'	=> 1,
			  ));
			  $oDiv->setElement($oInput);
			  $oBoxLeft->setElement($oDialog->createRow($this->t('Wochen'), $oDiv));
			  // Anzahl Wochen Ende

		}

		$oBoxLeft->setElement($oDialog->createRow($this->t('Interner Kommentar'), 'textarea', array(
			'db_column'			=> 'internal_comment',
			'db_alias'			=> 'ktcl',
			'class' => 'autoheight'
		)));

		$oBoxLeft->setElement($oDialog->createRow($this->t('Online buchbar'), 'select', [
			'db_column' => 'online_bookable_as_course',
			'selection' => new \TsTuition\Gui2\Selection\Class\BookableCourses(),
			'format' => new Ext_Gui2_View_Format_Null(),
			'dependency' => [
				[
					'db_alias' => 'ktcl',
					'db_column' => 'courses'
				]
			],
			'child_visibility' => [
				[
					'db_column' => 'bookable_only_in_full',
					'on_values' => array_keys($this->_aCourses)
				]
			]
		]));

		$oBoxLeft->setElement($oDialog->createRow($this->t('Nur als Ganzes buchbar'), 'checkbox', [
			'db_column'	=> 'bookable_only_in_full'
		]));

		if(AppService::hasApp(\TsTeacherLogin\Handler\ExternalApp::APP_NAME)) {
			$oBoxLeft->setElement($oDialog->createRow($this->t('Lehrer kann Schüler hinzufügen'), 'checkbox', array(
				'db_column'			=> 'teacher_can_add_students',
				'db_alias'			=> 'ktcl'
			)));
		}
		
		/** Levelerhöhung Tab */
		$this->setLevelIncreaseField($oDialog, $oBoxRight);

		$courseCategories = Ext_Thebing_School::getSchoolFromSession()->getCourseCategoriesList('select', $this->_oGui->getLanguageObject()->getLanguage());

		$optionsSearchByData = [];
		$courses = Ext_Thebing_Tuition_Course::query()->get()->whereIn('id', array_keys($this->_aCourses));
		foreach ($courses as $course) {
			$optionsSearchByData[$course->getId()]['search'] = strtolower($course->getCategory()->getName());
		}

		$oBoxRight->setElement($oDialog->createRow($this->t('Kurse'), 'select', array(
			'db_column'	=> 'courses',
			'db_alias' => 'ktcl',
			'required' => 1,
			'select_options' => $this->_aCourses,
			'select_options_data' => $optionsSearchByData,
			'data-keep-data' => 1,
			'multiple' => 5,
			'jquery_multiple' => 1,
			'style' => 'height: 80px;width:100%;',
			'searchable' => 1,
			// TODO Kann man diesen zusätzlichen Request nicht mit update_select_options kombinieren?
			'events' => array(
				  array(
				   'function'=>'checkLevel',
				   'event'=>'change'
				  )
			),
			'selection' => new Ext_Thebing_Gui2_Selection_ArrayList('Ext_Thebing_Tuition_Course'),
		)));

		$oBoxRight->setElement($oDialog->createRow($this->t('Unterrichtsdauer'), 'select', array(
			'db_column'	=> 'lesson_duration',
			'db_alias' => 'ktcl',
			'selection' => new TsTuition\Gui2\Selection\LessonDuration,
			'required' => true,
			'dependency' => [
				[
					'db_alias' => 'ktcl',
					'db_column' => 'courses',
				],
			],
		)));

		$oBoxRight->setElement($oDialog->createRow($this->t('Kurssprache'), 'select', array(
			'db_column'			=> 'courselanguage_id',
			'db_alias'			=> 'ktcl',
			'selection' => new TsTuition\Gui2\Selection\CourseLanguage,
			'dependency' => [
				[
					'db_alias' => 'ktcl', 
					'db_column' => 'courses',
				],
			],
		)));
		
		$oTab->setElement($oDivContainer);
		
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($this->t('Wochenbezogen'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($this->t('Level'), 'select',array(
			'db_column'			=> 'current_level',
			'select_options'	=> $this->_aLevels,
			'events' => array(
				  array(
				   'function'=>'checkLevel',
				   'event'=>'change'
				  )
			)
		)));
		
		$oDialog->setElement($oTab);
		
		$oTabLevelChange = $oDialog->createTab($this->t('Leveländerungen'));
		$oTabLevelChange->class = 'level_change_tab';
		
		$oTabLevelChange->setElement($oDialog->createSaveField('hidden', array(
			'db_column' => 'check_different_levels',
			'db_alias'	=> 'ktcl',
		)));
		
		$oDialog->setElement($oTabLevelChange);

		return $oDialog;
	}

	public function getDialogCopy($bForReplace=false) {
		global $_VARS;

		if($bForReplace) {
			$oDialogCopy = $this->_oGui->createDialog($this->t('Räume und Lehrer ändern'),$this->t('Räume und Lehrer ändern'));
		} else {
			$oDialogCopy = $this->_oGui->createDialog($this->t('Kopieren von Klassen'),$this->t('Kopieren von Klassen'));
		}
		
		//$oDialogCopy->width		= 900;
		//$oDialogCopy->height	= 650;

		$aHeader				= array(
			$this->t('Klasse'),
			$this->t('Laufzeit'),
			$this->t('Level'),
			$this->t('Klassenzimmer'),
			$this->t('Block'),
			$this->t('Lehrer'),
			$this->t('Inhalt'),
		);
		
		$oTab = $oDialogCopy->createTab($this->t('Klassen'));

		$oTable = new Ext_Gui2_Html_Table();
		$oTable->class = 'table guiTableHead copyDialogTable';
		$oTable->style	= 'width:100%;';

		$sHtmlColGroup	= $this->_getColGroupsHtmlCopy($bForReplace);
		$oTable->setElement($sHtmlColGroup);

		$oTr = new Ext_Gui2_Html_Table_tr();

		$sThStyle = 'border: 1px solid #ccc;';
		if(!$bForReplace) {
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->style = $sThStyle.' text-align: center;';
			$oInput = new Ext_Gui2_Html_Input();
			$oInput->type = 'checkbox';
			$oInput->title = $this->t('Einstellungen nur für nächste Woche übernehmen');
			$oInput->class = 'check_all';
			$oInput->setDataAttribute('type', 'changes_to_next_week');
			$oTh->setElement($oInput);
			$oTr->setElement($oTh);

			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->style = $sThStyle.' text-align: center;';
			$oInput = new Ext_Gui2_Html_Input();
			$oInput->type = 'checkbox';
			$oInput->title = $this->t('Einstellungen für alle Folgewochen übernehmen');
			$oInput->class = 'check_all';
			$oInput->setDataAttribute('type', 'changes_to_all_weeks');
			$oTh->setElement($oInput);
			$oTr->setElement($oTh);
		}

		foreach($aHeader as $iKey => $sHeaderTitle) {
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->title = $sHeaderTitle;
			$oTh->style = $sThStyle;
			// Für die 'Blocktage' unter 'Block' Table-Head eine zweite Spalte anpassen.
			if($iKey === 4) {
				$oTh->colspan = 2;
			}
			$oTh->setElement($sHeaderTitle);
			$oTr->setElement($oTh);
		}

		$oTable->setElement($oTr);

		$oTab->setElement($oTable);

		$oDialogCopy->setElement($oTab);

		$oTabLevelChanges = $oDialogCopy->createTab($this->t('Leveländerungen'));
		$oTabLevelChanges->class = 'level_change_tab';

		$oTabLevelChanges->setElement($oDialogCopy->createSaveField('hidden', array(
			'db_column' => 'check_different_levels',
			'value'		=> '1',
		)));

		$oDialogCopy->setElement($oTabLevelChanges);

		return $oDialogCopy;
	}

	protected function _getColGroupsHtmlCopy($bForReplace=false) {

//		$aWidths = array(
//			55,
//			55,
//			Ext_Thebing_Util::getTableColumnWidth('name'),
//			55,
//			60,
//			Ext_Thebing_Util::getTableColumnWidth('name'),
//			'auto',
//			Ext_Thebing_Util::getTableColumnWidth('name')
//		);

		$aWidths = [
			3, // einmalig
			3, // alle
			15, // Klasse
			5, // Dauer
			10, // Level
			10, // Raum
			'auto', //25, // Block
			10, // Tage
			10, // Lehrer
			20 // Inhalt
		];

		if($bForReplace) {
			unset($aWidths[0],$aWidths[1]);
		}

		$sHtmlColGroup = $this->_getColGroupsHtml($aWidths);

		return $sHtmlColGroup;
	}
	
	protected function _getColGroupsHtml($aWidths)
	{
		$sHtmlColGroup	= '<colgroup>';
		
		foreach($aWidths as $mWidth)
		{
			if(is_numeric($mWidth)) {
				$sWidth = $mWidth.'%';
			} else {
				$sWidth = $mWidth;
			}
			$sHtmlColGroup .= '<col style="min-width: '.$sWidth.'; width: '.$sWidth.';">';
		}
		$sHtmlColGroup .= '</colgroup>';

		return $sHtmlColGroup;
	}

	public function getCopyDialogHtml($sAdditional)
	{
		global $_VARS;

		$sHtml = '';

		$iWeekFilter	= $_VARS['filter']['week_filter'];
		if('replace_data'==$sAdditional)
		{
			$oWdDate = new WDDate($iWeekFilter, WDDate::TIMESTAMP);
			$oWdDate->sub(1, WDDate::WEEK);
			$iWeekFilter = $oWdDate->get(WDDate::TIMESTAMP);
		}

		$oSchool		= Ext_Thebing_School::getSchoolFromSession();

		$aRooms = $oSchool->getClassRooms(true);
		$aRooms = Ext_Thebing_Util::addEmptyItem($aRooms, Ext_Thebing_L10N::getEmptySelectLabel('room'));
		// Add virtual room
		$aRooms[-1] = $this->t('Virtueller Raum');

		$aTeachers		= $oSchool->getTeachersList($iWeekFilter,true);
		$aTeachers		= Ext_Thebing_Util::addEmptyItem($aTeachers, Ext_Thebing_L10N::getEmptySelectLabel('teacher'));

		$this->_aRooms	= $aRooms;
		$this->_aTeachers = $aTeachers;

		$sInterfaceLanguage		= $oSchool->getInterfaceLanguage();
		$aLevels				= $oSchool->getLevelList(true,$sInterfaceLanguage,1,true,true);
		$this->_aLevels			= $aLevels;
		
		$oTuitionClass	= new Ext_Thebing_Tuition_Class();
		
		if(!empty($_VARS['save']))
		{
			$aCopyData			= $_VARS['save'];
			$aBlocksNextWeek	= array();

			if(isset($aCopyData['check_different_levels']))
			{
				unset($aCopyData['check_different_levels']);
			}

			foreach($aCopyData as $sType => $aGroupedBlocksByType)
			{
				foreach($aGroupedBlocksByType as $iClassId => $aClassData)
				{
					foreach($aClassData['blocks'] as $iBlockId => $aBlockData)
					{
						$aBlocksNextWeek[$iBlockId] = $aBlockData;
					}
				}
			}
		}
		else
		{
			$aCopyData			= $oTuitionClass->getCopyWeekData($iWeekFilter);
			$aBlocksNextWeek	= $aCopyData['blocks_next_week'];
		}

		$this->_aBlocksNextWeek = $aBlocksNextWeek;
		
		$oTable			= new Ext_Gui2_Html_Table();
		$oTable->class	= 'table guiTableBody copyDialogTable';
		$oTable->id		= 'guiTableBody_'.$this->_oGui->hash;
		$oTable->style	= 'width:100%;';
		
		$sHtmlColGroup	= $this->_getColGroupsHtmlCopy($sAdditional);
		$oTable->setElement($sHtmlColGroup);

		$aTypeTitles = array(
			'both'		=> $this->t('Vorhandene Klassen'),
			'next'		=> $this->t('Bereits geplante Klassen'),
			'current'	=> $this->t('noch nicht vorhandene Klassen'),
		);
		if(isset($aCopyData['blocks_next_week']))
		{
			unset($aCopyData['blocks_next_week']);
		}
		if('replace_data'==$sAdditional && !empty($aCopyData['current']))
		{
			unset($aCopyData['current']);
		}

		foreach($aCopyData as $sType => $aGroupedBlocksByType) {

			if($sAdditional !== 'replace_data') {

				$oTr = new Ext_Gui2_Html_Table_tr();
				$oTr->style = 'border: 1px solid #CCC; cursor:pointer; background-color: #f0f0f0;';
				$oTr->id = 'tr_'.$sType; // tr_next tr_current tr_both

				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->colspan = 10;
				$oTd->class = 'guiBodyColumn';
				$oTd->style = 'border: 1px solid #ccc; font-weight: bold; padding: 1px;';
				$oTd->setElement('<p style="margin: 4px;"><i class="fa fa-minus toggle-icon"></i>&nbsp; '.$aTypeTitles[$sType].'</p>');
				$oTr->setElement($oTd);
				$oTable->setElement($oTr);

			}

			foreach($aGroupedBlocksByType as $iClassId => $aClassData)
			{
				if(isset($_VARS['save']))
				{
					$oTr = $this->_getTableColumns($sType, $aClassData, $iClassId, $sAdditional, false);
				}
				else
				{
					$oTr = $this->_getTableColumns($sType, $aClassData ,$iClassId, $sAdditional);
				}

				$oTable->setElement($oTr);
			}
		}

		$sHtml .= $oTable->generateHtml();

		return $sHtml;
	}

	/**
	 * Liefert ein TD für den "Kopieren von Klassen Dialog"
	 * @return Ext_Gui2_Html_Table_Tr_Td 
	 */
	protected function _getTd(){
		
		$sTdStyle = 'border: 1px solid #ccc;';
		
		$oTd	= new Ext_Gui2_Html_Table_Tr_Td();
		$oTd->class = 'guiBodyColumn';
		$oTd->style = $sTdStyle;
		return $oTd;
	}
	
	protected function _getTableColumns($sType,$aClassData,$iClassId,$sAdditional=false, $bFirstCall=true) {

		$sInterfaceLanguage = \Ext_Thebing_School::getSchoolFromSession()->getInterfaceLanguage();

		$oTr			= new Ext_Gui2_Html_Table_tr();
		$oTr->class		= 'tr_'.$sType.'_toggle';
		$oTr->setDataAttribute('container', 'tr_'.$sType);

		if('replace_data'!=$sAdditional)
		{
			$oTd = $this->_getTd();
			$oTd->style .= ' text-align: center';

			$oCheckbox = new Ext_Gui2_Html_Input();
			$oCheckbox->type = 'checkbox';
			$oCheckbox->name = 'save['.$sType.']['.$iClassId.'][changes_to_next_week]';
			$oCheckbox->class = 'changes_to_next_week';
			$oCheckbox->title = $this->t('Einstellungen nur für nächste Woche übernehmen');
			$oCheckbox->value = 1;
			
			if(
				isset($aClassData['changes_to_next_week']) ||
				(
					$bFirstCall &&
					'current'==$sType
				)
			)
			{
				$oCheckbox->checked = 'checked';
			}
				
			$oTd->setElement($oCheckbox);
			$oTr->setElement($oTd);

			$oTd = $this->_getTd();
			$oTd->style .= ' text-align: center';
			
			if('both'==$sType)
			{
				$oCheckbox = new Ext_Gui2_Html_Input();
				$oCheckbox->type = 'checkbox';
				$oCheckbox->name = 'save['.$sType.']['.$iClassId.'][changes_to_all_weeks]';
				$oCheckbox->class = 'changes_to_all_weeks';
				$oCheckbox->title = $this->t('Einstellungen für alle Folgewochen übernehmen');
				$oCheckbox->value = 1;
				if(isset($aClassData['changes_to_all_weeks']))
				{
					$oCheckbox->checked = 'checked';
				}
				$oTd->setElement($oCheckbox);
			}
			$oTr->setElement($oTd);
		}

		$oTd	=  $this->_getTd();
		$oTd->setElement((string)$aClassData['class_name']);
		$oInput = new Ext_Gui2_Html_Input();
		$oInput->type = 'hidden';
		$oInput->name = 'save['.$sType.']'.'['.$iClassId.'][class_name]';
		$oInput->value = (string)$aClassData['class_name'];
		$oTd->setElement($oInput);
		$oTr->setElement($oTd);

		$oTd	=  $this->_getTd();
		$oTd->setElement((string)$aClassData['current_week']);
		$oInput = new Ext_Gui2_Html_Input();
		$oInput->type = 'hidden';
		$oInput->name = 'save['.$sType.']'.'['.$iClassId.'][current_week]';
		$oInput->value = (string)$aClassData['current_week'];
		$oTd->setElement($oInput);
		$oTr->setElement($oTd);

		$oTd	=  $this->_getTd();
		$oSelect = new Ext_Gui2_Html_Select();
		$oSelect->class = 'form-control';
		//$oSelect->style = 'width:55px;';
		$oSelect->name	= 'save['.$sType.']'.'['.$iClassId.'][current_level]';

		foreach($this->_aLevels as $iLevelId => $sLevelName)
		{
			$oOption = new Ext_Gui2_Html_Option();
			$oOption->value = $iLevelId;
			$oOption->setElement($sLevelName);
			if($iLevelId==$aClassData['current_level'])
			{
				$oOption->selected = 'selected';
			}
			$oSelect->setElement($oOption);
		}
		$oTd->setElement($oSelect);
		$oTr->setElement($oTd);

		$oTd	=  $this->_getTd();

		foreach($aClassData['blocks'] as $iBlockId => $aBlockData)
		{
            $aRoomIds = explode(",", (string)$aBlockData['block_room_ids']);

            if(count($aRoomIds) > 1) {

				$aRoomNames = array_intersect_key($this->_aRooms, array_flip($aRoomIds));

                // Bei mehreren Räumen das Select weglassen (Mehrfachauswahl wird nicht dargestellt)
                $oDiv = new Ext_Gui2_Html_Div();
                $oDiv->style = "text-align: center;";
                $oDiv->setElement(implode(', ', $aRoomNames));

                $oInput = new Ext_Gui2_Html_Input();
                $oInput->name = 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_room_ids]';
                $oInput->type = 'hidden';
                $oInput->value = $aBlockData['block_room_ids'];
                $oDiv->setElement($oInput);

                $oTd->setElement($oDiv);
				
            } else {
				
                $oSelect	= new Ext_Gui2_Html_Select();
                $oSelect->class = 'form-control reload_field';
                $oSelect->name	= 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_room_ids]';
                $aAvailableRooms = $this->_getAvailableRooms($iBlockId, $aBlockData);

                foreach($aAvailableRooms as $iRoomId => $sRoomName) {

                    $oOption = new Ext_Gui2_Html_Option();
                    $oOption->value = $iRoomId;
                    $oOption->setElement((string)$sRoomName);

                    if(!empty($aRoomIds) && $iRoomId == $aRoomIds[0]) {
                        $oOption->selected = 'selected';
                    }
                    $oSelect->setElement($oOption);
                }

                $oTd->setElement($oSelect);
            }

		}
		$oTr->setElement($oTd);

		$oTd = $this->_getTd();
		/** @TODO Die Blöcke dürfen nicht so mehrmals durchgelaufen. Code auslagern und alles über eine Schleife machen! */
		foreach($aClassData['blocks'] as $iBlockId => $aBlockData) {
			$oDiv = new Ext_Gui2_Html_Div();
			$oDiv->style = 'line-height:20px;margin-bottom:1px;';
			$oDiv->setElement((string)$aBlockData['block_name']);
			$oInput = new Ext_Gui2_Html_Input();
			$oInput->type = 'hidden';
			$oInput->value = (string)$aBlockData['block_name'];
			$oInput->name = 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_name]';

			$oTd->setElement($oInput);
			$oTd->setElement($oDiv);

		}
		$oTr->setElement($oTd);

		$oTd = $this->_getTd();

		foreach($aClassData['blocks'] as $iBlockId => $aBlockData) {

			$aDays = explode(',', $aBlockData['block_days']);
			$sBlockDays = \Ext_Thebing_Util::buildJoinedWeekdaysString($aDays, $sInterfaceLanguage);

			$oDiv = new Ext_Gui2_Html_Div();
			$oDiv->style = 'line-height:20px;margin-bottom:1px;';
			$oDiv->setElement($sBlockDays);

			$oTd->setElement($oDiv);

		}
		$oTr->setElement($oTd);

		$oTd = $this->_getTd();
		
		foreach($aClassData['blocks'] as $iBlockId => $aBlockData)  {

			$oSelect = new Ext_Gui2_Html_Select();
			//$oSelect->style = 'width:100%;margin:2px 0;';
			$oSelect->class = 'form-control reload_field';
			$oSelect->name	= 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_teacher_id]';
			$aAvailableTeachers = $this->_getAvailableTeachers($iBlockId,$aBlockData);

			foreach($aAvailableTeachers as $iTeacherId => $sTeacherName) {

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = $iTeacherId;
				$oOption->setElement((string)$sTeacherName);

				if($iTeacherId==$aBlockData['block_teacher_id']) {
					$oOption->selected = 'selected';
				}
				$oSelect->setElement($oOption);

			}
			$oTd->setElement($oSelect);



		}
		$oTr->setElement($oTd);

		$oTd = $this->_getTd();
		foreach($aClassData['blocks'] as $iBlockId => $aBlockData) {

			$oTextarea = new Ext_Gui2_Html_Textarea();
			$oTextarea->class = 'form-control';
			$oTextarea->name = 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_description]';
			$oTextarea->style = 'resize: vertical';
			$oTextarea->setElement($aBlockData['block_description']);
			$oTd->setElement($oTextarea);

			$oInput = new Ext_Gui2_Html_Input();
			$oInput->name = 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_from]';
			$oInput->type = 'hidden';
			$oInput->value = $aBlockData['block_from'];
			$oTd->setElement($oInput);

			$oInput = new Ext_Gui2_Html_Input();
			$oInput->name = 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_until]';
			$oInput->type = 'hidden';
			$oInput->value = $aBlockData['block_until'];
			$oTd->setElement($oInput);

			$oInput = new Ext_Gui2_Html_Input();
			$oInput->name = 'save['.$sType.']'.'['.$iClassId.'][blocks]['.$iBlockId.'][block_days]';
			$oInput->type = 'hidden';
			$oInput->value = $aBlockData['block_days'];
			$oTd->setElement($oInput);

			$oInput = new Ext_Gui2_Html_Input();
			$oInput->name = 'blocks_next_week['.$iBlockId.'][block_room_ids]';
			$oInput->type = 'hidden';
			$oInput->value = $aBlockData['block_room_ids'];
			$oTd->setElement($oInput);
		}

		if('replace_data'==$sAdditional) {
			$oInput = new Ext_Gui2_Html_Input();
			$oInput->type = 'hidden';
			$oInput->name = 'save['.$sType.']['.$iClassId.'][changes_to_next_week]';
			$oInput->value = '1';
			$oTd->setElement($oInput);
		}

		$oTr->setElement($oTd);

		return $oTr;
	}

	protected function _getAvailableRooms($iBlockId, $aBlockData) {

		$aAvailableRooms = $this->_aRooms;

		$aRoomIds = explode(',', (string)$aBlockData['block_room_ids']);
		$iRoomId = (int)reset($aRoomIds);

		// Falls der Raum nicht mehr in der Liste der verfügbaren ist, Eintrag ergänzen
		if(
			!array_key_exists($iRoomId, $aAvailableRooms)
		) {
			$oRoom = Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
			$aAvailableRooms[$oRoom->id] = $oRoom->getName();
 		}

		$aBlockDataDays		= explode(',',$aBlockData['block_days']);
		$iBlockDataFrom		= $aBlockData['block_from'];
		$iBlockDataUntil	= $aBlockData['block_until'];

		foreach((array)$this->_aBlocksNextWeek as $iBlockIdNextWeek => $aBlockNextWeekData) {

            $aRoomIdsNextWeek = explode(',', (string)$aBlockNextWeekData['block_room_ids']);

			// Virtuelle Räume und "Kein Raum" sind mehrfach verwendbar, müssen also hier nicht betrachtet werden
			foreach($aRoomIdsNextWeek as $iRoomIdNextWeek) {
				if(
					$iBlockId != $iBlockIdNextWeek && 
					$iRoomIdNextWeek > 0
				) {

					$aDaysNextWeek	= explode(',', $aBlockNextWeekData['block_days']);
					$iFromNextWeek	= $aBlockNextWeekData['block_from'];
					$iUntilNextWeek = $aBlockNextWeekData['block_until'];

					$aDiff = array_diff($aBlockDataDays, $aDaysNextWeek);

					if(count($aDiff) != count($aBlockDataDays)) {

						if(
							$iFromNextWeek < $iBlockDataUntil && 
							$iUntilNextWeek > $iBlockDataFrom
						) {
							unset($aAvailableRooms[$iRoomIdNextWeek]);
						}

					}

				}
			}
			
		}

		return $aAvailableRooms;
	}

	protected function _getAvailableTeachers($iBlockId,$aBlockData)
	{
		$aAvailableTeachers	= $this->_aTeachers;
		
		if(
			!array_key_exists($aBlockData['block_teacher_id'], $aAvailableTeachers)
		){
			$oTeacher = Ext_Thebing_Teacher::getInstance($aBlockData['block_teacher_id']);
			$aAvailableTeachers[$oTeacher->id] = $oTeacher->getName();
 		}

		$aBlockDataDays		= explode(',',$aBlockData['block_days']);
		$iBlockDataFrom		= $aBlockData['block_from'];
		$iBlockDataUntil	= $aBlockData['block_until'];

		foreach((array)$this->_aBlocksNextWeek as $iBlockIdNextWeek => $aBlockNextWeekData)
		{
			if($iBlockId!=$iBlockIdNextWeek && 0<$aBlockNextWeekData['block_teacher_id'])
			{
				$aDaysNextWeek	= explode(',', $aBlockNextWeekData['block_days']);
				$iFromNextWeek	= $aBlockNextWeekData['block_from'];
				$iUntilNextWeek = $aBlockNextWeekData['block_until'];

				$aDiff = array_diff($aBlockDataDays, $aDaysNextWeek);
				if(count($aDiff)!=count($aBlockDataDays))
				{
					if($iFromNextWeek<$iBlockDataUntil && $iUntilNextWeek>$iBlockDataFrom)
					{
						unset($aAvailableTeachers[$aBlockNextWeekData['block_teacher_id']]);
					}
				}
			}
		}

		return $aAvailableTeachers;
	}

	protected function _prepareTableQueryData(&$aSql, &$sSql){

//		global $_VARS;

		$bMatch = preg_match("/\`ktb`.`week` = :filter_week_0/", $sSql);

		$sError	= false;
		$dDate	= false;
//		$iWeek	= $this->getCurrentWeekStart();
//
//		if(
//			isset($_VARS['filter']) &&
//			isset($_VARS['filter']['week_filter'])
//		)
//		{
//			$iWeek = $_VARS['filter']['week_filter'];
//		}

		$iWeek = (int)$this->_aFilter['week_filter'];

		try
		{
			$oDate	= new WDDate($iWeek);
			$dDate	= $oDate->get(WDDate::DB_DATE);
		}
		catch(Exception $e)
		{
			$sError = $e->getMessage();
		}

		if(
			$bMatch &&
			!$sError &&
			$dDate
		)
		{
			$aSql['filter_week_0'] = $dDate;
		}
		else
		{
			$sMessage = $this->t('Bitte überprüfen Sie Ihre Filtereingaben.');
			$this->aAlertMessages = array($sMessage);

			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= Util::getBacktrace()."\n\n";
			$sText .= 'Error WDDATE:'.$sError."\n\n";
			$sText .= print_r($_VARS,1)."\n\n";
			$sText .= $sSql."\n\n";
			$sText .= print_r($this->_oGui,1)."\n\n";

			__pout($sText);

			Ext_TC_Util::reportError('Tuition_Class_Gui2::_prepareTableQueryData: Ohne Filter', $sText);

		}
		
	}


	protected function _getErrorMessage($sError, $sField='', $sLabel='', $sAction=null, $sAdditional=null)
	{
		$oHelper = new Ext_Thebing_Tuition_Class_Helper_ErrorMessage($this->oWDBasic, $this->_oGui->getLanguageObject());
		$sErrorMessage = $oHelper->getErrorMessage($sError, $sField, $sLabel);
		
		if(!$oHelper->bFound) {
			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
		
		return $sErrorMessage;
	}
	
	/**
	 * Alle Lehrer & Räume löschen, sonst kommt man beim Kopieren nicht übers validate, wenn man 
	 * zwischen 2 Klassen Raum/Lehrer tauscht und übernehmen will, in  der DB sind die nähmlich immmer
	 * noch besetzt, wegen den Folgewochen kann man auch nicht so einfach nur die überschriebenen Räume/Lehrer
	 * überprüfen, siehe auch #816
	 * @param array $aBlockSaveData 
	 */
	protected function _resetBlocksData($aSaveData, $aFields)
	{	
		//Jetzige Woche die in der Folgewoche nicht existiert, braucht nicht freigesetzt zu werden
		//weil in der Datenbank dazu kein Eintrag existiert und keine Probleme verursachen würde
		unset($aSaveData['current']);
		
		foreach($aSaveData as $sType => $aClassData)
		{
			foreach($aClassData as $iClassId => $aClassInformation)
			{
				//Nur angeklickte im Kopierdialog freisetzen
				if(
					(
						//nur in die nächste Woche übernehmen
						isset($aClassInformation['changes_to_next_week']) &&
						$aClassInformation['changes_to_next_week'] == 1	
					) ||
					(
						//in alle Folgewochen übernehmen
						isset($aClassInformation['changes_to_all_weeks']) &&
						$aClassInformation['changes_to_all_weeks'] == 1			
					)
				)
				{
					if(isset($aClassInformation['blocks']))
					{
						$iAllWeeks = 0;
						
						if(
							isset($aClassInformation['changes_to_all_weeks']) &&
							$aClassInformation['changes_to_all_weeks'] == 1		
						)
						{
							$iAllWeeks = 1;
						}
						
						foreach($aClassInformation['blocks'] as $iBlockId => $aBlockData)
						{
							$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
							$oBlock->resetData($aFields, $iAllWeeks);
						}
					}
				}
			}
		}
	}
	
	/**
	 *
	 * @return array
	 */
	public function getPreparedCourseData()
	{
		return $this->_aCoursesShort;
	}
	
	/**
	 *
	 * @return array
	 */
	public function getPreparedLevelData()
	{
		return $this->_aLevels;
	}
	
	protected function _getDifferentLevelTableHTml(array $aDifferentLevels, $sDialogId, $sKeyBlackList = 'block_week', $sAlias = 'ktcl')
	{
		global $_VARS;


		$oDivDifferentLevels = new Ext_Gui2_Html_Div();
		$oDivDifferentLevels->class = 'GUIDialogContentPadding';
		
		// Nachdem einmal die Tabelle angezeigt wurde, nicht mehr überprüfen...
		$oInput			= new Ext_Gui2_Html_Input();
		
		$sColumn		= '[check_different_levels]';
		
		if($sAlias)
		{
			$sColumn	.= '[' . $sAlias . ']';
		}
		
		$oInput->id		= 'save[' . $this->_oGui->hash . '][' . $sDialogId . ']' . $sColumn;
		$oInput->name	= 'save' . $sColumn;
		
		$oInput->type	= 'hidden';
		$oInput->value	= 0;
		
		$oDivDifferentLevels->setElement($oInput);
		
		if(
			isset($_VARS['ignore_errors']) &&
			$_VARS['ignore_errors'] == 1
		)
		{
			$oInput			= new Ext_Gui2_Html_Input();
			$oInput->name	= 'ignore_errors';

			$oInput->type	= 'hidden';
			$oInput->value	= 1;

			$oDivDifferentLevels->setElement($oInput);
		}
		

		foreach($aDifferentLevels as $sHeader => $aAllocations)
		{
			$oH3 = new Ext_Gui2_Html_H4();
			$oH3->setElement($sHeader);
			
			$oDivDifferentLevels->setElement($oH3);
			
			$oTable = $this->_createAllocationTable();
			
			######### Header ########
			
			$oTr	= new Ext_Gui2_Html_Table_tr();
			
			// Name des Schülers
			$oTh	= $this->_createAllocationTh();
			$oTh->setElement($this->t('Name'));
			$oTr->setElement($oTh);
			
			// Klasse des Schülers
			$oTh	= $this->_createAllocationTh();
			$oTh->setElement($this->t('Klasse'));
			$oTr->setElement($oTh);
			
			// Schülerlevel
			$oTh	= $this->_createAllocationTh();
			$oTh->setElement($this->t('Schülerlevel'));
			$oTr->setElement($oTh);
			
			// Klassenlevel
			$oTh	= $this->_createAllocationTh();
			$oTh->setElement($this->t('Klassenlevel'));
			$oTr->setElement($oTh);
			
			// Level beibehalten
			$oTh	= $this->_createAllocationTh();
			$oTh->setElement($this->t('Level beibehalten'));
			$oTr->setElement($oTh);
			
			$oTable->setElement($oTr);

			######### Tabelleninhalt ########
			foreach($aAllocations as $aAllocationData)
			{
				$oTr	= new Ext_Gui2_Html_Table_tr();
				
				// Name des Schülers
				$oInquiry	= Ext_TS_Inquiry::getInstance($aAllocationData['inquiry_id']);
				$oTraveller	= $oInquiry->getFirstTraveller();
				
				$oTd	= $this->_createAllocationTd();
				$oTd->setElement($oTraveller->getName());
				$oTr->setElement($oTd);
				
				// Klasse des Schülers
				
				$oClass = Ext_Thebing_Tuition_Class::getInstance((int)$aAllocationData['class_id']);
				
				$oTd	= $this->_createAllocationTd();
				$oTd->setElement($oClass->name);
				$oTr->setElement($oTd);
				
				// Schülerlevel
				$oLevel	= Ext_Thebing_Tuition_Level::getInstance($aAllocationData['level']);
				
				$oTd	= $this->_createAllocationTd();
				$oTd->setElement($oLevel?->name_short ?? '');
				$oTr->setElement($oTd);
				
				// Klassenlevel
				$oLevel	= Ext_Thebing_Tuition_Level::getInstance($aAllocationData['block_level']);
				
				$oTd	= $this->_createAllocationTd();
				$oTd->setElement($oLevel->name_short);
				$oTr->setElement($oTd);
				
				// Level beibehalten
				$sBlackListKey		= $aAllocationData[$sKeyBlackList];
				$sKey				= $aAllocationData['inquiry_id'] . '_' . $aAllocationData['courselanguage_id'];
				
				$oTd				= $this->_createAllocationTd();
				$oCheckbox			= new Ext_Gui2_Html_Input();
				$oCheckbox->type	= 'checkbox';
				$oCheckbox->name	= 'save[level_black_list]['. $sBlackListKey .'][' . $sKey . ']';
				$oCheckbox->value	= 1;
				$oTd->setElement($oCheckbox);
				$oTr->setElement($oTd);
				
				$oTable->setElement($oTr);
			}
			
			$oDivDifferentLevels->setElement($oTable);
		}
		
		$sHtml = $oDivDifferentLevels->generateHTML();
		
		return $sHtml;
	}
	
	/**
	 * Tabelle der Levelübernahme
	 * 
	 * @return Ext_Gui2_Html_Table 
	 */
	protected function _createAllocationTable()
	{
		$oTable = new Ext_Gui2_Html_Table();
		$oTable->class = 'table tblDocumentTable tblDocumentPositions tblMainDocumentPositions';
		$oTable->style = 'background-color:#FFF;width: auto;';
		
		$aWidths = array(
			Ext_Thebing_Util::getTableColumnWidth('long_description'),
			Ext_Thebing_Util::getTableColumnWidth('short_name'),
			Ext_Thebing_Util::getTableColumnWidth('short_name'),
			Ext_Thebing_Util::getTableColumnWidth('id'),
		);

		$sColGroupHtml = $this->_getColGroupsHtml($aWidths);

		$oTable->setElement($sColGroupHtml);
		
		return $oTable;
	}
	
	/**
	 * TH's der Levelübernahme
	 * 
	 * @return Ext_Gui2_Html_Table_Tr_Th 
	 */
	protected function _createAllocationTh()
	{
		$oTh		= new Ext_Gui2_Html_Table_Tr_Th();
		$oTh->class = 'small';
		
		return $oTh;
	}
	
	
	/**
	 * TD's der Levelübernahme
	 * 
	 * @return Ext_Gui2_Html_Table_Tr_Td 
	 */
	protected function _createAllocationTd()
	{
		$oTd		= new Ext_Gui2_Html_Table_Tr_Td();
		
		return $oTd;
	}
	
	/**
	 * Spaltenüberschriften in Thebing-Datumsformat umändern
	 * 
	 * @param type $aDifferentLevels 
	 * @return array
	 */
	protected function _getDateFormatHeader(&$aDifferentLevels)
	{
		$aWeeks			= (array)Ext_Thebing_Util::getWeekOptions(WDDate::DB_DATE, 2);
		
		$aNewArray		= array();
		$oFormatDate	= new Ext_Thebing_Gui2_Format_Date();
		
		foreach($aDifferentLevels as $sDate => $aData)
		{
			if(isset($aWeeks[$sDate]))
			{
				$sFormatDate	= $aWeeks[$sDate];
			}
			else
			{
				$sFormatDate	= $oFormatDate->format($sDate);
			}
			
			$aNewArray[$sFormatDate]	= $aData;
		}
			
		return $aNewArray;
	}

//	Angefangen, noch nicht fertig (YML-Umstellung)
//	public static function getOrderby() {
//
//		return ['name' => 'ASC'];
//	}
//
//	public static function getSelectFilterEntriesCourseCategories() {
//
//		if(Ext_Thebing_System::isAllSchools()) {
//
//			$aSchoolObjects = \Ext_Thebing_Client::getStaticSchoolListByAccess(false, false, true);
//			$aCourseCategories = [];
//			foreach ($aSchoolObjects as $oSchool) {
//				$aCourseCategories += $oSchool->getCourseCategoriesList('select');
//			}
//		} else {
//			$oSchool = Ext_Thebing_School::getSchoolFromSession();
//			$aCourseCategories = $oSchool->getCourseCategoriesList('select');
//		}
//
//	return $aCourseCategories;
//	}
//
//	public static function getWhere() {
//
//		if(Ext_Thebing_System::isAllSchools()) {
//
//			$aSchoolObjects = \Ext_Thebing_Client::getStaticSchoolListByAccess(false, false, true);
//			$aSchools = [];
//			foreach ($aSchoolObjects as $oSchool) {
//				$aSchools[$oSchool->id] = $oSchool->ext_1;
//			}
//
//			$where = ['ktcl.school_id' => ['IN', array_keys($aSchools)],
//					'ktcl.active' => 1,
//					'ktb.active' => 1,];
//			} else {
//				$oSchool = Ext_Thebing_School::getSchoolFromSession();
//
//				$where = ['ktcl.school_id' => $oSchool->id,
//				'ktcl.active'	=> 1,
//				'ktb.active'	=> 1,];
//				}
//
//		return $where;
//	}
//	public static function getSelectFilterEntriesTeacher() {
//
//		if(Ext_Thebing_System::isAllSchools()) {
//
//			$aSchoolObjects = \Ext_Thebing_Client::getStaticSchoolListByAccess(false, false, true);
//
//			$aTeachers = [];
//			foreach ($aSchoolObjects as $oSchool) {
//				$aTeachers += $oSchool->getTeacherList(true);
//			}
//		} else {
//				$oSchool = Ext_Thebing_School::getSchoolFromSession();
//				$aTeachers = $oSchool->getTeacherList(true);
//				}
//		return $aTeachers;
//	}
//
//	public static function getSelectFilterEntriesCourse() {
//
//		if(Ext_Thebing_System::isAllSchools()) {
//
//			$aSchoolObjects = \Ext_Thebing_Client::getStaticSchoolListByAccess(false, false, true);
//			$aCourses = [];
//			foreach ($aSchoolObjects as $oSchool) {
//
//				$aCourses += $oSchool->getCourseList();
//			}
//		}else{
//			$oSchool = Ext_Thebing_School::getSchoolFromSession();
//			$aCourses = $oSchool->getCourseList();
//		}
//		return $aCourses;
//	}
//
//	public static function getSelectFilterEntriesState() {
//
//		return [
//			Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE => L10N::t('Lehrer mit Abwesenheit'),
//			Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_AVAILABILITY => L10N::t('Lehrer außerhalb der Verfügbarkeit'),
//			Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION => L10N::t('Lehrer außerhalb der Qualifikation')
//		];
//	}
//
//	public static function getFormatParamsSchool() {
//
//		if(Ext_Thebing_System::isAllSchools()) {
//
//			$aSchoolObjects = \Ext_Thebing_Client::getStaticSchoolListByAccess(false, false, true);
//
//			$aSchools  = [];
//			foreach ($aSchoolObjects as $oSchool) {
//				$aSchools[$oSchool->id] = $oSchool->ext_1;
//		}
//		}else {
//			$oSchool = Ext_Thebing_School::getSchoolFromSession();
//			$aSchools = [$oSchool->id => $oSchool->ext_1];
//		}
//
//		return $aSchools;
//	}

}
