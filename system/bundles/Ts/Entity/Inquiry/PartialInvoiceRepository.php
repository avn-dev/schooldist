<?php

namespace Ts\Entity\Inquiry;

class PartialInvoiceRepository extends \WDBasic_Repository {

	/**
	 * 
	 * @param \Ext_TS_Inquiry $inquiry
	 * @return \Ts\Entity\Inquiry\PartialInvoice
	 */
	public function getNext(\Ext_TS_Inquiry $inquiry) {
		
		$nextData = \DB::getQueryRow(
			"SELECT * FROM ts_inquiries_partial_invoices WHERE inquiry_id = :inquiry_id AND converted IS NULL ORDER BY date ASC LIMIT 1", 
			[
				'inquiry_id'=>$inquiry->id
			]
		);
		
		$next = null;
		if(is_array($nextData)) {
			$next = $this->_getEntity($nextData);
		}

		return $next;
	}
	
	/**
	 * 
	 * @param \Ext_TS_Inquiry $inquiry
	 * @return \Ts\Entity\Inquiry\PartialInvoice
	 */
	public function getConverted(\Ext_TS_Inquiry $inquiry) {
		
		$converted = \DB::getQueryRows(
			"SELECT * FROM ts_inquiries_partial_invoices WHERE inquiry_id = :inquiry_id AND converted IS NOT NULL ORDER BY date ASC", 
			[
				'inquiry_id'=>$inquiry->id
			]
		);
		
		$entities = array();
		if(is_array($converted)) {
			$entities = $this->_getEntities($converted);
		}

		return $entities;
	}
	
}