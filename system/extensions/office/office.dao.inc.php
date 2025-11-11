<?php
 
class classExtensionDao_Office {
	
	private $_arrConfig = array();
	
	function __construct($arrConfig=array()) {
		$this->_arrConfig = $arrConfig;
	}

	public function __get($sName) {
		
		if(isset($this->_arrConfig[$sName])) {
			$mValue = $this->_arrConfig[$sName];
			return $mValue;
		}
		
	}
	
	/**
	 * @author Sebastian Kaiser
	 * @param boolean $bPrepareSelect true = return as prepared select array
	 * @return array all active clients from office_clients
	 */
	static function getAllClients($bPrepareSelect = false)
	{
		if($bPrepareSelect)
		{
			$sSQL = "
				SELECT
					`id`,
					`title`
				FROM
					`office_clients`
				WHERE
					`active` = 1
			";
			$aClients = DB::getQueryPairs($sSQL);
		}
		else
		{
			$sSQL = "
				SELECT
					*
				FROM
					`office_clients`
				WHERE
					`active` = 1
			";
			$aClients = DB::getQueryData($sSQL);			
		}
		return $aClients;
	}

	function getContact($intContact) {
		
		$strSql = "SELECT id, sex, firstname, lastname, email, phone, mobile, `fax` FROM office_contacts WHERE `id` = '".(int)$intContact."'
								LIMIT 1";
		$aContact = DB::getQueryRow($strSql);
		
		return $aContact;
	}

	public function saveArticle($aArticle)
	{
		if((int)$aArticle['id'] == 0)
		{
			$sSQL = "
				INSERT INTO
					`office_articles`
				SET
					`active`		= 1
			";
			DB::executeQuery($sSQL);

			// Set the new customer ID
			$aArticle['id'] = (int)DB::fetchInsertID();
		}

		$sSQL = "
			UPDATE
				`office_articles`
			SET
				`product`		= :sProduct,
				`number`		= :sNumber,
				`unit`			= :sUnit,
				`productgroup`	= :iProductgroup,
				`currency`		= :sCurrency,
				`price`			= :iPrice,
				`cost`			= :iCost,
				`vat`			= :iVat,
				`month`			= :iMonth,
				`description`	= :sDescription
			WHERE
				`id` = :iArticleID
			LIMIT
				1
		";
		$aSQL = array(
			'sProduct'		=> $aArticle['product'],
			'sNumber'		=> $aArticle['number'],
			'sUnit'			=> $aArticle['unit'],
			'iProductgroup' => $aArticle['productgroup'],
			'sCurrency'		=> $aArticle['currency'],
			'iPrice'		=> $aArticle['price'],
			'iCost'			=> $aArticle['cost'],
			'iVat'			=> $aArticle['vat'],
			'iMonth'		=> $aArticle['month'],
			'sDescription'	=> $aArticle['description'],
			'iArticleID'	=> $aArticle['id']
		);
		DB::executePreparedQuery($sSQL, $aSQL);
	}

	public function saveContactPerson($aContactPerson)
	{
		// Define log subject
		$sLogSubject = 'Bearbeitet';

		// New contact person
		if((int)$aContactPerson['id'] <= 0)
		{
			$sSQL = "
				INSERT INTO
					`office_contacts`
				SET
					`created`		= NOW(),
					`active`		= 1,
					`customer_id`	= :iCustomerID
			";
			$aSQL = array('iCustomerID' => $aContactPerson['customer_id']);
			DB::executePreparedQuery($sSQL, $aSQL);

			// Set the new customer ID
			$aContactPerson['id'] = (int)DB::fetchInsertID();

			$sLogSubject = 'Angelegt';
		}

		// Update data
		$sSet = 'SET ';
		$iCount = count((array)$aContactPerson);
		$iFlag = 1;

		// Create the SET condition
		foreach((array)$aContactPerson as $sKey => $mValue)
		{
			if(array_key_exists($sKey, $aContactPerson))
			{
				$sSet .= '`'.$sKey.'` = :'.$sKey;
				if($iFlag < $iCount)
				{
					$sSet .= ', ';
				}
				$iFlag++;
			}
		}

		// Update the fields
		$sSQL = "
			UPDATE
				`office_contacts`
			{SET}
			WHERE
				`id` = :iID
			LIMIT
				1
		";
		$sSQL = str_replace('{SET}', $sSet, $sSQL);
		$aContactPerson['iID'] = $aContactPerson['id'];
		$aSQL = $aContactPerson;

		// Log
		global $user_data;
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> $aContactPerson['customer_id'],
			'contact_id'	=> 0,
			'editor_id'		=> $user_data['id'],
			'document_id'	=> 0,
			'topic'			=> 'customer_contact',
			'subject'		=> $sLogSubject,
			'state'			=> ''
		);
		$this->manageProtocols($aLog);

		DB::executePreparedQuery($sSQL, $aSQL);
	}

	static function getPaymentTerms($iTypeFlag=null, $bFull=false) {

		$aSQL = array(
			'iClientID'	=> (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id')
		);

		$sWhere = "";
		if($iTypeFlag !== null) {
			$sWhere .= " AND
				`type_flag` = :type_flag ";
			$aSQL['type_flag'] = (int)$iTypeFlag;
		}

		$sSelect = "`id`,
						`title`";
		if($bFull === true) {
			$sSelect = "*";	
		}
		
		$strSql = "
					SELECT
						".$sSelect."
					FROM
						office_payment_terms
					WHERE
						`client_id` = :iClientID
						".$sWhere."
					ORDER BY 
						days
					";
		if($bFull === true) {
			$arrTerms = DB::getQueryRows($strSql, $aSQL);
		} else {
			$arrTerms = DB::getQueryPairs($strSql, $aSQL);
		}

		return $arrTerms;
	}

	public function getPaymentTerm($intTermId) {

		$strSql = "
					SELECT
						*
					FROM
						office_payment_terms
					WHERE
						id = :intId
					";
		$arrTransfer = array('intId'=>(int)$intTermId);
		$arrTerms = DB::getPreparedQueryData($strSql, $arrTransfer);

		return $arrTerms[0];

	}

	function getContacts($intCustomer=null) {

		$strSql = "
				SELECT 
					id, 
					created,
					firstname, 
					lastname,
					`nickname`,
					email, 
					phone,
					mobile,
					sex,
					fax,
					description,
					invoice_contact,
					invoice_recipient,
					`customer_id`
				FROM 
					office_contacts 
				WHERE 
					active = 1 
				";
		if($intCustomer !== null) {
			$strSql .= " AND 
					customer_id = '".\DB::escapeQueryString($intCustomer)."'";
			$strSql .= " ORDER BY `lastname`, `firstname`";
		} else {
			$strSql .= " ORDER BY `customer_id`, `lastname`, `firstname`";
		}
		
		$resContacts = (array)DB::getQueryRows($strSql);
		$arrContacts = array();
		foreach($resContacts as $arrContact) {
			$arrContacts[$arrContact['id']] = $arrContact;
		}
		return $arrContacts;
	}

	public function removeArticle($iArticleID)
	{
		$sSQL = "
			DELETE FROM
				`office_articles`
			WHERE
				`id` = :iArticleID
			LIMIT
				1
		";
		$aSQL = array('iArticleID' => $iArticleID);

		DB::executePreparedQuery($sSQL, $aSQL);
	}

	public function removeContactPerson($iContactPersonID, $iCustomerID) {

		$sSQL = "
			UPDATE
				`office_contacts`
			SET 
				`active` = 0
			WHERE
				`id` = :iContactPersonID
			LIMIT
				1
		";
		$aSQL = array('iContactPersonID' => $iContactPersonID);

		DB::executePreparedQuery($sSQL, $aSQL);

		// Log
		global $user_data;
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> $iCustomerID,
			'contact_id'	=> 0,
			'editor_id'		=> $user_data['id'],
			'document_id'	=> 0,
			'topic'			=> 'customer_contact',
			'subject'		=> 'Gelöscht',
			'state'			=> ''
		);
		$this->manageProtocols($aLog);

	}

	function getCustomers($strSearch = "", $iClientID = 1, $iGroupId=0) {

		if(
			empty($this->_arrConfig['database']) ||
			empty($this->_arrConfig['field_number']	)
		) {
			return [];
		}
		
		$sWhere = '';

		$iClientID = (int)$iClientID;
		if($iClientID == 0)
		{
			$iClientID = 1;
		}

		if(is_array($strSearch)) {
			$this->_arrConfig = $strSearch;
			$strSearch = "";
		}

		if($iGroupId > 0) {
			$sWhere .= " AND `ocgj`.`group_id` = ".(int)$iGroupId." ";
		}

		$strSql = "
						SELECT 
							`cdb`.`id`,  
							`cdb`.`".$this->_arrConfig['field_number']."` number,  
							`cdb`.`".$this->_arrConfig['field_matchcode']."` name,  
							`cdb`.`".$this->_arrConfig['field_company']."` company,
							`cdb`.`".$this->_arrConfig['field_address']."` address,  
							`cdb`.`".$this->_arrConfig['field_addition']."` addition, 
							`cdb`.`".$this->_arrConfig['field_zip']."` zip,  
							`cdb`.`".$this->_arrConfig['field_city']."` city,  
							`cdb`.`".$this->_arrConfig['field_country']."` country,  
							`cdb`.`".$this->_arrConfig['field_phone']."` phone,  
							`cdb`.`".$this->_arrConfig['field_fax']."` fax,
							`oc`.`language`,
							`oc`.`vat_id_nr`,
							`oc`.`vat_id_valid`,
							`oc`.`vat_id_check`,
							GROUP_CONCAT(`ocgj`.`group_id`) `group_ids`
						FROM  ";
		
		if($strSearch != "") {

			$strSql .= "
							customer_db_".$this->_arrConfig['database']." cdb LEFT OUTER JOIN
							office_contacts o 
								ON
									`cdb`.`id` = `o`.`customer_id` LEFT OUTER JOIN
							`office_customers` AS `oc` ON
								`cdb`.`id` = `oc`.`id` LEFT OUTER JOIN
							`office_customers_groups_join` `ocgj` ON
								`ocgj`.`customer_id` = `oc`.`id`
						WHERE  
							`cdb`.`active` = 1 AND
							(
								`oc`.`client_id` = '" . $iClientID . "' OR
								`oc`.`client_id` IS NULL
							) AND
							(
								`cdb`.`".$this->_arrConfig['field_number']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR  
								`cdb`.`".$this->_arrConfig['field_matchcode']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_company']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_address']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_addition']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_zip']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_city']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_country']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_phone']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`cdb`.`".$this->_arrConfig['field_fax']."` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`o`.`firstname` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`o`.`lastname` LIKE '%".\DB::escapeQueryString($strSearch)."%' OR
								`o`.`email` LIKE '%".\DB::escapeQueryString($strSearch)."%'
							)
							".$sWhere."
						GROUP BY
							oc.id
						ORDER BY
							`cdb`.`".$this->_arrConfig['field_matchcode']."` ASC";

		} else {

			$strSql .= "
							customer_db_".$this->_arrConfig['database']." AS `cdb` LEFT OUTER JOIN
							`office_customers` AS `oc` ON
								`cdb`.`id` = `oc`.`id` LEFT OUTER JOIN
							`office_customers_groups_join` `ocgj` ON
								`ocgj`.`customer_id` = `oc`.`id`
						WHERE  
							`cdb`.active = 1 AND
							(
								`oc`.`client_id` = '" . $iClientID . "' OR
								`oc`.`client_id` IS NULL
							)
							".$sWhere."
						GROUP BY
							cdb.id
						ORDER BY
							`cdb`.`".$this->_arrConfig['field_matchcode']."` ASC";
		}

		$rCustomer = DB::executeQuery($strSql);
		while ($aCustomer = DB::getRowAssoc($rCustomer)) {
			$aCustomersFull[$aCustomer['id']] = $aCustomer;
		}
		return $aCustomersFull;
	}
	
	function getCustomer($intCustomer) {

		$aCustomer = DB::getQueryRow("SELECT 
								`id`, 
								`email`,
								`".$this->_arrConfig['field_number']."` number, 
								`".$this->_arrConfig['field_matchcode']."` matchcode, 
								`".$this->_arrConfig['field_company']."` company, 
								`".$this->_arrConfig['field_address']."` address, 
								`".$this->_arrConfig['field_addition']."` addition, 
								`".$this->_arrConfig['field_zip']."` zip, 
								`".$this->_arrConfig['field_city']."` city, 
								`".$this->_arrConfig['field_country']."` country, 
								`".$this->_arrConfig['field_phone']."` phone, 
								`".$this->_arrConfig['field_fax']."` fax 
								FROM 
									customer_db_".$this->_arrConfig['database']." c
								WHERE
									id = ".intval($intCustomer)." AND
									active = 1
								LIMIT 1");

		return $aCustomer;
	}
	
	function getCustomersByNumbers($aNumbers) {

		$sSql = "SELECT 
					`id`, 
					`email`,
					`".$this->_arrConfig['field_number']."` number, 
					`".$this->_arrConfig['field_matchcode']."` matchcode, 
					`".$this->_arrConfig['field_company']."` company, 
					`".$this->_arrConfig['field_address']."` address, 
					`".$this->_arrConfig['field_addition']."` addition, 
					`".$this->_arrConfig['field_zip']."` zip, 
					`".$this->_arrConfig['field_city']."` city, 
					`".$this->_arrConfig['field_country']."` country, 
					`".$this->_arrConfig['field_phone']."` phone, 
					`".$this->_arrConfig['field_fax']."` fax 
				FROM 
						customer_db_".$this->_arrConfig['database']." c
				WHERE
						`".$this->_arrConfig['field_number']."` IN (:numbers) AND
						active = 1
					";
		$aSql = array(
			'numbers'=>(array)$aNumbers
		);
		$aCustomers = DB::getQueryRows($sSql, $aSql);

		return $aCustomers;
	}
	
	function getInvoicesByNumbers($aNumbers) {

		$sSql = "SELECT 
					`od`.`id`,
					`od`.`client_id`,
					`od`.`customer_id`,
					`od`.`contact_person_id`,
					`od`.`editor_id`,
					`od`.`type`,
					`od`.`number`,
					`od`.`price`,
					`od`.`price_net`,
					`od`.`currency`,
					`od`.`payment`,
					`od`.`state`,
					UNIX_TIMESTAMP(`od`.`date`) `date`,
					`c`.`".$this->_arrConfig['field_number']."` `customer_number`, 
					`c`.`".$this->_arrConfig['field_matchcode']."` `customer_matchcode`, 
					`c`.`".$this->_arrConfig['field_company']."` `customer_name`
				FROM 
					`office_documents` `od` JOIN
					`customer_db_".$this->_arrConfig['database']."` `c` ON
						`od`.`customer_id` = `c`.`id`
				WHERE 
					`od`.`type` = 'account' AND
					`od`.`state` NOT IN ('paid', 'draft') AND
					`od`.`number` IN (:numbers) AND
					`od`.`number` != 0
					";
		$aSql = array('numbers'=>(array)$aNumbers);

		$aDocuments = DB::getQueryRows($sSql, $aSql);

		return $aDocuments;

	}
	
	function getInvoicesByCustomer($iCustomerId) {

		$sSql = "SELECT 
					`od`.`id`,
					`od`.`client_id`,
					`od`.`customer_id`,
					`od`.`contact_person_id`,
					`od`.`editor_id`,
					`od`.`type`,
					`od`.`number`,
					`od`.`price`,
					`od`.`price_net`,
					`od`.`currency`,
					`od`.`payment`,
					`od`.`state`,
					UNIX_TIMESTAMP(`od`.`date`) `date`,
					`c`.`".$this->_arrConfig['field_number']."` `customer_number`, 
					`c`.`".$this->_arrConfig['field_matchcode']."` `customer_matchcode`, 
					`c`.`".$this->_arrConfig['field_company']."` `customer_name`
				FROM 
					`office_documents` `od` JOIN
					`customer_db_".$this->_arrConfig['database']."` `c` ON
						`od`.`customer_id` = `c`.`id`
				WHERE 
					`od`.`type` = 'account' AND
					`od`.`state` NOT IN ('paid', 'draft') AND
					`od`.`customer_id` = :customer_id
				ORDER BY
					`od`.`date` ASC
					";
		$aSql = array('customer_id'=>(int)$iCustomerId);

		$aDocuments = DB::getQueryRows($sSql, $aSql);

		return $aDocuments;
	}


	public function getCustomerPayments($iCustomerID)
	{
		$sSQL = "
			SELECT
				*
			FROM
				`office_payment_terms`
		";
		$aPaymentTerms = DB::getQueryData($sSQL);

		$sSQL = "
			SELECT
				*
			FROM
				`office_customers`
			WHERE
				`id` = :iCustomerID
		";
		$aSQL = array('iCustomerID' => $iCustomerID);
		$aCustomerPayments = DB::getPreparedQueryData($sSQL, $aSQL);

		$aResult = array(
			'selectedPaymentInvoice'	=> '',
			'selectedPaymentMisc'		=> '',
			'aPaymentTerms'				=> $aPaymentTerms
		);
		if(!is_null($aCustomerPayments) && !empty($aCustomerPayments))
		{
			$aResult['selectedPaymentInvoice']	= $aCustomerPayments[0]['payment_invoice'];
			$aResult['selectedPaymentMisc']		= $aCustomerPayments[0]['payment_misc'];
		}

		return $aResult;
	}


	public function getCustomerAdditionals($iCustomerID)
	{
		$sSQL = "
			SELECT *
			FROM `office_customers`
			WHERE `id` = :iCustomerID
		";
		$aSQL = array('iCustomerID' => $iCustomerID);
		$aAdditionals = DB::getPreparedQueryData($sSQL, $aSQL);

		if(!empty($aAdditionals)) {
			return $aAdditionals[0];
		} else {
			return array();
		}

	}


	public function getPreparedCMSUserList()
	{
		$sSQL = "
			SELECT
				`id`,
				CONCAT(`firstname`, ' ', `lastname`) AS `name`
			FROM 
				`system_user`
			WHERE
				`active` = 1
			ORDER BY 
				`name`
		";
		$aUser = DB::getQueryData($sSQL);

		$aResult = array();
		foreach((array)$aUser as $aValue)
		{
			$aResult[] = array($aValue['id'], $aValue['name']);
		}

		return $aResult;
	}


	public function removeCustomer($iCustomerID)
	{
		$sSQL = "
			UPDATE
				`customer_db_".$this->_arrConfig['database']."`
			SET
				`active` = 0
			WHERE
				`id` = :iCustomerID
			LIMIT
				1
		";
		/* TODO : DELETE ONLY IF THE CUSTOMER HAS NO INVOICES
		$sSQL = "
			DELETE
				c,
				ocu,
				oco
			FROM
					`customer_db_".$this->_arrConfig['database']."` AS `c`
				LEFT OUTER JOIN
					`office_customers` AS `ocu`
						ON
					`c`.`id` = `ocu`.`id`
				LEFT OUTER JOIN
					`office_contacts` AS oco
						ON
					`c`.`id` = `oco`.`customer_id`
			WHERE
				`c`.`id` = :iCustomerID
		";
		*/
		$aSQL = array('iCustomerID' => $iCustomerID);

		DB::executePreparedQuery($sSQL, $aSQL);

		// Log
		global $user_data;
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> $iCustomerID,
			'contact_id'	=> 0,
			'editor_id'		=> $user_data['id'],
			'document_id'	=> 0,
			'topic'			=> 'customer_data',
			'subject'		=> 'Gelöscht',
			'state'			=> ''
		);
		$this->manageProtocols($aLog);
	}


	function getEditors()
	{
		$sSQL = "
			SELECT
				id, firstname, lastname
			FROM
				system_user
			WHERE
				active = 1
			ORDER BY
				lastname
		";
		$rEditor = DB::getQueryRows($sSQL);
		
		foreach($rEditor as $aEditor) {
			$aEditors[$aEditor['id']] = $aEditor['lastname'].", ".$aEditor['firstname'];
		}

		return $aEditors;
	}

	function getEditor($iEditorId) {
		$sSql = "
			SELECT
				*
			FROM
				system_user
			WHERE
				id = ".(int)$iEditorId."
			LIMIT
				1
		";

		$aEditor = DB::getQueryRow($sSql);

		return $aEditor;

	}
	
	function getForm($intFormId) {

		$strSql = "
				SELECT 
					* 
				FROM 
					office_forms 
				WHERE 
					id = '".\DB::escapeQueryString($intFormId)."'
				";
		$arrForm = DB::getQueryRow($strSql);

		$sSql = "
				SELECT 
					* 
				FROM 
					office_forms_tables
				WHERE 
					form_id = '".\DB::escapeQueryString($intFormId)."'
				";
		
		$resSql = (array)DB::getQueryRows($sSql);
		foreach($resSql as $arrSql) {
			$arrForm['fields'][$arrSql['key']] = $arrSql;
		}

		return $arrForm;
	}

	function updateFormTables($intFormId, $arrAttributes) {

		$iPosition = 0;
		foreach((array)$arrAttributes as $sKey => $aValue) {
			$sSql = "
				REPLACE
					office_forms_tables
				SET
					`form_id`			= :intFormId,
					`key`				= :strKey,
					`active`			= :bolActive,
					`title`				= :strTitle,
					`width`				= :intWidth,
					`decimal_places`	= :intDecimalPlaces,
					`position`			= :position
			";

			$arrSql = array(
				'intFormId'			=> $intFormId,
				'strKey'			=> $sKey,
				'bolActive'			=> (bool)$aValue['active'],
				'strTitle'			=> (string)$aValue['title'],
				'intWidth'			=> (int)$aValue['width'],
				'intDecimalPlaces'	=> (int)$aValue['decimal_places'],
				'position'			=> $iPosition
			);

			DB::executePreparedQuery($sSql, $arrSql);
			$iPosition++;
		}
	}

	function removeForm($intFormId) {

		$strSql = "
				DELETE
				FROM 
					office_forms 
				WHERE 
					id = '".\DB::escapeQueryString($intFormId)."'
				LIMIT 1
				";

		$resSql = DB::executeQuery($strSql);

		$strSql = "
				DELETE
				FROM 
					office_forms_tables
				WHERE 
					form_id = '".\DB::escapeQueryString($intFormId)."'
				";
		$resSql = DB::executeQuery($strSql);
		
		return $resSql;
	}

	function getFormItems($intFormId) {

		$strSql = "
				SELECT 
					* 
				FROM 
					office_forms_items
				WHERE
					form_id = '".\DB::escapeQueryString($intFormId)."'
				ORDER BY 
					id ASC
					";
		$resSql = DB::getQueryRows($strSql);
		$arrItems = array();
		foreach($resSql as $arrSql) {
			$arrItems[$arrSql['id']] = $arrSql;
		}

		return $arrItems;
	}	

	public function saveCustomerData($aCustomerData = array(), $aAdditionals = array()) {

		// Create a unique nickname if required
		if(!isset($aCustomerData['email']) || trim($aCustomerData['email']) == '')
		{
			$aCustomerData['email']		= \Util::generateRandomString(16);
		}
		if(!isset($aCustomerData['nickname']) || trim($aCustomerData['nickname']) == '')
		{
			if(isset($aCustomerData['email']) && trim($aCustomerData['email']) != '')
			{
				$aCustomerData['nickname'] = $aCustomerData['email'];
			}
			else
			{
				$aCustomerData['nickname']	= \Util::generateRandomString(16);
				$aCustomerData['email']		= \Util::generateRandomString(16);
			}
		}

		// New customer
		if(!isset($aCustomerData['id']) || (int)$aCustomerData['id'] <= 0)
		{
			$sSQL = "
				INSERT INTO
					`customer_db_".$this->_arrConfig['database']."`
				SET
					`email`		= :email,
					`nickname`	= :nickname,
					`access_code` = :access_code,
					`created`	= NOW(),
					`active`	= 1
			";
			$aSql = array('email'=>Util::generateRandomString(16), 'nickname'=>Util::generateRandomString(16), 'access_code'=>Util::generateRandomString(16));
			DB::executePreparedQuery($sSQL, $aSql);

			// Set the new customer ID
			$aCustomerData['id'] = (int)DB::fetchInsertID();
		}

		// Check if the customer exists in `office_customers`
		$sSQL = "
			SELECT
				*
			FROM
				`office_customers`
			WHERE
				`id` = :iCustomerID
			LIMIT
				1
		";
		$aSQL = array('iCustomerID' => $aCustomerData['id']);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		$aSQL = array(
			'iCustomerID'		=> $aCustomerData['id'],
			'iClientID'			=> (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id'),
			'iPaymentInvoice'	=> $aCustomerData['payment_invoice'],
			'iPaymentMisc'		=> $aCustomerData['payment_misc']
		);
		// Customer do not exists in the `office_customers`
		if(is_null($aResult) || empty($aResult))
		{
			$sSQL = "
				INSERT INTO
					`office_customers`
				SET
					`id`				= :iCustomerID,
					`client_id`			= :iClientID,
					`created`			= NOW(),
					`payment_invoice`	= :iPaymentInvoice,
					`payment_misc`		= :iPaymentMisc
			";
			DB::executePreparedQuery($sSQL, $aSQL);

			$sLogSubject = 'Angelegt';
		}
		// Customer exists in the `office_customers`
		else
		{
			$sSQL = "
				UPDATE
					`office_customers`
				SET
					`payment_invoice`	= :iPaymentInvoice,
					`payment_misc`		= :iPaymentMisc
				WHERE
					`id` = :iCustomerID
				LIMIT
					1
			";
			DB::executePreparedQuery($sSQL, $aSQL);

			$sLogSubject = 'Bearbeitet';
		}

		// Unset the payment conditions
		unset($aCustomerData['payment_invoice']);
		unset($aCustomerData['payment_misc']);

		// Save additional settings
		if(!empty($aAdditionals))
		{
			$sSQL = "
				UPDATE
					`office_customers`
				SET
					`cms_contact`	= :iCMS_Contact,
					`by_email`		= :iByEmail,
					`debitor_nr`	= :sDebitorNr,
					`creditor_nr`	= :sCreditorNr,
					`vat_id_nr`		= :vat_id_nr,
					`vat_id_valid`	= :vat_id_valid,
					`vat_id_check`	= :vat_id_check,
					`client_id`		= :iClientID,
					`language`		= :language,
					`redmine_project_id` = :redmine_project_id
				WHERE
					`id` = :iCustomerID
				LIMIT
					1
			";
			$aSQL = array(
				'iCustomerID'	=> (int)$aCustomerData['id'],
				'iCMS_Contact'	=> (int)$aAdditionals['cms_contact'],
				'iByEmail'		=> (int)$aAdditionals['by_email'],
				'sDebitorNr'	=> (string)$aAdditionals['debitor_nr'],
				'sCreditorNr'	=> (string)$aAdditionals['creditor_nr'],
				'vat_id_nr'		=> (string)$aAdditionals['vat_id_nr'],
				'vat_id_valid'	=> (string)$aAdditionals['vat_id_valid'],
				'vat_id_check'	=> (string)$aAdditionals['vat_id_check'],
				'iClientID'		=> (string)$aAdditionals['client_id'],
				'language'		=> (string)$aAdditionals['language'],
				'redmine_project_id' => (string)$aAdditionals['redmine_project_id'],
			);
			DB::executePreparedQuery($sSQL, $aSQL);
		}

		// Update a document
		$sSet = 'SET ';
		$iCount = count((array)$aCustomerData);
		$iFlag = 1;

		// Create the SET condition
		foreach((array)$aCustomerData as $sKey => $mValue)
		{
			if(array_key_exists($sKey, $aCustomerData))
			{
				$sSet .= '`'.$sKey.'` = :'.$sKey;
				if($iFlag < $iCount)
				{
					$sSet .= ', ';
				}
				$iFlag++;
			}
		}

		// Update the fields
		$sSQL = "
			UPDATE
				`customer_db_".$this->_arrConfig['database']."`
			{SET}
			WHERE
				`id` = :iCustomerID
			LIMIT
				1
		";

		$sSQL = str_replace('{SET}', $sSet, $sSQL);
		$aCustomerData['iCustomerID'] = $aCustomerData['id'];
		$aSQL = $aCustomerData;

		DB::executePreparedQuery($sSQL, $aSQL);

		// Save groups
		$aKeys = array('customer_id'=>(int)$aCustomerData['id']);
		DB::updateJoinData('office_customers_groups_join', $aKeys, $aAdditionals['group_id'], 'group_id');

		// Log
		global $user_data;
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> $aCustomerData['id'],
			'contact_id'	=> 0,
			'editor_id'		=> $user_data['id'],
			'document_id'	=> 0,
			'topic'			=> 'customer_data',
			'subject'		=> $sLogSubject,
			'state'			=> ''
		);
		$this->manageProtocols($aLog);

		return $aCustomerData['id'];
	}


	function saveFormItems($intFormId, $arrItems) {

		$strSql = "
				DELETE FROM office_forms_items WHERE form_id = '".\DB::escapeQueryString($intFormId)."'		
				";
		DB::executeQuery($strSql);
		foreach((array)$arrItems as $intKey=>$arrItem) {

			$strSql = "
				INSERT INTO 
					office_forms_items
				SET
					form_id 	= '".\DB::escapeQueryString($intFormId)."',
					position_x 	= '".\DB::escapeQueryString($arrItem['position_x'])."',
					position_y 	= '".\DB::escapeQueryString($arrItem['position_y'])."',
					font_color 	= '".\DB::escapeQueryString($arrItem['font_color'])."',
					width 		= '".\DB::escapeQueryString($arrItem['width'])."',
					font_size 	= '".\DB::escapeQueryString($arrItem['font_size'])."',
					font_id 	= '".\DB::escapeQueryString($arrItem['font_id'])."',
					alignment 	= '".\DB::escapeQueryString($arrItem['alignment'])."',
					display 	= '".\DB::escapeQueryString($arrItem['display'])."',
					content 	= '".\DB::escapeQueryString($arrItem['content'])."'
				";
			DB::executeQuery($strSql);

		}
	}

	function removeFormItem($intFormId, $intItemId) {

		$strSql = "
				DELETE
				FROM 
					office_forms_items 
				WHERE 
					id = '".\DB::escapeQueryString($intItemId)."' AND
					form_id = '".\DB::escapeQueryString($intFormId)."'
				LIMIT 1				
				";

		$resSql = DB::executeQuery($strSql);
		
		return $resSql;
	}	
	
	public function getDocumentItems($iDocumentID) {
		
		$sSQL = "
			SELECT
				*
			FROM
				`office_document_items`
			WHERE
				`document_id` = :iDocumentID
			ORDER BY
				`position`
		";
		$aSQL = array('iDocumentID' => $iDocumentID);

		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aResult;
	}

	function getDocumentPaymentReport($aTypes) {
		
		$strSql = "
					SELECT 
						`c`.`".$this->_arrConfig['field_matchcode']."` `name`,
						`c`.`".$this->_arrConfig['field_number']."` `number`,
						`d`.*,
						GROUP_CONCAT(`di`.`revenue_account`) `revenue_accounts`,
						UNIX_TIMESTAMP(`d`.`date`) `date`,
						(UNIX_TIMESTAMP(`d`.`date`) + (`p`.`days` * 60 * 60 * 24)) `due_date`,
						IF(`p`.`days_reminder` > 0, (UNIX_TIMESTAMP(`d`.`date`) + (`p`.`days_reminder` * 60 * 60 * 24)), NULL) `reminder_date`,
						IF(`p`.`days_reminder2` > 0, (UNIX_TIMESTAMP(`d`.`date`) + (`p`.`days_reminder2` * 60 * 60 * 24)), NULL) `reminder_date2`
					FROM	
						office_documents d LEFT OUTER JOIN 
						office_document_items di ON
							d.id = di.document_id LEFT OUTER JOIN
						office_payment_terms p ON
							`d`.`payment` = `p`.`id` LEFT OUTER JOIN
						customer_db_".$this->_arrConfig['database']." c ON
							`d`.`customer_id` = `c`.`id`
					WHERE
						`d`.`active` = 1 AND
						`d`.`type` IN (:types) AND
						(
							`d`.`state` = 'released' OR
							`d`.`state` = 'reminded'
						)
					GROUP BY
						`d`.`id`
					ORDER BY
						`d`.`number` ASC
					";
		$aSql = array(
			'types' => (array)$aTypes
		);
		
		$arrData = DB::getQueryRows($strSql, $aSql);

		foreach((array)$arrData as $intKey=>$arrItem) {
			
			$arrPayments = $this->getPayments($arrItem['id']);
			$arrData[$intKey]['receivable'] = ($arrData[$intKey]['price'] - $arrPayments['sum']);
			$arrData[$intKey]['cash_discount_receivable'] = ($arrData[$intKey]['price_cash_discount']  - $arrPayments['sum']);
			$arrData[$intKey]['payed'] = $arrPayments['sum'];

			if(
				$arrData[$intKey]['due_date'] < time() &&
				$arrData[$intKey]['payment'] > 0
			) {
				$arrData[$intKey]['due'] = 1;
			} else {
				$arrData[$intKey]['due'] = 0;
			}
		}

		return $arrData;	
	}
	
	public function getLiabilities() {
		
		$aTypes = array(
			'credit'
		);
		
		$aData = $this->getDocumentPaymentReport($aTypes);

		return $aData;
	}
	
	public function getReceivables() {
		
		$aTypes = array(
			'account',
			'cancellation_invoice'
		);
		
		$aData = $this->getDocumentPaymentReport($aTypes);

		return $aData;
	}

	public function getTemplateTexts($iTemplateID) {

		$sSQL = "
			SELECT
				*
			FROM
				office_templates
			WHERE
				id = :iTemplateID
			LIMIT
				1
		";
		$aSQL = array('iTemplateID' => $iTemplateID);

		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aResult[0];
	}

	public function manageProtocols($aLog)
	{
		// Save a new entry and get all entries for output
		if(isset($aLog['customer_id']) && $aLog['customer_id'] > 0)
		{
			// Save a new entry
			if(isset($aLog['id']) && $aLog['id'] <= 0)
			{
				$sSQL = "
					INSERT INTO
						`office_protocol`
					SET
						`customer_id`	= :iCustomerID,
						`contact_id`	= :iContactID,
						`editor_id`		= :iEditorID,
						`document_id`	= :iDocumentID,
						`topic`			= :sTopic,
						`subject`		= :sSubject,
						`date`			= NOW(),
						`state`			= :sState
				";
				$aSQL = array(
					'iCustomerID'	=> (int)$aLog['customer_id'],
					'iContactID'	=> (int)$aLog['contact_id'],
					'iEditorID'		=> (int)$aLog['editor_id'],
					'iDocumentID'	=> (int)$aLog['document_id'],
					'sTopic'		=> (string)$aLog['topic'],
					'sSubject'		=> (string)$aLog['subject'],
					'sState'		=> (string)$aLog['state']
				);
				DB::executePreparedQuery($sSQL, $aSQL);
			}
		}
	}

	public function getProtocolsList($iCustomerID)
	{
		// Get all entries for output
		$sSQL = "
			SELECT
				p.*,
				d.number,
				d.type,
				UNIX_TIMESTAMP(`p`.`date`) AS `date`,
				CONCAT(su.lastname, ', ', su.firstname) `editor`,
				CONCAT(oc.lastname, ', ', oc.firstname) `contact`
			FROM
				`office_protocol` p LEFT OUTER JOIN
				`office_documents` d ON p.document_id = d.id LEFT OUTER JOIN
				`system_user` su ON p.editor_id = su.id LEFT OUTER JOIN
				`office_contacts` oc ON p.contact_id = oc.id
				
			WHERE
				`p`.`customer_id` = :iCustomerID
			ORDER BY
				`p`.`date` DESC
		";
		$aSQL = array('iCustomerID' => $iCustomerID);

		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		// Prepare topics
		global $aActivities, $aTypeNames;

		// Get contact person, editor and activity as readable string
		foreach((array)$aResult as $iKey => $aValue) {

			$aResult[$iKey]['topic'] = $aActivities[$aResult[$iKey]['topic']];

			// if is document
			if($aValue['type']) {
				$aResult[$iKey]['subject'] = $aTypeNames[$aValue['type']]." ".$aValue['number'];
				$aResult[$iKey]['topic'] = $aValue['subject'];
			}

			$aResult[$iKey]['editor_id'] = $aValue['editor'];

			$aResult[$iKey]['date'] = strftime('%x %X', $aResult[$iKey]['date']);

			if($aValue['contact']) {
				$aResult[$iKey]['contact_id'] = $aValue['contact'];
			} else {
				$aResult[$iKey]['contact_id'] = '-';
			}
		}

		return $aResult;
	}

	public function getAcceptedTimestamp($iDocumentID)
	{
		$sSQL = "
			SELECT
				UNIX_TIMESTAMP(`date`) AS `date`
			FROM
				`office_protocol`
			WHERE
				`topic` = 'offer'
					AND
				`state` = 'accepted'
					AND
				`document_id` = :iDocumentID
			LIMIT
				1
		";
		$aSQL = array('iDocumentID' => $iDocumentID);

		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aResult[0];
	}

	function savePayment($iDocumentId, $fAmount, $iUserId, $bolSendMail = false, $sText = '', $bGrantCashDiscount = false) {

		$sSql = "
			INSERT INTO
				`office_payments` 
			SET
				`created` = NOW(),
				`document_id` = :intDocumentId,
				`user_id` = :intUserId,
				`amount` = :floAmount,
				`text` = :text
		";

		$aSql = array();
		$aSql['intDocumentId'] 	= $iDocumentId;
		$aSql['floAmount'] 		= $fAmount;
		$aSql['intUserId'] 		= $iUserId;
		$aSql['text'] 			= $sText;

		DB::executePreparedQuery($sSql, $aSql);

		$aPayments = $this->getPayments($iDocumentId);
		$fPaidAmount = $aPayments['sum'];

		$aDocument = $this->getDocumentData($iDocumentId);

		$fFinalPrice = $aDocument['price'];
		if($bGrantCashDiscount) {
			$fFinalPrice = $aDocument['price_cash_discount'];
		}

		if(bccomp($fPaidAmount, $fFinalPrice, 2) == 0) {
			$oAPI = new Ext_Office_Document($iDocumentId);
			$oAPI->state = 'paid';
			if($bGrantCashDiscount) {
				$oAPI->cash_discount_granted = 1;
			}
			$oAPI->save();
		}

		// optional: send info email
		if($bolSendMail) {
			$this->_sendEmail($iDocumentId, $fAmount, $iUserId);
		}

		// Log
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> (int)$aDocument['customer_id'],
			'contact_id'	=> (int)$aDocument['contact_person_id'],
			'editor_id'		=> $iUserId,
			'document_id'	=> $aDocument['id'],
			'topic'			=> $aDocument['type'],
			'state'			=> $aDocument['state']
		);
		$aLog['subject'] = 'Zahlung eingegangen';

		$this->manageProtocols($aLog);
	}

	function getPayments($intDocumentId) {

		$strSql = "
					SELECT 
						*,
						DATE(`created`) `date`
					FROM
						`office_payments` 
					WHERE
						`document_id` = :intDocumentId
					ORDER BY 
						`created` ASC
					";

		$arrSql = array();
		$arrSql['intDocumentId'] 	= $intDocumentId;
	
		$aPayments = (array)DB::getPreparedQueryData($strSql, $arrSql);
		
		$intSum = 0;
		foreach($aPayments as &$aPayment) {
			
			$oUser = User::getInstance($aPayment['user_id']);
			
			if($oUser instanceof User) {
				$aPayment['user'] = $oUser->firstname.' '.$oUser->lastname;
			}
			
			$intSum += $aPayment['amount'];
		}
		
		$arrReturn = array();
		$arrReturn['payments'] = (array)$aPayments;
		$arrReturn['sum'] = $intSum;
		
		return $arrReturn;

	}

	function getDocumentData($intDocumentId) {
		$strSql = "
					SELECT
						*,
						UNIX_TIMESTAMP(date) as date,
						UNIX_TIMESTAMP(booking_date) as booking_date 
					FROM
						`office_documents` 
					WHERE
						`id` = :intDocumentId
					LIMIT 1
					";

		$arrSql = array();
		$arrSql['intDocumentId'] 	= $intDocumentId;
	
		$arrDocument = DB::getPreparedQueryData($strSql, $arrSql);
		
		$arrDocument = $arrDocument[0];
		
		return $arrDocument;
		
	}

	function updateDocumentState($intDocumentId, $strState) {

		$sAddSet = '';
		if($strState == 'released')
		{
			$sAddSet = " , date = NOW() ";
		}

		$strSql = "
					UPDATE
						`office_documents` 
					SET
						`state` = :strState ".$sAddSet."
					WHERE
						`id` = :intDocumentId
					LIMIT 1
					";

		$arrSql = array();
		$arrSql['intDocumentId'] 	= $intDocumentId;
		$arrSql['strState'] 		= $strState;
	
		DB::executePreparedQuery($strSql, $arrSql);
		
	}	

	/**
	 * getArticleGroups
	 * @return array
	 */
	public function getArticleGroups($bAssociative=false) {
	
		$sSql = "SELECT `id`, `label` FROM `office_articlegroups` ORDER BY `label`";
		
		if($bAssociative) {
			$aArticlegroups = (array)DB::getQueryPairs($sSql);
		} else {
			$aArticlegroups = (array)DB::getQueryRows($sSql);

			foreach($aArticlegroups as &$aArticlegroup) {
				$aArticlegroup = array_values($aArticlegroup);
			}
		}
		
		return $aArticlegroups;
		
	}
	
	public function getArticles()
	{
		$sSQL = "
			SELECT 
				oa.*,
			/*	IF(`oa`.`currency` = '', 'keine', `oa`.`currency`) AS `currency`, */
				COALESCE(SUM(oc.amount), 0) `amount`,
				COALESCE(SUM(`oa`.`price` * ((`oc`.`interval` / `oa`.`month`) * (12 / `oc`.`interval`)) * `oc`.`amount` * (1 - `oc`.`discount` / 100)), 0) `total`,
				COALESCE(SUM(`oa`.`price` * ((`oc`.`interval` / `oa`.`month`) * (12 / `oc`.`interval`)) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) - SUM(((`oc`.`interval` / `oa`.`month`) * (12 / `oc`.`interval`)) * `oc`.`amount` * oa.cost), 0) `return`,
				oag.label `productgroup`
			FROM 
				`office_articles` oa LEFT OUTER JOIN
				`office_contracts` oc ON
					oa.id = oc.product_id AND oc.active = 1 LEFT OUTER JOIN
				`data_currencies` AS `dc` ON
					`oa`.`currency` = `dc`.`iso4217` LEFT JOIN
				`office_articlegroups` `oag` ON
					`oa`.`productgroup` = `oag`.`id`
			WHERE 
				oa.`active` = 1
			GROUP BY
				oa.id
			ORDER BY 
				`oa`.`number`,
				`oa`.`product`
		";
		$aResult = DB::getQueryData($sSQL);

		return $aResult;
	}

	public function getArticle($iID)
	{
		$sSQL = "
			SELECT
				*
			FROM
				`office_articles`
			WHERE
				`id` = :iID
			LIMIT
				1
		";
		$aSQL = array('iID' => (int)$iID);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aResult[0];
	}

	function getFonts() {

		$aFonts = array();

		$sSQL = "
			SELECT 
				*
			FROM
				office_fonts
			WHERE
				`active` = 1
			
		";
		$aResult = DB::getQueryData($sSQL);
		foreach((array)$aResult as $aItem) {
			$aFonts[$aItem['id']] = $aItem;
		}
		return $aFonts;
	}
	
	public function getDocuments($strType, $strState, $strSearch, $strFrom, $strTo, $iProductAreaId, $sOrder) {
		global $aTypeNames;
	
		$aCountries = Data_Countries::getList('de');
		$aCurrencies = Data::getCurrencys('sign');
		
		$oProductArea = new \Office\Entity\ProductArea;
		$aProductAreas = $oProductArea->getArrayList(true);
		
		$strWhereAddon = "";
	
		$_SESSION['type']	= $strType;
		$_SESSION['state']	= $strState;
		$_SESSION['search']	= $strSearch;
	
		if(!empty($strFrom)) {
			$_SESSION['from'] 	= $strFrom;
			$strFrom	= strtotimestamp($strFrom);
		}
		if(!empty($strTo)) {
			$_SESSION['until'] 	= $strTo;
			$strTo		= strtotimestamp($strTo)+(60*60*24)-1;
		}

		// get documents by client
		$iClientID = (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id');
		$strWhereAddon .= " AND d.client_id = ".(int)$iClientID." ";

		if(
			!empty($strFrom) && 
			!empty($strTo)
		) {
			$strWhereAddon .= " AND d.date BETWEEN '".date("Y-m-d", $strFrom)."' AND '".date("Y-m-d", $strTo)."'";
		} elseif(
			!empty($strFrom)
		) {
			$strWhereAddon .= " AND d.date > '".date("Y-m-d", $strFrom)."'";
		} elseif(
			!empty($strTo)
		) {
			$strWhereAddon .= " AND d.date < '".date("Y-m-d", $strTo)."'";
		}

		if(!empty($strType)) {
			$strWhereAddon .= " AND d.type = '".$strType."'";
		}
		if(!empty($strState)) {
			$strWhereAddon .= " AND d.state = '".$strState."'";
		}
		if(!empty($iProductAreaId)) {
			$strWhereAddon .= " AND `d`.`product_area_id` = ".(int)$iProductAreaId."";
		}
		if(!empty($strSearch)) {
			$strWhereAddon .= " AND (k.".$this->_arrConfig['field_matchcode']." LIKE '%".$strSearch."%' OR k.".$this->_arrConfig['field_company']." LIKE '%".$strSearch."%' OR d.number LIKE '%".$strSearch."%' OR d.subject LIKE '%".$strSearch."%')";
		}
	
		$sSql = "SELECT
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
						`oc`.`vat_id_nr`,
						`opa`.`name` `product_area`
					FROM 
						office_documents d LEFT OUTER JOIN
						system_user u ON d.editor_id = u.id LEFT OUTER JOIN
						office_contacts c ON d.contact_person_id = c.id  LEFT OUTER JOIN
						customer_db_".$this->_arrConfig['database']." k ON d.customer_id = k.id LEFT OUTER JOIN
						`office_customers` AS `oc` ON
							`k`.`id` = `oc`.`id` LEFT OUTER JOIN
						office_protocol op ON d.id = `op`.`document_id` AND `op`.`state` = 'send' LEFT JOIN
						`office_product_areas` `opa` ON
							`d`.`product_area_id` = `opa`.`id`
					WHERE 
						d.active = 1
						 ".$strWhereAddon."
					GROUP BY
						d.id
						";

		$sDirection = 'ASC';
		if(isset($_SESSION['office']['documents']['orderby_direction'])) {
			$sDirection = $_SESSION['office']['documents']['orderby_direction'];
			if(!empty($sOrder)) {
				if($_SESSION['office']['documents']['orderby_direction'] == 'ASC') {
					$sDirection = 'DESC';
				} else {
					$sDirection = 'ASC';
				}
			}
		}

		if(!empty($sOrder)) {
			$_SESSION['office']['documents']['orderby'] = $sOrder;
		}

		$_SESSION['office']['documents']['orderby_direction'] = $sDirection;
		switch($_SESSION['office']['documents']['orderby'] ?? null) {
			case 'id':
				$sSql .= "ORDER BY `d`.`id` ".$sDirection;
				break;
			case 'number':
				$sSql .= "ORDER BY `d`.`number` ".$sDirection;
				break;
			case 'customer':
				$sSql .= "ORDER BY `k_matchcode` ".$sDirection;
				break;
			case 'amount_net':
				$sSql .= "ORDER BY `d`.`price_net` ".$sDirection;
				break;
			case 'product_area':
				$sSql .= "ORDER BY `product_area` ".$sDirection;
				break;
			default:
				$sSql .= "ORDER BY `d`.`date` DESC";
				break;
		}

		$aItems = DB::getQueryData($sSql);

		$arrDocuments = array();
		foreach((array)$aItems as $arrResult) {

			$sCurrency = '€';
			if(!empty($arrResult['currency'])) {
				$sCurrency = $aCurrencies[$arrResult['currency']];
			}

			$aDocument = array(
				"id"			=>	$arrResult['id'],
				"number"		=>	$arrResult['number'],
				"currency"		=>	$sCurrency,
				"type"			=>	$aTypeNames[$arrResult['type']],
				"subject"		=>	$arrResult['subject'],
				"typeKey"		=>	$arrResult['type'],
				"state"			=>	$arrResult['state'],
				"price"			=>	$arrResult['price'],
				"price_net"		=>	$arrResult['price_net'],
				"f_price"		=>	number_format($arrResult['price'],2,",","."),
				"f_price_net"	=>	number_format($arrResult['price_net'],2,",","."),
				"k_matchcode"	=>	(string)$arrResult['k_matchcode'],
				"k_company"		=>	(string)$arrResult['k_company'],
				"k_country"		=>	(string)$aCountries[$arrResult['k_country']],
				"vat_id_nr"		=>	(string)$arrResult['vat_id_nr'],
				"u_firstname"	=>	(string)$arrResult['u_firstname'],
				"u_lastname"	=>	(string)$arrResult['u_lastname'],
				"c_firstname"	=>	(string)$arrResult['c_firstname'],
				"c_lastname"	=>	(string)$arrResult['c_lastname'],
				"date"			=>	(string)strftime("%x",$arrResult['date_ts']),
				"booking_date"			=>	(string)strftime("%x",$arrResult['booking_date_ts']),
				"send"			=>	(int)$arrResult['send'],
				'product_area' => (string)($aProductAreas[$arrResult['product_area_id']] ?? '')
			);	

			$_SESSION['office']['cache']['documents'][$arrResult['id']] = $aDocument;

			$arrDocuments[] = $aDocument;

		}

		return $arrDocuments;

	}
	
	function deleteDocument($idCar) {
		$strQuery = "DELETE FROM office_documents WHERE id = ".(int)$idCar." LIMIT 1";
		DB::executeQuery($strQuery);
		return $this->getDocuments();
	}
	
	public function checkAccountActivity($iDate, $fAmount, $sText) {
		
		$oDate = new WDDate($iDate);
		
		$aSql = array(
			'date' => $oDate->get(WDDate::DB_DATE),
			'amount' => (float)$fAmount,
			'text' => $sText
		);
		
		$sSql = "
			SELECT
				`account_id`
			FROM
				`office_accounts_activities`
			WHERE
				`date` = :date AND
				`amount` = :amount AND
				(
					`checksum` = CRC32(:text) OR
					`text` LIKE :text
				)
		";
		$iCheck = DB::getQueryOne($sSql, $aSql);
		
		if(!empty($iCheck)) {
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 * @param int $iDate
	 * @param float $fAmount
	 * @param text $sText
	 * @param text $sSender
	 * @param text $sReference
	 * @param text $sData
	 */
	public function addAccountActivity($iDate, $fAmount, $sText, $sSender=null, $sReference=null, $sData=null) {
		
		$oDate = new WDDate($iDate);
		
		$aSql = array(
			'date' => $oDate->get(WDDate::DB_DATE),
			'amount' => (float)$fAmount,
			'text' => $sText,
			'sender' => (string)$sSender,
			'reference' => (string)$sReference,
			'text' => (string)$sText,
			'data' => (string)$sData
		);

		$sSql = "
			INSERT INTO
				`office_accounts_activities`
			SET
				`date` = :date,
				`amount` = :amount,
				`checksum` = CRC32(:text),
				`sender` = :sender,
				`reference` = :reference,
				`text` = :text,
				`data` = :data
		";
		DB::executePreparedQuery($sSql, $aSql);
		
	}

	private function _sendEmail($iDocumentId, $fAmount, $iUserId){

		$oDocument = new Ext_Office_Document($iDocumentId);
		
		$aDocument	= $this->getDocumentData($iDocumentId);
		$aContact	= $this->getContact($aDocument['contact_person_id']);
		$aCustomer	= $this->getCustomer($aDocument['customer_id']);
		$oCustomer = new Ext_Office_Customer(null, $aDocument['customer_id']);

		$aAdditionalValues = array(
			'PaymentAmount' => number_format($fAmount, 2, ',', '.').' €',
			'PaymentDate' => strftime('%x')
		);
		
		$oOffice = new classExtension_Office();
		$aEmailTemplate = $oOffice->getEmailTemplate('paymentcomplete', $oCustomer->language);

		$aSubject = $oDocument->replacePlaceholders($aEmailTemplate['subject'], $aAdditionalValues);
		$aBody = $oDocument->replacePlaceholders($aEmailTemplate['body'], $aAdditionalValues);

		$sEmailTo = '';
		if(!empty($aContact['email'])) {
			$sEmailTo = $aContact['email'];
		} else {
			$sEmailTo = $aCustomer['email'];
		}

		$oMail = new \Office\Service\Email;
		$oMail->setSubject($aSubject);
		$oMail->setText($aBody);
		$oMail->send([$sEmailTo]);

		// Log
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> (int)$aDocument['customer_id'],
			'contact_id'	=> (int)$aDocument['contact_person_id'],
			'editor_id'		=> $iUserId,
			'document_id'	=> $aDocument['id'],
			'topic'			=> $aDocument['type'],
			'state'			=> $aDocument['state']
		);

		$aLog['subject'] = 'Buchungsbestätigung versendet';
		$this->manageProtocols($aLog);

	}

}
