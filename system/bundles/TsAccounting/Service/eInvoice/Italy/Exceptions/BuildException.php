<?php

namespace TsAccounting\Service\eInvoice\Italy\Exceptions;

use TcAccounting\Service\eInvoice\Service\Italy\Exceptions\BuildException as AbstractException;

class BuildException extends AbstractException {
	
	public function getTranslatedMessage(): string {

		switch($this->message) {
			case 'no_company':
				$sErrorMessage = \L10N::t('Für die Schule "%s" wurde keine Firma gefunden!');
				break;
			case 'final_export_exists':
				$sErrorMessage = \L10N::t('Für das Dokument "%s" existiert bereits ein finaler Export!');
				break;
			case 'company_data_missing':
				$sErrorMessage = \L10N::t('Bitte pflegen Sie die Informationen für die Firma "%s" in den App-Einstellungen!');
				break;
			default:
				$sErrorMessage = parent::getTranslatedMessage();
		}
		
		$sErrorMessage = vsprintf($sErrorMessage, $this->aParameters);
		
		return $sErrorMessage;
	}
	
}
