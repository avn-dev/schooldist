<?php

/**
 * Kommunikation DialogData
 */
class Ext_TC_Communication_Gui2_Dialog_Data extends Ext_Gui2_Dialog_Data {
	
	/**
	 * @see self::addRecipientInputField
	 * @var array 
	 */
	protected $_aRecipientInputFields = array();
	
	/**
	 * Empfänger cachen
	 * @var array
	 */
	public $aRecipientCache = array();

	/**
	 * Flags cachen
	 * Dies ist vor allem nötig, da man nachher wissen muss, welches Kontaktobjekt welche Flag-SubObjects mit sich bringt.
	 *
		public $aFlagCache = array(
			'email' => array(
	 			'customer' => array(
					'arrival_infos_requested' => array(
						'Ext_TA_Inquiry_Journey' => array(
							1 => array(
								'Ext_TA_Inquiry_Traveller' => array(
									2, 3
								),
								'Ext_TA_Inquiry_Booker' => array(
									1
								)
							)
						)
					)
	 			)
	 		)
		);
	 *
	 * @var array
	 */
	public $aFlagCache = array();
	
	/**
	 * Enthält die aktuellen Rechte des Icons
	 * Wird durch createDialogObject in der Kommunikationsklasse gesetzt.
	 * 
	 * @var array 
	 * @see Ext_TC_Communication::createDialogObject()
	 */
	public $aAccess = array();
	
	/**
	 * Sammelfunktion für Fehler
	 * @var array
	 */
	protected $_aErrors = array();
	protected $_sErrorAdditional = '';
	
	public function getHtml($sAction, $aSelectedIds, $sAdditional = false) {
		global $_VARS;

		if(
			$_VARS['task'] === 'openDialog' ||
			$_VARS['task'] === 'saveDialog'
		) {

			$aSelectedIds = $this->prepareSelectedIds($aSelectedIds);

			$oCommunication = Ext_TC_Communication::createCommunicationObject($this->_oDialog, $sAdditional, $aSelectedIds);
			$this->resetDialog();

			$aTabOptions = array();
			if(count($aSelectedIds) > 1) {
				$aTabOptions = array(
					'show_history' => false
				);
			}

			// Dialog aufbauen (dazu Cache leeren)
			$oCommunication->getDialogTabs($aTabOptions, true);
			
		} elseif($_VARS['task'] === 'reloadDialogTab') {
			
			$iTabId = (int)reset($_VARS['reload_tab']);
			
			$oCommunication = &$this->_oDialog->aElements[$iTabId]->getCommunicationObject();
			$sTabType = $this->_oDialog->aElements[$iTabId]->getType();
			
			$sTabClass = $oCommunication->getClassName('Tab');
			$this->_oDialog->aElements[$iTabId] = new $sTabClass($oCommunication, $sTabType);

		}

		$aData = parent::getHtml($sAction, $aSelectedIds, $sAdditional);

		// Besondere TinyMCE-Felder in $aTransfer['data']['communication'] … setzen
		$aData['communication']['recipient_input_fields'] = $this->_aRecipientInputFields;
		$aData['communication']['translations'] = $this->_getTranslations();

		return $aData;
		
	}
	
	public function save($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		global $_VARS;

		$aOriginalSelectedIds = $aSelectedIds;
		$aSelectedIds = $this->prepareSelectedIds($aSelectedIds);

		$oCommunication = Ext_TC_Communication::createCommunicationObject($this->_oDialog, $sAdditional, $aSelectedIds);

		// Aktuellen ausgewählten Tab aus dem Dialog-Cache holen
		list($oTab, $oTabArea) = $oCommunication->getTabsFromVars($_VARS['save']);
		
		$sTabType = $oTab->getType();
		$sTabAreaType = $oTabArea->getType();
		
		$iShowSkipCheckbox = 0;

		$iTabId = false;
		if(isset($_VARS['reload_tab'])) {
			$iTabId = (int)reset($_VARS['reload_tab']);
		} elseif(!empty($_VARS['save']['current_tab'])) {
			$iTabId = (int)$_VARS['save']['current_tab'];
		}

		// Auf SMS umschalten
		$oCommunication->sTransferType = $sTabType;

		if($bSave) {

			$aReturn = $oCommunication->saveDialog($aData);

			if($aReturn['success']) {
				// Alle Eingaben zurücksetzen
				$_VARS['save'] = array();
			}

			$aTransfer						= array();
			$aTransfer['action']			= 'saveDialogCallback';
			$aTransfer['dialog_id_tag']		= Ext_TC_Communication::sDialogIdPrefix;

			if(!empty($aReturn['errors'])) {

				$aTransfer['error'] = $this->getErrorData($aReturn['errors'], 'error', false);

			} elseif(
				!empty($aReturn['hints']) &&
				(
					!isset($_VARS['ignore_errors']) ||
					(
						isset($_VARS['ignore_errors']) &&
						$_VARS['ignore_errors'] != 1
					)
				)
			) {

				$aTransfer['error'] = $this->getErrorData($aReturn['hints'], 'hint', false);
				$iShowSkipCheckbox = 1;

			} elseif($aReturn['success']) {
				$aTransfer['error'] = array();

				if($oCommunication->sTransferType === 'app') {
					$aTransfer['success_message'] = $this->_oGui->t('Die Nachricht wurde erfolgreich verschickt.');
				} elseif($oCommunication->sTransferType === 'sms') {
					$aTransfer['success_message'] = $this->_oGui->t('Die SMS wurde erfolgreich verschickt.');
				} else {
					$aTransfer['success_message'] = $this->_oGui->t('Die E-Mail wurde erfolgreich verschickt.');
				}

			}

			// Wenn keine Fehler da sind und nicht Massenkommunikation, dann Verlauf anzeigen
			if(empty($aReturn['errors']) && count($_VARS['id']) === 1) {
				$aTransfer['tab'] = 'communication_history';
			}

			// Dialog zurücksetzen und komplett neu aufbauen
			$oCommunication->getDialogTabs(array(), true);

			// Beim Speichern die originalen Werte für $aSelectedIds setzen da sonst beim createTable die Auswahl zurückgesetzt
			// wird, wenn die Gui auf encode_data setzt
			$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog($sAction, $aOriginalSelectedIds, $iTabId, $sAdditional, false);
		} else {
			$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog($sAction, $aSelectedIds, $iTabId, $sAdditional, false);
		}


		$aTransfer['data'] = $aTransferData;
		$aTransfer['data']['show_skip_errors_checkbox'] = $iShowSkipCheckbox;
		
		$aErrors = $this->_aErrors;
		if(!empty($aErrors)) {

			// Beim Speichern einen anderen Fehlertitel anzeigen, als beim Laden vom Template
			if($bSave) {
				$sErrorTop = $oCommunication->getErrorMessageTop();
				
			} else {
				$sErrorTop = L10N::t('Es ist ein Fehler aufgetreten!');
			}
			array_unshift($aErrors, $sErrorTop);

			$aTransfer['action'] = 'showError';
			$aTransfer['error']	= $this->getErrorData($aErrors, 'error', false);

			$this->_aErrors = array();
			
		}

		return $aTransfer;
		
	}

	/**
	 * Resettet den Dialog
	 */
	public function resetDialog()
	{
		$this->_oDialog->aElements = array();
		$this->_aRecipientInputFields = array();
		$this->aRecipientCache = array();
		$this->_aErrors = array();
	}

	/**
	 * Fügt ein Feld zum Initialisieren der besonderen TinyMCEs hinzu
	 * @param string $sId 
	 */
	public function addRecipientInputField($sId)
	{
		if(!in_array($sId, $this->_aRecipientInputFields)) {
			$this->_aRecipientInputFields[] = $sId;
		}
	}
	
	/**
	 * Fehlermeldungen
	 * @param type $sError
	 * @param type $sField
	 * @param type $sLabel
	 * @return type 
	 */
	public function getErrorMessage($sError, $sField, $sLabel='') {
		$sMessage = '';

		switch($sError) {
			case 'NO_SENDER':
				$sMessage = $this->_oGui->t('Bitte wählen Sie einen Absender aus.');
				break;
			case 'NO_TEMPLATE':
				$sMessage = $this->_oGui->t('Bitte wählen Sie eine Vorlage aus.');
				break;
			case 'NO_RECIPIENTS':
				$sMessage = $this->_oGui->t('Bitte wählen Sie mindestens einen Empfänger aus.');
				break;
			case 'RECIPIENTS_WRONG_FORMAT':
				$sLabel = $this->_sErrorAdditional;
				$sMessage = $this->_oGui->t('Folgende E-Mail-Adresse ist nicht korrekt: %s');
				break;
			case 'RECIPIENTS_WRONG_FORMAT_MULTIPLE':
				$sLabel = $this->_sErrorAdditional;
				$sMessage = $this->_oGui->t('Folgende E-Mail-Adressen sind nicht korrekt: %s');
				break;
			case 'RECIPIENTS_WRONG_FORMAT_SMS':
				$sLabel = $this->_sErrorAdditional;
				$sMessage = $this->_oGui->t('Folgende Mobilfunknummer ist nicht korrekt: %s');
				break;
			case 'RECIPIENTS_WRONG_FORMAT_SMS_MULTIPLE':
				$sLabel = $this->_sErrorAdditional;
				$sMessage = $this->_oGui->t('Folgende Mobilfunknummern sind nicht korrekt: %s');
				break;
			case 'NO_SUBJECT':
				$sMessage = $this->_oGui->t('Bitte geben Sie einen Betreff ein.');
				break;
			case 'NO_CONTENT':
				$sMessage = $this->_oGui->t('Bitte geben Sie einen Inhalt ein.');
				break;
			case 'NOT_SENT':
				$sMessage = $this->_oGui->t('Die E-Mail konnte nicht verschickt werden.');
				break;
			case 'SERVER_ERROR':
				$sMessage = $this->_oGui->t('Der E-Mail-Server konnte die Nachricht nicht versenden.');
				break;
			case 'SMARTY_EXCEPTION':
				$sLabel = $this->_sErrorAdditional;
				$sMessage = $this->_oGui->t('Die Vorlage konnte nicht verarbeitet werden: %s');
				break;
			case 'SMARTY_EXCEPTION_PLACEHOLDER':
				$sLabel = $this->_sErrorAdditional;
				$sMessage = $this->_oGui->t('Die Vorlage enthält ungültige Platzhalter: %s');
				break;
			case 'NO_CREDITS_LEFT':
			case 'NOT_SENT_SMS':
			case 'NO_SENDER_SMS':
			case 'WRONG_SENDER_FORMAT':
			case 'SERVER_ERROR_SMS':
				$sMessage = Ext_TC_Communication_SMS_Gateway::convertErrorKeyToMessage($sError);
				$sMessage = $this->_oGui->t($sMessage);
				break;
			case 'NO_PLACEHOLDER':
				$sMessage = $this->_oGui->t('Es ist eine Makierung aktiv, allerdings kein Makierungs-Platzhalter vorhanden!');
				break;
			case 'NO_FLAG':
				$sMessage = $this->_oGui->t('Es ist ein Makierungs-Platzhalter vorhanden, allerdings keine Makierung aktiv!');
				break;
			case 'CHECKSUBOBJECT_INVALID':
				$sMessage = $this->_oGui->t('Die Einstellungen des Fragebogens stimmen nicht mit den gebuchten Leistungen überein!');
				break;
			case 'EMPTY_ATTACHMENT_FILE':
                $sMessage = $this->_oGui->t('Im Anhang befinden sich leere Dateien!');
                break;
			case 'ATTACHMENT_NO_RECEIPIENT':
				$sMessage = $this->_oGui->t('Für den Anhang "%s" wurde keine passender Empfänger ausgewählt');
				break;
			case '':
				$sMessage = '';
				break;
			default:
				$sMessage = Ext_Gui2_Data::convertErrorKeyToMessage($sError);
				$sMessage = L10N::t($sMessage, Ext_Gui2::$sAllGuiListL10N);
				break;
		}

		if(!empty($sLabel)){
			$sMessage = sprintf($sMessage, $sLabel);
		}

		return $sMessage;
	}
	
	protected function _getTranslations()
	{
		$aTranslations = array(
			'send_email' => Ext_TC_Communication::t('E-Mail absenden'),
			'send_sms' => Ext_TC_Communication::t('SMS absenden')
		);
		
		return $aTranslations;
	}
	
	public function setError($sError, $sAdditional = '') {
		
		$this->_aErrors[] = $sError;
		
		if(!empty($sAdditional)) {
			$this->_sErrorAdditional = htmlentities($sAdditional);
		}
		
	}

	protected function prepareSelectedIds(array $aSelectedIds): array {

		if ($this->_oGui->checkEncode() && !empty($sEncodeField = $this->_oGui->getOption('communication_encode_field'))) {
			// Bestimmten Key aus encode_data nehmen
			$aSelectedIds = array_map(fn ($id) => $this->_oGui->decodeId($id, $sEncodeField), $aSelectedIds);
		}

		return $aSelectedIds;
	}

}
