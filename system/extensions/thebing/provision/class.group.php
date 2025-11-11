<?php

/**
 * @property $id
 * @property $changed 	
 * @property $created 	
 * @property $active 	
 * @property $creator_id 	
 * @property $user_id 	
 * @property $school_id
 * @property $name 	
 * @property $comment 	
 * @property $old_structure
 */
class Ext_Thebing_Provision_Group extends Ext_Thebing_Basic {

	use Ts\Traits\ServiceSettings;
	
	// Tabellenname
	protected $_sTable = 'ts_commission_categories';
	protected $_sTableAlias = 'ts_coc';

	public $allocations = [];
	
	protected $ratesIndex = [];

	protected $_aJoinTables = [
		'rates' => [
			'table' => 'ts_commission_categories_rates',
			'primary_key_field' => 'category_id'
		],
	];
	
	public function __set($sName, $mValue){

		if(
			$sName === 'school_id' && 
			$mValue === '0'
		) {
			$mValue = null;
		}
		
		switch($sName){
			case 'year_select':
				break; // Select für Jahre dient nur als Filter
			default:
				parent::__set($sName, $mValue);
		}

	}

	public function __get($sName){

		Ext_Gui2_Index_Registry::set($this);

		switch($sName){
			case 'commission_transfer':
			case 'commission_extra_position':
				return 3;
			case 'year_select':
				break; // Select für Jahre dient nur als Filter
			default:
				$mValue = parent::__get($sName);
				break;
		}

		return $mValue;

	}

	public function getRatesIndex() {
		
		// Static cache
		if(empty($this->ratesIndex)) {

			$this->ratesIndex = [];

			foreach($this->rates as $rate) {
				$this->ratesIndex[$rate['type']][$rate['type_id']][$rate['parent_type']][$rate['parent_type_id']] = [$rate['rate'], $rate['rate_type']];
			}
		
		}
		
		return $this->ratesIndex;
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @param bool $bForceUpdateUser
	 */
	public function save($bLog = true, $bForceUpdateUser = false) {

		$bOriginalForceUpdateUser = $this->bForceUpdateUser;
		$this->bForceUpdateUser = (bool)$bForceUpdateUser;
		$mReturn = parent::save($bLog);
		$this->bForceUpdateUser = $bOriginalForceUpdateUser;
		
		$this->ratesIndex = [];
		
		return $mReturn;
	}

	protected function getSchools() {
		
		return [Ext_Thebing_School::getInstance($this->school_id)];
		
	}

	public function createAllocation($aAllocationData) {

		$this->allocations[$this->generateKey($aAllocationData)] = $aAllocationData;
		
	}

	public function getAllocations() {
		return $this->allocations;
	}
	
	public function setAllocationValues() {
		
		foreach($this->rates as $rate) {
			
			$rate['account_type'] = 'commission';
			
			$key = $this->generateKey($rate);
			
			if(isset($this->allocations[$key])) {
				$this->allocations[$key]['rate'] = $rate['rate'];
				$this->allocations[$key]['rate_type'] = $rate['rate_type'];
			}

//			$aData[$key] = array(
//				'account_number'	=> $aAllocation['account_number'],
//				'account_number_discount'	=> $aAllocation['account_number_discount'],
//				'automatic_account' => $aAllocation['automatic_account'],
//			);
		}

//		$this->setAllocationData($aData);
	}

}
