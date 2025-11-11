<?php

namespace Ts\Entity\Special;

class Code extends \Ext_Thebing_Basic {
	
	protected $_sTable = 'ts_specials_codes';
	protected $_sTableAlias = 'ts_spc';
	
	protected $_aJoinedObjects = [
		'special' => [
			'class'	=> \Ext_Thebing_School_Special::class,
			'key' => 'special_id',
		],
	];
	
	protected $_aJoinTables = [
		'usages' => [
			'table' => 'ts_specials_codes_usages',
			'foreign_key_field' => 'inquiry_id',
			'primary_key_field' => 'code_id'
		]
	];
	
	public function saveUsage(\Ext_TS_Inquiry $inquiry) {

		$usages = $this->usages;
		$usages[] = $inquiry->id;

		// Jede Buchung nur einmal zählen
		$usages = array_unique($usages);

		if($this->usage_limit !== null) {
			
			if(count($usages) >= $this->usage_limit) {
				$this->valid = 0;
			}
			
		}
		
		$this->usages = $usages;
		$this->latest_use = date('Y-m-d H:i:s');
		
		$this->save();
		
	}

	public function validate($bThrowExceptions = false)
	{
		$payload = parent::validate($bThrowExceptions);

		if ($payload === true) {
			$payload = [];

			if (
				$this->valid_from !== null &&
				$this->valid_until !== null
			) {
				$validFrom = new \DateTime($this->valid_from);
				$validUntil = new \DateTime($this->valid_until);

				if ($validFrom > $validUntil) {
					$payload[$this->_sTableAlias.'.valid_from'][] = 'INVALID_DATE_UNTIL_BEFORE_FROM';
				}
			}

			if (empty($payload)) {
				$payload = true;
			}
		}

		return $payload;
	}
	
}