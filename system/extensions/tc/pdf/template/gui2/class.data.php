<?php

class Ext_TC_Pdf_Template_Gui2_Data extends Ext_TC_Gui2_Data {
	
	protected static $_sL10NDescription = 'Thebing Core » Templates » PDF';
	
	public static function getPositionTableOptions() {
		$aOptions = array();

		$aOptions[1] = L10N::t('Abhängig von der Zahlungsart der Buchung', self::$_sL10NDescription);
		$aOptions[2] = L10N::t('Kundenansicht', self::$_sL10NDescription);
		$aOptions[3] = L10N::t('Agenturansicht', self::$_sL10NDescription);

		return $aOptions;

	}
	
	public function getTranslations($sL10NDescription) {
		$aTranslations = parent::getTranslations($sL10NDescription);

		$aTranslations['switch_warning']  = $this->_oGui->t('Wenn Sie das Layout wechseln, wird der gesamte Inhalt/Content des Templates gelöscht.\nWir empfehlen Ihnen, das Template vorher einmal zu kopieren ("speichern als neuen Eintrag") und danach den Inhalt vom alten Template in das neue Tempalte kopieren');
	
		return $aTranslations;
	}

	public function switchAjaxRequest($_VARS) {

		switch($_VARS['task']) {
			case 'showPreviewPdf':

				try {

					$iObject = (int)$_VARS['iObject'];
					$sLang = $_VARS['sLang'];
					$iTemplate = (int)$_VARS['iTemplate'];

					$oPdfTemplate = Ext_TC_Pdf_Template::getInstance($iTemplate);
					$oObject = Ext_TC_Factory::getObject('Ext_TC_SubObject', $iObject);
					
					$oPDF = new Ext_TC_Pdf($oPdfTemplate, $oObject, $sLang);
					$oPDF->create();
					$oPDF->Output('Preview', 'D');

				} catch (Exception $exc) {
					echo '<span style="color:red">'.$this->_oGui->t('Es ist ein Fehler aufgetreten. Bitte überprüfen sie ihre PDF Hintergründe.').'</span>';
				}

				$this->_oGui->save();
				die();

				break;
			default:
				parent::switchAjaxRequest($_VARS);
				break;
		}
	}

    protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

        if ($sAdditional === 'deactivate') {
            return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
        }

		$aLanguages = Factory::executeStatic(Ext_TC_Object::class, 'getLanguages', [true]);

		$aSelectedIds = (array)$aSelectedIds;

		if (count($aSelectedIds) > 1)  {
			return [];
		} else {
			$iSelectedId = (int) reset($aSelectedIds);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTemplate		= Ext_TC_Pdf_Template::getInstance($iSelectedId);
		$oLayout		= Ext_TC_Pdf_Layout::getInstance($oTemplate->layout_id);
		$oFirst			= reset($oDialogData->aElements);
		$oLast			= end($oDialogData->aElements);
		$aObjects		= $oTemplate->objects;
		$aElements		= array($oFirst);
		$aTypeElements	= $oLayout->getElements();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create new tabs

		foreach((array)$oTemplate->languages as $sLanguage)
		{
			$sLanguageLabel = $aLanguages[$sLanguage];

			// #3188
			#$sLabel	= $this->_oGui->t('Inhalte "%s"');
			#$sLabel	= sprintf($sLabel, $sLanguageLabel);			
			#$oTab	= $oDialogData->createTab($sLanguageLabel);				
			$oTab = $oDialogData->createTab('<img src="/admin/media/flag_'.\Util::convertHtmlEntities($sLanguage).'.gif" /> '. $sLanguageLabel);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Type elements fields

			foreach($aTypeElements as $oElement)
			{
				
				$sInputType = 'input';
				$aInputOptions = array(
							'db_column' => 'lang_tab_elements_' . $oElement->id . '_' . $sLanguage,
							'advanced' => true
						);
				
				if($oElement->element_type == 'text') {

				} elseif(
					$oElement->element_type == 'main_text' ||
					$oElement->element_type == 'html'
				) {

					if($oElement->wysiwyg == 1) {
						$sInputType = 'html';
					} else {
						$sInputType = 'textarea';
					}
					
					$aInputOptions['style'] = 'width:500px;';

				} elseif($oElement->element_type == 'img') {

					$sInputType = 'select';
					$aInputOptions['select_options'] = array('default_customer_picture' => $this->_oGui->t('Schülerbild'));
					
				} elseif($oElement->element_type == 'date') {
					
					$sInputType = 'calendar';

				} 

				$oTab->setElement(
					$oDialogData->createRow(
						$oElement->name,
						$sInputType,
						$aInputOptions
					)
				);
				
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // School fields

			$aNewObjects = array();

			foreach((array)$aObjects as $iKey => $iObjectId){

				$oTempObject = Factory::getInstance(Ext_TC_SubObject::class, $iObjectId);

				// Nur aktive Schulen anzeigen
				if($oTempObject->active != 1){
					continue;
				}
				
				$aNewObjects[mb_strtolower($oTempObject->name)] = $oTempObject;
			}

			// Sort schools
			ksort($aNewObjects);
	
			foreach((array)$aNewObjects as $oTempObject) {

				$aFiles				= $oTempObject->getUploads('pdf_background', $sLanguage);
				$aFilesSignatures	= $oTempObject->getUploads('signatures', $sLanguage);
//				$aFilesAttachments	= $oTempObject->getUploads('pdf_attachments', $sLanguage);

				$aFilesSignatures[0] = array('id' => 0, 'description' => '');
				ksort($aFilesSignatures);

				$aFilesDD = $aFilesSignaturesDD = array();

				foreach((array)$aFiles as $aFile)
				{
					$aFilesDD[$aFile['id']] = $aFile['description'];
				}
				foreach((array)$aFilesSignatures as $aFile)
				{
					$aFilesSignaturesDD[$aFile['id']] = $aFile['description'];
				}
//				foreach((array)$aFilesAttachments as $aFile)
//				{
//					$aFilesAttachmentsDD[$aFile['id']] = $aFile['description'];
//				}

				$sOnClick = 'aGUI[\''.$this->_oGui->hash.'\'].showPreviewPdf('.$oTemplate->id.', '.$oTempObject->id.', \''.$sLanguage.'\'); return false;';

				$oH3 = $oDialogData->create('h4');
				$sTitle = $oTempObject->name;
				$oH3->setElement('<div style="float:left; margin-top: 7px;">'.$sTitle.'</div>');
				$oH3->style = 'float:left; width: 100%;';
				
				$sPreview = '<div onclick="'.$sOnClick.'" class="guiBarElement guiBarLink" style="margin-top:0px; margin-left: 10px; float:left;">
								<div class="divToolbarIcon" style="float:left;">
									<i style="cursor:pointer; margin-top:3px; float:left;" class="fa '.Ext_TC_Util::getIcon('pdf').'" alt="'.$this->_oGui->t('Vorschau').'" title="'.$this->_oGui->t('Vorschau').'"></i>
								</div>
								<div class="divToolbarLabel" style="float:left; padding:5px;">
									'.$this->_oGui->t('Vorschau').' <span style="font-size:10px;">('.$this->_oGui->t('Bei Änderung der Einstellungen bitte zuerst speichern').')</span>
								</div>
								
							</div><div style="clear:both"></div>';
				
				$oH3->setElement($sPreview);
				$oTab->setElement($oH3);
				$oTab->setElement('<div style="clear:both"></div>');

				$sColumn = 'lang_tab_school_' . $oTempObject->id . '_' . $sLanguage . '_filename';

				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Syntax für den Dateinamen'),
						'input',
						array(
							'db_column'			=> $sColumn
						)
					)
				);

				$sColumn = 'lang_tab_school_' . $oTempObject->id . '_' . $sLanguage . '_first_page_pdf_template';

				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Erste Seite - PDF Hintergrund'),
						'select',
						array(
							'db_column'			=> $sColumn,
							'select_options'	=> $aFilesDD
						)
					)
				);

				$sColumn = 'lang_tab_school_' . $oTempObject->id . '_' . $sLanguage . '_additional_page_pdf_template';

				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Folgeseiten - PDF Hintergrund'),
						'select',
						array(
							'db_column'			=> $sColumn,
							'select_options'	=> $aFilesDD
						)
					)
				);

//				$sColumn = 'lang_tab_school_' . $oTempObject->id . '_' . $sLanguage . '_attachments';
//
//				$oTab->setElement(
//					$oDialogData->createRow(
//						$this->_oGui->t('Anhänge'),
//						'select',
//						array(
//							'db_column' => $sColumn,
//							'multiple' => 5,
//							'select_options' => $aFilesAttachmentsDD,
//							'jquery_multiple' => 1,
//							'searchable' => 1
//						)
//					)
//				);
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$oDialogData->setElement($oTab);

			$aElements[] = $oTab;
			
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		//$aElements[] = $oLast;
		
		$oTab = $oDialogData->createTab($this->_oGui->t('Platzhalter'));
		$oTab->no_padding = 1;
		$oTab->no_scrolling = 1;

		$aElements[] = $oTab;
		
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
		
		$aApplications = array();
		
		if($oTemplate->type != ''){
			$aApplications[] = $oTemplate->type;
		}
		
		$oExampleTab = Ext_TC_Placeholder_Html::getPlaceholderExampleTab($aApplications, $oDialogData, $this->_oGui);

		$oDialogData->setElement($oExampleTab);
		
		$aElements[] = $oExampleTab;		
		
		
		$oDialogData->aElements = $aElements;

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		//wieso kann man bei einem getter nicht getten?
		if(!$this->oWDBasic instanceof WDBasic){
			$this->_getWDBasicObject($aSelectedIds);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Set placeholder

		$sType = $this->oWDBasic->type;
		
		if(!empty($sType)) {

			$sHtml = self::getPlaceholderTabContent($sType);
			$aData['tabs'][count($aData['tabs']) - 2]['html'] = $sHtml;

		}

		return $aData;
	}

	public static function getPlaceholderTabContent($sType) {

		$aLinks = Ext_TC_Factory::executeStatic('Ext_TC_Pdf_Template', 'getPdfPlaceholderObject', array($sType));

		$sHTML = null;
		
		if(!empty($aLinks)) {

			$oObject = new $aLinks['class'](0);

			$oPlaceholder = $oObject->getPlaceholderObject();

			if($oPlaceholder){

				$oPlaceholderHtml = new Ext_TC_Placeholder_Html();
				$sPlaceholderHtml = $oPlaceholder->displayPlaceholderTable($sType);

				$sHTML = $oPlaceholderHtml->createPlaceholderContent($sPlaceholderHtml);

			} else {
				$sHTML = "No Placeholder Object found!";
			}
		}
	
		return $sHTML;
	}
	
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		$this->_getWDBasicObject($aSelectedIds);

		return $aData;

	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		return $aTransfer;

	}

}

