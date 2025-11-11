<?php

/**
 * The office contracts class
 */
class Ext_Office_Contract extends Ext_Office_Office
{
	/**
	 * The element data
	 */
	protected $_aData = array(
		'id'				=> 0,
		'changed'			=> null,
		'created'			=> null,
		'active'			=> 1,
		'client_id'			=> 1,
		'editor_id'			=> null,
		'product_id'		=> null,
		'customer_id'		=> null,
		'contact_id'		=> null,
		'start'				=> null,
		'end'				=> null,
		'interval'			=> null,
		'amount'			=> null,
		'discount'			=> null,
		'price'				=> null,
		'text'				=> ''
	);


	/**
	 * The constructor
	 * 
	 * @param string : The name of table
	 * @param int : The element ID
	 */
	public function __construct($iElementID = null, $sTable = null)
	{
		parent::__construct('office_contracts', $iElementID);
	}


	/**
	 * Creates the invoices from contracts
	 * 
	 * @param array : The array with contracts wich have to be payed
	 */
	public function createInvoices($aContracts) {
		global $user_data;

		DB::begin('Ext_Office_Contract:createInvoices');

		try {
			
			// Group by customer and contact person
			$aDocs = array();
			$aCustomer = array();
			foreach((array)$aContracts as $iKey => $aValue) {

				if(!isset($aDocs[$aValue['data']['customer_id']][$aValue['data']['contact_id']])) {

					$oCustomer = new Ext_Office_Customer(0, $aValue['data']['customer_id']);
					$aCustomer[$aValue['data']['customer_id']] = $oCustomer;

					$sLanguage = $oCustomer->language;
					if(empty($sLanguage)) {
						$sLanguage = 'de';
					}

					$sAddress = $oCustomer->getAddress();

					$oDocument = new Ext_Office_Document();

					$oDocument->customer_id			= $aValue['data']['customer_id'];
					$oDocument->contact_person_id	= $aValue['data']['contact_id'];
					$oDocument->editor_id			= $aValue['data']['editor_id'];
					$oDocument->address				= $sAddress;
					$oDocument->type				= 'account';
					$oDocument->date				= time();
					$oDocument->booking_date		= date('Y-m-d H:i:s');
					$oDocument->payment				= Ext_Office_Config::get('contract_payment', $sLanguage);
					$oDocument->subject				= 'Vertrag';
					$oDocument->text				= nl2br(Ext_Office_Config::get('contract_starttext', $sLanguage));
					$oDocument->endtext				= nl2br(Ext_Office_Config::get('contract_endtext', $sLanguage));

					// Explain: $aDocs[customer_id][contact_id] = array(...);
					$aDocs[$aValue['data']['customer_id']][$aValue['data']['contact_id']] = $oDocument;

				} else {

					$oDocument = $aDocs[$aValue['data']['customer_id']][$aValue['data']['contact_id']];

					$oCustomer = $aCustomer[$aValue['data']['customer_id']];

				}

				$sSQL = "
					SELECT *
					FROM `office_articles`
					WHERE `id` = :iArticleID
					LIMIT 1
				";
				$aArticle = DB::getQueryRow($sSQL, array('iArticleID' => $aValue['data']['product_id']));

				if(
					!empty($aValue['data']['price']) &&
					(float)$aValue['data']['price'] > 0
				) {
					$fPrice = $aValue['data']['price'];
				} else {
					$fPrice = $aArticle['price'];
				}

				$iPosition = 1;
				foreach((array)$aValue['from'] as $i_Key => $_iValue) {

					$aSearch = array('{VON}', '{BIS}', '{LISTE}');
					$aReplace = array(
						date('d.m.Y', $_iValue),
						date('d.m.Y', $aValue['till'][$i_Key]),
						$aValue['data']['text']
					);

					$fAmount = ($aValue['data']['interval'] / $aArticle['month'] * $aValue['data']['amount']);

					$aProduct = array(
						'position'			=> $iPosition++,
						'number'			=> $aArticle['number'],
						'product'			=> $aArticle['product'],
						'amount'			=> $fAmount,
						'unit'				=> $aArticle['unit'],
						'description'		=> str_replace($aSearch, $aReplace, $aArticle['description']),
						'price'				=> $fPrice,
						'discount_item'		=> $aValue['data']['discount'],
						'vat'				=> round($aArticle['vat']/100, 2),
						'only_text'			=> 0,
						'groupsum'			=> 0,
						'revenue_account'	=> 0,
						'group_display' => ''
					);

					/**
					 * Wenn EU und gültige Umsatzsteuer-ID oder 
					 * außergemeinschaftliches Ausland
					 */
					if(
						!$oCustomer->checkCalculateVat()
					) {
						$aProduct['vat'] = 0;
					}

					$oDocument->addItems($aProduct);

					$aInsert = array(
						'created'		=> date('Y-m-d H:i:s'),
						'date'			=> date('Y-m-d', $_iValue),
						'contract_id'	=> (int)$aValue['data']['id'],
						'editor_id'		=> (int)$user_data['id'],
						'amount'		=> (float)($fPrice * $fAmount) * (1 - $aValue['data']['discount'] / 100)
					);
					DB::insertData('office_contracts_payed', $aInsert);

				}

			}

			foreach((array)$aDocs as $iCustomer => $aContacts) {
				foreach((array)$aContacts as $iKey => $oDoc) {
					$oDoc->save();
				}
			}

			DB::commit('Ext_Office_Contract:createInvoices');

		} catch (Exception $ex) {

			DB::rollback('Ext_Office_Contract:createInvoices');
			
			return false;
			
		}

		return true;
	}


	/**
	 * Returns the list of active contracts wich are have to be payed
	 * 
	 * @return array : The list of active contracts
	 */
	public function getDueList($sOrderBy="", $sSearch="", $iIncludeFutureDays=0) {
		
		switch($sOrderBy) {
			case "start":
				$sOrderBy = "`start` ASC";
				break;
			case "end":
				$sOrderBy = "`end` ASC";
				break;
			case "company":
				$sOrderBy = "`company` ASC";
				break;
			case "product":
				$sOrderBy = "`product` ASC";
				break;
			case "editor":
				$sOrderBy = "`editor` ASC";
				break;
			case "amount":
				$sOrderBy = "`amount` ASC";
				break;
			case "interval":
				$sOrderBy = "`interval` ASC";
				break;
			case "price":
				$sOrderBy = "`price` ASC";
				break;
			case "discount":
				$sOrderBy = "`discount` ASC";
				break;
			default:
				$sOrderBy = "`created` DESC";
				break;
		}
	
		$sWhere = "";
		if(!empty($sSearch))
		{
			$sWhere .= " AND (";
				$sWhere .= " `oc`.`text` LIKE '%".$sSearch."%' ";
				$sWhere .= " OR `c`.".$this->_aConfig['field_matchcode']." LIKE '%".$sSearch."%' ";
				$sWhere .= " OR `oa`.`product` LIKE '%".$sSearch."%' ";
			$sWhere .= ") ";
		}

		$aSQL = array(
			'iClientID' => (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id')
		);

		$sSQL = "
			SELECT
				`oc`.*,
				UNIX_TIMESTAMP(`oc`.`created`) AS `created`,
				UNIX_TIMESTAMP(`oc`.`changed`) AS `changed`,
				UNIX_TIMESTAMP(`oc`.`start`) AS `start`,
				UNIX_TIMESTAMP(`oc`.`end`) AS `end`,
				IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) `price`,
				`oa`.`product`,
				`c`.`".$this->_aConfig['field_matchcode']."` AS `company`,
				`su`.`firstname`,
				`su`.`lastname`,
				MAX(UNIX_TIMESTAMP(`ocp`.`date`)) AS `payed_created`,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) `editor`,
				CONCAT(oco.lastname, ', ', oco.firstname) `contact`,
				(IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) * (`oc`.`interval` / `oa`.`month`) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) AS `total`
			FROM 
				`office_contracts` AS `oc` LEFT OUTER JOIN
				`office_contracts_payed` AS `ocp` ON
					`ocp`.`contract_id` = `oc`.`id` LEFT OUTER JOIN 
				`office_articles` AS `oa` ON 
					`oc`.`product_id` = `oa`.`id` LEFT OUTER JOIN 
				`customer_db_".$this->_aConfig['database']."` AS `c` ON 
					`oc`.`customer_id` = `c`.`id` LEFT OUTER JOIN 
				`system_user` AS `su` ON 
					`oc`.`editor_id` = `su`.`id` LEFT OUTER JOIN
				`office_contacts` oco ON 
					oc.contact_id = oco.id
			WHERE
				`oc`.`active` = 1 ".$sWhere." AND
				`oc`.`client_id` = :iClientID
			GROUP BY 
				`oc`.`id`
			ORDER BY 
				".$sOrderBy."
		";
		$aList = DB::getPreparedQueryData($sSQL, $aSQL);

		$iCompareTimestamp = strtotime("+".(int)$iIncludeFutureDays." day");

		$aContracts = array();
		foreach((array)$aList as $iKey => $aValue) {
			$iTime = $aValue['start'];
			
			// Solange der Vetrag schon begonnen hat
			while($iTime <= $iCompareTimestamp)	{

				if(
					$iTime <= $iCompareTimestamp &&
					$aValue['start'] <= $iTime &&
					(
						(
							$aValue['end'] != 0 && 
							$iTime <= $aValue['end']
						) || (
							$aValue['end'] == 0
						)
					) &&
					(
						(
							(int)$aValue['payed_created'] > 0 && 
							(int)$aValue['payed_created'] < $iTime
						) || 
						(int)$aValue['payed_created'] == 0
					)
				) {
					$aValue['due_date'] = $iTime;
					$aContracts[] = $aValue;
				}
				
				$iTime = strtotime('+'.$aValue['interval'].' month', $iTime);

			}

		}

		return $aContracts;

	}

	/**
	 * Returns the list of active contracts
	 * 
	 * @return array : The list of active contracts
	 */
	public function getContractsList($sOrderBy="", $sSearch="", $iCustomerId=null)
	{
		switch($sOrderBy) {
			case "start":
				$sOrderBy = "`start` ASC";
				break;
			case "end":
				$sOrderBy = "`end` ASC";
				break;
			case "company":
				$sOrderBy = "`company` ASC";
				break;
			case "product":
				$sOrderBy = "`product` ASC";
				break;
			case "editor":
				$sOrderBy = "`editor` ASC";
				break;
			case "amount":
				$sOrderBy = "`amount` ASC";
				break;
			case "interval":
				$sOrderBy = "`interval` ASC";
				break;
			case "price":
				$sOrderBy = "`price` ASC";
				break;
			case "discount":
				$sOrderBy = "`discount` ASC";
				break;
			default:
				$sOrderBy = "`created` DESC";
				break;
		}

		$sWhere = "";
		if(!empty($sSearch))
		{
			$sWhere .= " AND (";
				$sWhere .= " `oc`.`text` LIKE '%".$sSearch."%' ";
				$sWhere .= " OR `c`.".$this->_aConfig['field_matchcode']." LIKE '%".$sSearch."%' ";
				$sWhere .= " OR `oa`.`product` LIKE '%".$sSearch."%' ";
			$sWhere .= ") ";
		}
		
		$aSQL = array(
			'iClientID' => (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id')
		);

		if($iCustomerId !== null) {
			$sWhere .= " AND `oc`.`customer_id` = :customer_id ";
			$aSQL['customer_id'] = (int)$iCustomerId;
		}

		$sSQL = "
			SELECT
				`oc`.*,
				UNIX_TIMESTAMP(`oc`.`created`) AS `created`,
				UNIX_TIMESTAMP(`oc`.`changed`) AS `changed`,
				UNIX_TIMESTAMP(`oc`.`start`) AS `start`,
				UNIX_TIMESTAMP(`oc`.`end`) AS `end`,
				IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) `price`,
				`oa`.`product`,
				`c`.".$this->_aConfig['field_matchcode']." AS `company`,
				`su`.`firstname`,
				`su`.`lastname`,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) `editor`,
				CONCAT(oco.lastname, ', ', oco.firstname) `contact`,
				(IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) * (`oc`.`interval` / `oa`.`month`) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) AS `total`
			FROM 
				`office_contracts` AS `oc` LEFT OUTER JOIN
				`office_articles` AS `oa` ON 
					`oc`.`product_id` = `oa`.`id` LEFT OUTER JOIN 
				`customer_db_".$this->_aConfig['database']."` AS `c` ON 
					`oc`.`customer_id` = `c`.`id` LEFT OUTER JOIN 
				`system_user` AS `su` ON 
					`oc`.`editor_id` = `su`.`id` LEFT OUTER JOIN
				`office_contacts` oco ON 
					oc.contact_id = oco.id
			WHERE 
				`oc`.`active` = 1 ".$sWhere." AND
				`oc`.`client_id` = :iClientID
			ORDER BY 
				".$sOrderBy."
		";
		$aList = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aList;
	}


	/**
	 * Returns the stats of all contracts by year
	 * 
	 * @param int : The selected year
	 * @return array : The months list with total prices
	 */
	public function getContractsStats($iYear) {
		
		$aDebug = array();
		$aMonthDebug = array();
		
		$oStart = new WDDate();
		$oStart->set('00:00:00', WDDate::TIMES);
		$oStart->set('01.01.'.$iYear, WDDate::DATES);
		$iStart = $oStart->get(WDDate::TIMESTAMP);
		$oEnd = new WDDate($oStart);
		$oEnd->set('23:59:59', WDDate::TIMES);
		$oEnd->set('31.12.'.$iYear, WDDate::DATES);
		$iEnd = $oEnd->get(WDDate::TIMESTAMP);

		$sSql = "
			SELECT
				*
			FROM
				`office_contracts_payed`
			WHERE
				`date` BETWEEN :start AND :end AND
				`active` = 1
			";
		$aSql = array(
			'start'=>$oStart->get(WDDate::DB_DATE),
			'end'=>$oEnd->get(WDDate::DB_DATE)
		);
		$aItems = (array)DB::getQueryRows($sSql, $aSql);
		$aContractsPayed = array();
		foreach($aItems as $aItem) {
			$aContractsPayed[$aItem['contract_id']][$aItem['date']] = (float)$aItem['amount'];
		}

		$sSQL = "
			SELECT
				`oc`.`id`,
				`oc`.`start` AS `tmp_start`,
				`oc`.`end` AS `tmp_end`,
				`oc`.`customer_id`,
				UNIX_TIMESTAMP(`oc`.`start`) AS `start`,
				UNIX_TIMESTAMP(`oc`.`end`) AS `end`,
				MONTH(`oc`.`start`) AS `month`,
				`oc`.`interval`,
				`oa`.`month` `article_interval`,
				`oa`.`productgroup` `productgroup`,
				`oc`.`discount`,
				(IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) `price`,
				(IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) * (`oc`.`interval` / IF(`oa`.`month`=0, 1, `oa`.`month`)) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) AS `total`
			FROM 
				`office_contracts` AS `oc` INNER JOIN
				`office_articles`	AS `oa` ON
					`oc`.`product_id` = `oa`.`id`
			WHERE
				(`oc`.`end` = 0 OR SUBDATE(`oc`.`end`, INTERVAL `oc`.`interval` MONTH) > :iY_Start)
					AND
				`oc`.`start` < :iY_End
					AND
				`oc`.`active` = 1 AND
				`oc`.`client_id` = :iClientID
			ORDER BY MONTH(`oc`.`start`)
		";
		$aSQL = array(
			'iY_Start'	=> date('YmdHis', $iStart),
			'iY_End'	=> date('YmdHis', $iEnd),
			'iClientID' => (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id')
		);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		$oDate = new WDDate();
		$oDateEnd = new WDDate();

		$aStats = array();
		foreach((array)$aResult as $aValue){

			$aDebug[$aValue['id']]['contract'] = $aValue;
			
			$bLastMonthThisYear = false;
			
			$oDate->set($aValue['start'], WDDate::TIMESTAMP);
			$oDateStart = clone $oDate;

			if($aValue['end'] > 0) {
				$bLastMonthThisYear = true;
				$oDateEnd->set($aValue['end'], WDDate::TIMESTAMP);
				// Wenn das Vertragsende erst im nächsen Jahr liegt
				if($oDateEnd->get(WDDate::TIMESTAMP) >= $iEnd) {
					$oDateEnd->set($iEnd, WDDate::TIMESTAMP);
					$bLastMonthThisYear = false;
				}
			} else {
				$oDateEnd->set($iEnd, WDDate::TIMESTAMP);
			}

			if($oDateStart->get(WDDate::TIMESTAMP) <= $iStart) {
				$oDateStart->set($iStart, WDDate::TIMESTAMP);
			}

			if($aValue['end'] != 0) {

				while($oDate->get(WDDate::TIMESTAMP) <= $iEnd && $oDate->get(WDDate::TIMESTAMP) <= $aValue['end'])
				{
					if($oDate->get(WDDate::TIMESTAMP) >= $iStart && $oDate->get(WDDate::TIMESTAMP) <= $iEnd)
					{
						if(!isset($aStats[(int)$oDate->get(WDDate::MONTH)]))
						{
							$aStats[(int)$oDate->get(WDDate::MONTH)] = array('price'=>0, 'cleared'=>0);
						}
						$aStats[(int)$oDate->get(WDDate::MONTH)]['price'] += $aValue['total'];

						$aMonthDebug[(int)$oDate->get(WDDate::MONTH)][$aValue['customer_id']][$aValue['id']] += $aValue['total'];
						$aDebug[$aValue['id']]['month'][(int)$oDate->get(WDDate::MONTH)]['billing'] = $aValue['total'];

						if(isset($aContractsPayed[$aValue['id']][$oDate->get(WDDate::DB_DATE)])) {
							if(!empty($aContractsPayed[$aValue['id']][$oDate->get(WDDate::DB_DATE)])) {
								$aStats[(int)$oDate->get(WDDate::MONTH)]['cleared'] += $aContractsPayed[$aValue['id']][$oDate->get(WDDate::DB_DATE)];
							} else {
								$aStats[(int)$oDate->get(WDDate::MONTH)]['cleared'] += $aValue['total'];
							}
						}
					}

					$oDate->add($aValue['interval'], WDDate::MONTH);
				}
			}
			else
			{
				while($oDate->get(WDDate::TIMESTAMP) <= $iEnd)
				{
					if($oDate->get(WDDate::TIMESTAMP) >= $iStart && $oDate->get(WDDate::TIMESTAMP) <= $iEnd)
					{
						if(!isset($aStats[(int)$oDate->get(WDDate::MONTH)]))
						{
							$aStats[(int)$oDate->get(WDDate::MONTH)] = array('price'=>0, 'cleared'=>0);
						}
						$aStats[(int)$oDate->get(WDDate::MONTH)]['price'] += $aValue['total'];

						$aMonthDebug[(int)$oDate->get(WDDate::MONTH)][$aValue['customer_id']][$aValue['id']] += $aValue['total'];
						$aDebug[$aValue['id']]['month'][(int)$oDate->get(WDDate::MONTH)]['billing'] = $aValue['total'];
						
						if(isset($aContractsPayed[$aValue['id']][$oDate->get(WDDate::DB_DATE)])) {
							if(!empty($aContractsPayed[$aValue['id']][$oDate->get(WDDate::DB_DATE)])) {
								$aStats[(int)$oDate->get(WDDate::MONTH)]['cleared'] += $aContractsPayed[$aValue['id']][$oDate->get(WDDate::DB_DATE)];
							} else {
								$aStats[(int)$oDate->get(WDDate::MONTH)]['cleared'] += $aValue['total'];
							}
						}

					}

					$oDate->add($aValue['interval'], WDDate::MONTH);
				}
			}

			$oDate = clone $oDateStart;
			$oDate->set(1, WDDate::DAY);

			// Factor für ersten und letzten Monat
			$iFirstFactor = 1;
			$iLastFactor = 1;
			$iMonthDays = $oDateStart->get(WDDate::MONTH_DAYS);
			$iFirstDay = $oDateStart->get(WDDate::DAY);
			$iTotalDays = $iMonthDays;

			if($iFirstDay != 1) {
				$iTotalDays = $iTotalDays - $iFirstDay + 1;
				$iFirstFactor = $iTotalDays / $iMonthDays;
				if($bLastMonthThisYear) {
					$iLastFactor = 1 - $iFirstFactor;
				}
			}

			while($oDate->compare($oDateEnd) <= 0) {

				$iMonthDays = $oDate->get(WDDate::MONTH_DAYS);
				$iTotalDays = $iMonthDays;

				$iFactor = 1;
				
				// Erster Monat
				if($oDate->get(WDDate::STRFTIME, '%Y%m') == $oDateStart->get(WDDate::STRFTIME, '%Y%m')) {
					$iFactor = $iFirstFactor;
				}

				// Letzter Monat
				if($oDate->get(WDDate::STRFTIME, '%Y%m') == $oDateEnd->get(WDDate::STRFTIME, '%Y%m')) {
					$iFactor = $iLastFactor;
				}

				// Monatspreis ausrechnen
				if($aValue['article_interval'] == 0) {
					$aValue['article_interval'] = 1;
				}
				$fMonthPrice = ($aValue['price'] / $aValue['article_interval']) * $iFactor;
				
				$aDebug[$aValue['id']]['month'][(int)$oDate->get(WDDate::MONTH)]['amount'] = $fMonthPrice;
				
				$aStats[(int)$oDate->get(WDDate::MONTH)]['productgroups'][$aValue['productgroup']] += $fMonthPrice;

				$oDate->add(1, WDDate::MONTH);

			}
			
		}

		if($_REQUEST['office_debug'] == 1) {

			$aTotalSum = array(
				'billing' => 0,
				'amount' => 0
			);
			
			foreach($aDebug as &$aContract) {
				ksort($aContract['month']);
				$aContract['sum'] = array(
					'billing' => 0,
					'amount' => 0
				);
				foreach($aContract['month'] as &$aMonth) {
					$aContract['sum']['billing'] += $aMonth['billing'];
					$aContract['sum']['amount'] += $aMonth['amount'];
				}
				if(
					round($aContract['sum']['billing']) != round($aContract['sum']['amount'])
				) {
					__out($aContract);
				}
				
				$aTotalSum['billing'] += $aContract['sum']['billing'];
				$aTotalSum['amount'] += $aContract['sum']['amount'];
				
			}
			
			__out($aTotalSum);
			__out($aDebug);
			__out($aMonthDebug);
			__out($aStats);

		}

		return $aStats;

	}

	/**
	 * Returns the stats of all contracts by year
	 * 
	 * @param int : The selected year
	 * @return array : The months list with total prices
	 */
	public function getContractsStatsCustomer($iYear) {
		
		$aDebug = array();
		
		$oStart = new WDDate();
		$oStart->set('00:00:00', WDDate::TIMES);
		$oStart->set('01.01.'.$iYear, WDDate::DATES);
		$iStart = $oStart->get(WDDate::TIMESTAMP);
		$oEnd = new WDDate($oStart);
		$oEnd->set('23:59:59', WDDate::TIMES);
		$oEnd->set('31.12.'.$iYear, WDDate::DATES);
		$iEnd = $oEnd->get(WDDate::TIMESTAMP);

		Ext_Office_Config::get('contract_payment', $sLanguage);
		
		$sSQL = "
			SELECT
				`oc`.`id`,
				`c`.`".$this->_aConfig['field_matchcode']."` AS `customer`,
				`c`.`id` `customer_id`,
				`oc`.`start` AS `tmp_start`,
				`oc`.`end` AS `tmp_end`,
				UNIX_TIMESTAMP(`oc`.`start`) AS `start`,
				UNIX_TIMESTAMP(`oc`.`end`) AS `end`,
				MONTH(`oc`.`start`) AS `month`,
				`oc`.`interval`,
				`oa`.`month` `article_interval`,
				`oa`.`productgroup` `productgroup`,
				`oc`.`discount`,
				(IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) `price`,
				(IF(`oc`.`price` = 0, `oa`.`price`, `oc`.`price`) * (`oc`.`interval` / IF(`oa`.`month`=0, 1, `oa`.`month`)) * `oc`.`amount` * (1 - `oc`.`discount` / 100)) AS `total`
			FROM 
				`office_contracts` AS `oc` INNER JOIN
				`office_articles`	AS `oa` ON
					`oc`.`product_id` = `oa`.`id` JOIN
				`customer_db_".$this->_aConfig['database']."` AS `c` ON
					`oc`.`customer_id` = `c`.`id`
			WHERE
				(`oc`.`end` = 0 OR SUBDATE(`oc`.`end`, INTERVAL `oc`.`interval` MONTH) > :iY_Start)
					AND
				`oc`.`start` < :iY_End
					AND
				`oc`.`active` = 1 AND
				`oc`.`client_id` = :iClientID
			ORDER BY `customer`
		";
		$aSQL = array(
			'iY_Start'	=> date('YmdHis', $iStart),
			'iY_End'	=> date('YmdHis', $iEnd),
			'iClientID' => (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id')
		);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		$oDate = new WDDate();
		$oDateEnd = new WDDate();

		$aStats = array();
		foreach((array)$aResult as $aValue){

			$aDebug[$aValue['id']]['contract'] = $aValue;
			
			$bLastMonthThisYear = false;
			
			$oDate->set($aValue['start'], WDDate::TIMESTAMP);
			$oDateStart = clone $oDate;

			if($aValue['end'] > 0) {
				$bLastMonthThisYear = true;
				$oDateEnd->set($aValue['end'], WDDate::TIMESTAMP);
				// Wenn das Vertragsende erst im nächsen Jahr liegt
				if($oDateEnd->get(WDDate::TIMESTAMP) >= $iEnd) {
					$oDateEnd->set($iEnd, WDDate::TIMESTAMP);
					$bLastMonthThisYear = false;
				}
			} else {
				$oDateEnd->set($iEnd, WDDate::TIMESTAMP);
			}

			if($oDateStart->get(WDDate::TIMESTAMP) <= $iStart) {
				$oDateStart->set($iStart, WDDate::TIMESTAMP);
			}

			if($aValue['end'] != 0) {

				while($oDate->get(WDDate::TIMESTAMP) <= $iEnd && $oDate->get(WDDate::TIMESTAMP) <= $aValue['end'])
				{
					if($oDate->get(WDDate::TIMESTAMP) >= $iStart && $oDate->get(WDDate::TIMESTAMP) <= $iEnd)
					{
						if(!isset($aStats[(int)$aValue['customer_id']]))
						{
							$aStats[(int)$aValue['customer_id']] = array(
								'price'=>0,
								'name' => $aValue['customer']
							);
						}
						$aStats[(int)$aValue['customer_id']]['price'] += $aValue['total'];
						$aDebug[$aValue['id']]['customer'][(int)$aValue['customer_id']]['billing'] = $aValue['total'];

					}

					$oDate->add($aValue['interval'], WDDate::MONTH);
				}
			}
			else
			{
				while($oDate->get(WDDate::TIMESTAMP) <= $iEnd)
				{
					if($oDate->get(WDDate::TIMESTAMP) >= $iStart && $oDate->get(WDDate::TIMESTAMP) <= $iEnd)
					{
						if(!isset($aStats[(int)$aValue['customer_id']]))
						{
							$aStats[(int)$aValue['customer_id']] = array(
								'price'=>0,
								'name' => $aValue['customer']
							);
						}
						$aStats[(int)$aValue['customer_id']]['price'] += $aValue['total'];
						$aDebug[$aValue['id']]['customer'][(int)$aValue['customer_id']]['billing'] = $aValue['total'];

					}

					$oDate->add($aValue['interval'], WDDate::MONTH);
				}
			}

			
		}

		if($_REQUEST['office_debug'] == 1) {

			$aTotalSum = array(
				'billing' => 0,
				'amount' => 0
			);
			
			foreach($aDebug as &$aContract) {
				ksort($aContract['month']);
				$aContract['sum'] = array(
					'billing' => 0,
					'amount' => 0
				);
				foreach($aContract['customer'] as &$aMonth) {
					$aContract['sum']['billing'] += $aMonth['billing'];
					$aContract['sum']['amount'] += $aMonth['amount'];
				}
				if(
					round($aContract['sum']['billing']) != round($aContract['sum']['amount'])
				) {
					__out($aContract);
				}
				
				$aTotalSum['billing'] += $aContract['sum']['billing'];
				$aTotalSum['amount'] += $aContract['sum']['amount'];
				
			}
			
			__out($aTotalSum);
			__out($aDebug);
			__out($aStats);

		}

		return $aStats;

	}


	/**
	 * Return the years between max and min of start date
	 * 
	 * @return array : The list of years
	 */
	static public function getAvailableYears() {

		$sSQL = "
			SELECT 
				MAX(YEAR(`start`)) AS `max`,
				MIN(YEAR(`start`)) AS `min`
			FROM
				`office_contracts`
			WHERE
				`active` = 1
		";
		$aBorderYears = DB::getQueryRow($sSQL);

		$iMaxYear = max($aBorderYears['max'], date('Y')+2);

		$aYears = array();
		for($i = $iMaxYear; $i >= $aBorderYears['min']; $i--) {
			$aYears[(int)$i] = (int)$i;
		}

		if(empty($aYears)) {
			$aYears[date('Y')] = date('Y');
		}

		return $aYears;
	}
	
	public static function matchItems($aItems) {
		
		$sTable = '<table class="table" width="100%" cellspacing="0" cellpadding="4" border="0">';
		
		$sTable .= '<tr><th style="width: 50%;">Eintrag</th><th style="width: 25%;">Leistungsstart</th><th style="width: 25%;">Vertragsstart</th></tr>';
		
		foreach($aItems as $sItem) {

			$aItem = preg_split("/\s+/", $sItem);
			
			$sSql = "
				SELECT
					`id`, 
					`start`
				FROM
					`office_contracts`
				WHERE
					`text` LIKE :search AND
					`active` = 1 AND
					(
						`end` >= NOW() OR
						`end` = '0000-00-00'
					)
				LIMIT 1
				";
			$aSql = array(
				'search' => '%'.trim($aItem[0]).'%'
			);
			$aContract = DB::getQueryRow($sSql, $aSql);
			
			$sStartStyle = '';
			if(!empty($aContract)) {

				$oStart = new WDDate($aContract['start'], WDDate::DB_DATETIME);
				$sStart = $oStart->get(WDDate::STRFTIME, '%d.%m.');

				$oCompare = new WDDate($aItem[1], WDDate::STRFTIME, '%d.%m.%Y');
				$sCompare = $oCompare->get(WDDate::STRFTIME, '%d.%m.');

				if($sCompare != $sStart) {
					$sStartStyle = "color: red;";
				}
				
				$sStyle = "color: lime;";

			} else {
				
				$sStart = '';
				
				$sStyle = "color: red;";
			}

			$sTable .= '<tr><td style="'.$sStyle.'">'.$aItem[0].'</td><td>'.$aItem[1].'</td><td style="'.$sStartStyle.'">'.$sStart.'</td></tr>';

		}
		
		$sTable .= '</table>';
		
		return $sTable;
		
	}
	
}
