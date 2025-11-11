<?php

class Ext_Thebing_Accounting_Gui2_Teacher_Payment extends Ext_Thebing_Payment_Provider_Gui2_Abstract {

	protected function setQueryFilterDataByRef(&$aFilter, &$aQueryParts, &$aSql) {

		parent::setQueryFilterDataByRef($aFilter, $aQueryParts, $aSql);

		$sFrom = (string)$aFilter['search_time_from_1'];
		$sUntil = (string)$aFilter['search_time_until_1'];

		if(
			$sFrom == "" ||
			$sUntil == ""
		){
			$aFilterElements = $this->_oGui->getAllFilterElements();
			
			foreach((array)$aFilterElements as $iKey => $oElement) {
				if(
					$oElement->element_type == 'timefilter'
				){
					$sFrom = $oElement->default_from;
					$sUntil = $oElement->default_until;
				}
			}
		}

		$iFrom = Ext_Thebing_Format::ConvertDate($sFrom);
		$iUntil = Ext_Thebing_Format::ConvertDate($sUntil);

		$oWDDate = new WDDate();
		$oWDDate->set('00:00:00', WDDate::TIMES);

		$oWDDate->set($iFrom, WDDate::TIMESTAMP);
		//$oWDDate->set(1, WDDate::DAY);
		$aSql['from'] = $oWDDate->get(WDDate::DB_DATE);

		$oWDDate->set($iUntil, WDDate::TIMESTAMP);
		//$oWDDate->set($oWDDate->get(WDDate::MONTH_DAYS), WDDate::DAY);
		$aSql['until'] = $oWDDate->get(WDDate::DB_DATE);
		
		$aSql['temp_month_table'] = 'ktep_'.\Util::generateRandomString(32);
		$aSql['temp_month_table_2'] = 'ktep_'.\Util::generateRandomString(32);
		$aSql['temp_month_table_3'] = 'ktep_'.\Util::generateRandomString(32);
		$aSql['temp_month_table_4'] = 'ktep_'.\Util::generateRandomString(32);
		$aSql['temp_week_table'] = 'ktep_'.\Util::generateRandomString(32);

		// https://bugs.mysql.com/bug.php?id=10327
		$this->writeTempMonthTable($aSql, 'temp_month_table');
		$this->writeTempMonthTable($aSql, 'temp_month_table_2');
		$this->writeTempMonthTable($aSql, 'temp_month_table_3');
		$this->writeTempMonthTable($aSql, 'temp_month_table_4');
		$this->writeTempWeekTable($aSql);

	}

	public function writeTempMonthTable($aSql, $sKey) {

		$sTemp = "CREATE TEMPORARY TABLE #table ( `month` DATE )";
		#$sTemp = "CREATE TABLE #table ( `month` DATE )";
		$aTemp = array('table' => $aSql[$sKey]);

		DB::executePreparedQuery($sTemp, $aTemp);

		$oWDDate = new WDDate();
		$oWDDate->set('00:00:00', WDDate::TIMES);

		$oWDDate->set($aSql['until'], WDDate::DB_DATE);
		$iUntilMonth = $oWDDate->get(WDDate::TIMESTAMP);

		$oWDDate->set($aSql['from'], WDDate::DB_DATE);
		$iStartMonth = $oWDDate->get(WDDate::TIMESTAMP);

		do {

			$sMonth = $oWDDate->get(WDDate::DB_DATE);

			DB::insertData($aTemp['table'], array('month' => $sMonth), true);

			$oWDDate->add(1, WDDate::MONTH);

			$iStartMonth = $oWDDate->get(WDDate::TIMESTAMP);

		} while ($iStartMonth <= $iUntilMonth);

	}

	public function writeTempWeekTable($aSql){

		$sTemp = "CREATE TEMPORARY TABLE #table ( `week` DATE )";
		#$sTemp = "CREATE TABLE #table ( `week` DATE )";
		$aTemp = array('table' => $aSql['temp_week_table']);

		DB::executePreparedQuery($sTemp, $aTemp);

		$oWDDate = new WDDate();
		$oWDDate->set('00:00:00', WDDate::TIMES);

		$oWDDate->set($aSql['until'], WDDate::DB_DATE);
		$iUntilMonth = $oWDDate->get(WDDate::TIMESTAMP);

		$oWDDate->set($aSql['from'], WDDate::DB_DATE);
		$iStartMonth = $oWDDate->get(WDDate::TIMESTAMP);

		$oWDDate->set(1, WDDate::WEEKDAY);

		do {

			$sDate = $oWDDate->get(WDDate::DB_DATE);

			DB::insertData($aTemp['table'], array('week' => $sDate), true);

			$oWDDate->add(1, WDDate::WEEK);

			$iStartMonth = $oWDDate->get(WDDate::TIMESTAMP);

		} while ($iStartMonth <= $iUntilMonth);

	}

	public function switchAjaxRequest($_VARS) {
		global $user_data;

		$aTransfer = array();

		switch($_VARS['task']) {
			case 'saveDialog':

				if ($_VARS['action'] === 'edit_dialog_info_icon') {
					parent::switchAjaxRequest($_VARS);
					return;
				}

				$aErrors = array();
				$aHints	= array();
				$aTempSaveData = array();

				$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
				$oColumn = null;
				$sDate = $oDateFormat->convert($_VARS['save']['date'], $oColumn);

				// Gruppierungseintrag pro Lehrer
				$aGroupings = array();
				$aGroupingPayments = array();
				$aGeneratedGroupings = array();

				foreach((array)$_VARS['save']['amount'] as $iGuiId => $mAmount) {

					$aData = $this->_oGui->decodeId($iGuiId);

					$oTeacher = Ext_Thebing_Teacher::getInstance($aData['teacher_id']);
					$oTeacherNameFormat = new Ext_Thebing_Gui2_Format_TeacherName();
					$oTeacherSalary = Ext_Thebing_Teacher_Salary::getInstance($aData['salary']);

					// Lehrer in das Grouping einfügen
					if(!isset($aGroupings[$oTeacher->id])) {
						$aGroupings[$oTeacher->id] = $oTeacher;
					}
	
					// Kommentar ====================================================
					$aComment = array();
					// Lehrer
					$aComment[]		    = $oTeacherNameFormat->formatByResult(array('lastname' => $oTeacher->lastname, 'firstname' => $oTeacher->firstname));
					
					// Klasse
					if(!empty($aData['block_id'])){
						$iBlockId	= (int)$aData['block_id'];
						$oBlock		= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
						$oClass		= $oBlock->getClass();
						$aComment[] = $oClass->name;
					}

					// »Hybridtyp« Gehalt je Lektion gruppiert nach Monat läuft für ganzen alten Kram auf week, ist aber eigentlich month
					if($aData['calculation'] == 5) {
						$aData['select_type'] = 'month';
					}

					// Zeitraum
					$aTemp = array();
					$aTemp['select_type'] = $aData['select_type'];
					$aTemp['timepoint'] = $aData['timepoint'];
					$oFormat = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Week();
					$sDateFormatted = $oFormat->format($aData['select_value'], $aTemp, $aTemp);
					$aComment[] = $sDateFormatted;

					// Lektionen kommen bei Festgehalt aus dem Lehrergehalt, ansonsten errechnet im Query
					if($oTeacherSalary->costcategory_id == -1) {
						$fLessons = $oTeacherSalary->lessons;
					} else {
						$fLessons = (float)$aData['lessons'];
					}

					$sComment = implode(' - ', $aComment);

					$sCommentFinal	= $sComment;
					
					if(!empty($_VARS['save']['comment'])){
						$sCommentFinal	.= ' - ' . $_VARS['save']['comment'];
					}	
					
					$mAmountSchool		= $_VARS['save']['amount_school'][$iGuiId];
					$iCurrencyId		= $_VARS['save']['payment_currency_id'][$iGuiId];
					$sSinglePaymentNote = $_VARS['save']['single_payment_note'][$iGuiId];

					$oSchool = $oTeacher->getSchool();
					$iSchoolCurrencyId	= $oSchool->getCurrency();

					// Da deaktiviert -> keine daten übermittelt
					if($iCurrencyId <= 0){
						$iCurrencyId = $oSchool->getTeacherCurrency();
					}

					$fAmount			= Ext_Thebing_Format::convertFloat($mAmount);
					$fAmountSchool		= Ext_Thebing_Format::convertFloat($mAmountSchool);

					$oPayment = new Ext_Thebing_Teacher_Payment();
					$oPayment->block_id				= (int)$aData['block_id'];
					$oPayment->teacher_id			= (int)$aData['teacher_id'];
					$oPayment->salary_id			= $oTeacherSalary->id;
					$oPayment->timepoint			= $aData['timepoint'];
					$oPayment->comment				= $sCommentFinal;
					$oPayment->payment_note			= $sSinglePaymentNote;
					$oPayment->method_id			= $_VARS['save']['method_id'];
					$oPayment->amount				= (float)$fAmount;
					$oPayment->amount_school		= (float)$fAmountSchool;
					$oPayment->payment_currency_id	= (int)$iCurrencyId;
					$oPayment->school_currency_id	= (int)$iSchoolCurrencyId;
					$oPayment->payment_type			= $aData['select_type'];
					$oPayment->date					= (string)$sDate;
					$oPayment->salary_lessons		= $fLessons;
					$oPayment->salary_lessons_period = $oTeacherSalary->lessons_period;
					$oPayment->hours				= $aData['hours'];
					$oPayment->calculation = $aData['calculation'];
					$oPayment->course_list = $aData['course_list'];
					$oPayment->block_list = $aData['block_list'];

					// Momentanen Key speichern, um wieder auf die Daten zugreifen zu können für das PDF
					$oPayment->iSelectedId			= $iGuiId;

					$mValidate		= $oPayment->validate();
					if(!isset($_VARS['ignore_errors'])){
						$mValidateHint	= $oPayment->checkIgnoringErrors();
					}else{
						$mValidateHint	= true;
					}

					if($mValidate !== true || $mValidateHint !== true){
						if($mValidate !== true){
							$aErrors = array_merge($aErrors, (array)$mValidate);
						}
						if($mValidateHint !== true){
							$aHints = array_merge($aHints, (array)$mValidateHint);
						}
					} else {
						$oPayment->save();
						$aTempSaveData[] = $oPayment;
						$aGroupingPayments[$oTeacher->id][] = $oPayment;

						if(empty($aErrors)){

							foreach((array)$_VARS['save']['additional']['amount'][$iGuiId] as $iAdditionalKey => $fAmount){

								$fAdditionalAmount = $fAmount;
								$fAdditionalAmountSchool = $_VARS['save']['additional']['amount_school'][$iGuiId][$iAdditionalKey];

								$fAdditionalAmount			= Ext_Thebing_Format::convertFloat($fAdditionalAmount);
								$fAdditionalAmountSchool	= Ext_Thebing_Format::convertFloat($fAdditionalAmountSchool);

								if($fAdditionalAmount <= 0){
									continue;
								}

								$sCommentFinal = $sComment;

								if(!empty($_VARS['save']['additional']['comment'][$iGuiId][$iAdditionalKey])){
									$sCommentFinal .= ' - ' . $_VARS['save']['additional']['comment'][$iGuiId][$iAdditionalKey];
								}

								## Zusatzpositionen speichern ##
								$oAdditionalPayment = new Ext_Thebing_Teacher_Payment();
								$oAdditionalPayment->block_id			= $oPayment->block_id;
								$oAdditionalPayment->teacher_id			= $oPayment->teacher_id;
								$oAdditionalPayment->timepoint			= $oPayment->timepoint;
								$oAdditionalPayment->method_id			= $oPayment->method_id;
								$oAdditionalPayment->payment_currency_id= $oPayment->payment_currency_id;
								$oAdditionalPayment->school_currency_id	= $oPayment->school_currency_id;
								$oAdditionalPayment->payment_type		= $oPayment->payment_type;
								$oAdditionalPayment->date				= $oPayment->date;
								$oAdditionalPayment->comment			= $sCommentFinal;
								$oAdditionalPayment->payment_note		= $sSinglePaymentNote;
								$oAdditionalPayment->amount				= $fAdditionalAmount;
								$oAdditionalPayment->amount_school		= $fAdditionalAmountSchool;
								$oAdditionalPayment->parent_id			= $oPayment->id;
								$oAdditionalPayment->salary_lessons		= $oPayment->salary_lessons;
								$oAdditionalPayment->salary_lessons_period = $oPayment->salary_lessons_period;
								$oAdditionalPayment->hours				= $oPayment->hours;
								$oAdditionalPayment->course_list 		= $oPayment->course_list;
								$oAdditionalPayment->iSelectedId		= $iGuiId;

								$mValidate = $oAdditionalPayment->validate();

								if(!isset($_VARS['ignore_errors'])){
									$mValidateHint	= $oAdditionalPayment->checkIgnoringErrors();
								}else{
									$mValidateHint	= true;
								}
								if($mValidate !== true || $mValidateHint !== true){
									if($mValidate !== true){
										$aErrors = array_merge($aErrors, (array)$mValidate);
									}
									if($mValidateHint !== true){
										$aHints = array_merge($aHints, (array)$mValidateHint);
									}
								} else {
									$oAdditionalPayment->save();
									$aTempSaveData[] = $oAdditionalPayment;
									$aGroupingPayments[$oTeacher->id][] = $oAdditionalPayment;
								}
							}
						}
					}

					// Bei Fehler wieder Löschen
					if(!empty($aErrors) || !empty($aHints)) {

						foreach((array)$aTempSaveData as $iKey => $oPayment){
							$oPayment->delete();
							unset($aTempSaveData[$iKey]);
						}
						break;

					}

				}

				// Gruppierung schreiben pro Lehrer
				if(empty($aErrors) && empty($aHints)) {
					foreach($aGroupings as $oTeacher) {
						/** @var $oTeacher Ext_Thebing_Teacher */

						/** @var $aTeacherPayments Ext_Thebing_Teacher_Payment[] */
						$aTeacherPayments = $aGroupingPayments[$oTeacher->id];
						$fGroupingAmount = 0;
						$fGroupingAmountSchool = 0;
						$oSchool = $oTeacher->getSchool();
						$iTemplateId = (int)$_VARS['save']['template_id'];

						// Gesamtbeträge addieren
						foreach($aTeacherPayments as $oPayment) {
							$fGroupingAmount = (float)bcadd((string)$fGroupingAmount, (string)$oPayment->amount);
							$fGroupingAmountSchool = (float)bcadd((string)$fGroupingAmountSchool, (string)$oPayment->amount_school);
						}

						$oLastPayment = end($aTeacherPayments);

						$oGrouping = new Ext_TS_Accounting_Provider_Grouping_Teacher();
						$oGrouping->teacher_id = $oTeacher->id;
						$oGrouping->payment_method_id = $_VARS['save']['method_id'];
						$oGrouping->template_id = $iTemplateId;
						$oGrouping->date = $sDate;
						$oGrouping->amount = $fGroupingAmount;
						$oGrouping->amount_currency_id = $oLastPayment->payment_currency_id;
						$oGrouping->amount_school = $fGroupingAmountSchool;
						$oGrouping->amount_school_currency_id = $oLastPayment->school_currency_id;

						$oGrouping->save();
						$aGeneratedGroupings[] = $oGrouping;

						// Payments Gruppierung zuweisen
						// Wird für Platzhalter bereits hier benötigt
						foreach($aTeacherPayments as $oPayment) {
							$oPayment->grouping_id = $oGrouping->id;
							$oPayment->save();
						}

						// PDF (pro Gruppierung) generieren, wenn Template vorhanden
						// Dies muss nach save() passieren (und noch mal save() aufrufen) wegen der ID!
						if($iTemplateId > 0) {
							$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplateId);

							// Daten, die direkt in die Platzhalterklasse geschrieben werden unter dem Key »grouping_data«
							$aGroupingDataPlaceholder = array(
								'provider_payment_overview' => $this->getDataForPaymentOverviewPlaceholder($aTeacherPayments, $fGroupingAmount, $oSchool->language)
							);

							$sFilePath = $oGrouping->createPdf($oTemplate, $aGroupingDataPlaceholder);
							$sFilePath = str_replace(Util::getDocumentRoot().'storage', '', $sFilePath);
							$oGrouping->file = $sFilePath;
							$aGeneratedPdfs[] = $sFilePath;
							$oGrouping->save();
						}
					}
				}

				$aAction		= array('action' => 'teacher_payment');
				
				$aErrorData		= (array)$this->_getErrorData($aErrors, $aAction, 'error', true);
				$aErrorDataHint = (array)$this->_getErrorData($aHints, $aAction, 'hint', true);

				$aErrorsAll		= array_merge($aErrorData,$aErrorDataHint);

				$sDialogIDTag = 'TEACHER_PAYMENT';
				$_VARS['id'] = (array)$_VARS['id'];
				$_VARS['id'] = array_unique($_VARS['id']);

				$sDialogIDTag = $sDialogIDTag.implode('_', $_VARS['id']);

				if(empty($aErrorsAll)) {
					$aTransfer['action']		= 'closeDialogAndReloadTable';

					// Dialog zum Öffnen der generierten Dokumente anzeigen
					$aGeneratedPdfs = array();
					foreach($aGeneratedGroupings as $oGrouping) {
						if(!empty($oGrouping->file)) {
							$oTeacher = Ext_Thebing_Teacher::getInstance($oGrouping->teacher_id);
							$sUrl = '/storage/download'.$oGrouping->file;
							$aGeneratedPdfs[] = '<a target="_blank" href="'.$sUrl.'">'.$oTeacher->getName().'</a>';
						}
					}

					if(!empty($aGeneratedPdfs)) {
						$aTransfer['success_message'] = $this->t('Die Dokumente wurden erfolgreich angelegt. Bitte klicken Sie hier, um ein PDF mit allen Positionen anzuzeigen.');
						$aTransfer['success_message'] .= '<br /><br />'.join(', ', $aGeneratedPdfs);
					}

				} else {
					$aTransfer['action']		= 'saveDialogCallback';
				}
				
				$aTransfer['error']			= $aErrorsAll;

				$aTransfer['data']			= array();
				$aTransfer['data']['id']	= $sDialogIDTag;

				if(!empty($aErrorDataHint)){
					$aTransfer['data']['show_skip_errors_checkbox'] = 1;
				}

				echo json_encode($aTransfer);
				$this->_oGui->save();
				\Core\Facade\SequentialProcessing::execute();
				die();
				break;
			default:
				// sonst parent ( hier wird ein echo gestartet )
				parent::switchAjaxRequest($_VARS);
		}

	}

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {

		$aSelectedIds	= (array)$aSelectedIds;
		$sDescription	= $this->_oGui->gui_description;

		// get dialog object
		switch($sIconAction) {
			case 'teacher_payment':

				$oSchool			= Ext_Thebing_School::getSchoolFromSession();
				$iCurrencySchoolId	= $oSchool->getCurrency();
				$iCurrencyTeacherId	= $oSchool->getTeacherCurrency();
				$aTemplates = Ext_Thebing_Pdf_Template_Search::s('document_teacher_payment', $oSchool->getLanguage(), $oSchool->id, null, true);
				$aTemplates = Ext_TC_Util::addEmptyItem($aTemplates);

				$aPaymentMethodData = Ext_Thebing_Teacher_Payment::getMatchingPaymentMethods(false, true);

				$oDialog = $this->_oGui->createDialog($this->t('Bezahlen'), $this->t('Bezahlen'), $this->t('Bezahlen'));
				$oDialog->bBigLabels = true;
				$oDialog->width = 950;
				$oDialog->sDialogIDTag	= 'TEACHER_PAYMENT';

				$oDivRow = $oDialog->createRow(L10N::t('Datum', $sDescription), 'calendar',	array('db_column' => 'date', 'db_alias' => '', 'format' => new Ext_Thebing_Gui2_Format_Date(), 'required' => 1));
				$oDialog->setElement($oDivRow);
				$oDivRow = $oDialog->createRow(L10N::t('Methode', $sDescription), 'select',	array(
					'db_column' => 'method_id',
					'db_alias' => '',
					'select_options' => $aPaymentMethodData['methods'],
					'required' => 1,
					'class' => 'payment_method_select',
					'default_value' => $aPaymentMethodData['default_method']
				));
				$oDialog->setElement($oDivRow);
				$oDivRow = $oDialog->createRow( L10N::t('Kommentar', $sDescription), 'textarea',	array('db_column' => 'comment', 'db_alias' => ''));
				$oDialog->setElement($oDivRow);
				$oDialog->setElement($oDialog->createRow($this->t('Template'), 'select', array(
					'db_column' => 'template_id',
					'select_options' => $aTemplates
				)));

				$fAmountTotal = 0;

				foreach((array)$aSelectedIds as $iGuiId){

					$iTeacherId = $this->_oGui->decodeid($iGuiId, 'teacher_id');
					$fAmount	= $this->_oGui->decodeid($iGuiId, 'amount');
					$fAmountTotal += $fAmount;

					#################
					## Betragszeile##
					#################

					$aData = array();
					$aData['db_column_1']			= 'amount';
					$aData['db_column_2']			= 'amount_school';
					$aData['db_column_currency']	= 'payment_currency_id';
					$aData['db_alias']				= (int)$iGuiId;
					$aData['school_id']				= $oSchool->id;
					$aData['format']				= new Ext_Thebing_Gui2_Format_Amount();
					$aData['amount']				= $fAmount;
					$aData['currency_id']			= $iCurrencyTeacherId;
					$aData['school_currency_id']	= $iCurrencySchoolId;


					$oDummy;
					$aFormatData = $this->_oGui->decodeid($iGuiId);
					$oTeacherFormat = new Ext_Thebing_Gui2_Format_TeacherName();
					$oWeekFormat = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Week();

					$oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
					#$sTitel = $oTeacherFormat->format('', $oDummy, $aFormatData);
					$sTitel = $oTeacher->name;
					$sLabel = $oWeekFormat->format($aFormatData['select_value'], $oDummy, $aFormatData);

					$oH3 = new Ext_Gui2_Html_H4();
					$oH3->setElement($sTitel);
					$oDialog->setElement($oH3);

					$oAmountDiv	= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, $sLabel, false);
					$oDialog->setElement($oAmountDiv);

					#################
					## Zusatzzeile ##
					#################

					$aData = array();
					$aData['db_column_1']			= 'additional][amount]['.(int)$iGuiId.'][';
					$aData['db_column_2']			= 'additional][amount_school]['.(int)$iGuiId.'][';
					$aData['db_column_currency']	= 'additional][payment_currency_id';
					$aData['db_alias_currency']		= '['.(int)$iGuiId.'][]';
					$aData['db_alias']				= '';
					$aData['school_id']				= $oSchool->id;
					$aData['format']				= new Ext_Thebing_Gui2_Format_Amount();
					$aData['amount']				= 0;
					$aData['currency_id']			= $iCurrencyTeacherId;
					$aData['school_currency_id']	= $iCurrencySchoolId;

					$oDivLabel = new Ext_Gui2_Html_Div();
					$oInput = new Ext_Gui2_Html_Input();
					$oDivLabel->class = 'input-group currency_amount_row_label_input';
					$oInput->class = "txt form-control";
					$oInput->placeholder = $this->_oGui->t('Zusatzposition');
					$oInput->style = "float:left;";
					$oInput->name = "save[additional][comment][".$iGuiId."][]";
					$oDivLabel->setElement($oInput);

					$oInputGroupBtn = new \Ext_Gui2_Html_Span();
					$oInputGroupBtn->class = 'input-group-btn';
					$oButton = new \Ext_Gui2_Html_Button();
					$oButton->class = 'btn';
					$oButton->title = $this->_oGui->t('weitere Zusatzzeile');
					$oButton->setElement('<i class="fa fa-plus"></i>');
					$oButton->onclick="aGUI['".$this->_oGui->hash."'].copyGuiRow(this); aGUI['".$this->_oGui->hash."'].checkPaymentCurrencyCallback(); return false;";
					$oInputGroupBtn->setElement($oButton);
					$oDivLabel->setElement($oInputGroupBtn);

					$oAmountDiv	= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, $oDivLabel, false);
					$oDialog->setElement($oAmountDiv);

					$oDivRow = $oDialog->createRow(L10N::t('Kommentar', $sDescription), 'textarea',	array('name' => 'save[single_payment_note]['.(int)$iGuiId.']'));
					$oDialog->setElement($oDivRow);

					$oHr = new Ext_Gui2_Html_Hr();
					$oDialog->setElement($oHr);

				}
				
				$aData = array();
				$aData['db_column_1']			= 'sum_amount';
				$aData['db_column_2']			= 'sum_amount_school';
				$aData['db_column_currency']	= 'sum_payment_currency_id';
				$aData['school_id']				= $oSchool->id;
				$aData['format']				= new Ext_Thebing_Gui2_Format_Amount();
				$aData['amount']				= $fAmountTotal;
				$aData['currency_id']			= $iCurrencyTeacherId;
				$aData['school_currency_id']	= $iCurrencySchoolId;
				$aData['disable_all']			= 1;
				$aData['class_name_from']		= 'currency_sum_row_input_from';
				$aData['class_name_to']			= 'currency_sum_row_input_to';


				// SUMME
				$oAmountDiv	= Ext_Thebing_Gui2_Util::getCurrencyAmountRow($oDialog, $aData, '', false, true);
				$oDialog->setElement($oAmountDiv);

				$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

				break;
			default :
				$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
				break;
		}

		return $aData;
	}

	/**
	 * Generiert ein Array mit formatierten Daten aus der GUI-Liste
	 * für den Platzhalter »provider_payment_overview«.
	 *
	 * @see Ext_TS_Accounting_Provider_Grouping_Placeholder_PaymentOverviewTable
	 * @param Ext_Thebing_Teacher_Payment[] $aPayments
	 * @param float $fSum
	 * @return array
	 */
	public function getDataForPaymentOverviewPlaceholder($aPayments, $fSum) {

		$aData = array();
		$oDummy = null;

		// Die Werte müssen neu formatiert werden für das PDF, also entsprechende Klassen holen
		$oFormatWeekAndMonth = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Week();
		$oFormatLessons = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Lesson();
		$oFormatHours = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Hours();
		$oFormatAmount = new Ext_Thebing_Gui2_Format_Amount();
		$oFormatPositionData = new Ext_TS_Accounting_Provider_Grouping_Teacher_Gui2_Format_PositionData();

		foreach($aPayments as $oPayment) {

			// Daten aus enkodierter GUI holen
			$aDecodedData = $this->_oGui->decodeId($oPayment->iSelectedId);

			$aResultData = array(
				'id' => $oPayment->id,
				'payment_type' => $oPayment->payment_type,
				'currency_id' => $oPayment->payment_currency_id,
				'select_type' => $oPayment->payment_type,
				'timepoint' => $oPayment->timepoint,
				'salary_lessons_period' => $oPayment->salary_lessons_period,
				'salary_lessons' => $oPayment->salary_lessons
			);

			// Spalte: Zeitraum
			$sWeeksMonths = $oFormatWeekAndMonth->format(null, $oDummy, $aResultData);

			// Spalte: Lektionen
			$sLessons = $oFormatLessons->format($aResultData['salary_lessons'], $oDummy, $aResultData);

			// Spalte: Stunden
			$sHours = $oFormatHours->format($aDecodedData['hours']);

			// Spalte: Pro Lektion/Monat
			// Die Formatklasse stellt bereits einen benötigten Wrapper bereit
			$oFakeColumn = new stdClass();
			$oFakeColumn->db_column = 'per_lesson_month';
			$sPerLessonMonth = $oFormatPositionData->format(null, $oFakeColumn, $aResultData);

			// Spalte: Betrag
			$sAmount = $oFormatAmount->format($oPayment->amount, $oDummy, $aResultData);

			$aData['rows'][] = array(
				'weeks_months' => $sWeeksMonths,
				'classname' => $aDecodedData['classname'],
				'lessons' => $sLessons,
				'hours' => $sHours,
				'per_lesson_month' => $sPerLessonMonth,
				'amount' => $sAmount,
			);

		}

		// $aResultData: Da eine Gruppierung immer die selbe Währung hat, kann hier weiterhin $aResultData verwendet werden
		$aData['amount_sum'] = $oFormatAmount->format($fSum, $oDummy, $aResultData);

		return $aData;
	}
	
	public static function getOrderby(){
		return ['teacher_id' => 'DESC', 'timepoint' => 'DESC'];
	}
	
	public static function getDefaultFilterFrom(){
		
		$oDate = new WDDate();
		$oDate->set(1, WDDate::DAY);
		$oDate->sub(1, WDDate::MONTH);
		$iFilterStart = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iFilterStart);
	}
	
	public static function getDefaultFilterUntil(){
		
		$oDate = new WDDate();
		$oDate->set(1, WDDate::DAY);
		$oDate->sub(1, WDDate::MONTH);

		$oDate->add(1, WDDate::MONTH);
		$oDate->set($oDate->get(WDDate::MONTH_DAYS), WDDate::DAY);
		$iFilterEnd = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iFilterEnd);
	}
	
	public static function getSalaryStatusSelectOptions(\Ext_Thebing_Gui2 $oGui){
		$aFilterOptions = [
			'yes' => $oGui->t('vorhanden'),
			'no' =>	$oGui->t('nicht vorhanden')
		];
		return $aFilterOptions;
	}

	public function prepareColumnListByRef(&$columnList) {

		parent::prepareColumnListByRef($columnList);
		
		if(System::d('debugmode') == 2) {
			$oColumn = $this->_oGui->createColumn();
			$oColumn->db_column = 'calculation';
			$oColumn->title = 'Calc';
			$columnList[] = $oColumn;
		}
		
	}
	
	public static function getSingleAmountColumnTitle(\Ext_Thebing_Gui2 $oGui) {
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		if(!empty($oSchool->teacher_payment_type)) {
			return $oGui->t('je Stunde/Monat');
		} else {
			return $oGui->t('je Lektion/Monat');
		}	
	}
	
	public static function getTeacherSelectOptions(){
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aTeachers = $oSchool->getTeacherList(true);
		return $aTeachers;
	}
	

}