<?php

class Ext_TS_System_Checks_Pickup_ConfirmRights extends Ext_TS_System_Checks_Usergroup_Rights {
	
	protected $_aRights = array (
	 	'thebing_pickup_confirmation_button_request',
	 	'thebing_pickup_confirmation_button_confirm_transfer',
	 	'thebing_pickup_confirmation_button_confirm_accommodation',
	 	'thebing_pickup_confirmation_button_confirm_student',
	);
	
	public function getTitle() {
		return 'Pickup confirmation';
	}
	
	public function getDescription() {
		return 'Sets the rights for the new structure of pickup confirmations';
	}
	
}
