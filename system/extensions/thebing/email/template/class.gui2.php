<?php


class Ext_Thebing_Email_Template_Gui2 extends Ext_Thebing_Gui2_Data
{

	use \Tc\Traits\Gui2\ImageTab;

	public function getTranslations($sL10NDescription){

		$aData = parent::getTranslations($sL10NDescription);

		$aData['email_preview_note'] = L10N::t('Die E-Mail sollte nicht breiter als 600 Pixel sein.', $sL10NDescription);
		$aData['email_preview_title'] = L10N::t('E-Mail Format Vorschau', $sL10NDescription);

		return $aData;
	}
	
	public function switchAjaxRequest($_VARS)
    {

		if(
			$_VARS['task'] == 'getLayoutCode'
		) {
			$aTransfer = array();
			$aTransfer['action'] = 'openPreviewTemplate';

			//$aTransfer['layout_id'] = $_VARS['layout_id'];

			$oLayout = Ext_Thebing_Email_Layout::getInstance($_VARS['layout_id']);

			$aTransfer['layout'] = $oLayout->html;
			
			$aTransfer['data']['id'] = $_VARS['dialog_id'];
			
			$aTransfer['language_code'] = $_VARS['language_code'];

			echo json_encode($aTransfer);

		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}

    protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false)
    {

		$aLanguages = Ext_Thebing_Client::getLangList(true);

		$aSelectedIds = (array)$aSelectedIds;
		if(count($aSelectedIds) > 1) {
			return array();
		} else {
			$iSelectedId = (int) reset($aSelectedIds);
		}

		$oTemplate = Ext_Thebing_Email_Template::getInstance($iSelectedId);
		
		$oFirst = reset($oDialogData->aElements);
		list($oPlaceholderTab, $oSmartyPlaceholderTab) = array_slice($oDialogData->aElements, -2);

		$aElements = array($oFirst);

		$aLayouts = Ext_Thebing_Email_Layout::getList(true);
		$aLayouts = Ext_Thebing_Util::addEmptyItem($aLayouts);

		// create new tabs
		foreach((array)$oTemplate->languages as $sLanguage) {

			$sLanguageLabel = $aLanguages[$sLanguage];
			$sLabel = L10N::t('Inhalte für %s', $this->_oGui->gui_description);
			$sLabel = sprintf($sLabel, $sLanguageLabel);
			$oTab = $oDialogData->createTab($sLabel);

			// Layout nur anzeigen, wenn HTML Template
			if($oTemplate->html) {
				$oTab->setElement($oDialogData->createRow(L10N::t('Layout', $this->_oGui->gui_description), 'select', array('db_column'=>'layout_'.$sLanguage, 'select_options' => $aLayouts)));
			}

			$oTab->setElement($oDialogData->createRow(L10N::t('Betreff', $this->_oGui->gui_description), 'input', array('db_column'=>'subject_'.$sLanguage)));
			
			if($oTemplate->html) {

				$oTab->setElement($oDialogData->createRow(L10N::t('Inhalt', $this->_oGui->gui_description), 'html', array(
					'db_column' => 'content_'.$sLanguage,
					'style' => 'width: 671px; height:380px;',
					'advanced' => true
				)));

			} else {
				$oTab->setElement($oDialogData->createRow(L10N::t('Inhalt', $this->_oGui->gui_description), 'textarea', array('db_column'=>'content_'.$sLanguage, 'style'=>'width: 600px;height:380px;')));
			}

			if($oTemplate->html) {
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Vorschau'), 
						'button', 
						array(
							'onclick'=>"aGUI['".$this->_oGui->hash."'].openPreviewTemplate(this, '".$sLanguage."');return false;",
							'value'=>$this->_oGui->t('Öffnen')
						)
					)
				);
			}
			
			$oUpload = new Ext_Gui2_Dialog_Upload(
				$this->_oGui,
				L10N::t('Anhänge', $this->_oGui->gui_description),
				$oDialogData,
				'attachments_'.$sLanguage,
				'',
				$oTemplate->getUploadPath(false)
			);

			$oUpload->multiple = 1;
			$oTab->setElement($oUpload);
			
			$oDialogData->setElement($oTab);

			$aElements[] = $oTab;

		}

		$aElements[] = $oPlaceholderTab;
		$aElements[] = $oSmartyPlaceholderTab;

		$oDialogData->aElements = $aElements;

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds);

		foreach($aData['tabs'] as $iTab=>&$aTabData) {

			if($aTabData['options']['task'] == 'smarty_placeholder') {
			
				$aApplications = (array)$oTemplate->applications;

				$sClass = null;
				foreach($aApplications as $sApplication) {
					
					$entityClass = Ext_Thebing_Communication::getObjectFromApplication($sApplication);

					// 1. Application kann auch einfach ein String sein, z.B. cronjob
					// 2. getPlaceholderObject() fragt _sPlaceholderClass ab und wenn das nicht gesetzt ist, sagt WDBasic::__get() guten Tag
					try {
						$dummyEntity = new $entityClass;
						$oPlaceholder = $dummyEntity->getPlaceholderObject();
						break;
					} catch (\Throwable $e) {
						#__pout($e);
					}
					
				}
				
				if($oPlaceholder) {				
					$oPlaceholder->bCommunication = true;
					$sPlaceholderHtml = $oPlaceholder->displayPlaceholderTable(1, array(), $aApplications);
					$oPlaceholderHtml = new Ext_TC_Placeholder_Html();
					$sHtml = $oPlaceholderHtml->createPlaceholderContent($sPlaceholderHtml);
					$aTabData['html'] = $sHtml;
				} else {
					unset($aData['tabs'][$iTab]);
				}
				
			} elseif($aTabData['options']['task'] == 'placeholder') {
				
				$aApplications = (array)$oTemplate->applications;

				$sClass = null;
				foreach($aApplications as $sApplication) {

//					$entityClass = Ext_Thebing_Communication::getObjectFromApplication($sApplication);
//
//					// 1. Application kann auch einfach ein String sein, z.B. cronjob
//					// 2. getPlaceholderObject() fragt _sPlaceholderClass ab und wenn das nicht gesetzt ist, sagt WDBasic::__get() guten Tag
//					try {
//						$dummyEntity = new $entityClass;
//						$oPlaceholder = $dummyEntity->getPlaceholderObject();
//						break;
//					} catch (\Throwable $e) {
//					}

					$sTempClass = Ext_Thebing_Communication::getPlaceholderClass($sApplication);

					if(
						$sClass !== null &&
						$sClass != $sTempClass
					) {
						$sClass = false;
						break;
					}
					$sClass = $sTempClass;
				}

				if (is_subclass_of($sClass, Ext_TC_Placeholder_Abstract::class)) {
					$sEntity = Ext_Thebing_Communication::getObjectFromApplication($sApplication);
					$oPlaceholder = new $sClass(new $sEntity());
				} elseif ($sClass) {
					$oPlaceholder = new $sClass();
				}

				if($oPlaceholder) {				
					$oPlaceholder->bCommunication = true;
					$sPlaceholderHtml = $oPlaceholder->displayPlaceholderTable(1, array(), $aApplications);
					$oPlaceholderHtml = new Ext_TC_Placeholder_Html();
					$sHtml = $oPlaceholderHtml->createPlaceholderContent($sPlaceholderHtml);
					$aTabData['html'] = $sHtml;
				} else {
					unset($aData['tabs'][$iTab]);
				}

			}

		}

		return $aData;

	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
		
		if($sIconAction == 'new' || $sIconAction == 'edit')
		{
			
			$iNewTabCount = count($aData['tabs']);
			
			$aData['tabs'][$iNewTabCount]['title'] = $this->t('Bilder');
			$aData['tabs'][$iNewTabCount]['html'] = $this->writeImageTabHTML();
			
		}

		return $aData;
	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{
		$oClient = Ext_Thebing_Client::getInstance();
		$aUsers = $oClient->getUsers(true);

		$aLanguages = Ext_Thebing_Client::getLangList(true);
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$aApplications = Ext_Thebing_Communication::getApplications(false, true);

		$oDialog = $oGui->createDialog($oGui->t('E-Mail Vorlage "{name}"'), $oGui->t('Neue E-Mail Vorlage'));

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'open';

		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array('db_column'=>'name', 'required'=>1)));

		$oTab->setElement($oDialog->createRow($oGui->t('Verfügbarkeit'), 'select', array('db_column'=>'applications', 'multiple'=>5, 'jquery_multiple'=>1, 'searchable'=>1, 'style'=>'height: 100px;', 'select_options'=>$aApplications)));
		$oTab->setElement($oDialog->createRow($oGui->t('Schulen'), 'select', array('db_column'=>'schools', 'multiple'=>5, 'jquery_multiple'=>1, 'searchable'=>1, 'style'=>'height: 100px;', 'select_options'=>$aSchools)));
		$oTab->setElement($oDialog->createRow($oGui->t('Sprachen'), 'select', array('db_column'=>'languages', 'multiple'=>5, 'jquery_multiple'=>1, 'searchable'=>1, 'style'=>'height: 100px;', 'select_options'=>$aLanguages)));
		$oTab->setElement($oDialog->createRow($oGui->t('Vorausgewählte Markierungen'), 'select', array('db_column'=>'flags', 'multiple'=>5, 'jquery_multiple'=>1, 'searchable'=>1, 'style'=>'height: 100px;', 'selection' => new Ext_Thebing_Gui2_Selection_Communication_Flags(), 'dependency'=>array(array('db_alias'=>'', 'db_column' => 'applications')))));
		$oTab->setElement($oDialog->createRow($oGui->t('Standard Identität'), 'select', array('db_column'=>'default_identity_id', 'select_options' => Ext_Thebing_Util::addEmptyItem($aUsers))));

		$oTab->setElement($oDialog->createRow($oGui->t('CC (Semikolon getrennt)'), 'input', array('db_column'=>'cc')));
		$oTab->setElement($oDialog->createRow($oGui->t('BCC (Semikolon getrennt)'), 'input', array('db_column'=>'bcc')));
		$oTab->setElement($oDialog->createRow($oGui->t('HTML'), 'checkbox', array('db_column'=>'html', 'default_value'=>1)));

		$oTab->aOptions['section'] = 'admin_email_templates';
		$oTab->aOptions['task'] = 'settings';
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Platzhalter'));
		$oTab->aOptions['section'] = 'admin_email_templates_placeholder';
		$oTab->aOptions['task'] = 'placeholder';
		$oTab->no_padding = 1;
		$oTab->no_scrolling = 1;
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Erweiterte Platzhalter'));
		$oTab->aOptions['section'] = 'admin_email_templates_smarty_placeholder';
		$oTab->aOptions['task'] = 'smarty_placeholder';
		$oTab->no_padding = 1;
		$oTab->no_scrolling = 1;
		$oDialog->setElement($oTab);

		return $oDialog;
	}

	public static function getOrderby()
	{

		return ['name' => 'ASC'];
	}

	public static function getWhere()
	{

		return ['client_id' => \Ext_Thebing_Client::getClientId()];
	}

	public static function getSchoolSelectOptions()
	{
		$oSchool = Ext_Thebing_School::getInstance();
		$aSchoolOptions = $oSchool->getArrayList(true, 'short');

		return $aSchoolOptions;
	}

	public static function getApplicationSelectOptions()
	{
		$aApplications = Ext_Thebing_Communication::getApplications(false, true);

		return $aApplications;
	}

	public static function getFormatParamsApplicationColumn1()
	{
		$aApplications = Ext_Thebing_Communication::getApplications(false, true);

		return $aApplications;
	}

}