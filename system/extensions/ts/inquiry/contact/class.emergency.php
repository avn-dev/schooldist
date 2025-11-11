<?php

class Ext_TS_Inquiry_Contact_Emergency extends Ext_TS_Contact{

	protected $_sTableAlias = 'tc_c_e';

	protected $type = 'emergency';

	protected $_sPlaceholderClass = 'Ts\Service\Placeholder\Booking\OtherContact';

	public function __get($name) {
		
		if(isset($this->_aData[$name])) {
			return $this->_aData[$name];
		}
		
		if($name === 'type') {
			return $this->type;
		}
		
		return parent::__get($name);
	}
	
	public function __set($name, $value) {
		
		if($name === 'type') {
			$this->type = $value;
			return;
		}
		
		parent::__set($name, $value);
		
	}
	
	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {
		
		if($this->isEmpty()) {
			if(
				$this->id > 0 &&
				$this->active == 1
			) {
				$this->active = 0;
			} else {
				return true;
			}
		}
		
		$mSuccess = parent::save($bLog);
		
		return $mSuccess;

	}

	/**
	 * @return bool
	 */
	public function isEmpty() {

		$sEmail = $this->getEmail();
		$sPhonePrivate = $this->getDetail('phone_private');
		$sLastName = $this->lastname;
		$sFirstName = $this->firstname;

		if(
			empty($sEmail) &&
			empty($sPhonePrivate) &&
			empty($sLastName) &&
			empty($sFirstName)
		) {
			return true;
		}

		return false;

	}

}
