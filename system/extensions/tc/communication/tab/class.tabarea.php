<?php

/**
 * Tabarea im Kommunikationsdialog
 * 
 * In einer abgeleiteten Klasse von dieser sollen sich dann die jeweiligen
 *	Methoden zum Generieren der Tabs befinden.
 */
class Ext_TC_Communication_Tab_TabArea {
	
	/**
	 * @var Ext_TC_Communication_Tab 
	 */
	protected $_oParent;
	protected $_sType;
	
	/**
	 * @var Ext_TC_Communication_Template
	 */
	protected $_oTemplate = null;
	
	/**
	 * Empfänger cachen
	 * @var array
	 */
	public $aRecipientCache = array();
	
	/**
	 *
	 * @var Ext_TC_Communication_Template_Content
	 */
	protected $_oTemplateContent = null;
	
	public function __construct(Ext_TC_Communication_Tab &$oTab) {
		
		$this->_oParent = $oTab;

	}
	
	public function setType($sType) {
		$this->_sType = $sType;
	}
	
	/**
	 * Ein Array mit Instanzen aller ausgewählten Objekte
	 * 
	 * Wird in createTemplateSelect() gesetzt und in setBaseContent() wieder benutzt.
	 * @var Ext_TC_Basic
	 */
	protected $_oPreparedObjects = null;
	
	/**
	 * Setzt den Basisinhalt von der Tabarea.
	 * TabArea->createTab() gibt ein oDiv zurück! 
	 * @param Ext_Gui2_Html_Div $oTab 
	 */
	public function setBaseContent(Ext_Gui2_Html_Div &$oTab) {

		$oParentTab = $this->_oParent;
		$oDialog = $oParentTab->getDialogObject();
		$oCommunication = $oParentTab->getCommunicationObject();
		$sParentType = $oParentTab->getType(); // email, sms
		$aValues = $oCommunication->getSaveValues();

		$aTabvars = array();
		if(!empty($aValues[$sParentType])) {
			$aTabvars = $aValues[$sParentType];
		}

		// Interne Daten der Kommunikationsklasse initiieren
		$oCommunication->initCommunicationData($oParentTab, $aTabvars);

		// Prüfen, ob es überhaupt Empfänger gibt
		$bCheck = $this->checkPossibleRecipients();
		if($bCheck === false) {
			$oNotification = $oDialog->createNotification(sLabel: Ext_TC_Communication::t('Es sind keine möglichen Empfänger verfügbar.'), aOptions: ['dismissible' => false]);
			$oTab->setElement($oNotification);
			return;
		}
		
		$iTemplateId = null;

		if(
			isset($aValues[$sParentType]) &&
			isset($aValues[$sParentType][$this->_sType]) &&
			isset($aValues[$sParentType][$this->_sType]['template_id']) &&
			$aValues[$sParentType][$this->_sType]['template_id'] > 0
		) {
			
			$iTemplateId = $aValues[$sParentType][$this->_sType]['template_id'];
		}

		$oTemplateSelect = $this->createTemplateSelect($iTemplateId);
		$oTab->setElement($oTemplateSelect);

		/**
		 * Prüfen, ob bei ReloadDialogTab ein Template ausgewählt wurde
		 */
		if($iTemplateId !== null) {

			/** @TODO Mehrsprachigkeit */
			$oObject = $this->_oPreparedObjects[0];

			$aSelectedIds = $this->getParentTab()->getCommunicationObject()->getSelectedIds();

			// Template holen und Platzhalter ersetzen
			$this->_oTemplate = Ext_TC_Communication_Template::getInstance($iTemplateId);

			$mLanguage = $oObject->getCorrespondenceLanguage($this->_sType);

			// Wenn es mehrere Sprachen gibt (Massenkommunikation), ist dieser Fall nicht definiert
			if(is_array($mLanguage)) {
				$mLanguage = reset($mLanguage);
			}

			$this->_oTemplateContent = $this->_oTemplate->getJoinedObjectChildByValue('contents', 'language_iso', $mLanguage);

			$sSubject = $this->_oTemplateContent->subject;
			$sContent = $this->_oTemplateContent->content;

			// Platzhalter nur ersetzen, wenn es sich um ein einzelnes ausgewähltes Objekt handelt
			if(count($aSelectedIds) === 1) {

				$aPlaceholderOptions = array(
					'language' => $mLanguage,
					'object' => $oObject,
					'type' => $this->_oTemplate->shipping_method
				);

				$aSubject = $this->_oParent->getCommunicationObject()->replacePlaceholders($sSubject, $aPlaceholderOptions);

				if($aSubject['success']) {

					$aContent = $this->_oParent->getCommunicationObject()->replacePlaceholders($sContent, $aPlaceholderOptions);

					if(!$aContent['success']) {
						$aErrors = $aContent['errors'];
					}

					$sSubject = $aSubject['text'];
					$sContent = $aContent['text'];

				} else {
					$aErrors = $aSubject['errors'];
				}

			}

			// Wenn ein Templatefehler aufgetreten ist
			if(
				!empty($aErrors)
			) {

				foreach($aErrors as $aErrorItems) {
					foreach($aErrorItems as $sErrorKey=>$sErrorValue) {
						if(
							$sErrorKey === 'UNKNOWN_TAG'
						) {
							$this->_oParent->getCommunicationObject()->getDialogDataObject()->setError('SMARTY_EXCEPTION_PLACEHOLDER', $sErrorValue);		
						} elseif(
							$sErrorKey === 'SMARTY_EXCEPTION'		
						) {
							$this->_oParent->getCommunicationObject()->getDialogDataObject()->setError('SMARTY_EXCEPTION', $sErrorValue);		
						}
					}
				}

			}
			
			$bHtmlEditor = false;
			if($this->_oTemplate->shipping_method == 'html') {
				$bHtmlEditor = true;
			}
			
			// To/CC/BCC
			$oRecipientFields = $this->createRecipientFields();
			if($oRecipientFields && $oRecipientFields->hasElements()) {
				$oTab->setElement($oRecipientFields);
			}
			
			// Betreff
			if($sParentType !== 'sms') {
				$oSubjectField = $this->createSubjectField($sSubject);
				$oTab->setElement($oSubjectField);
			}
			
			// Inhalt
			$oContentField = $this->createContentField($sContent, $bHtmlEditor);
			$oTab->setElement($oContentField);
			
			// Anhänge
			if($sParentType !== 'sms') {
				$oAttachmentsFields = $this->createAttachmentFields();
				$oH2 = $oDialog->create('h4');
				$oH2->setElement(Ext_TC_Communication::t('Anhänge'));
				$oTab->setElement($oH2);

				if($oAttachmentsFields) {
					$oTab->setElement($oAttachmentsFields);
				}

				$oGui = $this->_oParent->getDialogObject()->oGui;
				$sFilePath = Ext_TC_Factory::executeStatic('Ext_TC_Communication', 'getUploadPath', array('sent'));
				
				if(substr($sFilePath, 0, 1) !== '/') {
					$sFilePath = '/' . $sFilePath;
				}
				
				$oUpload = new Ext_Gui2_Dialog_Upload($oGui, Ext_TC_Communication::t('Upload'), $oDialog, 'attachment', '', $sFilePath);
				$oUpload->bAddColumnData2Filename = 0;
				$oUpload->multiple = 1;
				$oTab->setElement($oUpload->generateHTML());

			}
			
			// Markierungen
			$oFlags = $this->createFlagFields();
			if($oFlags) {
				$oH2 = $oDialog->create('h4');
				$oH2->setElement(Ext_TC_Communication::t('Markierungen'));
				$oTab->setElement($oH2);
				$oTab->setElement($oFlags);
			}

		}
		
	}
	
	
	public function getAttachmentFields()
	{
		$aSelects = array();
		return $aSelects;
	}
	
	public function getRecipientSelects()
	{
		
		throw new Exception('Please overwrite getRecipientSelects()!');
		
	}
	
	/**
	 * Erzeugt ein Vorlagen-Select
	 * @param int $iTemplateId
	 * @return Ext_Gui2_Html_Div 
	 */
	final public function createTemplateSelect($iTemplateId = 0)
	{
		// Datenbankstruktur stimmt mit Typen überein (email, sms)
		$sRecipient = $this->getType();
		$oCommunication = $this->getParentTab()->getCommunicationObject();
		$sApplication = $oCommunication->getApplication();
		
		$aFilterData = $this->setAndPrepObjsForTemplateChoice();

		$aTemplateFilter = array(
			'application' => $sApplication,
			'recipient' => $sRecipient,
			'languages' => $aFilterData['languages'],
			'sub_objects' => $aFilterData['sub_objects']
		);

		$aTemplates = Ext_TC_Communication_Template::getSelectOptions($this->_oParent->getType(), $aTemplateFilter);

		// Flagge nur anzeigen, wenn nur ein Objekt
		if(empty($aFilterData['languages'][0])) {
			return $oCommunication->getDialogObject()->createNotification(Ext_TC_Communication::t("Fehler"), Ext_TC_Communication::t("Es ist keine Korrespondenzsprache vorhanden!"), 'error');
		} else {

			// Doppelte entfernen
			$aCorrespondenceLanguages = array_unique($aFilterData['languages']);

			$aFlags = [];
			foreach ($aCorrespondenceLanguages as $sCorrespondenceLanguage) {
				$oFlag = new Ext_Gui2_Html_Image();
				$oFlag->src = Ext_TC_Util::getFlagIcon($sCorrespondenceLanguage);
				$oFlag->style = 'position: relative; top: 2px';
				$oFlag->title = Ext_TC_Communication::t('Korrespondenzsprache');
				$aFlags[] = $oFlag->generateHTML();
			}

            if ($iTemplateId > 0 && !isset($aTemplates[$iTemplateId])) {
                $oTemplate = \Ext_TC_Pdf_Template::getInstance($iTemplateId);
                if (!$oTemplate->isActive()) {
                    $aTemplates[$iTemplateId] = $oTemplate->name.' ('.Ext_TC_Communication::t('Gelöscht').')';
                } else if (!$oTemplate->isValid()) {
                    $aTemplates[$iTemplateId] = $oTemplate->name.' ('.Ext_TC_Communication::t('Deaktiviert').')';
                }
            }

			$oRow = Ext_TC_Gui2_Util::getInputSelectRow($this->_oParent->getCommunicationObject()->getDialogObject(), array(
				'items' => array(
					array(
						'input' => 'select',
						'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][template_id]',
						'select_options' => $aTemplates,
						'default_class' => 'template_select_communication',
						'value' => (int)$iTemplateId,
						'text_after' => '&nbsp;'.implode('&nbsp;', $aFlags)
					)
				)
			), Ext_TC_Communication::t('Vorlage'));
			
			return $oRow;
		
		} 

	}

	/**
	 * Holt alle markierten Objekte, setzt sie und bereitet sie auch auf
	 */
	protected function setAndPrepObjsForTemplateChoice()
	{
		$aReturn = array(
			'sub_objects' => array(),
			'languages' => array()
		);

		$oCommunication = $this->getParentTab()->getCommunicationObject();
		$aSelectedObjects = $oCommunication->getSelectedObjects();

		// Manche Objekte liefern nur eine Sprache, manche mehrere (z.B. Agentur Schulgruppen)
		foreach($aSelectedObjects as $oObject) {

			$mSubObject = $oObject->getSubObject()?->id;
			if(is_array($mSubObject)) {
				$aReturn['sub_objects'] += $mSubObject;
			} elseif(!empty($mSubObject)) {
				$aReturn['sub_objects'][] = $mSubObject;
			}

			$mCorrespondenceLang = $oObject->getCorrespondenceLanguage($this->_sType);
			if(is_array($mCorrespondenceLang)) {
				$aReturn['languages'] += $mCorrespondenceLang;
			} else {
				$aReturn['languages'][] = $mCorrespondenceLang;
			}

		}

		$this->_oPreparedObjects = $aSelectedObjects;
		return $aReturn;

	}
	
	final public function createAttachmentFields() {
		
		$aRecipientSelects = $this->getAttachmentFields();
		
		if(!empty($aRecipientSelects)) {
			
			$oCommunicationDialog = $this->_oParent->getCommunicationObject()->getDialogObject();
			$oContainer = $oCommunicationDialog->create('div');
			
			foreach($aRecipientSelects as $aRecipientSelect) {
				
				if(isset($aRecipientSelect['info'])) {
					$oContainer->setElement($oCommunicationDialog->createNotification(Ext_TC_Communication::t($aRecipientSelect['label']), $aRecipientSelect['info'], 'info'));
				}
				
				$oContainer->setElement(
					$oCommunicationDialog->createRow(
						Ext_TC_Communication::t($aRecipientSelect['label']), 
						'select', 
						array(
							'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][attachments]['.$aRecipientSelect['type'].']',
							'multiple' => 3, 
							'jquery_multiple' => 1, 
							'style' => 'width: 800px; height: 65px;', 
							'select_options' => $aRecipientSelect['options']
						)
					)
				);		
			}
			
			return $oContainer;
			
		}
		
		return false;
		
	}
	
	/**
	 * Erzeugt den Bereich mit den Empfängern
	 * @return Ext_Gui2_Html_Div
	 */
	public function createRecipientFields() {

		$oContainer = $this->_oParent->getCommunicationObject()->getDialogObject()->create('div');
		
		$aRecipientSelects = $this->getRecipientSelects();

		// Einzelne Empfängerfelder
		foreach($aRecipientSelects as $oRecipientSelect) {
			$oRecipientSelect->aRecipients = $oRecipientSelect->getRecipients();
		}

		foreach($this->_oParent->getCommunicationObject()->getRecipientInputs() as $sKey => $sTranslation) {

			$oFieldset = $this->_oParent->getCommunicationObject()->getDialogObject()->create('fieldset');
			$oFieldset->class = 'simple_editor_container';

			$sRecipientFieldId = $this->_oParent->getType().'_'.$sKey.'_'.$this->_sType;
			
			$oDiv = $this->_oParent->getCommunicationObject()->getDialogObject()->create('div');
			$oDiv->id = 'recipient_save_'.$sRecipientFieldId;
			
			if($sKey != 'to') {
				$oDiv->style = 'display: none';
			}

			$this->_oParent->getCommunicationObject()->getDialogObject()->getDataObject()->addRecipientInputField('save_'.$sRecipientFieldId);
			
			$oFieldset->setElement($this->_oParent->getCommunicationObject()->getDialogObject()->createRow(Ext_TC_Communication::t($sTranslation), 'textarea', array(
				'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][recipients]['.$sKey.']',
				'id' => 'save_'.$sRecipientFieldId,
				'style' => 'height: 40px;width: 800px;',
				'class' => "txt simple_editor"
			)));

			foreach($aRecipientSelects as $oRecipientSelect) {
				
				$oDiv->setElement($this->_oParent->getCommunicationObject()->getDialogObject()->createRow($oRecipientSelect->sTitle, 'select', array(
					'db_alias' => 'communication',
					'db_column' => $this->_oParent->getType().'_'.$sKey.'_'.$this->_sType.'_'.$oRecipientSelect->sKey,
					'multiple' => 5, 
					'jquery_multiple' => 1,
					'select_options' => $oRecipientSelect->aRecipients,
					'searchable' => 1,
					'class' => 'recipientSelect',
					'style' => 'width: 800px; height: 65px;'
				)));
				
			}

			$oFieldset->setElement($oDiv);
			$oContainer->setElement($oFieldset);

		}

		return $oContainer;
		
	}
	
	/**
	 * Erzeugt ein Betrefffeld
	 * @return Ext_Gui2_Html_Div 
	 */
	final public function createSubjectField($sSubject = '')
	{
		global $_VARS;
		
		$aValues = $this->_oParent->getCommunicationObject()->getSaveValues();
		$mValue = $aValues[$this->_oParent->getType()][$this->_sType]['subject'];
		
		if(
			!$mValue ||
			$_VARS['task'] === 'reloadDialogTab'
		) {
			$mValue = $sSubject;
		}
		
		$oRow = $this->_oParent->getCommunicationObject()->getDialogObject()->createRow(Ext_TC_Communication::t('Betreff'), 'input', array(
			'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][subject]',
			'value' => $mValue,
			'style' => 'width: 800px;'
		));
		
		return $oRow;
	}
	
	/**
	 * Erzeugt das Inhaltsfeld
	 * @return Ext_Gui2_Html_Div 
	 */
	final public function createContentField($sContent = '', $bHtml = false)
	{
		global $_VARS;
		
		$aValues = $this->_oParent->getCommunicationObject()->getSaveValues();
		$mValue = $aValues[$this->_oParent->getType()][$this->_sType]['content'];
		
		if(
			!$mValue ||
			$_VARS['task'] === 'reloadDialogTab'
		) {
			$mValue = $sContent;
		}

		$aOptions = array(
			'id' => 'save_'.$this->_oParent->getType().'_'.$this->_sType.'_content',
			'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][content]',
			'default_value' => $mValue,
			'style' => 'width: 800px; height:380px;'
		);

		$sType = 'textarea';
		if($bHtml) {

			$sType = 'html';
			$aOptions['advanced'] = true;

		}
		
		$oRow = $this->_oParent->getCommunicationObject()->getDialogObject()->createRow(Ext_TC_Communication::t('Inhalt'), $sType, $aOptions);
		
		return $oRow;
	}
	
	/**
	 * Holt die Markierungen und erzeugt ein Select
	 * @param Ext_TC_Communication_Template $oTemplate
	 * @return mixed 
	 */
	final public function createFlagFields()
	{
		$aTemplateFlags = $this->_oTemplate->flags;
				while(ob_get_level()) {
					ob_end_clean();
				}

		if(!empty($aTemplateFlags)) {

			$oCommunication = $this->_oParent->getCommunicationObject();
			$oCommunicationDialog = $oCommunication->getDialogObject();

			// Wird nachher in die Ext_TC_Communication_Gui2_Dialog_Data geschrieben, damit man dort den Wert wieder hat
			$aCacheFlags = array();

			$aFlagOptions = $this->getFlags();
			$oDiv = $oCommunicationDialog->create('div');

			// Nur die Flags anzeigen, die auch beim Template angegeben sind
			foreach($aTemplateFlags as $sFlagKey) {

				// Prüfen ob der Flag für den aktuellen Dialog erlaubt ist
				if (!$this->validateTemplateFlag($sFlagKey)) {
					continue;
				}

				$aFieldsetCache = array(); // Cached die Objekte für Fieldsets
				$aFlagOption = $aFlagOptions[$this->getType()][$sFlagKey];
				$sSubobjectMethod = $aFlagOption['subobjects_method'];
				$bHasSubObjects = !empty($sSubobjectMethod);
				$aSubObjectOptions = array();

				// Cache initiieren für aktuellen Flag – siehe Ext_TC_Communication_Gui2_Dialog_Data::$aFlagCache
				$aCacheFlag = &$aCacheFlags[$this->_oParent->getType()][$this->getType()][$sFlagKey];
				$aCacheFlag = array();

				// Multiselects generieren
				if($bHasSubObjects) {

					// Alle Empfänger holen und für jeden Empfänger die SubObjects holen
					$aRecipients = (array)$oCommunication->getDialogDataObject()->aRecipientCache[$this->getType()];
         
					foreach($aRecipients as $aRecipient) {
                        
                        // Exceptions abfangen
                        if(
                            empty($aRecipient['object']) ||
                            $aRecipient['object_id'] <= 0
                        ){
                            throw new Exception('Unknown Object!');
						}

						$oContactClass = Ext_TC_Factory::getInstance($aRecipient['object'], $aRecipient['object_id']); // Bsp.: Ext_TA_Inquiry_Contact
						$oSelectedObject = Ext_TC_Factory::getInstance($oCommunication->getObjectClassName(), $aRecipient['selected_id']); // Bsp.: Ext_TA_Inquiry

						if(!method_exists($oContactClass, $sSubobjectMethod)) {
							throw new Exception('Method "'.get_class($oContactClass).'" doesn\'t own needed method "'.$sSubobjectMethod.'" for createFlagFields()!');
						}

						$aContactMultiSelects = $oContactClass->$sSubobjectMethod($sFlagKey, $oSelectedObject);

						// SelectOptions auf 2ter Ebene filtern
						// Gleiche Funktion wie array_merge_recursive unter Beibehaltung der Keys (Ids)
						foreach($aContactMultiSelects as $sClass => $aContactMultiSelect) {
							foreach($aContactMultiSelect as $iId => $sLabel) {

								// Daten in den Cache schreiben – siehe Ext_TC_Communication_Gui2_Dialog_Data::$aFlagCache
								if(!in_array($iId, (array)$aCacheFlag[$aRecipient['object']][$aRecipient['object_id']][$sClass])) {
									$aCacheFlag[$aRecipient['object']][$aRecipient['object_id']][$sClass][] = $iId;
								}

								// SelectOptions filtern
								if(!in_array($iId, (array)$aSubObjectOptions[$sClass])) {
									$aSubObjectOptions[$sClass][$iId] = $sLabel;
								}
							}
						}
					}

					if(!empty($aSubObjectOptions)) {
						// Jedes Element ist ein einzelnes Multiselect (Key) mit dessen Values (Wert)
						foreach($aSubObjectOptions as $sClass => $aSelectOption) {
                            
                            // Exceptions abfangen
                            if(
                                empty($sClass)
                            ){
                               throw new Exception('Unknown Class!');
                            }
                            
                            $oClass = Ext_TC_Factory::executeStatic($sClass, 'getClassLabel', array(true));
                            
							$aFieldsetCache[] = $oCommunicationDialog->createRow($oClass, 'select', array(
								'multiple' => 5,
								'jquery_multiple' => 1,
								'select_options' => $aSelectOption,
								'searchable' => 1,
								'style' => 'width: 800px; height: 65px;',
								'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][flags]['.$sFlagKey.'][subobjects]['.$sClass.'][]',
								'class' => 'communication_flag_checkbox_multiselect',
								'row_style' => 'display: none',
							));
						}
					} else {
						$aFieldsetCache[] = $oCommunicationDialog->createNotification(Ext_TC_Communication::t('Warnung'), Ext_TC_Communication::t('Es sind keine entsprechenden Optionen vorhanden.'), 'hint');
					}

				}

				$oFieldSet = $oCommunicationDialog->create('fieldset');

				$bDisabled =  false;
				// Markierung deaktivieren, wenn Unterobjekte da sein sollten, aber fehlen
				if(
					empty($aSubObjectOptions) &&
					$bHasSubObjects
				) {
					$bDisabled =  true;
				}

				$oCheckbox = $oCommunicationDialog->createRow($aFlagOption['label'], 'checkbox', array(
					'name' => 'save['.$this->_oParent->getType().']['.$this->_sType.'][flags]['.$sFlagKey.'][checked]',
					'class' => 'communication_flag_checkbox',
					'readonly' => $bDisabled
				));
				$oFieldSet->setElement($oCheckbox);

				// Multiselects in Fieldset schreiben
				foreach($aFieldsetCache as $oElement) {
					$oFieldSet->setElement($oElement);
				}

				$oDiv->setElement($oFieldSet);
                
			}

			$oCommunication->getDialogDataObject()->aFlagCache = $aCacheFlags;
            
            
			return $oDiv;
			
		}
		
		return false;
		
	}

	protected function validateTemplateFlag(string $sFlag): bool {
		return true;
	}

	/**
	 * Generiert ein Array mit den ausgewählten Uploads im Template (Content-Sprache) für die Anhänge
	 * @return array
	 */
	public function getUploadAttachmentField()
	{
		
		$aUploads = $aSelect = array();
		$aContentUploads = $this->_oTemplateContent->to_uploads;

		if(!empty($aContentUploads)) {
			
			foreach($aContentUploads as $iUpload) {
				$oUpload = Ext_TC_Upload::getInstance($iUpload);
				if($oUpload->id > 0) {
					$aUploads[$oUpload->id] = $oUpload->getName();
				}
			}

			$aSelect = array(
				'label' => Ext_TC_Communication::t('Uploads'),
				'type' => 'upload',
				'options' => $aUploads

			);

		}
		
		return $aSelect;
		
	}

	/**
	 * Liefert für den jeweiligen Anwendungsfall die Flags
	 *
	 * Array Ebenen:
	 *
	 * 1. Ebene: Key: Typ der TabArea, Wert (array): Flags
	 * 2. Ebene  Key: Flag, Wert (array): Flag Werte
	 * 3. Ebene: Werte für die Flags (Label, SubObject Methode, SetFlag Methode)
	 *
	 * SubObject Methode: Diese Methode wird auf das Empfängerobjekt (Contact) aufgerufen.
	 * 	Diese Methode liefert ein multidimensonales Array mit den Multiselects unter dieser Checkbox
	 * 	Parameter: $sFlag (Key des Flags), $oSelectedObject (Inquiry etc)
	 * SetFlag Methode: Diese Methode wird auf das SelectedObject (Inquiry etc) aufgerufen.
	 * 	Parameter: $aFlags (Array mit Flags und ausgewählten Werten in Multiselects), $aEmail, $iLogId
	 */
	public static function getFlags()
	{
		$aFlags = array();
		return $aFlags;
	}
	
	public function getParentTab()
	{
		return $this->_oParent;
	}

	public function getType() {
		return $this->_sType;
	}
	
	/**
	 * Möglichkeit vorab die Verfügbarkeit von Empfängern zu prüfen
	 * @return boolean
	 */
	protected function checkPossibleRecipients() {
		return true;
	}
	
}
