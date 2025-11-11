<?php

/**
 * Modulverwaltung GUI2 Ableitung
 */
class Ext_TC_User_Gui2 extends Ext_TC_Gui2_Data {

	static $aAccess = array('core_admin_user', '');

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){
		global $_VARS;
		
		$aTransfer = array();

		if($sAction === 'access') {

			$iUserID = reset($aSelectedIds);
			$aAccessList = array();
			$oUser = Ext_TC_User::getInstance($iUserID);
			foreach((array)$_VARS['access'] as $iCategory => $aData){
				foreach((array)$aData as $iSection => $aAccess){
					foreach((array)$aAccess as $iAccess => $iStatus){
						if($iStatus != -1){
							$aAccessList[] = array('user_id' => (int)$iUserID, 'access_id' => (int)$iAccess, 'status' => (int)$iStatus);
						}
					}
				}
			}

			$oUser->accessJoin = $aAccessList;
			$oUser->save();

			\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);

			Ext_TC_User::resetAccessCache();

			$aData = $this->prepareOpenDialog($sAction, $aSelectedIds);
			$aTransfer					= array();
			$aTransfer['action'] 		= 'saveDialogCallback';
			$aTransfer['dialog_id_tag']	= 'ACCESS_';
			$aTransfer['error'] 		= array();
			$aTransfer['data'] 			= $aData;
			$aTransfer['save_id'] 		= reset($aSelectedIds);

		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}

		return $aTransfer;
	}
	
	/**
	 * Erzeugt ein Array mit den HTML und Tab Daten
	 *
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param string $sAdditional
	 * @return array 
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional=false){

		if($sIconAction == 'access') {
			$aSelectedIds = (array)$aSelectedIds;
			$iUser = reset($aSelectedIds);
			$oUser = Ext_TC_User::getInstance($iUser);
			$aSavedAccess = (array)$oUser->accessJoin;
			$aGroupData = $oUser->getGroupAccessData();
			$oDialogData = static::getAccessDialog($this->_oGui, $aSavedAccess, true, 'core', $aGroupData);

			$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		} else {
			$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
		}

		return $aData;

	}
		
	/**
	 * Das Modul aus der Zwischentabelle entfernen
	 * @param int $iRowId 
	 */
	protected function deleteRowHook($iRowId) {

		$aSql = array(
			'row_id' => $iRowId
		);
		
		$sSql = "
			DELETE FROM
				`tc_system_user_to_access`
			WHERE
				`user_id` = :row_id
		";
		
		DB::executePreparedQuery($sSql, $aSql);

		// Unique Daten bearbeiten
		$oUser = \Factory::getInstance(\Ext_TC_User::class, $iRowId);
		$oUser->username = Ext_TC_Util::generateRandomString(8).'_'.$oUser->username;
		$oUser->email = Ext_TC_Util::generateRandomString(8).'_'.$oUser->email;
		$oUser->save(false);

	}

	public function requestRemoveSecret($_VARS) {

		foreach((array)$_VARS['id'] as $iId) {
			$oUser = Ext_TC_Factory::getInstance('Ext_TC_User', $iId);
			$oUser->secret = '';
			$oUser->save();
		}

		$aTransfer = array();
		$aTransfer['action'] 	= 'loadTable';
		$aTransfer['error'] 	= array();

		return $aTransfer;
	}

	public function requestUnblock($_VARS) {

		foreach((array)$_VARS['id'] as $iId) {
			$oUser = Ext_TC_Factory::getInstance('Ext_TC_User', $iId);
			$oUser->unblockLogin();
		}

		$aTransfer = array();
		$aTransfer['action'] 	= 'loadTable';
		$aTransfer['error'] 	= array();

		return $aTransfer;
	}

	/**
	 *
	 * @param \Ext_Gui2 $oGui (Gui2 Class for ->t() )
	 * @param array $aSavedAccess (saved accesses, needed for check the checkboxes )
	 * @param boolean $bOnlyForCurrentAccess (display only licence access )
	 * @param string $sCurrentType
	 * @param array $aDataForComparison (list with Access IDs)
	 * @return Ext_Gui2_Dialog
	 */
	public static function getAccessDialog(\Ext_Gui2 $oGui, $aSavedAccess = [], $bOnlyForCurrentAccess = false, $sCurrentType = 'core', $aDataForComparison = null){

		$bComparData = false;
		if(is_array($aDataForComparison)){
			$bComparData = true;
		} else {
			$aDataForComparison = array();
		}


		// Wenn verglichen wird gibt es keine Checkboxen sondern Selects
		// dann muss aSacedAccess ein array mit arrays sein ( access_id - status (0/1) verknÃ¼pfung)
		// daher hier das array fÃ¼r die unten folgenden vergleiche neu anordnen
		if($bComparData){
			$aSavedAccessNew = array();

			foreach((array)$aSavedAccess as $aData){
				$aSavedAccessNew[$aData['access_id']] = $aData['status'];
			}
			$aSavedAccess = $aSavedAccessNew;
		}

		$oDialogData = new Ext_Gui2_Dialog($oGui->t('Rechte bearbeiten'), $oGui->t('Rechte bearbeiten'));
		$oDialogData->width = 1200;
		$oDialogData->sDialogIDTag = 'ACCESS_';

		$oTemp = new Ext_TC_Access_Section_Category();
		$aCategories = $oTemp->getObjectList();

		$aList = array();
		foreach((array)$aCategories as $iCKey => $oCategory){
			$aSections = $oCategory->getJoinedObjectChilds('sections');
			foreach((array)$aSections as $iSKey => $oSection){
				/* @var \Ext_TC_Access_Section $oSection */
				$aAccess = $oSection->getJoinedObjectChilds('access');
				foreach((array)$aAccess as $iAKey => $oAccess){
					/* @var \Ext_TC_Access $oAccess */
					if(
						!$bOnlyForCurrentAccess ||
						// Mit Customer-Access-File abgleichen
						$oAccess->check()
					){
						$aList[$oCategory->id][$oSection->id][] = $oAccess;
					}
				}
			}
		}

		foreach((array)$aList as $iCategory => $aSections){

			$oCategory = Ext_TC_Access_Section_Category::getInstance($iCategory);

			if(
				$oCategory->type != $sCurrentType &&
				$oCategory->type != 'core' &&
				$sCurrentType != 'core'
			){
				continue;
			}

			$oTab = $oDialogData->createTab($oCategory->name);
			$oTab->class = 'v-scrolling';

				if(!$bComparData){
					$oCheckboxAll = new Ext_Gui2_Html_Input();
					$oCheckboxAll->type = "checkbox";
					$oCheckboxAll->class = "category_all_checkbox";
					$oCheckboxAll->value = 'checkbox_'.$oCategory->id;
					$oRow = $oDialogData->createRow($oGui->t('Alles auswählen/abwählen'), $oCheckboxAll, array('css'));
					$oTab->setElement($oRow);
				}

				foreach((array)$aSections as $iSection => $aAccess){

					$oSection = Ext_TC_Access_Section::getInstance($iSection);

					$oH3 = new Ext_Gui2_Html_H4();
					$oH3->setElement($oSection->name);
					$oTab->setElement($oH3);

					$oTable = new Ext_Gui2_Html_Table();
					$oTable->class = "table table-bordered";
					$oTr = new Ext_Gui2_Html_Table_Tr();

					if(!$bComparData){
						$oTH = new Ext_Gui2_Html_Table_Tr_Th();
						$oTH->style = "width: 100px;";
						$oTH->setElement($oGui->t('Alle Rechte'));
						$oTr->setElement($oTH);
					}

					foreach((array)$aAccess as $oAccess){
						$oTH = new Ext_Gui2_Html_Table_Tr_Th();
						$oTH->style = "width: 100px;";
						$oTH->setElement($oAccess->name);
						$oTr->setElement($oTH);
					}

					$oTable->setElement($oTr);

					$oTr = new Ext_Gui2_Html_Table_Tr();
					if(!$bComparData){
						$oTD = new Ext_Gui2_Html_Table_Tr_Td();
						$oCheckbox = new Ext_Gui2_Html_Input();
						$oCheckbox->type = "checkbox";
						$oCheckbox->class = "section_all_checkbox checkbox_".$oCategory->id;
						$oCheckbox->value = 'checkbox_'.$oCategory->id."_".$oSection->id;

						$oTD->setElement($oCheckbox);

						$oTr->setElement($oTD);
					}
					foreach((array)$aAccess as $oAccess){

						$oTD = new Ext_Gui2_Html_Table_Tr_Td();

						if(!$bComparData){
							$oCheckbox = new Ext_Gui2_Html_Input();
							$oCheckbox->type = "checkbox";
							$oCheckbox->class = "checkbox_".$oCategory->id."_".$oSection->id;
							$oCheckbox->name = "access[".$oCategory->id."][".$oSection->id."][".$oAccess->id."]";
							$oCheckbox->value = $oAccess->id;
							if(in_array($oAccess->id, $aSavedAccess)){
								$oCheckbox->checked = "checked";
							}
							$oTD->setElement($oCheckbox);
						} else {
							if(in_array($oAccess->id, $aDataForComparison)){
								$oTD->style = "background-color:green;";
							} else {
								$oTD->style = "background-color:red;";
							}
							$oSelect = new Ext_Gui2_Html_Select();
							$oSelect->class = 'form-control input-sm';
							$oSelect->name = "access[".$oCategory->id."][".$oSection->id."][".$oAccess->id."]";
							$oOption = new Ext_Gui2_Html_Option();
							$oOption->value = "-1";
							$oOption->setElement($oGui->t('wie Gruppe'));
							$oSelect->setElement($oOption);
							$oOption = new Ext_Gui2_Html_Option();
							$oOption->value = "1";
							$oOption->setElement($oGui->t('erlauben'));
							if(
								isset ($aSavedAccess[$oAccess->id]) &&
								$aSavedAccess[$oAccess->id] == 1
							){
								$oOption->selected = "selected";
							}
							$oSelect->setElement($oOption);
							$oOption = new Ext_Gui2_Html_Option();
							$oOption->value = "0";
							$oOption->setElement($oGui->t('verbieten'));
							if(
								isset ($aSavedAccess[$oAccess->id]) &&
								$aSavedAccess[$oAccess->id] == 0
							){
								$oOption->selected = "selected";
							}
							$oSelect->setElement($oOption);
							$oTD->setElement($oSelect);
						}
						$oTr->setElement($oTD);
					}

					$oTable->setElement($oTr);
					$oTab->setElement($oTable);
				}

			$oDialogData->setElement($oTab);

		}

		return $oDialogData;
	}

	/**
	 *
	 * @param Ext_Gui2_Bar $oBar
	 */
	public static function getGuiIconBarHook($oBar, $oGui) {

	}

	public static function getGuiCreateHook() {

	}

	/**
	 * Get the GUI for the Current System
	 * @param string $sSystem
	 * @param mixed $sAccess
	 * @return Ext_TC_Gui2
	 */
	public static function createGui(){

		static::getGuiCreateHook();

		$sUserClassName = Factory::getClassName(\Ext_TC_User::class);

		$sDataClass = Factory::getClassName(\Ext_TC_User_Gui2::class);

		$oGui = new Ext_TC_Gui2(md5('tc_admin_user'), $sDataClass);

		$oGui->calendar_format = Factory::getObject('Ext_TC_Gui2_Format_Date');

		$oGui->gui_description		= Factory::executeStatic(\Ext_TC_System_Navigation::class, 'tp');
		$oGui->gui_title			= Factory::executeStatic(\Ext_TC_System_Navigation::class, 't');
		$oGui->setWDBasic($sUserClassName);

		$oGui->setTableData('orderby', array('su.lastname'=>'ASC', 'su.firstname'=>'ASC'));

		#$oGui->access = static::$aAccess;
		$oGui->query_id_alias		= 'su';
		$oGui->multiple_selection	= 0;

		$oGui->row_style				= new Ext_TC_User_Gui2_Style;
		$oGui->row_icon_status_active	= Factory::getObject(\Ext_TC_User_Gui2_Icon::class);

		$oGui->include_jquery = true;
		$oGui->include_jquery_multiselect = true;

		$oGui->sSection = 'admin_users';

		$oGui->class_js = 'UserGUI';
		
		$oDialogNew		= static::getDialog($oGui);
		$oDialogEdit	= static::getDialog($oGui, false);

		$oDialogEdit->access = array(static::$aAccess[0], 'edit');

		// Suche
		$oBar						= $oGui->createBar();

		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= array('username', 'firstname', 'lastname');
		$oFilter->db_alias			= array('su', 'su', 'su');
		$oFilter->db_operator		= 'LIKE';
		$oFilter->id				= 'search';
		$oFilter->placeholder		= $oGui->t('Suche').'…';
		$oBar->setElement($oFilter);

		$oSeperator = $oBar->createSeperator();
		$oBar->setElement($oSeperator);

		$aSystemTypes = \Tc\Entity\SystemTypeMapping::getSelectOptions(Ext_TC_User::MAPPING_TYPE);

		$oFilter = $oBar->createFilter('select');
		$oFilter->db_column = 'category_id';
		$oFilter->db_alias = 'tc_etc';
		$oFilter->id = 'category_filter';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($aSystemTypes,'--'.$oGui->t('Kategorien').'--');
		$oBar ->setElement($oFilter);

		$aStatusOptions			= array();
		$aStatusOptions['status_1']	= L10N::t('aktiv');
		$aStatusOptions['status_0']	= L10N::t('inaktiv');

		$oFilter = $oBar->createFilter('select');
		$oFilter->db_column = 'status';
		$oFilter->id = 'status_filter';
		$oFilter->value = 'status_1';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($aStatusOptions,'--'.$oGui->t('Status').'--');
		$oFilter->filter_query = array(
			'status_1' => "
				`su`.`status` = 1 
			",
			'status_0' => "
				`su`.`status` = 0
			 "
		);
		$oBar ->setElement($oFilter);

		$oGui->setBar($oBar);

		// Aktionen
		$oBar			= $oGui->createBar();

		$oIcon			= $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialogNew, $oGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);

		$oIcon			= $oBar->createEditIcon($oGui->t('Editieren'), $oDialogEdit, $oGui->t('Editieren'));
		$oBar->setElement($oIcon);

		$oIcon			= $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oBar->setElement($oIcon);

		$oDialogAccess = static::getAccessDialog($oGui);

		$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('access'), 'openDialog', $oGui->t('Rechte bearbeiten'), $oGui->t('Rechte bearbeiten'));
		$oIcon->access = [static::$aAccess[0], 'access'];
		$oIcon->label = $oIcon->title;
		$oIcon->dialog_data = $oDialogAccess;
		$oIcon->action = 'access';
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createFileManagerIcon();
		$oBar->setElement($oIcon);
		
		$oIcon = $oBar->createNoticesIcon();
		$oBar->setElement($oIcon);
		
		$oIcon = $oBar->createIcon(
			'fa-unlock',
			'request',
			$oGui->t('Entsperren')
		);
		$oIcon->label			= $oGui->t('Entsperren');
		$oIcon->action			= 'unblock';
		$oBar->setElement($oIcon);



		////////////// SECRET \\\\\\\\\\\\\\\\
		$oIcon = $oBar->createIcon(
			'fa-eraser',
			'request',
			$oGui->t('Authentifizierungs-Schlüssel zurücksetzen')
		);
		$oIcon->label			= $oGui->t('Authentifizierungs-Schlüssel zurücksetzen');
		$oIcon->action			= 'remove-secret';
		$oBar->setElement($oIcon);

		static::getGuiIconBarHook($oBar, $oGui);

		$oGui->setBar($oBar);



		// Paginator
		$oBar			= $oGui->createBar();
		$oBar->position	= 'top';
		$oPagination	= $oBar->createPagination();
		$oBar->setElement($oPagination);
		$oBar->createCSVExportWithLabel();
		$oLoading		= $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGui->setBar($oBar);

		//Spalten
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'lastname';
		$oColumn->db_alias		= 'su';
		$oColumn->order_settings = array('lastname'=>'ASC', 'firstname'=>'ASC');
		$oColumn->title			= $oGui->t('Name');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oColumn->format		= new Ext_TC_Gui2_Format_Name();
		$oGui->setColumn($oColumn);

		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'lastlogin';
		$oColumn->db_alias		= 'su';
		$oColumn->title			= $oGui->t('Letzter Login');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('date_time');
		$oColumn->width_resize	= false;
		$oColumn->format = Factory::getObject('Ext_TC_Gui2_Format_Date_Time');
		$oGui->setColumn($oColumn);

		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'type';
		$oColumn->title			= $oGui->t('Kategorie');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->default 		= false;
		$oGui->setColumn($oColumn);

		// Inhalt wird ausschliesslich über Formatklasse geholt, daher keine Sortierung!
/*		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'send_email_from';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oGui->t('Absender-E-Mail-Adresse');
		$oColumn->mouseover_title = $oGui->t('Von dieser E-Mail-Adresse senden');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('email');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_User_Format_Email();
		$oColumn->sortable		= false;
		$oGui->setColumn($oColumn);*/

		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'email';
		$oColumn->db_alias		= 'su';
		$oColumn->title			= $oGui->t('E-Mail');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('email');
		$oColumn->width_resize	= false;
		$oGui->setColumn($oColumn);

		static::addAdditionalColumns($oGui);

		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'status';
		$oColumn->db_alias		= 'su';
		$oColumn->title			= $oGui->t('Aktiv');
		$oColumn->small			= true;
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('yes_no');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_Gui2_Format_YesNo();
		$oGui->setColumn($oColumn);

		$oGui->addDefaultColumns();

		// Farblegende
		$sHtmlLegend = '';
		$sHtmlLegend .= '<div style="float: left"><strong>' . $oGui->t('Legende') . ': </strong>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('Hauptbenutzer') . '</div> <div class="colorkey" style="background-color: '.Ext_TC_Util::getColor('highlight', 40).'" ></div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('Gesperrt') . '</div> <div class="colorkey" style="background-color: '.Ext_TC_Util::getColor('bad').'" ></div>';
        $sHtmlLegend .= '<div style="float: left">' . $oGui->t('Veränderte Rechte') . '</div> <div class="colorkey" style="background-color: '.Ext_TC_Util::getColor('neutral').'" ></div>';

		$oBarLegend = new Ext_Gui2_Bar($oGui);
		$oBarLegend->position = 'bottom';
		$oHtml = $oBarLegend->createHtml($sHtmlLegend);
		$oBarLegend ->setElement($oHtml);

		$oGui->setBar($oBarLegend);

		$aOptionalInfo = array();

		return $oGui;
	}

	static public function addAdditionalColumns(Ext_Gui2 $oGui) {

	}

	/**
	 * Get The Dialog fpr the Current System and the User GUI
	 * @param Ext_Gui2 $oGui
	 * @param boolean $bNew
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog($oGui, $bNew = true){

		$oDialog = $oGui->createDialog($oGui->t('Benutzer "{name}" editieren'), $oGui->t('Neuen Benutzer anlegen'));

		$oGui->i18n_languages = Factory::executeStatic(\Ext_TC_Util::class, 'getTranslationLanguages');

		$oTab = $oDialog->createTab($oGui->t('Logindaten'));
		$oTab->aOptions['section'] = 'admin_user_login_data';

		$oTab->setElement($oDialog->createRow($oGui->t('Aktiv'), 'checkbox', array('db_column' => 'status', 'db_alias' => 'su','default_value' => 1)));
		$oTab->setElement($oDialog->createRow($oGui->t('Loginname'), 'input', array('db_column' => 'username', 'db_alias' => 'su', 'required' => 1)));
        $oTab->setElement($oDialog->createRow($oGui->t('Passwort'), 'input', array('db_column' => 'password', 'db_alias' => 'su', 'required' => $bNew)));
		$oTab->setElement($oDialog->createRow($oGui->t('E-Mail'), 'input', array('db_column' => 'email', 'db_alias' => 'su', 'required' => 1)));

		$oTab->setElement($oDialog->createRow($oGui->t('Hauptbenutzer'), 'checkbox', array('db_column' => 'access', 'db_alias' => 'su', 'value'=>'admin')));

		$oDialog->setElement($oTab);

        // --- Persönliche Daten ---
		$oDialog->setElement(
            self::getUserPersonalDataTab($oGui, $oDialog)
        );


		// --- E-Mail Einstellungen ---
		$oDialog->setElement(
            self::getUserEmailSettingsTab($oGui, $oDialog)
        );

		// --- Signatureinstellungen ---
		$oDialog->setElement(
            self::getUserEmailSettingsTab($oGui, $oDialog)
        );


		$oTab = Ext_TC_User::getGroupTab($oGui, $oDialog);
		$oDialog->setElement($oTab);

		$oDialog->save_as_new_button  = true;
		$oDialog->save_bar_options   = true;
		$oDialog->save_bar_default_option = 'open';

		return $oDialog;
	}

    protected static function getUserPersonalDataTab(\Ext_Gui2 $oGui2, Ext_Gui2_Dialog $oDialog): Ext_Gui2_Dialog_Tab {
        $oTab = $oDialog->createTab($oGui2->t('Persönliche Daten'));
        $oTab->aOptions['section'] = 'admin_user_personal_data';
        $oTab->setElement($oDialog->createRow($oGui2->t('Vorname'), 'input', array('db_column' => 'firstname', 'db_alias' => 'su', 'required' => 1)));
        $oTab->setElement($oDialog->createRow($oGui2->t('Nachname'), 'input', array('db_column' => 'lastname', 'db_alias' => 'su', 'required' => 1)));
        $oTab->setElement($oDialog->createRow($oGui2->t('Geschlecht'), 'select', array('db_column' => 'sex', 'db_alias' => 'su', 'select_options' => Ext_TC_Util::getGenders())));

        return $oTab;
    }

    protected static function getUserEmailSettingsTab(\Ext_Gui2 $oGui2, Ext_Gui2_Dialog $oDialog): Ext_Gui2_Dialog_Tab {

        $aExportSeparator = Ext_TC_Export::getSeparatorOptions();
        $aExportSeparator = Ext_TC_Util::addEmptyItem($aExportSeparator, $oGui2->t('Einstellung der Agentur verwenden'));
        $aExportCharset = Ext_TC_Export::getCharsetOptions();
        $aExportCharset = Ext_TC_Util::addEmptyItem($aExportCharset, $oGui2->t('Einstellung der Agentur verwenden'));

        $oTab = $oDialog->createTab($oGui2->t('Einstellungen'));
        $oTab->access = array('core_admin_emailaccounts', '');

        $oH3 = $oDialog->create('h4');
        $oH3->setElement($oGui2->t('E-Mail'));
        $oTab->setElement($oH3);

        $oTab->setElement($oDialog->createRow($oGui2->t('Senden von E-Mail-Adresse'), 'select', array(
            'db_alias' => 'su',
            'db_column' => 'send_email_account',
            'selection' => new Ext_TC_User_Selection_EmailAccount(),
            'required' => true
        )));

        $oTab->setElement($oDialog->createRow($oGui2->t('Absender-Identitäten'), 'select', array(
            'db_alias' => 'su',
            'db_column' => 'email_identities',
            'multiple' => 5,
            'jquery_multiple' => 1,
            'select_options' => \Ext_TC_Factory::executeStatic('Ext_TC_User', 'getSelectOptions'),
            'searchable' => 1,
        )));

        $oH3 = $oDialog->create('h4');
        $oH3->setElement($oGui2->t('Export'));
        $oTab->setElement($oH3);

        $oTab->setElement($oDialog->createRow($oGui2->t('CSV-Trennzeichen'), 'select', array(
            'db_alias' => 'tc_s_u_e',
            'db_column' => 'csv_separator',
            'select_options' => $aExportSeparator
        )));

        $oTab->setElement($oDialog->createRow($oGui2->t('CSV-Zeichenkodierung'), 'select', array(
            'db_alias' => 'tc_s_u_e',
            'db_column' => 'csv_charset',
            'select_options' => $aExportCharset
        )));

        return $oTab;
    }

    protected static function getUserSignaturesTab(\Ext_Gui2 $oGui2, Ext_Gui2_Dialog $oDialog): Ext_Gui2_Dialog_Tab {

        $aSubObjects = Ext_TC_Util::addEmptyItem(Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true)));
        $sSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel', array(false));

        $oTab = $oDialog->createTab($oGui2->t('Signatur'));

        $oTab->setElement($oDialog->createRow($oGui2->t('Vorname'), 'input', array(
            'db_alias' => 'su',
            'db_column' => 'signature_firstname'
        )));

        $oTab->setElement($oDialog->createRow($oGui2->t('Nachname'), 'input', array(
            'db_alias' => 'su',
            'db_column' => 'signature_lastname'
        )));

        $oJoinContainer = $oDialog->createJoinedObjectContainer('signatures', array('min' => 0, 'max' => 255));

        /**
         * @TODO AUF SELECT OPTIONS UMSTELLEN
         */
        $oJoinContainer->setElement($oJoinContainer->createRow($oGui2->t($sSubObjectLabel), 'select', array(
            'db_alias' => 'tc_sus',
            'db_column' => 'object_id',
            'select_options' => $aSubObjects,
            'joined_object_key' => 'signatures',
            'required' => true,
            'dependency' => array(
                array(
                    'db_alias' => 'tc_sus',
                    'db_column' => 'object_id'
                )
            )
        )));

        $oJoinContainer->setElement($oJoinContainer->createRow($oGui2->t('E-Mail'), 'input', array(
            'db_alias' => 'tc_sus',
            'db_column' => 'email',
            'joined_object_key' => 'signatures'
        )));

        $oJoinContainer->setElement($oJoinContainer->createRow($oGui2->t('Telefon'), 'input', array(
            'db_alias' => 'tc_sus',
            'db_column' => 'phone',
            'joined_object_key' => 'signatures'
        )));

        $oJoinContainer->setElement($oJoinContainer->createRow($oGui2->t('Fax'), 'input', array(
            'db_alias' => 'tc_sus',
            'db_column' => 'fax',
            'joined_object_key' => 'signatures'
        )));

        $oJoinContainer->setElement($oJoinContainer->createRow($oGui2->t('Skype'), 'input', array(
            'db_alias' => 'tc_sus',
            'db_column' => 'skype',
            'joined_object_key' => 'signatures'
        )));

        $oJoinContainer->setElement($oDialog->createI18NRow($oGui2->t('Titel'), array(
            'db_alias' => 'titles_i18n',
            'db_column' => 'title',
            'i18n_parent_column' => 'user_id',
            'joined_object_key' => 'signatures'
        )));

        $oTab->setElement($oJoinContainer);

        return $oTab;
    }

	public function getTranslations($sL10NDescription){

		$aData = parent::getTranslations($sL10NDescription);

		$aData['very_week'] = L10N::t('Ganz schwach', 'Login');
		$aData['week'] = L10N::t('Schwach', 'Login');
		$aData['sufficient'] = L10N::t('Ausreichend', 'Login');
		$aData['good'] = L10N::t('Gut', 'Login');
		$aData['very_good'] = L10N::t('Sehr gut', 'Login');
		$aData['password_strength'] = L10N::t('Passwort-Stärke', 'Login');
		
		return $aData;

	}
	
}
