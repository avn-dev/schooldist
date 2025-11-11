<?php

class Ext_Thebing_Agency_Member_Placeholder extends Ext_Thebing_Placeholder {


	protected  $_oAgencyStaff;


	public function __construct($iObjectId = 0) {

		$this->_oAgencyStaff	= Ext_Thebing_Agency_Contact::getInstance($iObjectId);
		$this->_sSection		= 'agencies_users';
		$this->_iFlexId			= $this->_oAgencyStaff->id;

		parent::__construct();
	   
	}


	/**
	 * Get the list of available placeholders
	 * 
	 * @return array
	 */
	public function getPlaceholders($sType = '')
	{
		$aPlaceholders = array();

		return $aPlaceholders;
	}

}
