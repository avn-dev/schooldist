<?php

/**
 * Kommunikation
 * 
 * Hinweise:
 * 
 *	Fehlermeldungen
 *	
 *		Es gibt zwei Wege, um einen Fehler zu setzen:
 *		* Die Rückgabe in saveDialog
 *		* Über das DataObject, die setError()-Methode
 * 
 *
 * @since 07.10.2011
 */
class Ext_TC_Communication {

	const sL10NPath = 'Thebing Core » Communication';
	const sDialogIdPrefix = 'COMMUNICATION_';

	const SEND_MODE_AUTOMATIC = 'automatic';
	const SEND_MODE_SPOOL = 'spool';

	protected $_sClassNamePrefix = 'Ext_TC_Communication';
	
	/**
	 * @var Ext_Gui2_Dialog
	 */
	protected $_oDialog;

	protected $_aSelectedIds = array();
	protected $_aOriginalSelectedIds = array();
	protected $_sApplication = '';

	protected $_aRecipientInputs = array(
		'to' => 'An',
		'cc' => 'CC',
		'bcc' => 'BCC'
	);

	/**
	 * Default-Einstellungen, welche Tabs angezeigt werden
	 * @var array
	 */
	protected $_aDialogTabOptions = array(
		'show_notices' => false,
		'show_history' => true,
		'show_placeholders' => true
	);

	/**
	 * Objekt-Klassenname
	 * @var string 
	 */
	protected $_sObject = '';

	// TODO Entfernen mit Methode und Vorkommnissen
	/**
	 * Objekt-Instanz
	 * @var WDBasic 
	 */
	protected $_oObject = null;
	
	/**
	 * Typ des Versendens
	 * @var string E-Mail oder SMS
	 */
	public $sTransferType = 'email';
	
	/**
	 * Enthält nach dem Senden das $oMail oder SMS-Gateway Objekt.
	 * @var object
	 */
	protected $_oSendObject = null;
	
	/**
	 * Absender von SMS
	 * Wird nur bei Bedarf gesetzt.
	 * @review DG Bitte korrektes Format für den Namen verwenden
	 * @var string
	 */
	protected $_sSmsSender = '';

	/**
	 * Absender
	 * @var Ext_TC_User
	 */
	protected $_oIdentityUser = null;

	protected $_bMassCommunication = false;
	public $bSkipAdressRelationSaving = false;
	protected $_sDefaultLanguage = 'en';

	/**
	 * Platzhalter die nicht über die generellen Platzhalter ersetzt werden können oder sollten (z.b. Passwörter)
	 * TODO schöner wäre es das mit in die zentralen Platzhalter zu übernehmen
	 *
	 * [
	 * 		'mask_with' => '*' Wert wird beim Speichern der Nachricht maskiert (z.b. Passwörter)
	 * ]
	 *
	 * @var array
	 */
	protected $_aHiddenPlaceholders = [];

	/**
	 * Array mit allen Tabs des Dialogs
	 * @var array
	 */
	protected $_aCachedTabs = array();
	
	/**
	 * Zuweisung der Appliations zu ihren Objekten
	 * @var array
	 */
	protected static $_aApplicationAllocations = array(
		'test' => 'Ext_Test_Communication'
	);

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param $aSelectedIds
	 */
	public function __construct(Ext_Gui2_Dialog $oDialog, $aSelectedIds) {
		$this->_oDialog = $oDialog;

		$this->_aSelectedIds = $this->_aOriginalSelectedIds = $aSelectedIds;
		$this->_sApplication = $oDialog->additional ?? null;
	}

	/**
	 * @param $sTranslate
	 * @return string
	 */
	public static function t($sTranslate) {

		if(!empty($sTranslate)) {
			$sTranslate = L10N::t($sTranslate, self::sL10NPath);
		}

		return $sTranslate;
	}

	/**
	 * @param string $sClassSuffix
	 * @return string
	 */
	public function getClassName($sClassSuffix='') {
		
		$sClassName = get_class($this);

		if(!empty($sClassSuffix)) {
			if(strpos($sClassName, '\\') !== false) {
				$sClassName .= '\\'.str_replace('_', '\\', $sClassSuffix);
			} else {
				$sClassName .= '_'.$sClassSuffix;
			}
		}

		if(!class_exists($sClassName)) {
			$sClassName = 'Ext_TC_Communication';
			if(!empty($sClassSuffix)) {
				$sClassName .= '_'.$sClassSuffix;
			}
		}

		return $sClassName;
	}

	/**
	 * @return array
	 */
	public function getSelectedIds() {
		return $this->_aSelectedIds;
	}

	/**
	 * Erzeugt das Objekt des Kommunikationsdialoges
	 *
	 * @param Ext_Gui2 $oGui
	 * @param array $aAccess
	 * @param $sApplication
	 * @return Ext_Gui2_Dialog
	 */
	public static function createDialogObject(Ext_Gui2 &$oGui, $aAccess, $sApplication) {

		$oDialog = $oGui->createDialog(self::t('Kommunikation'), '', self::t('Massenkommunikation'));
		$oDialog->sDialogIDTag = self::sDialogIdPrefix;
		$oDialog->setDataObject(\Ext_TC_Communication_Gui2_Dialog_Data::class);
		$oDialog->getDataObject()->aAccess = $aAccess;
		$oDialog->save_button = false;
		$oDialog->action = 'communication';
		$oDialog->additional = $sApplication;

		$aButton = array(
			'label'			=> self::t('Absenden'),
			'task'			=> 'saveDialog',
			'action'		=> 'communication',
			'additional'	=> $sApplication
		);
		
		$oDialog->aButtons = array($aButton);
		
		return $oDialog;
	}

	/**
	 * Erzeugt eine Instanz der Klasse anhand ihrer Application
	 * Dies ist eine Factory-Methode.
	 *
	 * @param $oDialog Ext_Gui2_Dialog
	 * @param $sApplication
	 * @param $aSelectedIds
	 * @return Ext_TC_Communication
	 */
	public static function createCommunicationObject(&$oDialog, $sApplication, $aSelectedIds) {

		$sCommunication = Factory::executeStatic('Ext_TC_Communication', 'getClassNameOfApplication', array($sApplication));
		$oCommunication = new $sCommunication($oDialog, $aSelectedIds);

		return $oCommunication;
	}

	/**
	 * Communication Tabs
	 * Als Beispiel soll kein SMS-Tab angezeigt werden, wenn kein Gateway eingestellt ist.
	 *
	 * @param array $aOptions
	 * @return array Ext_TC_Communication_Tab
	 */
	public function getTabs($aOptions = array()) {

		$aTabs = array();

		$sTabClass = $this->getClassName('Tab');
		
		if(
			!isset($aOptions['show_email']) ||
			$aOptions['show_email'] === true
		) {
			$aTabs[] = Ext_TC_Factory::getObject($sTabClass, array($this, 'email'));
		}

		if(
			!empty($aOptions['show_app']) &&
			(
				php_sapi_name() === 'cli' || // Nachrichten die über das PP verschickt werden
				Access::getInstance()->hasRight(['core_communication', 'app'])
			)
		) {
			$aTabs[] = Ext_TC_Factory::getObject($sTabClass, array($this, 'app'));
		}
		
		if(
			(
				!isset($aOptions['show_sms']) ||
				$aOptions['show_sms'] === true
			) &&
			Access::getInstance()->hasRight(['core_communication', 'sms'])
		) {
			$aTabs[] = Ext_TC_Factory::getObject($sTabClass, array($this, 'sms'));
		}

		if(
			!empty($aOptions['show_notices']) &&
			Access::getInstance()->hasRight(['core_communication_notes'])
		) {
			$aTabs[] = Ext_TC_Factory::getObject($sTabClass, array($this, 'notice'));
		}

		if(!empty($aOptions['show_history'])) {
			$aTabs[] = Ext_TC_Factory::getObject($sTabClass, array($this, 'history'));
		}

		if(!empty($aOptions['show_placeholders'])) {
			$aTabs[] = Ext_TC_Factory::getObject($sTabClass, array($this, 'placeholder'));
		}

		return $aTabs;
	}

	/**
	 * Zuweisungsmethode der Applications zu den jeweiligen Ableitungen der Kommunikationsklasse.
	 *
	 * FACTORY-Methode
	 *
	 * @param $sApplicationKey
	 * @return mixed
	 * @throws Exception
	 * @since 25.10.2011
	 */
	public static function getClassNameOfApplication($sApplicationKey) {

		if(!isset(static::$_aApplicationAllocations[$sApplicationKey])) {
			throw new Exception('There is no communication application key "'.$sApplicationKey.'"!');
		}
		
		return static::$_aApplicationAllocations[$sApplicationKey];
	}

	/**
	 * @return array
	 */
	static public function getApplicationAllocations() {
		return static::$_aApplicationAllocations;
	}
	
	/**
	 * @param $sEmails
	 * @param $oTabArea
	 * @return array|bool
	 */
	protected function _decodeRecipients($sEmails, $oTabArea) {

		if(empty($sEmails)) {
			return [];
		}
		
		$aRecipients = array();
		$aErrorFields = array();
		
		$oDialogData = $this->_oDialog->getDataObject();
		
		$sEmails = strip_tags($sEmails, '<span>');

		// HTML Sonderzeichen decodieren
		$sEmails = str_replace('&nbsp;', ' ', $sEmails);
		$sEmails = html_entity_decode($sEmails, ENT_NOQUOTES, 'UTF-8');

		preg_match_all("/<span.*?title=\"(.*?)\".*?>(.*?)<\/span>/i", $sEmails, $aMatches);

		// Adressen aus dem Cache
		foreach((array)$aMatches[0] as $iKey => $sMatch) {
			$aRecipients[] = $oDialogData->aRecipientCache[$oTabArea->getType()][$aMatches[1][$iKey]];
			$sEmails = str_replace($sMatch, '', $sEmails);
		}                            

		$sEmails = preg_replace("/;\s*;/", "", $sEmails);
		$aParts = preg_split("/\s*;\s*/", $sEmails);

		// Manuell eingegebene Adressen
		foreach((array)$aParts as $sPart) {
			
			$sPart = trim($sPart);

			$oValidate = new WDValidate();
			$oValidate->value = $sPart;

			if($this->sTransferType === 'sms') {
				$oValidate->check = 'PHONE_ITU';
			} else {
				$oValidate->check = 'MAIL';
			}

			if($oValidate->execute()) {
				$aItem = array();    
				$aItem['object'] = false;
				$aItem['object_id'] = -1;
				$aItem['address'] = $sPart;
				$aRecipients[] = $aItem;
			}
			// Leere Felder ignorieren
			elseif(!empty($sPart)) {
				$aErrorFields[] = $sPart;
			}
			
		}

		$mReturn = $aRecipients;
		if(!empty($aErrorFields)) {
			
			$sError = 'RECIPIENTS_WRONG_FORMAT';
			if($this->sTransferType === 'sms') {
				$sError = 'RECIPIENTS_WRONG_FORMAT_SMS';
			}
			
			if(count($aErrorFields) > 1) {
				$sError .= '_MULTIPLE';
			}
			
			$this->getDialogDataObject()->setError($sError, implode(', ', $aErrorFields));
			
			$mReturn = false;
			
		}

		return $mReturn;
	}

	/**
	 * Diese Methode dekodiert die Flags, in dem nur die richtigen Flags zurückgeliefert werden, die der abgesendeten TabArea entsprechen
	 */
	protected function _decodeFlags($aSelectedFlags, Ext_TC_Communication_Tab_TabArea $oTabArea) {

		$aReturn = array();

		foreach((array)$aSelectedFlags as $sFlagKey => $aFlagValues) {

			// Wenn Flag eh nicht ausgewählt wurde, dann rausschmeißen
			if(!$aFlagValues['checked']) {
				continue;
			}

			$aReturn[$sFlagKey] = $aFlagValues;

		}

		return $aReturn;
	}

	/**
	 * Ersetzt die Platzhalter
	 *
	 * @param string $sText
	 * @param array $aOptions language (ISO-2); object (Instanz); type (text, html)
	 * @return string
	 * @since 11.01.12
	 */
	public function replacePlaceholders($sText, $aOptions = array()) {

		$oObject = $aOptions['object'];

		$iSubObject = (int)$oObject->getSubObject()?->id;
		$oSubObject = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $iSubObject);
		
		// Signatur - Platzhalter

		$oUserSignature = $this->_oIdentityUser->getSignatureForObject($oSubObject);
        if (!$oUserSignature) {
            // Die Signaturen-Platzhalter sollten auf jeden Fall ersetzt werden da ansonsten das Template die Platzhalter nicht
            // kennt und Fehler wirft.
            $oUserSignature = Factory::getObject(\Ext_TC_User_Signature::class);
        }

        $oSignaturePlaceholder = $oUserSignature->getPlaceholderObject();
        $oSignaturePlaceholder->setDisplayLanguage($aOptions['language']);

        $aSignaturePlaceholder = $oSignaturePlaceholder->getPlaceholders();

        foreach($aSignaturePlaceholder as $sSignaturePlaceholder => $aSignaturePlaceholderData) {

            $sSignaturePlaceholder = '{'.$sSignaturePlaceholder.'}';

            if(mb_strpos($sText, $sSignaturePlaceholder) !== false) {
                $sSignature = $oSignaturePlaceholder->replace($sSignaturePlaceholder);
                $sText = str_replace($sSignaturePlaceholder, $sSignature, $sText);
            }
        }

		// --- Feste E-Mail Signatur ---
		if(mb_strpos($sText, '{email_signature}') !== false) {
			
			$aSignatureTexts = $oSubObject->communication_emailsignatures;
			$sSignature = '';

			foreach((array)$aSignatureTexts as $aSignatureText) {
				if($aSignatureText['language_iso'] == $aOptions['language']) {
					if($aOptions['type'] == 'html') {
						$sSignature = $aSignatureText['html'];
					} else {
						$sSignature = $aSignatureText['text'];
					}
					break;
				}
			}

			if(!empty($sSignature)) {
				if($oUserSignature) {
					$oSignaturePlaceholder = $oUserSignature->getPlaceholderObject();					
					$sSignature = $oSignaturePlaceholder->replace($sSignature);
				} else {
					// Schauen ob noch Platzhalter in dem Signature-Template vorhanden sind
					$oPlaceholderUtil = new Ext_TC_Placeholder_Util();
					$aSignaturePlaceholders = $oPlaceholderUtil->getPlaceholdersInTemplate($sSignature);
					// Wenn es noch Platzhalter gibt dann müssen diese mit "" ersetzt werden. Ansonsten werden diese
					// mit in das Placeholder-Objekt unten geschleift und dieses wirft dann Fehler. Im Frontend gibt es nie
					// ein User-Objekt mit dem Platzhalter ersetzt werden könnten
					if(!empty($aSignaturePlaceholders)) {
						$sSignature = '';
					}					
				}
			}

			$sText = str_replace('{email_signature}', $sSignature, $sText);

		}

		// --- Restliche Platzhalter ---
		$aErrors = array();
		$oPlaceholder = $oObject->getPlaceholderObject();

		if($oPlaceholder) {
			/* @var \Ext_TC_Placeholder_Abstract $oPlaceholder*/

			foreach($this->_aHiddenPlaceholders as $sPlaceholder => $aPlaceholderOptions) {
				$sText = str_replace('{'.$sPlaceholder.'}', '#'.$sPlaceholder.'#', $sText);
			}

			$oPlaceholder->setType('communication');
			$oPlaceholder->setCommunicationSender($this->_oIdentityUser);
			$oPlaceholder->setDisplayLanguage($aOptions['language']);
			$sText = $oPlaceholder->replace($sText);
			$aErrors = $oPlaceholder->getErrors();

			foreach($this->_aHiddenPlaceholders as $sPlaceholder => $aPlaceholderOptions) {
				$sText = str_replace('#'.$sPlaceholder.'#', '{'.$sPlaceholder.'}',$sText);
			}
		}

		$bSuccess = true;
		if(count($aErrors) !== 0) {
			$bSuccess = false;
		}

		$aReturn = array(
			'success' => $bSuccess,
			'text' => $sText,
			'errors' => $aErrors
		);

		return $aReturn;
	}

	/**
	 * Platzhalter die nicht über die zentralen Platzhalter ersetzt werden können (bzw. sollten) hier ersetzen. Z.b. dürfen
	 * Passwörter nicht im Klartext in der Historie gespeichert werden
	 *
	 * TODO mit in die zentralen Platzhalter einbauen, ist aber nicht so einfach da man Passwörter beim Speichern der Nachricht
	 * in der Historie maskieren muss. Vllt will man auch Passwörter an jeden Empfänger einzeln schicken und dann wird es mit der
	 * zentralen Platzhalterklasse auch schon schwer
	 *
	 * @param string $sText
	 * @param array $aEmail
	 * @param bool $bMask
	 * @return string|string[]
	 */
	public function replaceHiddenPlaceholders(string $sText, array $aEmail, bool $bMask = true) {

		foreach($this->_aHiddenPlaceholders as $sPlaceholder => $aPlaceholderOptions) {

			$mPlaceholderValue = $this->getHiddenPlaceholderValue($sPlaceholder, $aEmail, $bMask);

			if ($mPlaceholderValue !== null) {
				if ($bMask)  {
					$iStrLength = strlen($mPlaceholderValue);
					$sText = str_replace('{'.$sPlaceholder.'}', str_pad("", $iStrLength, $aPlaceholderOptions['mask_with'] ?? '*'), $sText);
				} else {
					$sText = str_replace('{'.$sPlaceholder.'}', $mPlaceholderValue, $sText);
				}
			}
		}

		return $sText;
	}

	protected function getHiddenPlaceholderValue($sPlaceholder, $aEmail, bool $bMask = true): ?string {
		throw new \LogicException('Please implement logic for hidden placeholders!');
	}

	/**
	 * Bereitet die Daten für die Klasse vor
	 *
	 * Diese Methode wird von der save-Methode und reloadDialogTab aufgerufen
	 * @param $oTab
	 * @param $aTabVars
	 */
	public function initCommunicationData($oTab, $aTabVars) {

		// Identität setzen
		$iIdentityId = (int)($aTabVars['identity_id'] ?? 0);
		$this->_oIdentityUser = Ext_TC_Factory::getInstance('Ext_TC_User', $iIdentityId);

		// Bestimmen, ob Massenkommunikation
		if(count($this->_aSelectedIds) > 1) {
			$this->_bMassCommunication = true;
		}

	}

	public function getTabsFromVars($aSave) {

		$aTabs = $this->_oDialog->aElements;
		$oTab = $aTabs[($aSave['current_tab'] ?? 0)] ?? null;

		if(!is_object($oTab)) {
			$oTab = $aTabs[0];
		}

		// Aktuelle TabArea bestimmen
		// Da die TabAreas nicht im Cache stehen, sondern nur ihre TabArea_Tabs als Div, neu generieren
		$aTabAreas = $oTab->getInnerTabs();
		$oTabArea = $aTabAreas[($aSave['current_tabarea'] ?? 0)] ?? null;
		
		if(!is_object($oTabArea)) {
			$oTabArea = $aTabAreas[0];
		}
	
		return [$oTab, $oTabArea];
	}


	/**
	 * »Senden«
	 * @param $aVars
	 * @return array
	 */
	public function saveDialog($aVars) {
		global $user_data, $_VARS;

		$oDialogData = &$this->getDialogDataObject();

		// Aktuellen ausgewählten Tab aus dem Dialog-Cache holen
		list($oTab, $oTabArea) = $this->getTabsFromVars($aVars);

		$sTabType = $oTab->getType();
		$sTabAreaType = $oTabArea->getType();

		$this->initCommunicationData($oTab, $aVars[$sTabType]);
		$aData = $aVars[$sTabType][$sTabAreaType];

		$aErrors = array();
		$aHints = array();

		$bTemplate = true;
		$oTemplate = array();

		// Sendevorgang - Automatisch/E-Mail-Verteiler
		$sSendMode = $aVars[$sTabType]['send_mode'] ?? self::SEND_MODE_AUTOMATIC;

		if (!in_array($sSendMode, [self::SEND_MODE_AUTOMATIC, self::SEND_MODE_SPOOL])) {
			throw new \RuntimeException('Invalid send mode "'.$sSendMode.'"');
		}

		// Prüfung der Flags vorbereiten
		$oCheckFlagsHelper = new Ext_TC_Communication_Helper_CheckFlag($oTabArea);
		$oCheckFlagsHelper->aSelectedFlags = $this->_decodeFlags($aData['flags'] ?? [], $oTabArea);
		$oCheckFlagsHelper->aCachedFlags = $oDialogData->aFlagCache[$sTabType][$sTabAreaType] ?? [];

		if(
			!is_numeric($aData['template_id']) ||
			$aData['template_id'] == 0
		) {
			$bTemplate = false;
		} else {
			$oTemplate = Ext_TC_Communication_Template::getInstance($aData['template_id']);
		}

		if($bTemplate === false) {

			$aErrors[] = 'NO_TEMPLATE';

		} else {

			// Empfänger vorbereiten
			$aRecipients = [];
			$aRecipients['to'] = $this->_decodeRecipients($aData['recipients']['to'] ?? '', $oTabArea);
			$aRecipients['cc'] = $this->_decodeRecipients($aData['recipients']['cc'] ?? '', $oTabArea);
			$aRecipients['bcc']	= $this->_decodeRecipients($aData['recipients']['bcc'] ?? '', $oTabArea);

			$this->addStaticRecipients($oTabArea, $aRecipients);

			if(empty($aRecipients['to'])) {
				$aErrors[] = 'NO_RECIPIENTS';
			}

			if(empty($aErrors)) {

				$aObjects = array();
				$aCopyAddresses = array();

				// Adressen mit und ohne Objekte trennen
				foreach($aRecipients['to'] as $aObject) {

					if(!$aObject['object']) {
						$aCopyAddresses[] = $aObject;
					} else {
						$aObjects[] = $aObject;
					}

				}

				// Wenn keine Adresse mit Objekt ausgewählt wurde, setze Fake-Objekte
				$bOnlyManualAddresses = false;
				if(empty($aObjects)) {

					// Das erste Objekt setzen
					$aObjects = (array)$oDialogData->aRecipientCache[$oTabArea->getType()];

					if (!empty($aObjects)) {

						$aCacheObjects = $aObjects;
						$aObjects = [];

						foreach($aRecipients['to'] as $aObject) {
							// Eingegebene E-Mail-Adresse mit den Kontakten aus $aRecipientCache abgleichen
							$aMatchingCacheObjects = array_filter($aCacheObjects, fn ($aCacheObject) => $aCacheObject['address'] === $aObject['address']);
							$aObjects = array_merge($aObjects, $aMatchingCacheObjects);
						}

						if (empty($aObjects)) {
							$aObjects = [\Illuminate\Support\Arr::first($aCacheObjects)];
						}

					} else {
						$aObjects = array_map(fn ($iSelectedId) => ['selected_id' => $iSelectedId], $this->_aSelectedIds);
					}

					$bOnlyManualAddresses = true;

				}

				$aEmails = array();

				if(empty($aObjects)) {
					$aErrors[] = 'NO_RECIPIENTS';
				} else {
					foreach($aObjects as $aObject) {

						$iSelectedId = $aObject['selected_id'];

						$mObject = $aObject['object'];
						$iObjectId = $aObject['object_id'];

						$sSubject = $aData['subject'] ?? null;
						$sContent = $aData['content'];

						$aObjectFlags = array();
						$sLanguage = $this->_sDefaultLanguage;

						// Bsp: Ext_TA_Inquiry
						$oSelectedObject = Factory::getInstance($this->_sObject, $iSelectedId);

						if($mObject) {

							// Bsp: Ext_TA_Office
							$iSubObjectId = $oSelectedObject->getSubObject()?->id;

							// Bsp: Ext_TA_Inquiry_Traveller
							$oObject = Factory::getInstance($mObject, $iObjectId);

							$sObjectLanguage = $oObject->getCorrespondenceLanguage($iSubObjectId);

							// Default-Sprache nur überschreiben, wenn Sprache vorhanden
							// Beispielsweise liefern Objekte über die automatischen E-Mails keine Sprache,
							// 	daher wird in diesem Fall eigentlich die Standardsprache genommen!
							if(!empty($sObjectLanguage)) {
								$sLanguage = $sObjectLanguage;
							}

							// Prüfen, wie SubObjects der Flags anhand ihrer Abhängigkeiten zugewiesen worden
							$aObjectFlags = $oCheckFlagsHelper->getFlagsOfObject($aObject);

						} else {
							$iSubObjectId = null;
						}

						// Layout einbauen falls Template = HTML
						if($oTemplate->shipping_method === 'html') {
							$oLayout = null;
							$oContent = $oTemplate->getJoinedObjectChildByValue('contents', 'language_iso', $sLanguage);
							if($oContent) {
								$sContent = $oContent->insertLayout($sContent);
							}
						}

						// Fehler, wenn Betreff oder Inhalt leer sind
						if(
							$this->sTransferType === 'email' &&
							empty($sSubject)
						) {
							$aErrors[] = 'NO_SUBJECT';
						}

						if(empty($sContent)) {
							$aErrors[] = 'NO_CONTENT';
						}

						// Platzhalter ersetzen
						if($oSelectedObject) {

							$aPlaceholderOptions = array(
								'language' => $sLanguage,
								'type' => $oTemplate->shipping_method,
								'object' => $oSelectedObject,
								'with_hidden' => true
							);

							$aSubject = $this->replacePlaceholders($sSubject, $aPlaceholderOptions);
							$aContent = $this->replacePlaceholders($sContent, $aPlaceholderOptions);

							$sSubject = $aSubject['text'];
							$sContent = $aContent['text'];

							if (!empty($aSubject['errors'])) {
								$aErrors = array_merge($aErrors, $aSubject['errors']);
							}

							if (!empty($aContent['errors'])) {
								$aErrors = array_merge($aErrors, $aContent['errors']);
							}

						}

						// TMC Platzhalter ersetzen
						$sCommunicationCode = self::generateCode();
						$sCommunicationCodeTag = '[TMC:'.$sCommunicationCode.']';
						$sSubject = str_replace(['[#]', '[TMC]'], $sCommunicationCodeTag, $sSubject);
						$sContent = str_replace(['[#]', '[TMC]'], $sCommunicationCodeTag, $sContent);

						// Recipients setzen
						// Jede Adresse ohne Objekt bekommt die Nachricht vom Objekt
						// CC und BCC sind immer gleich!
						$aEmailRecipients = $aRecipients;
						if($bOnlyManualAddresses === false) {
							// Manuelle Empfänger zusätzlich setzen
							$aEmailRecipients['to'] = array_merge(array($aObject), $aCopyAddresses);
						} else {
							// Nur manuelle Empfänger setzen
							$aEmailRecipients['to'] = $aCopyAddresses;
						}

						if(empty($aErrors)) {

							$aEmail = array(
								'code' => $sCommunicationCode,
								'subject' => $sSubject,
								'content' => $sContent,
								'recipients' => $aEmailRecipients,
								'object' => $mObject, // Bsp: STRING Ext_TA_Inquiry_Traveller
								'object_id' => $iObjectId, // Bsp: ID Ext_TA_Inquiry_Traveller
								'selected_object' => $oSelectedObject, // Bsp: INSTANZ Ext_TA_Inquiry
								'selected_id' => $iSelectedId, // Bsp: ID Ext_TA_Inquiry
								'language' => $sLanguage,
								'template' => $oTemplate, // Bsp: INSTANZ Ext_TC_Communication_Template
								'subobject_id' => $iSubObjectId, // Bsp: ID Ext_TA_Office
								'flags' => $aObjectFlags
							);

							if (isset($aVars['event_manager_process'])) {
								$aEmail['event_manager_process'] = $aVars['event_manager_process'];
							}

							if (isset($aVars['event_manager_task'])) {
								$aEmail['event_manager_task'] = $aVars['event_manager_task'];
							}

							if ($this->sTransferType === 'app' && isset($aVars['thread'])) {
								$aEmail['thread'] = $aVars['thread'];
							}

							if ($oSelectedObject) {
								$oSelectedObject->prepareCommunicationFlags($aObjectFlags, $aEmail, $aErrors);
							}

							$aEmails[] = $aEmail;

						}
					}

				}
			}
		}

		$oCheckFlagsHelper->generateWarnings();
		$aHints = array_merge($aHints, $oCheckFlagsHelper->getWarnings());

		// Bei Fehler wieder zurück auf die Maske springen
		if(!empty($aErrors) ||
			(
				!empty($aHints) &&
				empty($_VARS['ignore_errors'])
			)
		) {

			if(!empty($aErrors) ) {
				$sError = $this->getErrorMessageTop();
				array_unshift($aErrors, $sError);
			}

			$bSuccess = false;
			
		} else {

			// Ausgewählte Attachments in andere Struktur konvertieren
			$aAttachments = array();
			$oObjectInstance = Ext_TC_Factory::getInstance($this->getObjectClassName(), reset($this->getSelectedIds()));

			// Nicht jedes SelectedObjekt besitzt Attachments und demzufolge auch diese Methode
			if(method_exists($oObjectInstance, 'getAttachmentsFromRequest')) {
				$aAttachments = $oObjectInstance->getAttachmentsFromRequest($aData);
			}

			if(!empty($aVars['email']['attachments'])) {
				$aAttachments = array_merge($aAttachments, $aVars['email']['attachments']);
			}
			
			// --- Uploads vorbereiten ---
			$aAttachments['uploads'] = array();
			$iSelectedId = reset($this->getSelectedIds());
			$aUploadFileData = array();
			$sColumn = 'attachment';
			$sAlias = '';

			/* Datei Informationen für den Upload
			 * vorbereiten vorbereiten */
			$aFileNames = (array)$_FILES['save']['name'][$sColumn];
			$aFileTmpNames = (array)$_FILES['save']['tmp_name'][$sColumn];

			foreach($aFileNames as $iFileArrayId => $sFileName) {
				if(!empty($sFileName)) {					
					$aUploadFileData[] = new \Illuminate\Http\UploadedFile($aFileTmpNames[$iFileArrayId], $sFileName);
				}
			}

			/* Für die Upload Konfiguration benötigen
			 * wir die Daten aus dem Upload-Feld. */
			$aOptionValues = array();
			$aSaveData = $this->getDialogObject()->aSaveData;
			foreach($aSaveData as $aOptions) {
				if(
					isset($aOptions['db_column']) && 
					$aOptions['db_column'] == $sColumn
				) {
					$aOptionValues = $aOptions;
					break;
				}
			}

			/* Der Dateiname soll umbenannt werden,
			 * sofern die hochgeladenen Dateien den
			 * selben Namen haben */
			$aFileNameCache = [];
			foreach($aUploadFileData as $iIndex => $oUploadFile) {

				if($oUploadFile->isValid()) {

					$sFileName = $oUploadFile->getClientOriginalName();
					
					$iDuplicateFileCount = 1;
					if(isset($aFileNameCache[$sFileName])) {
						$iDuplicateFileCount = ++$aFileNameCache[$sFileName];
					}

					if($iDuplicateFileCount > 1) {
						$aPathInfo = pathinfo($sFileName);
						$sFileName = $aPathInfo['filename'].'_'.$iDuplicateFileCount;
						if($aPathInfo['extension']) {
							$sFileName .= '.'.$aPathInfo['extension'];
						}
						
						$aUploadFileData[$iIndex] = new \Illuminate\Http\UploadedFile($aFileTmpNames[$iFileArrayId], $sFileName);
					}

					$aFileNameCache[$sFileName] = $iDuplicateFileCount;
				}

			}

			/* Alle Dateien auf den
			 * Server laden */
			$aMovedUploadFiles = array();
			if(!empty($aUploadFileData)) {				
				$oHandler = (new Gui2\Handler\Upload($aUploadFileData, $aOptionValues, true))
					->setColumn($sColumn);
				
				$aMovedUploadFiles = $oHandler->handle();
			}

			foreach($aMovedUploadFiles as $sMovedUploadFile) {
				$aAttachments['uploads'][] = array(
					'file' => \Util::getDocumentRoot(false) . $aOptionValues['upload_path'] . $sMovedUploadFile,
					'name' => $sMovedUploadFile
				);
			}

			$aAttachmentErrors = $this->checkAttachments($aEmails, $aAttachments);

			if(!empty($aAttachmentErrors)) {
				$aErrors = array_merge($aErrors, $aAttachmentErrors);
				$bSuccess = false;
			}

			if(empty($aErrors)) {
				// Array mit temporären Uploads zum Löschen
				$aTmpAttachmentsDel = array();

				// --- An Empfänger versenden ---
				foreach((array)$aEmails as $aEmail) {

					$oTemplate = $aEmail['template'];
					$sLanguage = $aEmail['language'];
					$sSubject = $aEmail['subject'];
					$sContent = &$aEmail['content'];
					$aRecipients = $aEmail['recipients'];
					$aSaveFlags = $aEmail['flags'];

					// --- HTML entfernen wenn keine HTML E-Mail ---
					if(
						$oTemplate->shipping_method == 'text'
					) {
						$sContent = strip_tags($sContent);
					}

					$sSaveContent = $sContent;

					$sContent = $this->replaceHiddenPlaceholders($sSaveContent, $aEmail, false);

					$aEmailAttachments = [];
					
					// --- VERSAND ---
					if($this->sTransferType === 'app') {

						$aEmailAttachments = $this->filterAttachmentsforEmail($aEmail, $aAttachments);

						if ($sSendMode === \Ext_TC_Communication::SEND_MODE_AUTOMATIC) {
							// E-Mail absenden
							$result = $this->_sendApp($aEmail);
						} else {
							$result = true;
						}

						if($result !== true) {
							$aErrors += $result;
							$bSuccess = false;
						} else {
							$bSuccess = true;
						}

					} elseif($this->sTransferType === 'sms') {

						if ($sSendMode === \Ext_TC_Communication::SEND_MODE_AUTOMATIC) {
							// SMS absenden
							$sSMSReturn = $this->_sendSMS($aEmail);
						} else {
							$sSMSReturn = 'SENT';
						}

						if($sSMSReturn === 'SENT') {
							$bSuccess = true;
						} else {
							$bSuccess = false;

							/* Array enthält alle Fehler, die vom SMS-Gateway zurückgegeben werden,
							 *	die an Übersetzungen in der Communication Dialog Data übersetzt sind.
							 */
							$aDefinedSMSErrors = array(
								'NO_CREDITS_LEFT', // Gateway: Keine Credits mehr auf CORE
								'WRONG_SENDER_FORMAT', // Gateway: Absender entspricht nicht RegEx (sollte eigentlich nicht vorkommen, da schon bei Eingabe geprüft)
								'NO_SENDER_SMS' // s.u. / SubObject: Kein SMS-Absender vorhanden
							);

							if(in_array($sSMSReturn, $aDefinedSMSErrors)) {
								$aErrors[] = $sSMSReturn;
							} else {
								$aErrors[] = 'SERVER_ERROR_SMS';
								// Trace wäre über 30MB an Text groß und das möchte sich keiner antun.
								mail('thebing_message@p32.de', 'TC Communication SMS Senden Fehler', print_r($sSMSReturn, true)."\n\n".print_r($aEmail, true)."\n\n".print_r($_SERVER, true));
							}

						}

					} else {

						$aEmailAttachments = $this->filterAttachmentsforEmail($aEmail, $aAttachments);

						// E-Mail absenden
						$bSuccess = $this->_sendMail($aEmail, $aEmailAttachments, $sSendMode);

						if(!$bSuccess) {
							$aErrors[] = 'SERVER_ERROR';
						}

					}

					if($bSuccess) {

						// --- Versand protokollieren ---
						#DB::begin('communication_log_message');

						$oMessage = Ext_TC_Factory::getObject('Ext_TC_Communication_Message');
						$oMessage->date = time();
						$oMessage->subject = (string)$sSubject;
						$oMessage->code = (string)$aEmail['code'];
						// Platzhalter maskieren, z.b. Passwörter
						$oMessage->content = (string)$this->replaceHiddenPlaceholders($sSaveContent, $aEmail, true);
						$oMessage->creator_id = (int)$user_data['id'];
						$oMessage->direction = 'out';
						$oMessage->content_type = $oTemplate->shipping_method;
						$oMessage->sent = ($sSendMode === self::SEND_MODE_AUTOMATIC) ? 1 : 0;
						if ($sSendMode === self::SEND_MODE_AUTOMATIC) {
							if ($this->sTransferType === 'email') {
								$oMessage->status = \Communication\Enums\MessageStatus::RECEIVED->value;
							} else {
								$oMessage->status = \Communication\Enums\MessageStatus::SENT->value;
							}
						}
						$oMessage->save();

						// --- Absender protokollieren ---					
						$oUser = System::getCurrentUser();
						$oAddress = $oMessage->getJoinedObjectChild('addresses');
						$oAddress->type = 'from';
						$aFromAddressRelations = [];

						if($this->sTransferType === 'app') {
							if($oUser instanceof Ext_TC_User) {
								$oAddress->name = $oUser->name;
							}
							$oMessage->type = 'app';
						} elseif($this->sTransferType === 'sms') {
							if($oUser instanceof Ext_TC_User) {
								$oAddress->name = $oUser->name;
							}
							$oAddress->address = $this->_sSmsSender;
							$oMessage->type = 'sms';
						} else {

							$oEmailAccount = Ext_TC_Communication_EmailAccount::getUserOptions($this->_oSendObject);
							$oAddress->address = $oEmailAccount->email;
							$oAddress->name = $oEmailAccount->sUserName;

							if($this->_oSendObject->message instanceof \Symfony\Component\Mailer\SentMessage) {
                                $oMessage->imap_message_id = $this->_oSendObject->message->getMessageId();
								$oMessage->unseen = 0;
								$oMessage->account_id = $oEmailAccount->id;
							}

							$aFromAddressRelations[] = ['relation' => get_class($oEmailAccount), 'relation_id' => $oEmailAccount->id];

                            $aEmail['email_account'] = $oEmailAccount;
						}

						// TODO Wofür ist das da, wenn es nochmal tc_communication_messages_creators gibt?
						if($oUser instanceof Ext_TC_User) {
							$aFromAddressRelations[] = ['relation' => get_class($oUser), 'relation_id' => $oUser->id];
						}

						$oAddress->relations = $aFromAddressRelations;

						// --- Adressen speichern ---
						foreach($aRecipients as $sField => $aField) {

							foreach($aField as $aRecipient) {

								$oAddress = $oMessage->getJoinedObjectChild('addresses');
								$oAddress->type = $sField;
								$oAddress->address = $aRecipient['address'];
								$oAddress->name = $aRecipient['name'];

								// Nur Relationen speichern, wenn auch Objekt vorhanden
								if(
									!$this->bSkipAdressRelationSaving &&
									$aRecipient['object'] &&
									$aRecipient['object_id'] > 0
								) {

									$oAddress->relations = array(
										array(
											'relation' => $aRecipient['object'],
											'relation_id' => $aRecipient['object_id'],
										)
									);

								}

								// Da die Adressen ohne Objekte erst hinter die mit Objekten kommen, wird versucht,
								// den Adressen ohne Objekte demnach ihre entsprechenden zugewiesenen Objekte zuzuordnen.
								elseif(
									!$this->bSkipAdressRelationSaving &&
									$sField === 'to' &&
									$aRecipients['to'][0]['object'] &&
									$aRecipients['to'][0]['object_id'] > 0
								) {

									$oAddress->relations = array(
										array(
											'relation' => $aRecipients['to'][0]['object'],
											'relation_id' => $aRecipients['to'][0]['object_id'],
										)
									);

								}

							}

						}

						// --- Template und ggf. Layout loggen ---
						if($oTemplate) {
							$oMessageTemplate = $oMessage->getJoinedObjectChild('templates');
							$oMessageTemplate->template_id = $oTemplate->id;

							if(isset($oLayout) && $oLayout->id > 0) {
								$oMessageTemplate->layouts = array($oLayout->id);
							}
						}

						// --- Attachments loggen ---
						if(!empty($aEmailAttachments['uploads'])) {

							// Liefert in etwa: /var/www/vhosts/dev.core.thebing.com/httpdocs/media/secure/tc/communication/out/MSGID/
							$sMessageDir = self::getUploadPath('out', true).$oMessage->id.'/';
							$bMessageDirCheck = Util::checkDir($sMessageDir);

							// Uploads verschieben und loggen
							if($bMessageDirCheck) {
								foreach($aEmailAttachments['uploads'] as $aAttachment) {
									$sFilePath = $sMessageDir.$aAttachment['name'];
									$bCopy = copy($aAttachment['file'], $sFilePath);
									$aTmpAttachmentsDel[$aAttachment['file']] = $sFilePath;

									$sCleanedPath = str_replace(\Util::getDocumentRoot(false), '', $sFilePath);

									if($bCopy) {
										$oLogFile = $oMessage->getJoinedObjectChild('files');
										$oLogFile->file = $sCleanedPath;
										$oLogFile->name = $aAttachment['name'];
									}
								}
							}

						}

						// DOCUMENT_ROOT aus dem Dateinamen wieder entfernen
						foreach((array)($aEmailAttachments['files'] ?? []) as $aAttachment) {
							$sFileName = str_replace($_SERVER['DOCUMENT_ROOT'], '', $aAttachment['file']);

							$oLogFile = $oMessage->getJoinedObjectChild('files');
							$oLogFile->file = $sFileName;
							$oLogFile->name = $aAttachment['name'];

							if(!empty($aAttachment['object']) && !empty($aAttachment['object_id'])) {
								$oLogFile->relations = array(
									array(
										'relation' => $aAttachment['object'],
										'relation_id' => $aAttachment['object_id']
									)
								);
							}

						}

						// Dokumente durchgehen und nur bestimmte Werte loggen
						$aLogDocuments = array();
						foreach((array)($aEmailAttachments['documents'] ?? []) as $aAttachment) {
							if(isset($aAttachment['version_id'])) {
								$aLogDocuments[] = $aAttachment['version_id'];
							}
						}

						$oMessage->documents = $aLogDocuments;

						// --- Finales Speichern ---
						$oMessage->save();

						// --- Relations speichern ---
						$this->setRelations($aEmail, $oMessage);

						// --- Markierungen speichern ---
						foreach($aSaveFlags as $sFlagKey => $aSubObjects) {
							// --- Template und ggf. Layout loggen ---
							$oFlag = $oMessage->getJoinedObjectChild('flags');
							$oFlag->flag = $sFlagKey;

							// Ausgewählte SubObjects speichern
							if(!empty($aSubObjects)) {

								$aRelations = $oFlag->relations;
								foreach($aSubObjects as $sSubObjectClass => $aSubObjectIds) {
									foreach($aSubObjectIds as $iSubObjectId) {
									$aRelations[] = array(
											'relation' => $sSubObjectClass,
											'relation_id' => $iSubObjectId
										);
									}
								}
								$oFlag->relations = $aRelations;
							}

							$oFlag->save();
						}

						#DB::commit('communication_log_message');

                        // --- Markierungen ausführen ---
                        if(
                            !empty($aSaveFlags) &&
                            is_object($aEmail['selected_object']) &&
                            method_exists($aEmail['selected_object'], 'setCommunicationFlags')
                        ) {
							// \Tc\Traits\Communication\FlagNotify
                            $aEmail['selected_object']->setCommunicationFlags($aSaveFlags, $aEmail, $oMessage);
                        }
					}
					
				}	
			}

			if(empty($aErrors)) {

				$oDialogData->aRecipientCache = array();

				// --- Temporäre Uploads löschen (wurden kopiert) ---
				foreach($aTmpAttachmentsDel as $sFrom => $sTo) {
					unlink($sFrom);
				}

			}

		}

		$aReturn = array(
			'success' => $bSuccess,
			'errors' => $aErrors,
			'hints' => $aHints
		);

		return $aReturn;
	}
	
	/**
	 * Prüfen ob die Anhänge verschickt werden können
	 * 
	 * @param array $aEmails
	 * @param array $aAttachments
	 * @return array
	 */
    protected function checkAttachments(array $aEmails, array $aAttachments) {
        
		$aErrors = [];
		
		$aEmailObjectData = [];
		foreach($aEmails as $aEmail) {
			$aEmailObjectData[$aEmail['object']][$aEmail['object_id']] = 1;
		}
		
        foreach($aAttachments as $sType => $aTypeAttachments) {
            foreach ($aTypeAttachments as $aAttachment) {

                $sFile = $aAttachment['file'];
                
                if(file_exists($sFile)) {
                    if(filesize($sFile) == 0) {
						// Leere Datei
                        $aErrors[] = 'EMPTY_ATTACHMENT_FILE';
                    }                    
                }
                
				// Wenn ein Anhang an einen bestimmte Empfänger(-gruppe) gerichtet ist
				// prüfen die entsprechenden Empfänger auch adressiert werden
				if(isset($aAttachment['object'])) {
					
					if(isset($aAttachment['object_id'])) {
						$bReceipientExists = isset($aEmailObjectData[$aAttachment['object']][$aAttachment['object_id']]);
					} else {
						$bReceipientExists = isset($aEmailObjectData[$aAttachment['object']]);
					}
					
					if(!$bReceipientExists) {
						// kein passender Emfänger gefunden
						$aErrors[] = $this->getDialogDataObject()->getErrorMessage('ATTACHMENT_NO_RECEIPIENT', null, $aAttachment['name']);
					}					
				}				
            }
        }
        
        return $aErrors;
    }
	
	/**
	 * Hier werden alle für die Email bestimmten Anhänge gesammelt
	 * 
	 * @param array $aEmail
	 * @param array $aAllAttachments
	 * @return array
	 */
	protected function filterAttachmentsforEmail(array $aEmail, array $aAllAttachments) {
		
		$aAttachments = [];
		foreach($aAllAttachments as $sType => $aTypeAttachments) {
            foreach ($aTypeAttachments as $aAttachment) {
				
				$bAdd = true;
				// Wenn der Anhang nur für bestimmte Empfänger(-gruppen) gedacht ist
				if(isset($aAttachment['object'])) {
					$bAdd = false;
					if(is_a($aEmail['object'], $aAttachment['object'], true)) {
						$bAdd = true;						
						if(
							isset($aAttachment['object_id']) &&
							$aEmail['object_id'] != $aAttachment['object_id']
						) {
							// Der Anhang ist nur für einen bestimmten Empfänger gedacht
							$bAdd = false;
						}
					}
				}
				
				if($bAdd) {
					$aAttachments[$sType][] = $aAttachment;
				}				
			}
		}
		
		return $aAttachments;
	}
	
	/**
	 * @todo Jede Versandart braucht seinen eigenen Service, der dann auch das Handling der Fehlermeldungen übernimmt
	 * @param array $aEmail
	 * @param array $aAttachments
	 * @param string $sSendMode
	 * @return bool|mixed
	 */
	protected function _sendMail($aEmail, $aAttachments, $sSendMode) {
		global $user_data;

		$oTemplate = $aEmail['template'];
		$sSubject = $aEmail['subject'];
		$sContent = $aEmail['content'];

		$aRecipients = array();

		foreach($aEmail['recipients'] as $sField => $aField) {
			foreach($aField as $aRecipient) {
				$aRecipients[$sField][] = $aRecipient['address'];
			}
		}

		$oMail = new Ext_TC_Communication_WDMail();
		$oMail->subject = $sSubject;

		if ($aEmail['selected_object']) {
			// SubObject setzen für den Absendernamen
			$oMail->iSubjectId = $aEmail['selected_object']->getSubObject()?->id;
		}

		if($oTemplate->shipping_method == 'html') {
			$oMail->html = $sContent;
		} else {
			$oMail->text = $sContent;
		}

		if(!empty($aRecipients['cc'])) {
			$oMail->cc = $aRecipients['cc'];
		}
		if(!empty($aRecipients['bcc'])) {
			$oMail->bcc = $aRecipients['bcc'];
		}

		$aAttachmentsFinal = array();
		foreach((array)$aAttachments as $sAttachmentType => $aFiles) {
			foreach($aFiles as $aFile) {
				$aAttachmentsFinal[$aFile['file']] = $aFile['name'];
			}
		}

		if(!empty($aAttachmentsFinal)) {
			$oMail->attachments = $aAttachmentsFinal;
		}

		// Absender User
		$oMail->from_user = $this->_oIdentityUser;

		// Wird hier gemacht da $this->_oSendObject gesetzt sein muss für die Relations
		if ($sSendMode === self::SEND_MODE_AUTOMATIC) {
			// Absenden
			$aRecipients['to'] = array_unique($aRecipients['to']);
			$bSuccess = $oMail->send($aRecipients['to']);
		} else {
			$bSuccess = true;
		}
		
		$this->_oSendObject = $oMail;

		return $bSuccess;
	}
	
	/**
	 * @todo Jede Versandart braucht seinen eigenen Service, der dann auch das Handling der Fehlermeldungen übernimmt
	 * Holt den SMS-Absender über das Objekt und versendet die SMS schließlich
	 * @param array $aEmail s.o.
	 * @return string
	 */
	protected function _sendSMS($aEmail)
	{
		$sContent = $aEmail['content'];
		$aRecipients = $aEmail['recipients'];

		// Absender holen – dies geschieht über das jeweilige SubObject, wo dieses über das Objekt geholt wird.
		$iSubObject = $aEmail['selected_object']->getSubObject()?->id;

		$oSubObject = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $iSubObject);
		$this->_sSmsSender = $oSubObject->getSMSSender();

		$sSender = $this->_sSmsSender;
		if(empty($sSender)) {
			$sReturn = 'NO_SENDER_SMS';
		}
		
		if(empty($sReturn)) {
			foreach($aRecipients as $sKey => $aRecipientsReal) {
				foreach($aRecipientsReal as $aRecipient) {
					$oSMS = new Ext_TC_Communication_SMS_Gateway();
					$oSMS->setRecipient($aRecipient['address']);
					$oSMS->setMessage($sContent);
					$oSMS->setSender($this->_sSmsSender);
					$sReturn = $oSMS->send();
				}
			}
		}
		
		return $sReturn;
		
	}
	
	/**
	 * @todo Jede Versandart braucht seinen eigenen Service, der dann auch das Handling der Fehlermeldungen übernimmt
	 * @param array $aEmail s.o.
	 * @return string
	 */
	protected function _sendApp($aEmail) {
		throw new Exception('Not implemented on core!');
	}

	public static function generateCode() {
		
		do {
			
			$sCode = Ext_TC_Util::generateRandomString(8);
			
			$sSql = "
				SELECT 
					`message_id`
				FROM 
					`tc_communication_messages_codes`
				WHERE
					`code` = :code
				LIMIT 1
				";
			$aSql = array(
				'code' => $sCode
			);
			$bExists = (bool)DB::getQueryOne($sSql, $aSql);
			
		} while($bExists === true);
		
		return $sCode;
		
	}
	
	/**
	 * Kann abgeleitet werden um zusätzliche Relations zu liefern zu einem Objekt
	 * @param array $aEmail
	 * @param Ext_TC_Communication_Message $oMessage
	 * @return array
	 */
	public function setRelations(array $aEmail, Ext_TC_Communication_Message $oMessage) {

		$oEmailAccount = $aEmail['email_account'] ?? null;
		$oSelectedObject = $aEmail['selected_object'];

		$bSave = false;

		$aRelations = [];

        if ($oEmailAccount) {
            $aRelations[] = ['relation' => get_class($oEmailAccount), 'relation_id' => $oEmailAccount->id];
        }

		// Kann auch NULL sein, wenn kein Objekt vorhanden
		if($oSelectedObject) {
			$aRelations[] = ['relation' => get_class($oSelectedObject), 'relation_id' => $oSelectedObject->id];
		}

		if(!empty($process = $aEmail['event_manager_process'])) {
			$aRelations[] = ['relation' => $process['class'], 'relation_id' => $process['id']];
		}

		if(!empty($task = $aEmail['event_manager_task'])) {
			$aRelations[] = ['relation' => $task['class'], 'relation_id' => $task['id']];
		}

		$this->addApplicationRelations($aEmail, $aRelations);

		if (!empty($aRelations)) {
			$oMessage->relations = array_values($aRelations);
			$bSave = true;
		}

		if ($this->sTransferType === 'app') {
			$this->setAppIndexRelations($aEmail, $oMessage);
			$bSave = true;
		}

		if ($bSave) {
			$oMessage->save();
		}
	}

	protected function addApplicationRelations($aEmail, array &$aRelations) {}

	protected function setAppIndexRelations(array $aEmail, Ext_TC_Communication_Message $oMessage) {

		$aAddresses = $oMessage->getJoinedObjectChilds('addresses', true);

		$oFromAddress = \Illuminate\Support\Arr::first($aAddresses, fn ($oAddress) => $oAddress->type === 'from');
		$aToAddresses = array_filter($aAddresses, fn ($oAddress) => $oAddress->type === 'to');

		$aThread = $oFromAddress->relations[0] ?? ['relation' => '', 'relation_id' => 0];

		if (isset($aEmail['thread'])) {

			// Schauen, ob der angegebene Thread in den From-Relations existiert, ansonsten diesen benutzen
			$aExisting = \Illuminate\Support\Arr::first($oFromAddress->relations, fn ($aRelation) => $aRelation['relation'] === $aEmail['thread']['relation']);
			if ($aExisting !== null) {
				$aThread = $aExisting;
			} else {
				$aThread = [
					'relation' => $aEmail['thread']['relation'],
					'relation_id' => ($aEmail['thread']['relation_id'] !== '*') ? (int)$aEmail['thread']['relation_id'] : 0
				];
			}
		}

		$aAppRelations = array_map(function ($oAddress) use ($aThread) {
			return [
				'device_relation' => $oAddress->relations[0]['relation'],
				'device_relation_id' => $oAddress->relations[0]['relation_id'],
				'thread_relation' => $aThread['relation'],
				'thread_relation_id'  => $aThread['relation_id'],
			];
		}, $aToAddresses);

		$oMessage->app_index = array_values($aAppRelations);

	}

	/**
	 * Neue Methode, um die Instanzen aller ausgewählten Objekte zu bekommen
	 * @return array
	 */
	public function getSelectedObjects() {

		$aReturn = array();
		$aSelectedIds = $this->getSelectedIds();
		$sSelectedClass = $this->getObjectClassName();

		foreach($aSelectedIds as $iSelectedId) {
			$aReturn[] = Ext_TC_Factory::getInstance($sSelectedClass, $iSelectedId);
		}

		return $aReturn;
	}

	/**
	 * Liefert das Dialogobjekt der Klasse
	 * @return Ext_Gui2_Dialog 
	 */
	public function getDialogObject() {
		return $this->_oDialog;
	}
	
	/**
	 * Liefert das Dataobject des Dialoges
	 * @return Ext_TC_Communication_Gui2_Dialog_Data
	 */
	public function getDialogDataObject() {
		return $this->_oDialog->getDataObject();
	}
	
	/**
	 * Liefert die »bereinigten« Values
	 * @return mixed 
	 */
	public function getSaveValues() {
		global $_VARS;

		$mReturn = $_VARS['save'] ?? null;
		
		return $mReturn;
	}
	
	public function getRecipientInputs() {
		return $this->_aRecipientInputs;
	}
	
	public function getObjectClassName() {
		return $this->_sObject;
	}
	
	public function getApplication() {
		return $this->_sApplication;
	}
	
	/**
	 * Liefert die höchste Fehlermeldung für eine Fehlermeldung (wer hätte das gedacht?)
	 * @return string
	 */
	public function getErrorMessageTop() {

		if($this->sTransferType === 'sms') {
			$sError = 'NOT_SENT_SMS';
		} else {
			$sError = 'NOT_SENT';
		}

		return $sError;
	}

	/**
	 * Liefert die verschiedenen Upload-Pfade für die Kommunikation
	 *
	 * @param string $sType
	 * @param bool $bDocumentRoot
	 * @return string
	 * @since 15.12.11
	 */
	public static function getUploadPath($sType = '', $bDocumentRoot = false) {

		$sDirectory = Ext_TC_Util::getSecureDirectory($bDocumentRoot);
		$sDirectory .= 'communication/';

		if($sType === 'in') {
			$sDirectory .= 'in/';
		} elseif($sType === 'out') {
			$sDirectory .= 'out/';
		} elseif($sType === 'templates/email') {
			$sDirectory .= 'templates/email/';
		}

		return $sDirectory;
	}

	/**
	 * Holt die Tabs für den Dialog: Aus dem Cache oder frisch erzeugt
	 *
	 * @since 16.07.2012
	 */
	public function getDialogTabs($aOptions = array(), $bOverwrite = false) {

		$oDialogData = $this->getDialogDataObject();

		$aOptions = array_merge($this->_aDialogTabOptions, $aOptions);

		// Wenn überschreiben: Dialog-Cache leeren und Tabs neu generieren
		if($bOverwrite) {

			$oDialogData->resetDialog();

			$aTabs = $this->getTabs($aOptions);

			foreach($aTabs as $oTab) {
				$this->_oDialog->setElement($oTab);
			}

		}

		return $this->_oDialog->aElements;
	}

	/**
	 * Setzt die Standardsprache
	 * @param string $sLanguage
	 */
	public function setDefaultLanguage($sLanguage) {
		$this->_sDefaultLanguage = $sLanguage;
	}

	/**
	 * Ob Massenkommunikation oder nicht
	 *
	 * @return bool
	 */
	public function isMassCommunication() {
		return $this->_bMassCommunication;
	}
	
	/**
	 * Select Options mit den Empfängergruppen
	 * @return array
	 */
	public static function getSelectRecipientGroups() {

		$aRetVal = array(
			'customer' => self::t('Kunden'),
			'partner' => self::t('Partner'),
			'school' => self::t('Schulen')
		);

		return $aRetVal;
	}
	
	/**
	 * Select Options mit den Applications
	 * @return array
	 */
	public static function getSelectApplications(\Tc\Service\LanguageAbstract $l10n, \Access $access = null): \Illuminate\Support\Collection
	{
		$applications = \Communication\Facades\Communication::getAllApplications($access)
			->except('global')
			->map(fn ($class, $key) => \Factory::executeStatic($class, 'getTitle', [$l10n, $key]));

		return $applications;
	}

	public static function getSelectApplicationRecipients(\Access $access = null): \Illuminate\Support\Collection
	{
		$recipients = \Communication\Facades\Communication::getAllApplications($access)
			->except('global')
			->map(fn ($class, $key) => \Factory::executeStatic($class, 'getRecipientKeys', [$key]));

		return $recipients;
	}

	public static function getSelectApplicationFlags(\Access $access = null): \Illuminate\Support\Collection
	{
		$flags = \Communication\Facades\Communication::getAllApplications($access)
			->except('global')
			->map(fn ($class, $key) => \Factory::executeStatic($class, 'getFlags'));

		return $flags;
	}

	/**
	 * Select Options mit den Markierungen
	 *
	 * @param bool $bSelectOptions
	 * @param array $aTypes
	 * @return array
	 */
	final public function getSelectFlags($bSelectOptions = true, $aTypes = array()) {

		$aReturnFlags = array();
		$aTabs = $this->getDialogObject()->aElements;

		foreach($aTabs as $oTab) {
			/* @var $oTab Ext_TC_Communication_Tab */
			$aTabAreas = $oTab->getInnerTabs();

			foreach($aTabAreas as $oTabArea) {
				/* @var $oTabArea Ext_TC_Communication_Tab_TabArea */
				$aTabAreaFlags = $oTabArea->getFlags();

				foreach($aTabAreaFlags as $sTabAreaType => $aFlags) {
					// Filtern nach Typen
					if(
						empty($aTypes) ||
						in_array($sTabAreaType, $aTypes)
					) {
						foreach($aFlags as $sFlagKey => $aFlagOptions) {

							if($bSelectOptions) {
								$aReturnFlags[$sFlagKey] = $aFlagOptions['label'];
							} else {
								$aReturnFlags[$sFlagKey] = $aFlagOptions;
							}

						}
					}
				}
			}

		}
		asort($aReturnFlags);

		return $aReturnFlags;
	}

	/**
	 * Liefert _alle_ Markierungen aus alle Anwendungsfällen
	 *
	 * @param bool $bSelectOptions
	 * @param array $aTypes Filter: TabArea Types
	 * @return array
	 */
	public static function getAllSelectFlags($bSelectOptions = true, $aTypes = array()) {

		$aAllApplications = Ext_TC_Factory::getProperty('Ext_TC_Communication', '_aApplicationAllocations');
		$aFlags = array();
		foreach($aAllApplications as $sApplication => $sApplicationClass) {

			$oFakeDialog = new Ext_Gui2_Dialog();

			/* @var $oCommunication Ext_TC_Communication */
			$oCommunication = new $sApplicationClass($oFakeDialog, array());
			$aTabs = $oCommunication->getTabs();

			foreach($aTabs as $oTab) {
				$oFakeDialog->setElement($oTab);
			}

			$aFlags += $oCommunication->getSelectFlags($bSelectOptions, $aTypes);
		}

		return $aFlags;
	}

	/**
	 * Select Options (primär für Templates)
	 * @static
	 * @return array
	 */
	public static function getSelectInvoiceTypes() {

		$aRetVal = array(
			'netto' => self::t('Nettorechnungen'),
			'brutto' => self::t('Bruttorechnungen')
		);

		return $aRetVal;
	}

	/**
	 * Select Options  (primär für Templates)
	 * @static
	 * @return array
	 */
	public static function getSelectReceipts() {

		$aRetVal = array(
			'receipt' => self::t('Quittung'),
			'payment_overview_per_booking' => self::t('Zahlungsübersicht pro Buchung'),
			'payment_overview_per_invoice' => self::t('Zahlungsübersicht pro Rechnung')
		);

		return $aRetVal;
	}

	/**
	 * Select Options (primär für Templates)
	 * @static
	 * @return array
	 */
	public static function getSelectFilterDirections() {

		$aRetVal = array(
			'in' => self::t('Empfangen'),
			'out' => self::t('Gesendet')
		);

		return $aRetVal;
	}	

	/**
	 * gibt ein Array mit den Klassennamen zurück, über die die Platzhalterklassen
	 * für die Übersichten geholt werden
	 *
	 * @return array 
	 */
	public static function getPlaceholderClasses() {
		$aReturn = array();
		return $aReturn;
	}
	
	/**
	 * Fügt einer GUI die JS-Datei für die Kommunikation an
	 * 
	 * @param Ext_Gui2 $oGui 
	 */
	public static function addJsFile(Ext_Gui2 $oGui) {
		$oGui->addJs('js/communication.js', 'tc');
	}
	
	/**
	 * Fügt Empfänger beim Versand statisch hinzu, also ohne sie auswählen zu müssen.
	 * 
	 * @param \Ext_TC_Communication_Tab_TabArea $oTabArea
	 * @param array $recipients
	 */
	public function addStaticRecipients(\Ext_TC_Communication_Tab_TabArea $oTabArea, &$recipients) {
		
	}

}
