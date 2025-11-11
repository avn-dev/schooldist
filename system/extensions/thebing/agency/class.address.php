<?php

/**
 * @property int $id
 * @property string $changed
 * @property string $created
 * @property int $active
 * @property int $agency_id
 * @property string $shortcut
 * @property string $company
 * @property string $contact
 * @property string $street 
 * @property string $zip
 * @property string $city
 * @property string $country
 * @property string $phone
 */
class Ext_Thebing_Agency_Address extends Ext_Thebing_Basic {
	
	protected $_sTable = 'ts_companies_addresses';

	protected $_aFormat = array(
						'shortcut' => array(
					  			'required'	=> true
							),
						'company' => array(
					  			'required'	=> true
							)
						);
	
	public function getAddressString() {
		
		$sAddress = "";
		$sAddress .= $this->company."\n";
		$sAddress .= $this->contact."\n";
		$sAddress .= $this->street."\n";
		$sAddress .= $this->zip." ".$this->city."\n";
		$sAddress .= $this->country."\n";
		$sAddress .= $this->phone."\n";
		return $sAddress;
		
	}

}
