<?php

namespace TcAccounting\Service\eInvoice\Service\Italy\Exceptions;

use TcAccounting\Service\eInvoice\Exceptions\BuildException as AbstractException;

class BuildException extends AbstractException {
	
	public function getTranslatedMessage(): string {
		
		switch($this->message) {
			case 'no_pdf':
				$sErrorMessage = \L10N::t('Für das Dokument "%s" wurde kein PDF gefunden!');
				break;
			default:
				$sErrorMessage = parent::getTranslatedMessage();
		}
		
		$sErrorMessage = vsprintf($sErrorMessage, $this->aParameters);
		
		return $sErrorMessage;
	}
	
}
