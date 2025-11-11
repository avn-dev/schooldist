<?php

namespace TsAccounting\Service\eInvoice\Italy\ExternalApp;

use TsAccounting\Handler\ExternalApp\AbstractCompanyApp;

/**
 * Externe App für das e-Invoicing in Italien
 */
class XmlIt extends AbstractCompanyApp {
	
	const APP_NAME = 'xml_it';
	
	public function getTitle(): string {
		return \L10N::t('XML Rechnungsübermittlung (IT)');
	}
	
	public function getDescription(): string {
		return \L10N::t('XML Rechnungsübermittlung (IT) - Beschreibung');
	}

	public function getCategory(): string {
		return \TcExternalApps\Interfaces\ExternalApp::CATEGORY_ACCOUNTING;
	}
	
} 

