<?php

use Illuminate\Support\Arr;

class Ext_Thebing_Teacher_Gui2  extends \Ext_Thebing_Document_Gui2 {

	use \Tc\Traits\Gui2\Import;

	public function getSelectedObject(int $iObjectId = null): \Ts\Interfaces\Entity\DocumentRelation
	{
		if ($iObjectId !== null) {
			return $this->_oGui->getWDBasic($iObjectId);
		}

		$id = Arr::first($this->request->input('id'));
		/** @var \Ext_Thebing_Teacher $teacher */
		$teacher = $this->_oGui->getWDBasic($id);

		return $teacher;
	}

	public static function getCostcategoryGui($oGui) {

		/**
		 * Get select values
		 */
		$aDays = Ext_Thebing_Util::getDays();
		
		$aPeriods = Ext_Thebing_Teacher_Salary::getPeriods();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iCurrency = $oSchool->getTeacherCurrency();
		$oCurrency = Ext_Thebing_Currency::getInstance($iCurrency);
		$sSchoolCurrency = $oCurrency->getSign();

		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		
		$oInnerGui = $oGui->createChildGui(md5('thebing_teacher_salary'), 'Ext_Thebing_Teacher_Gui2');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= '';
		$oInnerGui->foreign_key			= 'teacher_id';
		$oInnerGui->foreign_key_alias	= '';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= 0;
		$oInnerGui->multiple_selection  = 0;
		$oInnerGui->row_style			= new Ext_Thebing_Gui2_Style_Teacher_Salary_Row();
		$oInnerGui->calendar_format		= new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Teacher_Salary();

		$oInnerGui->setWDBasic('Ext_Thebing_Teacher_Salary');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('valid_from'=>'DESC'));
		//$oInnerGui->setTableData('where', array('active'=>'1'));

		// Listen Optionen
		$oInnerGui->gui_description 	= $oGui->gui_description;

		// Neu Anlegen
		$oInnerDialog1 = $oInnerGui->createDialog($oGui->t('Vertragsparameter von Lehrer "{teacher_name}" bearbeiten'), $oGui->t('Vertragsparameter für den Lehrer "{teacher_name}" anlegen'));
		$oInnerDialog1->sDialogIDTag = 'TEACHER_SALARY_';

		$oInnerDialog1->setElement($oInnerDialog1->createRow(L10N::t('Schule', $oGui->gui_description), 'select', array('db_alias'=>'kts', 'db_column'=>'school_id', 'selection'=> new \TsTuition\Gui2\Selection\Schools, 'required'=>true)));
		$oInnerDialog1->setElement($oInnerDialog1->createRow(L10N::t('Kostenkategorie', $oGui->gui_description), 'select', 
				array(
					'db_alias'=>'kts', 
					'db_column'=>'costcategory_id', 
					'selection'=> new \TsTuition\Gui2\Selection\CostCategories, 
					'required'=>true,
					'dependency' => [
						[
							'db_column'	=> 'school_id',
							'db_alias' => 'kts',
						]
					],
				)
			)
		);
		
		$oContainer = Ext_Thebing_Gui2_Util::getInputSelectRow($oInnerDialog1, array('db_alias'=>'kts', 'db_column_1'=>'salary', 'db_column_2'=>'salary_period', 'select_options'=>$aPeriods, 'class_1'=>'amount', 'format_1'=>new Ext_Thebing_Gui2_Format_Float()), L10N::t('Gehalt', $oGui->gui_description), $sSchoolCurrency.' '.L10N::t('pro', $oGui->gui_description));
		$oContainer->id = 'salary_container_'.$oInnerGui->hash;
		$oContainer->style = 'display: none;';
		$oInnerDialog1->setElement($oContainer);

		$oInnerDialog1->setElement(Ext_Thebing_Gui2_Util::getInputSelectRow($oInnerDialog1, array('db_alias'=>'kts', 'db_column_1'=>'lessons', 'db_column_2'=>'lessons_period', 'select_options'=>$aPeriods, 'class_1'=>'amount', 'format_1'=>new Ext_Thebing_Gui2_Format_Float()), L10N::t('Lektionen', $oGui->gui_description), L10N::t('Lektionen pro', $oGui->gui_description)));
		$oInnerDialog1->setElement($oInnerDialog1->createRow(L10N::t('Gültig ab', $oGui->gui_description), 'calendar', array('db_alias'=>'kts', 'db_column'=>'valid_from', 'format'=>new Ext_Thebing_Gui2_Format_Date(), 'required'=>true)));
		$oInnerDialog1->setElement($oInnerDialog1->createRow(L10N::t('Kommentar', $oGui->gui_description), 'textarea', array('db_alias'=>'kts', 'db_column'=>'comment')));

		$oInnerDialog1->width = 850;
		$oInnerDialog1->height = 500;


		// Editieren OHNE Datum
		$oInnerDialog2 = $oInnerGui->createDialog($oGui->t('Vertragsparameter von Lehrer "{teacher_name}" bearbeiten'), $oGui->t('Vertragsparameter für den Lehrer "{teacher_name}" anlegen'));
		$oInnerDialog2->sDialogIDTag = 'TEACHER_SALARY_';

		$oInnerDialog2->setElement($oInnerDialog2->createRow(L10N::t('Kostenkategorie', $oGui->gui_description), 'select', array('db_alias'=>'kts', 'db_column'=>'costcategory_id', 'selection'=> new \TsTuition\Gui2\Selection\CostCategories, 'required'=>true)));

		$oContainer = new Ext_Gui2_Html_Div();
		$oContainer->id = 'salary_container_'.$oInnerGui->hash;
		$oContainer->style = 'display: none;';
		$oContainer->setElement(Ext_Thebing_Gui2_Util::getInputSelectRow($oInnerDialog2, array('db_alias'=>'kts', 'db_column_1'=>'salary', 'db_column_2'=>'salary_period', 'select_options'=>$aPeriods, 'class_1'=>'amount', 'format_1'=>new Ext_Thebing_Gui2_Format_Float()), L10N::t('Gehalt', $oGui->gui_description), $sSchoolCurrency.' '.L10N::t('pro', $oGui->gui_description)));
		$oInnerDialog2->setElement($oContainer);

		$oInnerDialog2->setElement(Ext_Thebing_Gui2_Util::getInputSelectRow($oInnerDialog2, array('db_alias'=>'kts', 'db_column_1'=>'lessons', 'db_column_2'=>'lessons_period', 'select_options'=>$aPeriods, 'class_1'=>'amount', 'format_1'=>new Ext_Thebing_Gui2_Format_Float()), L10N::t('Lektionen', $oGui->gui_description), L10N::t('Lektionen pro', $oGui->gui_description)));
		$oInnerDialog2->setElement($oInnerDialog2->createRow(L10N::t('Kommentar', $oGui->gui_description), 'textarea', array('db_alias'=>'kts', 'db_column'=>'comment')));

		$oInnerDialog2->width = 850;
		$oInnerDialog2->height = 500;



		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		/*$oLabelGroup = $oBar->createLabelGroup(L10N::t('Aktionen', $oGui->gui_description));
		$oBar ->setElement($oLabelGroup);*/

		$oIcon = $oBar->createNewIcon(
								L10N::t('Neuer Eintrag', $oGui->gui_description),
								$oInnerDialog1,
								L10N::t('Neuer Eintrag', $oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oIcon = $oBar->createEditIcon(
								L10N::t('Editieren', $oGui->gui_description),
								$oInnerDialog2,
								L10N::t('Editieren', $oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon(
								L10N::t('Löschen', $oGui->gui_description),
								L10N::t('Löschen', $oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		# START - Leiste 3 #
			$oBar = $oInnerGui->createBar();
			$oBar->width = '100%';
			$oBar->position = 'top';

			$oPagination = $oBar->createPagination();
			$oBar ->setElement($oPagination);

			$oLoading = $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);

			$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		$oBarLegend = $oInnerGui->createBar();
		$oBarLegend->position = 'bottom';
		$sGreen = Ext_Thebing_Util::getColor('marked');
		$sHtmlLegend = '<div style="float: left"><b>' . L10N::t('Status', $oGui->gui_description) . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . L10N::t('Aktiv', $oGui->gui_description) . '</div> <div class="colorkey" style="background-color: '.$sGreen.'" ></div>';
		$oHtml = $oBarLegend->createHtml($sHtmlLegend);
		$oBarLegend ->setElement($oHtml);
 
		$oInnerGui->setBar($oBarLegend);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'school_id';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Schule', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = false;
		$oColumn->sortable = false;
		$oColumn->format = new Ext_Gui2_View_Format_Selection($aSchools);
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'costcategory_id';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Kostenkategorie', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Teacher_Costcategory();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'salary';
		$oColumn->db_alias		= 'su';
		$oColumn->title			= L10N::t('Gehalt', $oGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Teacher_Salary();
		$oColumn->sortable		= false;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'valid_from';
		$oColumn->db_alias		= 'kts';
		$oColumn->title			= L10N::t('Gültig ab', $oGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'valid_until';
		$oColumn->db_alias		= 'kts';
		$oColumn->title			= L10N::t('Gültig bis', $oGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'comment';
		$oColumn->db_alias		= 'kts';
		$oColumn->title			= L10N::t('Kommentar', $oGui->gui_description);
		$oColumn->width			= 200;
		$oColumn->width_resize	= true;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'user_id';
		$oColumn->db_alias		= 'kts';
		$oColumn->title			= L10N::t('Bearbeiter', $oGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('user_name');
		$oColumn->width_resize	= false;
		$oColumn->inplaceEditor	= 0;
		$oColumn->format		= new Ext_Gui2_View_Format_UserName();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'changed';
		$oColumn->db_alias		= 'kts';
		$oColumn->db_type		= 'timestamp';
		$oColumn->title			= L10N::t('Verändert', $oGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date_DateTime();
		$oInnerGui->setColumn($oColumn);

		return $oInnerGui;

	}

	public static function getContractGui($oGui) {

		$sL10NDescription = 'Thebing » Tuition » Teachers » Contracts';
		$sAccessRight = 'thebing_tuition_teacher_contracts';
		$sGuiKey = 'thebing_tuition_teacher_contracts';
		$sGuiTitle = 'Lehrerverträge';
		$sSection = 'teacher_contracts';
		$sItem = 'teacher';
		$sItemLabel = 'Lehrer';

		$oInnerGui = $oGui->createChildGui(md5($sGuiKey), 'Ext_Thebing_Contract_Gui2');
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		$oInnerGui->setWDBasic('Ext_Thebing_Contract_Version');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('kcont.date'=>'DESC'));
		$oInnerGui->setTableData('where', array('kcont.school_id'=>(int)$iSessionSchoolId, 'kcont.item'=>$sItem, 'kcont.active'=>1, 'kcontv.active'=>1));

		// Listen Optionen
		$oInnerGui->gui_description 	= $sL10NDescription;
		$oInnerGui->gui_title			= L10N::t($sGuiTitle, $oInnerGui->gui_description);
		$oInnerGui->column_sortable		= 1;
		$oInnerGui->row_sortable		= 0;
		$oInnerGui->multiple_selection 	= 0;
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kcontv';
		$oInnerGui->load_admin_header	= false;

		$oInnerGui->foreign_key			= 'item_id';
		$oInnerGui->foreign_key_alias	= 'kcont';
		$oInnerGui->parent_primary_key	= 'id';

		$oInnerGui->sSection			= $sSection;
		$oInnerGui->calendar_format		= new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Contract();

		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		$oLabelGroup = $oBar->createLabelGroup(L10N::t('Details', $oInnerGui->gui_description));
		$oBar ->setElement($oLabelGroup);

		$oIcon = $oBar->createIcon(
									Ext_Thebing_Util::getIcon('pdf'),
									'request',
									L10N::t('Vertragsversion öffnen', $oInnerGui->gui_description)
								);
		$oIcon->label				= L10N::t('Vertragsversion öffnen', $oInnerGui->gui_description);
		$oIcon->action				= 'contract_open';
		$oIcon->dbl_click_element	= 1;
		$oBar->setElement($oIcon);

		$oInnerGui->setBar($oBar);

		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';

		$oPagination = $oBar->createPagination();
		$oBar ->setElement($oPagination);

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);

		$oInnerGui->setBar($oBar);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'number';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Vertrag', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = false;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'name';
		$oColumn->db_alias = 'kcontt';
		$oColumn->select_column = 'template_name';
		$oColumn->title = L10N::t('Vorlage', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'date';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Vertragsdatum', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'valid_from';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Laufzeit', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_period');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Contract_Period();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'sent';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Versendet', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_user');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Contract_DateUser();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'confirmed';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Bestätigt', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_user');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Contract_DateUser();
		$oInnerGui->setColumn($oColumn);

		$oDefaultColumn = $oInnerGui->getDefaultColumn();
		$oDefaultColumn->setAliasForAll('kcontv');
		$oInnerGui->setDefaultColumn($oDefaultColumn);

		$oInnerGui->addDefaultColumns();

		return $oInnerGui;

	}

	/**
	 * @deprecated
	 * 
	 * @param type $oGui
	 * @return type
	 */
	public static function getScheduleGui($oGui) {

		/**
		 * Get select values
		 */

		$oInnerGui = $oGui->createChildGui(md5('thebing_teacher_schedule'), 'Ext_Thebing_Teacher_Gui2');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kts';
		$oInnerGui->foreign_key			= 'idTeacher';
		$oInnerGui->foreign_key_alias	= 'kts';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;

		$oInnerGui->setWDBasic('Ext_Thebing_Teacher_Schedule');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('idDay'=>'ASC'));

		// Listen Optionen
		$oInnerGui->gui_description 	= $oGui->gui_description;

		

		$oInnerDialog->width = 850;
		$oInnerDialog->height = 300;

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		/*$oLabelGroup = $oBar->createLabelGroup(L10N::t('Aktionen', $oGui->gui_description));
		$oBar ->setElement($oLabelGroup);*/

		$oIcon = $oBar->createNewIcon(
								L10N::t('Neuer Eintrag', $oGui->gui_description),
								$oInnerDialog,
								L10N::t('Neuer Eintrag', $oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oIcon = $oBar->createEditIcon(
								L10N::t('Editieren', $oGui->gui_description),
								$oInnerDialog,
								L10N::t('Editieren', $oGui->gui_description)
							);
		$oBar ->setElement($oIcon);


		$oIcon = $oBar->createDeleteIcon(
								L10N::t('Löschen', $oGui->gui_description),
								L10N::t('Löschen', $oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		# START - Leiste 3 #
			$oBar = $oInnerGui->createBar();
			$oBar->width = '100%';
			$oBar->position = 'top';

			$oPagination = $oBar->createPagination();
			$oBar ->setElement($oPagination);

			$oLoading = $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);

			$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'idDay';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Tag', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Day();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'timeFrom';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Von', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('time');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Time();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'timeTo';
		$oColumn->db_alias = '';
		$oColumn->title = L10N::t('Bis', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('time');
		$oColumn->width_resize = false;
		$oColumn->inplaceEditor = 0;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Time();
		$oInnerGui->setColumn($oColumn);

		return $oInnerGui;

	}

	protected function _getErrorMessage($sError, $sField='', $sLabel='', $sAction = null, $sAdditional = null){
		
		$sMessage = '';
		
		switch($sError){
			case 'INVALID_DATE':
				// #2817 umbenannt in etwas allgemeines da es beim deaktivieren sonst keinen sinn ergab
				$sMessage = L10N::t('Das Datum ist in einem falschen Format angegeben.', 'Thebing » Errors');
				break;
			case 'INVALID_DATE_PAST':
				$sMessage = L10N::t('Person ist noch nicht geboren.', 'Thebing » Errors');
				break;
			case 'COSTCATEGORY_NOT_CHANGABLE':
				$sMessage = $this->t('Es existieren noch Zahlungen zu dieser Kostenkategorie. Die Kostenkategorie darf nicht verändert werden.');
				break;
			case 'SALARY_PERIOD_NOT_CHANGABLE':
				$sMessage = $this->t('Es existieren noch Zahlungen. Die Kostenart darf nicht verändert werden.');
				break;
			case 'PAYMENTS_EXISTS':
				$sMessage = $this->t('Es existieren noch Zahlungen.');
				break;
			case 'DEACTIVATE_ERROR_BLOCKS_EXISTS':
				$sMessage = $this->t('Deaktivieren nicht möglich. Es existieren noch Zuweisungen in der Klassenplanung.');
				break;
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
		
		return $sMessage;
	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		return new \Ts\Service\Import\Teacher();
	}

	protected function getImportDialogId() {
		return 'TEACHER_IMPORT_';
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {

		$oRow = $oDialog->createRow($this->t('Vorhandene Vertragsparameter leeren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'delete_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren (werden anhand der E-Mail-Adresse erkannt)'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Fehler überspringen'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'skip_errors']);
		$oDialog->setElement($oRow);
		
	}

	public function getDialogHTML(&$iconAction, &$dialog, $selectedIds = array(), $additional = false) {

		if ($iconAction === 'additional_document') {

			global $_VARS;
			// Data-Class für den Dokumenten-Dialog setzen, geht leider nicht anders
			$_VARS['data_class'] = \TsTuition\Gui2\Data\Teacher\Document::class;

			$documentHelper = new \Ext_Thebing_Document();
			$this->_oGui->setOption('document_class', $documentHelper);

			$dialog = $documentHelper->getEditDialog($this->_oGui, 0, 'teacher', $selectedIds);

		}

		return parent::getDialogHTML($iconAction, $dialog, $selectedIds, $additional);
	}

}
