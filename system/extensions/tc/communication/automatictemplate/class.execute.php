<?php

/**
 * Aktionen zum Ausführen für E-Mails anhand automatischer Templates
 * TODO das hier muss unabhängig von einem Gui2-Dialog laufen
 */
class Ext_TC_Communication_AutomaticTemplate_Execute
{
	protected $_oEntity;
	protected $_sApplication;
	protected $_oAutomaticTemplate;
	protected $_oTemplate;
	protected $_oTemplateContent;
	protected $_oCommunication;

	protected $aAttachments = array();
	
	protected $aErrors = [];
	
	/**
	 * @var Log
	 */
	protected $oLog;

	/**
	 * @param Ext_TC_Basic $oEntity
	 * @param Ext_TC_Frontend_Combination $oCombination
	 * @param string $sApplication
	 */
	public function __construct(Ext_TC_Basic $oEntity, Ext_TC_Communication_AutomaticTemplate $oAutomaticTemplate, string $sLanguage, string $sApplication) {
		$this->_oEntity = $oEntity;
		$this->_oAutomaticTemplate = $oAutomaticTemplate;
		$this->_sLanguage = $sLanguage;
		$this->_sApplication = $sApplication;

		$this->oLog = Log::getLogger('communication_automatic');
	}

	/**
	 * Setzt grundlegende Daten
	 */
	protected function _setData()
	{
		$this->_oTemplate = $this->_oAutomaticTemplate->getTemplate();
		$this->_oTemplateContent = $this->_oTemplate->getJoinedObjectChildByValue('contents', 'language_iso', $this->_sLanguage);
		//$iPdfTemplateId = (int)$oCombination->items_pdf_template_id;
		//$oPdfTemplate = Ext_TC_Factory::getInstance('Ext_TC_Pdf_Template', $iPdfTemplateId);
	}

	/**
	 * Erzeugt das Kommunikationsobjekt inklusive Dialog
	 */
	protected function _createCommunicationObject()
	{
		// Kommunikations-Dialog bauen
		$oFakeGui = new Ext_TC_Gui2();
		$oDialog = Ext_TC_Factory::executeStatic('Ext_TC_Communication', 'createDialogObject', array(&$oFakeGui, array(), $this->_sApplication));
		$this->_oCommunication = Ext_TC_Communication::createCommunicationObject($oDialog, $this->_sApplication, (array)$this->_oEntity->id);
		$this->_oCommunication->setDefaultLanguage($this->_sLanguage);

		// Verhindern, dass Relationen für E-Mail-Adressen mit Objekten gespeichert werden
		$this->_oCommunication->bSkipAdressRelationSaving = true;

		// Dialog generieren, da die Kommunikation Daten daraus ausliest
		$this->_oCommunication->getDialogTabs(array(
			'show_history' => false,
			'show_placeholders' => false,
			'show_sms' => false,
			'show_app' => false,
			'show_notices' => false
		), true);
	}

	protected function _getCustomerEmailAndName()
	{
		return array(
			'address' => '',
			'object' => null
		);
	}

	protected function _getSubObjectEmail()
	{
		return '';
	}

	/**
	 * Einzelnen Empfänger bestimmen
	 * @param string $sKey
	 * @param string $sMail
	 * @param string $sSendTo
	 * @param array $aAdditionalSendTo
	 * @return bool
	 */
	protected function _determineSingleRecipient($sKey, $sMail, &$sSendTo, &$aAdditionalSendTo) {
		if(
			in_array($sKey, (array)$this->_oAutomaticTemplate->recipients) &&
			!empty($sMail)
		) {
			if(!empty($sSendTo)) {
				$aAdditionalSendTo[] = $sMail;
			} else {
				$sSendTo = $sMail;
			}
			return true;
		}
		return false;
	}

	/**
	 * Empfänger bestimmen
	 * @return array|bool
	 */
	protected function _determineRecipients()
	{
		$aCustomerData = $this->_getCustomerEmailAndName();

		$sSendTo = '';
		$sContactName = '';
		$aAdditionalSendTo = array();

		// To und Additional To setzen (Reihenfolge hat Priorität)
		$bCustomerSet = $this->_determineSingleRecipient('customer', $aCustomerData['address'], $sSendTo, $aAdditionalSendTo);
		$this->_determineSingleRecipient('subobject', $this->_getSubObjectEmail(), $sSendTo, $aAdditionalSendTo);
		$this->_determineSingleRecipient('individual', $this->_oAutomaticTemplate->to, $sSendTo, $aAdditionalSendTo);

		// Wenn an Customer gesendet wird, dann später Namen ins Cache-Objekt setzen
		if($bCustomerSet) {
			$sContactName = $aCustomerData['object']->getName();
		}

		// Wenn keine Adresse rauskommt, dann Abbruch
		if(empty($sSendTo)) {
			return false;
		}

		// Kontaktobjekt-Daten direkt in den Empfängercache der Kommunikation schreiben
		$this->_oCommunication->getDialogDataObject()->aRecipientCache = array(
			'customer' => array(
				1 => array(
					'name' => $sContactName,
					'address' => $sSendTo,
					'object' => get_class($aCustomerData['object']),
					'object_id' => $aCustomerData['object']->id,
					'selected_id' => $this->_oEntity->id,
					'crc' => crc32(mt_rand())
				)
			)
		);

		// Empfänger-Eingabe für den Kommunikationsdialog zusammenbauen
		$sSendTo = '<span style="text-decoration: underline;" title="1">'.$sContactName.' ('.$sSendTo.')</span>';
		$aRecipients = array(
			'to' => join(';', array_merge(array($sSendTo), $aAdditionalSendTo)),
			'cc' => $this->_oAutomaticTemplate->cc,
			'bcc' => $this->_oAutomaticTemplate->bcc
		);

		return $aRecipients;
	}

	/**
	 * Senden ausführen
	 * @return array
	 */
	public function execute() {

		$this->_setData();

		if(
			$this->_oEntity->id > 0 &&
			$this->_oAutomaticTemplate->id > 0 &&
			$this->_oTemplate->id > 0 &&
			$this->_oTemplateContent->id > 0
		) {

			// Kommunikation erstellen
			$this->_createCommunicationObject();

			// Empfänger bestimmen
			$mRecipients = $this->_determineRecipients();

			// Wenn es keinen Empfänger gibt, dann Abbruch
			if(!$mRecipients) {
				$this->aErrors[] = 'No recipients';
				$this->oLog->addError('No recipients', ['entity' => get_class($this->_oEntity), 'entity_id' => $this->_oEntity->id, 'recipients' => $mRecipients]);
				return false;
			}

			// Daten für Kommunikationsdialog
			$aVarsData = array(
				'current_tab' => '0', // Tab: E-Mail
				'current_tabarea' => '0', // Tab: Kunden
				'email' => array(
					'identity_id' => '0', // Absender (Systembenutzer)
					'customer' => array(
						'template_id' => $this->_oTemplate->id,
						'recipients' => $mRecipients,
						'subject' => $this->_oTemplateContent->subject,
						'content' => $this->_oTemplateContent->content,
					),
					'attachments' => $this->aAttachments
				)
			);

            try {

				$aSave = $this->_oCommunication->saveDialog($aVarsData);
				
				if($aSave['success'] === true) {
					$this->oLog->addInfo('Automatic e-mail sent', [
						'entity' => get_class($this->_oEntity), 
						'entity_id' => $this->_oEntity->id, 
						'save' => $aSave
					]);
				} else {
					$this->aErrors[] = $aSave['errors'];
					$this->oLog->addError('Automatic e-mail failed', [
						'entity' => get_class($this->_oEntity), 
						'entity_id' => $this->_oEntity->id, 
						'save' => $aSave
					]);
				}

			} catch(Exception $e) {

				$this->aErrors[] = $e->getMessage();
				$this->oLog->addError('Exception', [
					'entity' => get_class($this->_oEntity), 
					'entity_id' => $this->_oEntity->id, 
					'exception' => $e->getMessage()
				]);
				$aSave['success'] = false;

			}

			return $aSave['success'];
		} else {

			$aInfo = [
				'entity' => get_class($this->_oEntity),
				'entity_id' => $this->_oEntity->id,
				'automatictemplate_id' => $this->_oAutomaticTemplate->id,
				'template_id' => $this->_oTemplate->id,
				'content_id' => $this->_oTemplateContent->id
			];
			
			$this->aErrors[] = 'Information missing: '.print_r($aInfo, 1);
			$this->oLog->addError('Information missing', $aInfo);

		}

		return false;
	}

	/**
	 * fügt der automatischen E-Mail einen Anhang hinzu
	 * 
	 * $sKey = 'documents', 'files', 'uploads'
	 * 
	 * array(
	 *		'file' => $oVersion->getPath(true),
	 *		'name' => basename($oVersion->getPath()),
	 *		'version_id' => $oVersion->id
	 * )
	 * 
	 * @param array $aDocumentData
	 * @param string $sKey
	 */
	public function addAttachment(array $aDocumentData, $sKey = 'documents') {
		$this->aAttachments[$sKey][] = $aDocumentData;
	}

	public function getErrors() {
		return $this->aErrors;
	}

}
