<?php

class Ext_TC_Contact_Search {
	
	protected $_aCriterions = array();
	
	public function addCriterion($sType, $mValue) {

		$this->_aCriterions[$sType] = $mValue;

	}
	
	public function execute() {
		
		$sSql = "
			SELECT
				`tc_c`.`id`
			FROM
				`tc_contacts` `tc_c`
			";
		$aSql = array();
		$sWhere = "";
		
		foreach($this->_aCriterions as $sType=>$mValue) {
			
			switch($sType) {
				case 'email':
					$sSql .= " JOIN 
				`tc_contacts_to_emailaddresses` `tc_c_to_ea` ON
					`tc_c`.`id` = `tc_c_to_ea`.`contact_id` JOIN
				`tc_emailaddresses` `tc_ea` ON
					`tc_ea`.`id` = `tc_c_to_ea`.`emailaddress_id`
				";
					$sWhere .= " AND `tc_ea`.`email` = :email";
					$aSql['email'] = $mValue;
					break;
				case 'firstname':
					$sWhere .= " AND `tc_c`.`firstname` = :firstname";
					$aSql['firstname'] = $mValue;
					break;
				case 'lastname':
					$sWhere .= " AND `tc_c`.`lastname` = :lastname";
					$aSql['lastname'] = $mValue;
					break;
				case 'birthday':
					$sWhere .= " AND `tc_c`.`birthday` = :birthday";
					$aSql['birthday'] = $mValue;
					break;
				default:
					throw new Exception('Unknown criterion "'.$sType.'" in contact search!');
			}
			
		}
		
		$sSql .= "
			WHERE
				`tc_c`.`active` = 1
				".$sWhere."
			LIMIT 1
		";
		$iContactId = DB::getQueryOne($sSql, $aSql);
		
		return $iContactId;
		
	}
	
}