<?php

namespace Ts\Exception;

// PDF_Exception ist innerhalb dieser Datei definiert
use Ext_TC_Pdf_Fpdi;

class MissingBackgroundFileException extends \PDF_Exception {

	public function __construct($sFilePath, $iTemplateId, $sLanguage, $iSchoolId) {
		$sMessage = $this->getTranslatedMessage($sFilePath, $iTemplateId, $sLanguage, $iSchoolId);
		parent::__construct($sMessage, 500);
	}

	private function getTranslatedMessage(string $sFilePath, int $iTemplateId, string $sLanguage, int $iSchoolId){

		// Background PDF File {file} does not exist! (xref-failure would follow; tpl: {template_id}, lang: {language}, school: {school}
		$sMessage = \L10N::t('Für die Sprache ({language}) der Vorlage konnte kein Hintergrund gefunden werden. Bitte weisen Sie der Vorlage ({template}) einen Hintergrund zu und speichern Sie diese erneut ab (Admin - Vorlagen - PDF Vorlagen).', 'Thebing » PDF');

//		$sMessage = str_replace('{file}', $sFilePath, $sMessage);
//		$sMessage = str_replace('{template_id}', $iTemplateId, $sMessage);
		$sMessage = str_replace('{language}', $sLanguage, $sMessage);
//		$sMessage = str_replace('{school}', $iSchoolId, $sMessage);
		$sMessage = str_replace('{template}', \Ext_Thebing_Pdf_Template::getInstance($iTemplateId)->name, $sMessage);

 		return $sMessage;
	}

}