<?php

namespace Office\Entity\Settlementlist;

class Item extends \WDBasic {

	protected $_sTable = 'office_settlement_list_items';
	protected $_sTableAlias = 'osli';

	protected $_aFormat = array(
		'product'=>array(
			'required' => true
		)
	);
	
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$sFieldMatchcode = \Ext_Office_Config::get('field_matchcode');
		$iDatabase = (int)\Ext_Office_Config::get('database');
/*
		$strQuery = "SELECT
						d.*,
						UNIX_TIMESTAMP(`d`.`date`) as date_ts,
						UNIX_TIMESTAMP(`d`.`booking_date`) as booking_date_ts,
						u.firstname u_firstname,
						u.lastname u_lastname,
						c.firstname c_firstname,
						c.lastname c_lastname,
						k.".$this->_arrConfig['field_company']." k_company,
						k.".$this->_arrConfig['field_matchcode']." k_matchcode,
						k.".$this->_arrConfig['field_country']." k_country,
						IF(ISNULL(op.id), 0, 1) `send`,
						`oc`.`vat_id_nr`
					FROM 
						office_documents d LEFT OUTER JOIN
						system_user u ON d.editor_id = u.id LEFT OUTER JOIN
						office_contacts c ON d.contact_person_id = c.id  LEFT OUTER JOIN
						customer_db_".$this->_arrConfig['database']." k ON d.customer_id = k.id LEFT OUTER JOIN
						`office_customers` AS `oc` ON
							`k`.`id` = `oc`.`id` LEFT OUTER JOIN
						office_protocol op ON d.id = `op`.`document_id` AND `op`.`state` = 'send'
					WHERE 
						1  
						 ".$strWhereAddon."
					GROUP BY
						d.id
					ORDER BY `d`.`date` DESC";

		$aItems = DB::getQueryData($strQuery);
		*/
		
		$aSqlParts['select'] .= ',
			`customer`.`'.$sFieldMatchcode.'` `customer`,
			CONCAT(`contact`.`lastname`, ", ", `contact`.`firstname`) `contact`
			';
		
		$aSqlParts['from'] .= ' LEFT JOIN
			`customer_db_'.$iDatabase.'` `customer` ON `osli`.`customer_id` = `customer`.`id` LEFT JOIN
			`office_contacts` `contact` ON `osli`.`customer_contact_id` = `contact`.`id`
		';

	}
	
	public function save() {

		if($this->id == 0) {
			$this->creator_id = \System::getCurrentUser()->id;
		}
		
		$this->editor_id = \System::getCurrentUser()->id;
		
		parent::save();
		
		return $this;		
	}

}