<?php
 
class Ext_Thebing_Accounting_Cheque_Number extends Ext_Thebing_Number {

	protected $_oSchool;

	public function __construct($iSchoolId) {

		$this->_oSchool = Ext_Thebing_School::getInstance($iSchoolId);

	}

	public function make() {

		// Statisch weil nur noch von Lacunza genutzt
		$this->format			= '%count';
		$this->offset			= 1;
		$this->digits			= 3;
		$this->offset_iteration	= 1;
		
		$sCustomerNumber = $this->generateNumber();

		return $sCustomerNumber;

	}

	protected function _searchLatestNumber($sPrefix, $sPostfix) {

		$sQuery = "
					SELECT
						`kcp`.`cheque_number`
					FROM
						`kolumbus_cheque_payment` `kcp`
					WHERE
						`kcp`.`cheque_number` LIKE :pattern AND
						`kcp`.`cheque_number` != '' AND
						`kcp`.`school_id` = :school_id AND
						`kcp`.`active` = 1
					GROUP BY
						`kcp`.`cheque_number`
					ORDER BY
						(REPLACE(REPLACE(`kcp`.`cheque_number`, :postfix, ''), :prefix, '') + 0) DESC,
						`kcp`.`created` DESC
					LIMIT 1
					";

		$aSql = array(
			'school_id' => (int)$this->_oSchool->id,
			'pattern' => $sPrefix.'%'.$sPostfix,
			'prefix' => $sPrefix,
			'postfix' => $sPostfix
		);

		$sLatestNumber = DB::getQueryOne($sQuery, $aSql);

		return $sLatestNumber;
	}

}