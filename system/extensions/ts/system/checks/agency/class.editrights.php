<?php

class Ext_TS_System_Checks_Agency_EditRights extends Ext_TS_System_Checks_Usergroup_Rights {
	
	protected $_aRights = array (
	 	'thebing_marketing_agencies_edit',
	 	'thebing_marketing_agencies_members_edit',
	 	'thebing_marketing_agencies_notes_edit',
		'thebing_marketing_agencies_uploads_edit',
	 	'thebing_marketing_agencies_creditnotes_edit'
	);
	
	public function getTitle() {
		return 'Marketing - Agencies';
	}
	
	public function getDescription() {
		return 'Sets the rights for agency management';
	}
	
}
