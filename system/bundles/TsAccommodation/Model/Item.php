<?php

namespace TsAccommodation\Model;

class Item extends \Ext_Thebing_Inquiry_Document_Version_Item {
	
	public $original;
	
	public function __set($name, $value) {
		
		if(
			$name === 'amount' ||
			$name === 'amount_net' ||
			$name === 'amount_provision' ||
			$name === 'index_from' ||
			$name === 'index_until' ||
			$name === 'additional_info'
		) {
			$arrayName = $name;
			if(
				$name === 'index_from' ||
				$name === 'index_until'
			) {
				$arrayName = str_replace('index_', '', $name);
			}
			
			$this->original[$arrayName] = $value;

		}
		
		parent::__set($name, $value);
	}
	
}
