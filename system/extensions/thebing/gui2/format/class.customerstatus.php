<?php

class Ext_Thebing_Gui2_Format_CustomerStatus extends Ext_Gui2_View_Format_Selection {

	public function __construct(array $aSelection = array()) {

		$this->aSelectOptions = \Ext_TS_Inquiry_Index_Gui2_Data::getCustomerStatusOptions();

	}

}
