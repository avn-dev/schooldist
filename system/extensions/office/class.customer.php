<?php

/**
 * v1
 */
class Ext_Office_Customer extends Ext_Office_Office implements \Office\Interfaces\LogoInterface
{
	/**
	 * The element data
	 */
	protected $_aData = array(
		'id' => 0
	);

	/**
	 * The constructor
	 * 
	 * @param string : The name of table
	 * @param int : The element ID
	 */
	public function __construct($sTable = null, $iElementID = null)
	{
		parent::__construct('office_contacts', $iElementID);

		if($this->_aData['id'] == 0)
		{
			$this->_preloadFields();
		}
	}

	public function __get($sName) {

		$mValue = parent::__get($sName);
		
		if(
			$sName == 'language' &&
			empty($mValue)
		) {
			$mValue = 'de';
		}
		
		return $mValue;
		
	}
	
	/**
	 * Wenn der Kunde Deutschland als Land hat, oder kein Land zugewiesen ist
	 * @return boolean
	 */
	public function isGermany() {
		$sCountry = $this->country;
		if(
			empty($sCountry) ||
			$sCountry === 'DE'
		) {
			return true;
		}
		return false;
	}
	
	/**
	 * Wenn kein Land angegeben ist, oder das angegebene Land in der EU ist.
	 * @return boolean
	 */
	public function isEU() {
		
		$sCountry = $this->country;

		if(strlen($sCountry) == 2) {
			
			$oCountry = new Data_Countries($sCountry);
			
			if($oCountry->cn_eu_member == 1) {
				return true;
			} else {
				return false;
			}

		}

		return true;
		
	}

	public function checkCalculateVat() {

		$oVatCalculator = new \Office\Service\VatCalculator();

		if($this->isGermany()) {
			return true;
		}

		if(
			!$this->isEU() ||
			$this->vat_id_valid ||
			!$oVatCalculator->check($this->country, $this->zip)
		) {
			return false;
		}
		
//		if(
//			(
//				!$this->isGermany() &&
//				$this->isEU() &&
//				$this->vat_id_valid
//			) ||
//			(
//				!$this->isEU()
//			)
//		) {
//			return false;
//		}

		return true;
	}
	
	public function getAddress() {

		$sAddress = '';

		$sAddition = $this->addition;
		$sCountry = $this->country;

		if(!empty($sAddition)) {
			$sAddress .= $sAddition."\n";
		}

		$sAddress .= $this->address;
		$sAddress .= "\n";
		
		$sZip = trim($this->zip);
		
		if(!empty($sZip)) {
			$sAddress .= $sZip.' ';
		}

		$sAddress .= trim($this->city);

		if(!empty($sCountry)) {
			
			if(strlen($sCountry) == 2) {
				$aCountries = Data_Countries::getList($this->language);
				
				$sCountry = $aCountries[$sCountry];
			}
			
			$sAddress .= "\n" . $sCountry;
		}

		return $sAddress;

	}

	public function generateBillingsAccount($iCustomerID, $aPositions) {
		global $user_data;

		DB::begin('Office::generateBillingsAccount');
		
		$oCustomer = new Ext_Office_Customer(0, $iCustomerID);

		$bCalculateVat = $oCustomer->checkCalculateVat();
		
		$sAddress = $oCustomer->getAddress();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create the document

		$oDocument = new Ext_Office_Document();

		$oDocument->customer_id			= (int)$iCustomerID;
		$oDocument->contact_person_id	= 0;
		$oDocument->editor_id			= $user_data['id'];
		$oDocument->address				= $sAddress;
		$oDocument->telefon				= $oCustomer->phone;
		$oDocument->telefax				= $oCustomer->fax;
		$oDocument->type				= 'account';
		$oDocument->payment				= $this->_aConfig['tickets_payment'];
		$oDocument->subject				= '';
		$oDocument->text				= nl2br($this->_aConfig['tickets_starttext']);
		$oDocument->endtext				= nl2br($this->_aConfig['tickets_endtext']);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create the document positions

		$iPosition = 1;

		foreach((array)$aPositions as $sKey => $aPosition) {

			if(empty($aPosition['active'])) {
				continue;
			}

			$iTicketId = (int)$aPosition['ticket_id'];
			
			$aItem = array();
			$aItem['id'] = 0;
			$aItem['position'] = $iPosition;
			$aItem['number'] = '';
			$aItem['discount_item'] = 0;
			if($bCalculateVat) {
				$aItem['vat'] = 0.19;
			} else {
				$aItem['vat'] = 0;
			}
			$aItem['only_text'] = 0;
			$aItem['groupsum'] = 0;
			$aItem['revenue_account'] = 0;
			$aItem['group_display'] = '';

			if($aPosition['type'] == 'office') {
				
				$oTicket = new WDBasic($iTicketId, 'office_tickets');

				$oProject = new WDBasic($oTicket->project_id, 'office_projects');

				$oCategory = new WDBasic($aPosition['category_id'], 'office_project_categories');

				$oDate = new WDDate($oTicket->created, WDDate::DB_TIMESTAMP);

				$aItem['product']			= $oProject->title . ' - ' . $oTicket->title . ' - ' . $oCategory->title;
				$aItem['description']		= L10N::t('Ticket-ID: ') . $iTicketId . "\n" . L10N::t('Datum: ') . $oDate->get(WDDate::DATES);

				// Stundenschätzung oder nach Aufwand
				if(
					$aPosition['billing_flag'] == 'h' ||
					$aPosition['billing_flag'] == 'b'
				) {

					$iAmount = str_replace(',', '.', str_replace('.', '', $aPosition['hours']));

					$aItem['amount']			= $iAmount;
					$aItem['unit']				= L10N::t('PS');
					$aItem['price']				= $aPosition['price'];

				} elseif($aPosition['billing_flag'] == 'm') {

					$aItem['amount']			= 1;
					$aItem['unit']				= L10N::t('pausch.');
					$aItem['price']				= str_replace(',', '.', str_replace('.', '', $aPosition['price']));

				} else {
					
					
					
				}

				$oTicket->cleared = 1;
				$oTicket->save();

				DB::updateData('office_project_positions', array('active' => 0), "`ticket_id` = ".(int)$oTicket->id);

			} elseif($aPosition['type'] == 'redmine') {

				$dCreated = new DateTime($aPosition['created']);
				$dClosed = new DateTime($aPosition['closed']);
				
				$aItem['product']			= $aPosition['title'];
				$aItem['description']		= L10N::t('Ticket: ') .'#'.$iTicketId . "\n" . L10N::t('Erstellt: ') . $dCreated->format('d.m.Y') . "\n" . L10N::t('Geschlossen: ') . $dClosed->format('d.m.Y');

				$fAmount = str_replace(',', '.', str_replace('.', '', $aPosition['hours']));

				$aItem['amount']			= $fAmount;
				$aItem['unit']				= L10N::t('PS');
				$aItem['price']				= $aPosition['price'];

				$this->setRedmineIssueBilled($iTicketId);

			}
			
			if((float)$aItem['price'] > 0) {
				$iPosition++;
				$oDocument->addItems($aItem);
			}
			
		}

		try {

			$oDocument->save();

			$oDocument->state	= 'draft';
			$oDocument->date	= time();

			$oDocument->save();
			
			DB::commit('Office::generateBillingsAccount');
			
			return true;
			
		} catch (Exception $e) {
			
			DB::rollback('Office::generateBillingsAccount');
			
			return false;
			
		}

	}

	private function setRedmineIssueBilled($iRedmineIssueId) {
		
		$sApiUrl = Ext_Office_Config::get('redmine_api_url');
		$sApiKey = Ext_Office_Config::get('redmine_api_key');
		$iCustomFieldBilling = Ext_Office_Config::get('redmine_api_customfield_billing');
		$iCustomFieldBilled = Ext_Office_Config::get('redmine_api_customfield_billed');

		if(
			!empty($sApiUrl) &&
			!empty($sApiKey) &&
			!empty($iCustomFieldBilling) &&
			!empty($iCustomFieldBilled)
		) {

			$oProjectCategory = Office\Entity\Project\Category::getInstance(1);
			$fHourlyRate = $oProjectCategory->price;

			$oRedmineClient = new Redmine\Client($sApiUrl, $sApiKey);

			$aParams = array(
				'custom_fields' => array(
					array(
						'id' => $iCustomFieldBilled,
						'value' => 1
					)
				),
				'notes' => "Automatic note from office invoice generator"
			);

			$oRedmineClient->api('issue')->update($iRedmineIssueId, $aParams);

		}

	}

	public function getBillings($iCustomerID) {

		$sSQL = "
			SELECT
				`ot`.`id` AS `timeclock_id`,
				`oti`.`title` AS `ticket_title`,
				`op`.`title` AS `project_title`,
				`oti`.`id` AS `ticket_id`,
				`ot`.`p2p_id`,
				`oti`.`project_id`,
				`oti`.`hours`,
				`oti`.`money`,
				`oti`.`billing`,
				`opc`.`price` AS `price`,
				`opc`.`id` AS `category_id`,
				`opc`.`title` AS `category_title`,
				COALESCE(SUM(UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`)), 0) AS `time`,
				COALESCE(SUM((UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`)) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100)), 0) AS `factor_time`,
				COALESCE(SUM((UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`)) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100) * (`opc`.`price` / 3600)), 0) AS `total`
			FROM
				`office_tickets` AS `oti`				INNER JOIN
				`office_ticket_notices` AS `otn`			ON
					`oti`.`id` = `otn`.`ticket_id`		AND
					`otn`.`state` = 7					INNER JOIN
				`office_projects` AS `op`					ON
					`op`.`customer_id` = :iCustomerID	AND
					`oti`.`project_id` = `op`.`id`		AND
					`op`.`active` = 1					INNER JOIN
				`office_project_positions` AS `opp`			ON
					`opp`.`ticket_id` = `oti`.`id`		AND
					`opp`.`project_id` = `op`.`id`		INNER JOIN
				`office_project_categories` AS `opc`		ON
					`opp`.`category_id` = `opc`.`id`	LEFT OUTER JOIN
				`office_timeclock` AS `ot`					ON
					`opp`.`id` = `ot`.`p2p_id`			AND
					`ot`.`active` = 1					AND
					`ot`.`end` > 0						AND
					`ot`.`action` != 'new'				AND
					`ot`.`action` != 'declined'			LEFT OUTER JOIN
				`office_project_employees` AS `ope`			ON
					`ot`.`p2e_id` = `ope`.`id`			LEFT OUTER JOIN
				`office_employee_contract_data` AS `oecd` ON
					`ope`.`employee_id` = `oecd`.`employee_id` AND
					`oecd`.`active` = 1 AND
					(
						UNIX_TIMESTAMP(`oecd`.`from`) <= UNIX_TIMESTAMP(`ot`.`start`) AND
						(
							UNIX_TIMESTAMP(`oecd`.`until`) = 0 OR
							UNIX_TIMESTAMP(`oecd`.`until`) > UNIX_TIMESTAMP(`ot`.`start`)
						)
					)									LEFT OUTER JOIN
				`office_employee_factors` AS `oef`			ON
					`oef`.`employee_id` = `ope`.`employee_id`	AND
					`oef`.`contract_id` = `oecd`.`id`			AND
					`oef`.`category_id` = `opp`.`category_id`
			WHERE
				`oti`.`cleared` = 0 AND
				`oti`.`active` = 1 AND
				`oti`.`type` = 'ext'				
			GROUP BY 
				`oti`.`id`, 
				`opc`.`id`
		";
		$aBillings = DB::getPreparedQueryData($sSQL, array('iCustomerID' => $iCustomerID));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Format output

		$aReturn = array();

		foreach((array)$aBillings as $aBilling) {

			if(!isset($aReturn[$aBilling['ticket_id'] . '_' . $aBilling['category_id']])) {

				$iOriginalTicketID = $aBilling['ticket_id'];

				$aReturn[$aBilling['ticket_id']] = array(
					'type' => 'office',
					'ticket_id'		=> $aBilling['ticket_id'],
					'category_id'	=> $aBilling['category_id'],
					'title'			=> $aBilling['project_title'] . ' - ' . $aBilling['ticket_title']  . ' (' . $aBilling['category_title'] . ')',
					'price'			=> $aBilling['price'],
					'time'			=> 0,
					'factor_time'	=> 0,
					'total'			=> 0,
					'created' => '',
					'closed' => ''
				);

				if(
					$aBilling['billing'] == 0 && 
					$aBilling['category_id'] == 1
				) {

					$sMoneyKey = $aBilling['ticket_id'];
					$sHoursKey = $aBilling['ticket_id'];
					
					if(
						$aBilling['hours'] > 0 && 
						$aBilling['money'] > 0
					) {
						$sMoneyKey = $aBilling['ticket_id'] . '.5';
						$aReturn[$sMoneyKey] = $aReturn[$aBilling['ticket_id']];
						$aReturn[$sMoneyKey]['ticket_id'] = $aBilling['ticket_id'] . '.5';
					}

					if($aBilling['hours'] > 0) {

						$aReturn[$sHoursKey]['billing'] = $aBilling['hours'] . ' h';
						$aReturn[$sHoursKey]['billing_flag'] = 'h';		
						$aReturn[$sHoursKey]['time'] += $aBilling['time'];
						$aReturn[$sHoursKey]['factor_time']	+= ($aBilling['hours']*3600);
						$aReturn[$sHoursKey]['total'] += bcmul($aBilling['hours'] * $aBilling['price'], 5);

					}
					
					if($aBilling['money'] > 0) {

						$aReturn[$sMoneyKey]['billing'] = $aBilling['money'] . ' €';
						$aReturn[$sMoneyKey]['billing_flag'] = 'm';
						$aReturn[$sMoneyKey]['time'] += $aBilling['time'];
						$aReturn[$sMoneyKey]['factor_time']	+= $aBilling['money'];
						$aReturn[$sMoneyKey]['total'] += $aBilling['money'];

					}

				} else {

					$aReturn[$aBilling['ticket_id']]['billing'] = '&nbsp;';
					$aReturn[$aBilling['ticket_id']]['billing_flag'] = 'b';					
					$aReturn[$aBilling['ticket_id']]['time'] += $aBilling['time'];
					$aReturn[$aBilling['ticket_id']]['factor_time']	+= $aBilling['factor_time'];
					$aReturn[$aBilling['ticket_id']]['total'] += $aBilling['total'];
					
				}

			}

		}

		$aReturn = array_values($aReturn);

		$this->getRedmineBillings($iCustomerID, $aReturn);

		return $aReturn;
	}

	public function getRedmineBillings($iCustomerId, &$aReturn) {

		$oCustomer = new Ext_Office_Customer(null, $iCustomerId);

		$sRedmineProjectId = $oCustomer->redmine_project_id;

		$sApiUrl = Ext_Office_Config::get('redmine_api_url');
		$sApiKey = Ext_Office_Config::get('redmine_api_key');
		$iCustomFieldBilling = Ext_Office_Config::get('redmine_api_customfield_billing');
		$iCustomFieldBilled = Ext_Office_Config::get('redmine_api_customfield_billed');

		if(
			!empty($sApiUrl) &&
			!empty($sApiKey) &&
			!empty($iCustomFieldBilling) &&
			!empty($iCustomFieldBilled)
		) {

			$oProjectCategory = Office\Entity\Project\Category::getInstance(1);
			$fHourlyRate = $oProjectCategory->price;

			$oRedmineClient = new Redmine\Client($sApiUrl, $sApiKey);

			$aParams = array(
				'limit' => 9999,	
				'status_id' => 5,
				'cf_'.$iCustomFieldBilling => '1',
				'cf_'.$iCustomFieldBilled => '!1'
			);

			if(!empty($sRedmineProjectId)) {
				$aParams['project_id'] = $sRedmineProjectId;
			}
			
			$aResult = $oRedmineClient->api('issue')->all($aParams);

			if(!empty($aResult['issues'])) {
				foreach($aResult['issues'] as $aIssue) {

					$aTimes = $oRedmineClient->api('time_entry')->all(array('limit' => 9999, 'issue_id' => $aIssue['id']));

					$fTotal = 0;
					$fEstimatedHours = $aIssue['estimated_hours'];

					if(!empty($aTimes['time_entries'])) {

						foreach($aTimes['time_entries'] as $aTimeEntry) {
							$fTotal += $aTimeEntry['hours'];
						}

					}

					if(
						$fTotal > 0 ||
						$fEstimatedHours > 0
					) {

							$dCreated = new DateTime($aIssue['created_on']);
							$dClosed = new DateTime($aIssue['closed_on']);

							$aItem = array(
								'type' => 'redmine',
								'ticket_id'		=> (int)$aIssue['id'],
								'title'			=> $aIssue['subject'],
								'price'			=> (float)$fHourlyRate,
								'time'			=> (float)$fTotal*60*60,
								'total'			=> (float)bcmul($fTotal, $fHourlyRate, 5),
								'billing' => (float)$fEstimatedHours*60*60,
								'billing_flag' => 'b',
								'created' => $dCreated->format('Y-m-d'),
								'closed' => $dClosed->format('Y-m-d')
							);

							if($fEstimatedHours > 0) {
								$aItem['factor_time'] = (float)$fEstimatedHours*60*60;
							} else {
								$aItem['factor_time'] = (float)$fTotal*60*60;
							}

							$aReturn[] = $aItem;

						}


				}

			}

		}
		
		return $aReturn;		
	}

	/**
	 * Returns the list of contact persons by customer ID
	 * 
	 * @return array : The list of contact persons
	 */
	public function getContactsList()
	{
		$sSQL = "
			SELECT
				*
			FROM
				`office_contacts`
			WHERE
				`customer_id` = :iID AND
				`active` = 1
		";
		$aResult = DB::getPreparedQueryData($sSQL, array('iID' => (int)$this->_aData['id']));

		return $aResult;
	}


	/**
	 * Handles the activation for a customer
	 * 
	 * @param int : The activation number (1 || 0)
	 */
	public function handleActivation($iActivation)
	{
		if((int)$iActivation != 1 && (int)$iActivation != 0)
		{
			throw new Exception('"'.$iActivation.'" is a bad input! Please enter "1" or "0".');
		}

		$aUpdate = array('active' => (int)$iActivation);
		DB::updateData('customer_db_'.$this->_aConfig["database"], $aUpdate, '`id` = '.$this->_aData['id']);
		DB::updateData('office_customers', $aUpdate, '`id` = '.$this->_aData['id']);
	}


	/**
	 * Deletes entries from DB by ID
	 */
	public function remove($bDelete = false) {
		return null;
	}


	/**
	 * Saves the element data into the DB
	 */
	public function save()
	{
		// Prepare insert arrays
		$aTemp = array();
		foreach((array)$this->_aConfig as $sCKey => $mCValue)
		{

			$sChangedCKey = str_replace('field_', '', $sCKey);

			// Protect the customer number if the number is the ID
			if($mCValue == 'id' && $sChangedCKey == 'number')
			{
				unset($this->_aData[$sChangedCKey]);
				continue;
			}

			if(array_key_exists($sChangedCKey, $this->_aData))
			{
				$aTemp[$mCValue] = $this->_aData[$sChangedCKey];
				unset($this->_aData[$sChangedCKey]);
			}
		}
		$aTemp['email'] = $this->_aData['email'];
		unset($this->_aData['email']);

		// Create a unique nickname if required
		if(!isset($aTemp['nickname']) || trim($aTemp['nickname']) == '')
		{
			if(isset($aTemp['email']) && trim($aTemp['email']) != '')
			{
				$aTemp['nickname'] = $aTemp['email'];
			}
			else
			{
				$aTemp['nickname'] = $aTemp['email'] = \Util::generateRandomString(16).'°^~*><|';
			}
		}

		$this->_sTable = 'office_customers';
		$this->_convertTimestamps('from_unix_ts');

		// Create an new entry into the DB
		if(intval($this->_aData['id']) == 0)
		{
			$aTemp['access_code'] = \Util::generateRandomString(16).'°^~*><|';
			DB::insertData('customer_db_'.$this->_aConfig["database"], $aTemp);
			$this->_aData['id'] = DB::fetchInsertID();
			DB::insertData('office_customers', $this->_aData);
		}
		// Update an entry
		if(intval($this->_aData['id']) > 0)
		{
			DB::updateData('customer_db_'.$this->_aConfig["database"], $aTemp, '`id` = '.intval($this->_aData['id']));
			DB::updateData('office_customers', $this->_aData, '`id` = '.intval($this->_aData['id']));
		}

		// Activate the customer
		$this->handleActivation(1);

		// Reload the data
		$this->_loadData();
	}


	/**
	 * Loads the element data from the DB
	 */
	protected function _loadData()
	{
		$this->_sTable = $this->_aConfig['database'];

		$sSQL = "
			SELECT
				`cdb`.`id`,
				`cdb`.`email`,
				`cdb`.`".$this->_aConfig['field_number']."`		AS `number`,
				`cdb`.`".$this->_aConfig['field_matchcode']."`	AS `matchcode`,
				`cdb`.`".$this->_aConfig['field_company']."`	AS `company`,
				`cdb`.`".$this->_aConfig['field_address']."`	AS `address`,
				`cdb`.`".$this->_aConfig['field_addition']."`	AS `addition`,
				`cdb`.`".$this->_aConfig['field_zip']."`		AS `zip`,
				`cdb`.`".$this->_aConfig['field_city']."`		AS `city`,
				`cdb`.`".$this->_aConfig['field_country']."`	AS `country`,
				`cdb`.`".$this->_aConfig['field_phone']."`		AS `phone`,
				`cdb`.`".$this->_aConfig['field_fax']."`		AS `fax`,
				UNIX_TIMESTAMP(`oc`.`created`) AS `created`,
				UNIX_TIMESTAMP(`oc`.`changed`) AS `changed`,
				`oc`.`payment_invoice`,
				`oc`.`payment_misc`,
				`oc`.`cms_contact`,
				`oc`.`by_email`,
				`oc`.`debitor_nr`,
				`oc`.`creditor_nr`,
				`oc`.`vat_id_nr`,
				`oc`.`vat_id_valid`,
				`oc`.`language`,
				`oc`.`logo_extension`,
				`oc`.`paymill_client_id`,
				`oc`.`redmine_project_id`
			FROM
				`customer_db_".$this->_aConfig['database']."` AS `cdb`
					LEFT OUTER JOIN
				`office_customers` AS `oc`
					ON
				`cdb`.`id` = `oc`.`id`
			WHERE
				`cdb`.`id` = :iID
			LIMIT
				1
		";
		$this->_aData = DB::getQueryRow($sSQL, array('iID' => $this->_aData['id']));

		foreach((array)$this->_aData as $sKey => $mValue)
		{
			if(stripos($mValue, '°^~*><|') !== false)
			{
				$this->_aData[$sKey] = '';
			}
		}
	}


	/**
	 * Creates / fills fields for new entry in DB
	 */
	protected function _preloadFields()
	{
		$this->_aData['email'] 				= '';
		$this->_aData['number'] 			= '';
		$this->_aData['matchcode'] 			= '';
		$this->_aData['company'] 			= '';
		$this->_aData['address'] 			= '';
		$this->_aData['addition'] 			= '';
		$this->_aData['zip'] 				= '';
		$this->_aData['city'] 				= '';
		$this->_aData['country'] 			= '';
		$this->_aData['phone'] 				= '';
		$this->_aData['fax'] 				= '';
		$this->_aData['created'] 			= null;
		$this->_aData['changed'] 			= null;
		$this->_aData['payment_invoice'] 	= 0;
		$this->_aData['payment_misc'] 		= 0;
		$this->_aData['cms_contact'] 		= '';
		$this->_aData['by_email'] 			= '';
		$this->_aData['debitor_nr'] 		= '';
		$this->_aData['creditor_nr'] 		= '';
		$this->_aData['vat_id_nr'] 			= '';
		$this->_aData['vat_id_valid'] 		= '';
		$this->_aData['language'] 			= '';
		$this->_aData['logo_extension']		= null;
		$this->_aData['paymill_client_id']	= null;
		$this->_aData['redmine_project_id']	= null;
	}

	public static function getGroups($iClientId=1) {

		$sSql = "
			SELECT
				`id`,
				`name`
			FROM
				`office_customers_groups`
			WHERE
				`active` = 1 AND
				`client_id` = :client_id
			ORDER BY
				`name`
				";
		$aSql = array('client_id'=>(int)$iClientId);

		$aGroups = DB::getQueryPairs($sSql, $aSql);

		return $aGroups;

	}

	/**
	 * Gibt die Kundengruppen (IDs) des Kunden zurück.
	 * 
	 * @return array <p>
	 * Die Ids der Kundengruppen, dem dieser Kunde angehört als Array.
	 * </p>
	 */
	public function getGroupIds(){
		// Keys um die Kundengruppe zu bekommen
		$aKeys = array('customer_id' => $this->id);
		// Hole die Kundengruppen (IDs) des Kunden
		$aCustomerGroups = \DB::getJoinData('office_customers_groups_join', $aKeys, 'group_id');

		return $aCustomerGroups;
	}

	/* {@inheritdoc} */
	public function getLogoWebDir(){
		return 'storage/public/office/customers/logos/';
	}

	/**
	 * Holt alle Einträge der Abrechnungsliste des Kunden
	 */
	public function getSettlementListItems() {

		$oRepo = \Office\Entity\Settlementlist\Item::getRepository();
		
		$aCriteria = array(
			'customer_id' => (int)$this->id,
			'active' => 1,
			'cleared' => 0
		);
		$aSettlementListItems = $oRepo->findBy($aCriteria);

		return $aSettlementListItems;
	}
	
}
