<?php

namespace TsAccommodation\Hook;

/**
 * Ticket #17257 – GLS - Automatische Berechnung der City Tax
 * Betrag von City Tax-Position ausrechnen
 * 
 * Ticket #18186 - GLS - Anpassungen Steuern - Provisionsrechnungen
 * 
 */
class DocumentModifyItems {

	/**
	 * @param \Ext_Thebing_Inquiry_Document_Version_Item[] $items
	 * @param \Ext_Thebing_Inquiry_Document $document
	 */
	public function run(array $items, \Ext_Thebing_Inquiry_Document $document) {

		if(\TcExternalApps\Service\AppService::hasApp(\TsAccommodation\Handler\ExternalApp\CityTax::APP_NAME)) {
			$cityTax = new \TsAccommodation\Service\CityTax;
			$cityTax->handle($items, $document);
		}
		
	}

}
