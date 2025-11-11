<?php

/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Email_Template_Attachment extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_email_templates_languages_attachments';

	public function getUploadPath($bDocumentRoot=true) {
		$oClient = Ext_Thebing_Client::getInstance();
		$sPath = $oClient->getFilePath($bDocumentRoot);

		$sPath .= 'email_templates/';

		return $sPath;
	}

	/**
	 * Liefert den Pfad unter dem das doc zu finden ist
	 */
	public function getPath(){
		$sUploeadPath = $this->getUploadPath();
		$sFile = $this->attachment;

		return $sUploeadPath.$sFile;
	}

}