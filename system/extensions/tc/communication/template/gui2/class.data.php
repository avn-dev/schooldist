<?php

/**
 * GUI2-Ableitung der Templates
 */
class Ext_TC_Communication_Template_Gui2_Data extends Ext_TC_Gui2_Data {

	use \Tc\Traits\Gui2\ImageTab;
	public function switchAjaxRequest($_VARS) {

		if($_VARS['task'] == 'getLayoutCode') {

			$aTransfer = array();
			$sLayout = '';
			$aTransfer['action'] = 'openPreviewTemplate';

			$oLayout = Ext_TC_Communication_Template_Email_Layout::getInstance((int)$_VARS['layout_id']);
			//$oContent = $oLayout->getJoinedObjectChildByValue('contents', 'language_iso', $_VARS['language_code']);

			if(
				!is_null($oLayout)
			) {
				$sLayout = $oLayout->html;
			}

			$aTransfer['layout'] = $sLayout;
			$aTransfer['data']['id'] = $_VARS['dialog_id'];
			$aTransfer['language_code'] = $_VARS['language_code'];

			echo json_encode($aTransfer);

		} else {
			parent::switchAjaxRequest($_VARS);
		}

	}

	public function getTranslations($sL10NDescription){

		$aData = parent::getTranslations($sL10NDescription);

		$aData['email_preview_note'] = L10N::t('Die E-Mail sollte nicht breiter als 600 Pixel sein.', $sL10NDescription);
		$aData['email_preview_title'] = L10N::t('E-Mail Template Vorschau', $sL10NDescription);

		return $aData;
	}

	/**
	 * @param string $sGui
	 * @return Ext_TC_Gui2
	 */
	public static function buildGui($sGui) {

		if($sGui === 'app') {
			$sRight = 'core_admin_templates_app';
			$sMD5 = 'tc_app_templates';
			$sDialogTitle = 'App';
		} elseif($sGui === 'sms') {
			$sRight = 'core_admin_templates_sms';
			$sMD5 = 'tc_sms_templates';
			$sDialogTitle = 'SMS';
		} else {
			$sGui = 'email';
			$sRight = 'core_admin_templates_email';
			$sMD5 = 'tc_email_templates'; // Hash wird im JS benutzt!
			$sDialogTitle = 'E-Mail';
		}

		$sDataClass = Ext_TC_Factory::getClassName('Ext_TC_Communication_Template_Gui2_Data');

		$oGui = new Ext_TC_Gui2(md5($sMD5), $sDataClass);
		$oGui->gui_description = Ext_TC_Communication::sL10NPath;
		$oGui->gui_title = Ext_TC_System_Navigation::t();
		$oGui->include_jquery = true;
		$oGui->include_jquery_multiselect = true;
		$oGui->access = array($sRight, '');

		if($sGui === 'email') {
			$oGui->class_js = 'CommunicationTemplateGui';
		}

		$oGui->setWDBasic('Ext_TC_Communication_Template');
		$oGui->setTableData('limit', 30);
		$oGui->setTableData('orderby', array('tc_ct.name' => 'ASC'));

		$aLanguages = \Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true));
		$aSubObjects = \Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));
		$sSubObjectLabel = \Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');

		$applications = \Factory::executeStatic('Ext_TC_Communication', 'getSelectApplications', [$oGui->getLanguageObject(), \Access_Backend::getInstance()]);

		asort($aSubObjects);
		asort($aLanguages);

		if($sGui === 'email') {
			//$aInvoiceTypes = Ext_TC_Communication::getSelectInvoiceTypes();
			//$aRecieptTypes = Ext_TC_Communication::getSelectReceipts();
			//$aIncomingFilesCategories = Ext_TC_IncomingFile_Category::getSelectOptions();
			//$aPdfTemplates = \Factory::executeStatic('Ext_TC_Pdf_Template', 'getAdditionalTemplates');
			$aShippingMethods = Ext_TC_Communication_Template::getShippingMethods();
			//$aUploads = Ext_TC_Upload::getSelectOptionsBySearch('communication');
		}

		$aRecipients = \Communication\Facades\Communication::getAllRecipients($oGui->getLanguageObject())->toArray();

		$oDialog = $oGui->createDialog($oGui->t($sDialogTitle.'-Template "{name}" editieren'), $oGui->t($sDialogTitle.'-Template anlegen'));
		$oDialog->save_as_new_button = true;
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$sText = $oGui->t('Bitte speichern Sie zuerst die Einstellungen, um die sprachabhängigen Texte zu hinterlegen.');
		$oNotification = $oDialog->createNotification($sText, false, 'info');
		$oTab->setElement($oNotification);

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_ct',
			'db_column' => 'name',
			'required' => true
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Korrespondenzsprachen'), 'select', array(
			'db_alias' => '',
			'db_column' => 'languages',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => $aLanguages,
			'searchable' => 1,
			'required' => 1
		)));

		$oTab->setElement($oDialog->createRow($oGui->t($sSubObjectLabel), 'select', array(
			'db_alias' => '',
			'db_column' => 'objects',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => $aSubObjects,
			'searchable' => 1,
			'required' => 1
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Übersichten'), 'select', array(
			'db_alias' => '',
			'db_column' => 'applications',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => $applications->sort(),
			'searchable' => 1,
			'required' => 1,
			'events' => [
				[
					'event' => 'change',
					'function' => 'reloadDialogTab',
					'parameter' => 'aDialogData.id, new Array(0, -1, -2)'
				]
			]
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Empfängergruppe'), 'select', array(
			'db_alias' => '',
			'db_column' => 'recipients',
			'selection' => new \Ext_TC_Communication_Gui2_Selection_Recipients(),
			'jquery_multiple' => 1,
			'multiple' => 5,
			'required' => true,
			// Wird nicht benötigt, da hier eh schon reloadDialogTab drauf ist
			//'dependency' => array(
			//	array(
			//		'db_column' => 'applications'
			//		)
			//)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Markierungen'), 'select', array(
			'db_alias' => '',
			'db_column' => 'flags',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'selection' => new \Ext_TC_Communication_Gui2_Selection_Flags(),
			'searchable' => 1,
			'dependency' => [
				['db_column' => 'recipients']
			]
		)));

		if($sGui === 'email') {

			$attachmentsFields = [];

			if (!empty($aInvoiceTypes)) {
				$attachmentsFields[] = $oDialog->createRow($oGui->t('Rechnungen'), 'select', array(
					'db_alias' => '',
					'db_column' => 'invoice_types',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'select_options' => $aInvoiceTypes,
					'searchable' => 1,
				));
			}

			if (!empty($aRecieptTypes)) {
				$attachmentsFields[] = $oDialog->createRow($oGui->t('Quittungen und Zahlungsübersichten'), 'select', array(
					'db_alias' => '',
					'db_column' => 'receipt_types',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'select_options' => $aRecieptTypes,
					'searchable' => 1,
				));
			}

			if (!empty($aIncomingFilesCategories)) {
				$attachmentsFields[] = $oDialog->createRow($oGui->t('Eingehende Dokumente'), 'select', array(
					'db_alias' => '',
					'db_column' => 'incoming_files_categories',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'select_options' => $aIncomingFilesCategories,
					'searchable' => 1,
				));
			}

			if (!empty($aPdfTemplates)) {
				$attachmentsFields[] = $oDialog->createRow($oGui->t('PDF Vorlagen'), 'select', array(
					'db_alias' => '',
					'db_column' => 'pdf_templates',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'select_options' => $aPdfTemplates,
					'searchable' => 1,
				));
			}

			if (!empty($aPdfTemplates)) {
				$attachmentsFields[] = $oDialog->createRow($oGui->t('PDF Vorlagen (erhalten)'), 'select', array(
					'db_alias' => '',
					'db_column' => 'pdf_templates_received',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'select_options' => $aPdfTemplates,
					'searchable' => 1,
				));
			}

			if (!empty($aUploads)) {
				$attachmentsFields[] = $oDialog->createRow($oGui->t('Uploads (erhalten)'), 'select', array(
					'db_alias' => '',
					'db_column' => 'uploads_received',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'select_options' => $aUploads,
					'searchable' => 1,
				));
			}

			if (!empty($attachmentsFields)) {
				$oH2 = $oDialog->create('h4');
				$oH2->setElement($oGui->t('Anhänge'));
				$oTab->setElement($oH2);

				foreach ($attachmentsFields as $attachmentField) {
					$oTab->setElement($attachmentField);
				}
			}

			$oH2 = $oDialog->create('h4');
			$oH2->setElement($oGui->t('Versand'));
			$oTab->setElement($oH2);

			$defaultShippingMethod = ($sGui === 'email') ? 'html' : 'text';

			$oTab->setElement($oDialog->createRow($oGui->t('Versandart'), 'select', array(
				'db_alias' => 'tc_ct',
				'db_column' => 'shipping_method',
				'select_options' => $aShippingMethods,
				'default_value' => $defaultShippingMethod,
				'required' => true
			)));

			$oTab->setElement($oDialog->createRow($oGui->t('Standard Identität'), 'select', [
				'db_alias' => 'tc_ct',
				'db_column'=>'default_identity_id',
				'format' => new \Ext_Gui2_View_Format_Null(),
				'select_options' => collect(\Factory::executeStatic(\User::class, 'getList'))
					->mapWithKeys(fn ($user) => [$user['id'] => implode(', ', [$user['lastname'], $user['firstname']])])
					->prepend('', '0')
			]));

			$oTab->setElement($oDialog->createRow($oGui->t('CC (Semikolon getrennt)'), 'input', [
				'db_alias' => 'tc_ct',
				'db_column'=>'cc'
			]));
			$oTab->setElement($oDialog->createRow($oGui->t('BCC (Semikolon getrennt)'), 'input', [
				'db_alias' => 'tc_ct',
				'db_column'=>'bcc'
			]));

		}

		$oDialog->setElement($oTab);

		$oDialog->_sGuiType = $sGui;

		$oBar = $oGui->createBar();

		$oFilter = $oBar->createFilter();
		$oFilter->db_column = array('name');
		$oFilter->db_operator = 'LIKE';
		$oFilter->id = 'search';
		$oFilter->placeholder = $oGui->t('Suche').'…';
		$oBar->setElement($oFilter);
		$oGui->setBar($oBar);

		$oBar->setElement($oBar->createSeperator());
		
		$oFilter = $oBar->createFilter('select');
		$oFilter->db_column = array('application');
		$oFilter->db_alias = 'applications';
		$oFilter->db_operator = '=';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($applications->toArray(),'--'.$oGui->t('Übersicht').'--');
		$oBar->setElement($oFilter);

		$oFilter = $oBar->createFilter('select');
		$oFilter->db_column = array('object_id');
		$oFilter->db_alias = 'objects';
		$oFilter->db_operator = '=';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($aSubObjects,'--'.$sSubObjectLabel.'--');
		$oBar->setElement($oFilter);

		$oFilter = $oBar->createFilter('select');
		$oFilter->db_column = array('recipient');
		$oFilter->db_alias = 'recipients';
		$oFilter->db_operator = '=';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($aRecipients,'--'.$oGui->t('Empfängergruppe').'--');
		$oBar->setElement($oFilter);

		$oFilter = $oBar->createFilter('select');
		$oFilter->db_column = array('language_iso');
		$oFilter->db_alias = 'languages';
		$oFilter->db_operator = '=';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($aLanguages,'--'.$oGui->t('Korrespondenzsprache').'--');
		$oBar->setElement($oFilter);

		// TODO - entfernen---------------------------------------------------------------------------------------------
		$legacyTemplates = \Core\Facade\Cache::remember('core_templates_legacy', 60*60*24, function() {
			$count = DB::getQueryOne('SELECT COUNT(`id`) FROM `tc_communication_templates` WHERE `active` = 1 && `legacy` = 1');
			return $count;
		});

		if ($legacyTemplates > 0) {
			$oFilter = $oBar->createFilter('select');
			$oFilter->db_column = array('legacy');
			$oFilter->db_alias = 'tc_ct';
			$oFilter->db_operator = '=';
			$oFilter->select_options = Ext_TC_Util::addEmptyItem(\Ext_TC_Util::getYesNoArray(false),'--'.$oGui->t('Veraltet').'--');
			$oFilter->filter_query = [
				'yes' => ' `tc_ct`.`legacy` = 1 ',
				'no' => ' `tc_ct`.`legacy` = 0 OR `tc_ct`.`legacy` IS NULL',
			];
			$oBar->setElement($oFilter);
		}
		// TODO - entfernen---------------------------------------------------------------------------------------------

        $oBar->setElement($oBar->createSeperator());
        $oFilter = $oBar->createValidFilter($oGui->t('Gültigkeit'));
        $oBar->setElement($oFilter);

		$oBar = $oGui->createBar();
		$oIcon = $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialog, $oGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createEditIcon($oGui->t('Editieren'), $oDialog, $oGui->t('Editieren'));
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oBar->setElement($oIcon);
        $oIcon = $oBar->createDeactivateIcon($oGui->t('Deaktivieren'), $oGui->t('Deaktivieren'));
        $oBar->setElement($oIcon);
		$oGui->setBar($oBar);

		$oBar = $oGui->createBar();
		$oPagination = $oBar->createPagination(false, true);
		$oBar->setElement($oPagination);
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGui->setBar($oBar);

		$oColumn = $oGui->createColumn();
		$oColumn->db_column = 'name';
		$oColumn->db_alias = 'tc_ct';
		$oColumn->title = $oGui->t('Name');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_alias = 'languages';
		$oColumn->select_column = 'languages';
		$oColumn->title = $oGui->t('Sprachen');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('language');
		$oColumn->format = new Ext_TC_Gui2_Format_Imagelist();
		$oColumn->sortable = 0;
		$oColumn->width_resize = false;
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_alias = 'objects';
		$oColumn->select_column = 'objects';
		$oColumn->title = $sSubObjectLabel;
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_TC_Gui2_Format_Multiselect($aSubObjects);
		$oColumn->sortable = 0;
		$oColumn->width_resize = false;
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_alias = 'applications';
		$oColumn->select_column = 'applications';
		$oColumn->title = $oGui->t('Übersichten');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_TC_Gui2_Format_Multiselect($applications->toArray(), ', ');
		$oColumn->sortable = 0;
		$oColumn->width_resize = false;
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_alias = 'recipients';
		$oColumn->select_column = 'recipients';
		$oColumn->title = $oGui->t('Empfängergruppe');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_TC_Gui2_Format_Multiselect($aRecipients, ', ');
		$oColumn->sortable = 0;
		$oColumn->width_resize = false;
		$oGui->setColumn($oColumn);

		$oGui->addDefaultColumns();

		return $oGui;
	}

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

        if ($sAdditional === 'deactivate') {
            return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
        }

		$aLanguages = \Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true));
		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = (int)reset($aSelectedIds);
		$oTemplate = Ext_TC_Communication_Template::getInstance($iSelectedId);

		/*if ((bool)$oTemplate->legacy && \System::d('debugmode') != 2) {
			$oDialogData->save_button = false;
			$oDialogData->save_as_new_button = false;
		}*/

		if($oDialogData->_sGuiType === 'email') {
			$aLayouts = Ext_TC_Util::addEmptyItem(Ext_TC_Communication_Template_Email_Layout::getSelectOptions());			
			$sFilePath = Ext_TC_Communication::getUploadPath('templates/email');
		}
		
		$aTabs = $oDialogData->aElements;
		$aNewTabs = array(reset($aTabs));

		if ((bool)$oTemplate->legacy && empty($oDialogData->getOption('legacy_warning'))) {
			$aElements = $aNewTabs[0]->aElements;

			$aElements = \Illuminate\Support\Arr::prepend($aElements, $oDialogData->createNotification($this->t('Veraltete Vorlage'), $this->t('Diese Vorlage entspricht nicht mehr dem aktuellen Stand, bitte legen Sie diese neu an.'), 'hint', [
				'dismissible' => false,
			])->generateHTML());

			$aNewTabs[0]->aElements = $aElements;

			$oDialogData->setOption('legacy_warning', 1);
		} else if (!(bool)$oTemplate->legacy && !empty($oDialogData->getOption('legacy_warning'))) {
			$aElements = $aNewTabs[0]->aElements;
			unset($aElements[0]);
			$aNewTabs[0]->aElements = array_values($aElements);

			$oDialogData->setOption('legacy_warning', null);
		}

		// TABs Inhalte
		
		foreach((array)$oTemplate->languages as $sIso) {
			
			$sLanguage = $aLanguages[$sIso];
			
			// #3188
			#$oTab = $oDialogData->createTab('<img src="/admin/media/flag_'.\Util::convertHtmlEntities($sIso).'.gif" /> '.sprintf($this->t('Inhalte "%s"'), $sLanguage));
			$oTab = $oDialogData->createTab('<img src="/admin/media/flag_'.\Util::convertHtmlEntities($sIso).'.gif" /> '. $sLanguage);

			if($oDialogData->_sGuiType === 'email') {

				if($oTemplate->shipping_method == 'html') {
					$oTab->setElement($oDialogData->createRow($this->t('HTML-Layout'), 'select', array(
						'db_alias' => '',
						'db_column' => 'layout_id_'.$sIso,
						'select_options' => $aLayouts
					)));
				}

				$oTab->setElement($oDialogData->createRow($this->t('Betreff'), 'input', array(
					'db_alias' => '',
					'db_column' => 'subject_'.$sIso,
					'required' => true
				)));
				
				if($oTemplate->shipping_method == 'html') {
					$sContentType = 'html';
				} else {
					$sContentType = 'textarea';
				}

				$oTab->setElement($oDialogData->createRow($this->t('Inhalt'), $sContentType, array(
					'db_alias' => '',
					'db_column' => 'content_'.$sIso,
					'style' => 'height: 380px;',
					'advanced' => true
				)));
				
				$aUploads = Factory::executeStatic(Ext_TC_Upload::class, 'getSelectOptionsBySearch', ['communication', $sIso]);
				
				$oTab->setElement($oDialogData->createRow($this->t('Uploads'), 'select', array(
					'db_alias' => '',
					'db_column' => 'to_uploads_'.$sIso,
					'multiple' => 5, 
					'jquery_multiple' => 1,
					'select_options' => $aUploads,
					'searchable' => 1,
				)));

				$oUpload = new Ext_Gui2_Dialog_Upload($this->_oGui, $this->t('Upload'), $oDialogData, 'content_uploads_'.$sIso, '', $sFilePath);
				$oUpload->bAddColumnData2Filename = 0;
				$oTab->setElement($oUpload);
				
				if($oTemplate->shipping_method == 'html') {
					$oTab->setElement($oDialogData->createRow($this->t('Vorschau'), 'button', array(
						'onclick' => "aGUI['".$this->_oGui->hash."'].openTemplatePreview('".$sIso."', this); return false;",
						'value' => $this->t('Öffnen')
					)));
				}
				
			} elseif(
				$oDialogData->_sGuiType === 'sms' ||
				$oDialogData->_sGuiType === 'app'
			) {
				
				if($oDialogData->_sGuiType === 'app') {
					$oTab->setElement($oDialogData->createRow($this->t('Betreff'), 'input', array(
						'db_alias' => '',
						'db_column' => 'subject_'.$sIso,
						'required' => true
					)));
				}
				
				$oTab->setElement($oDialogData->createRow($this->t('Inhalt'), 'textarea', array(
					'db_alias' => '',
					'db_column' => 'content_'.$sIso,
					'style' => 'height: 380px;'
				)));
				
			}
			
			$aNewTabs[] = $oTab;
			
		}
		
		/**
		 * Tab Platzhalter
		 */
		
		$oTab = $oDialogData->createTab($this->_oGui->t('Platzhalter'));
		$oTab->no_padding = 1;
		$oTab->no_scrolling = 1;
		
		if(isset($this->oWDBasic)){
			$aApplications = (array)$this->oWDBasic->applications;
		}else{
			$aApplications = (array)$oTemplate->applications;
		}
		
		// Platzhaltertabellen erstellen
		$sPlaceholderHtml = $this->_getPlaceholderHTML($aApplications);
		// HTML setzen
		$oHtml = new Ext_TC_Placeholder_Html();
		$sContent = $oHtml->createPlaceholderContent($sPlaceholderHtml);
		
		$oTab->setElement($sContent);

		// Tab setzen
		$aNewTabs[] = $oTab;

		/**
		 * Tab Platzhalter Beispiele 
		 */
		
		$oTab = Ext_TC_Placeholder_Html::getPlaceholderExampleTab($aApplications, $oDialogData, $this->_oGui, 'communication');
		// Tab setzen
		$aNewTabs[] = $oTab;

		$tab = $oDialogData->createTab($this->_oGui->t('Bilder'));
		$html = new Ext_Gui2_Html_Div();
		$html->setElement($this->writeImageTabHTML());
		$tab->setElement($html);
		$aNewTabs[] = $tab;
		// - - - - - - - - - - - - - - - - - - - - - -
		
		$oDialogData->aElements = $aNewTabs;
		
		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
		
		return $aData;
		
	}
	
	/**
	 * Create HTML tables with placeholders by given applications 
	 * @param array $aApplications
	 * @return string 
	 */
	protected function _getPlaceholderHTML(array $aApplications)
	{

		// Array mit allen WDBasic für die Platzhalter
		$aObjects = \Factory::executeStatic('Ext_TC_Communication', 'getPlaceholderClasses');
		$sHTML = '';

		$aUsedObjects = array();

		// Applications durchlaufen
		foreach($aApplications as $sApplication) {

			// wenn für Applikation eine WDBasic vorhanden ist
			if(
				isset($aObjects[$sApplication]) &&
				isset($aObjects[$sApplication]['class'])
			){
				
				// Klassenname
				$sClass = $aObjects[$sApplication]['class'];
				
				// Wenn WDBasic noch nicht verwendet wurde
				#if(!in_array($sClass, $aUsedObjects)){

					// Instanz der WDBasic erzeugen
					/* @var \Ext_TC_Basic $oObject */
					$oObject = is_callable($sClass) ? $sClass($sApplication) : \Factory::getInstance($sClass);
					// Platzhalterobjekt
					$oPlaceholder = $oObject->getPlaceholderObject();

					if ($oPlaceholder) {
						// Title setzen
						if(isset($aObjects[$sApplication]['title'])){
							$oH2 = new Ext_Gui2_Html_H4();
							$oH2->style = 'margin:0 0 5px 0;';
							$oH2->setElement($aObjects[$sApplication]['title']);
							$sTitle = $oH2->generateHTML();
							$sHTML .= $sTitle;
						}

						// Platzhaltertabelle erstellen
						$sHTML .= $oPlaceholder->displayPlaceholderTable($sApplication) . '<br />';
					}
					// verwendete WDBasic merken
					$aUsedObjects[] = $sClass;
					
				#}
				
			}
			
		}

		return $sHTML;
	}
	
}
