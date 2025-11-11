<?php

class Ext_TS_Inquiry_Contact_Booker extends Ext_TS_Inquiry_Contact_Abstract {
	
	/**
	 * @var boolean
	 */
	public $bCheckGender = false;
	
	/**
	 * @return string
	 */
	protected function _getType() {
		return 'booker';
	}

}
