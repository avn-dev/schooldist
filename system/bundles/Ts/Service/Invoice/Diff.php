<?php

namespace Ts\Service\Invoice;

class Diff {
	
	/**
	 * @var \Ext_TS_Inquiry
	 */
	private \Ext_TS_Inquiry $inquiry;
	
	private array $items = [];

	public function __construct(\Ext_TS_Inquiry $inquiry) {
		
		$this->inquiry = $inquiry;
		
	}
	
	public function loadItemsFromInvoices() {
		
		$documentSearch = new \Ext_Thebing_Inquiry_Document_Search($this->inquiry->id);
		$documentSearch->setType('invoice_without_proforma');
		$documents = $documentSearch->searchDocument();
		
		$this->items = [];
		foreach($documents as $document) {
			$this->items = array_merge($this->items, $document->getLastVersion()->getItems());
		}
		
	}
		
	/**
	 * @todo Erweitern, so dass bei Items mit Betragsänderung der alte Betrag gutgeschrieben und der neue berechnet wird.
	 * 
	 * @param array $items
	 * @return array
	 */
	public function getDiff(array $items):array {
		
		$checkAttributes = [
			'type',
			'type_id',
			'parent_id'
		];
		
		$diffItems = [];

		foreach($items as $item) {
			
			$itemFound = false;
			foreach($this->items as $savedItem) {

				if(array_intersect_key($item, array_flip($checkAttributes)) == array_intersect_key($savedItem, array_flip($checkAttributes))) {
					$itemFound = true;
				}
				
			}
			
			if(!$itemFound) {
				$diffItems[$item['item_key']] = $item;
			}
			
		}
		
		return $diffItems;
	}
	
}
