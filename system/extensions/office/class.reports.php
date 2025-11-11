<?php
 
class Ext_Office_Reports
{
	private $_oOfficeDAO;
	private $_arrConfig;


	/**
	 * The constructor
	 */
	public function __construct($aConfig=array()) {
		$this->_oOfficeDAO = new classExtensionDao_Office();

		$this->_arrConfig = $aConfig;
	}


	/**
	 * Return the number of accounts by date periode
	 * 
	 * @return array $aResult
	 */
	public function countAccounts($sFrom, $sTill) {

		// Get timestamp
		$iFrom = mktime(0,0,0,(int)substr($sFrom, 3, 2), (int)substr($sFrom, 0, 2), (int)substr($sFrom, 6, 4));
		$iTill = mktime(23,59,59,(int)substr($sTill, 3, 2), (int)substr($sTill, 0, 2), (int)substr($sTill, 6, 4));

		echo $sSQL = "
			SELECT
				COUNT(`od`.`id`) AS `counter`, 
				AVG(`od`.`price_reports`) AS `price`
			FROM
				`office_documents` AS `od`
			WHERE
				`od`.`type` = 'account' AND
				`od`.`booking_date` BETWEEN ".date("YmdHis", $iFrom)." AND ".date("YmdHis", $iTill)." AND
				`od`.`state` != 'draft' 
		";
		$aResult = DB::getQueryData($sSQL);

		return $aResult[0];
	}


	/**
	 * Return the number of accepted offers by date periode
	 * 
	 * @return array $aResult
	 */
	public function countAcceptedOffers($sFrom, $sTill) {

		// Get timestamp
		$iFrom = $sFrom;//mktime(0,0,0,(int)substr($sFrom, 3, 2), (int)substr($sFrom, 0, 2), (int)substr($sFrom, 6, 4));
		$iTill = $sTill;//mktime(23,59,59,(int)substr($sTill, 3, 2), (int)substr($sTill, 0, 2), (int)substr($sTill, 6, 4));

		$sSQL = "
			SELECT
				COUNT(`op`.`id`) AS `counter`, 
				SUM(`price_reports`) AS `sum_net`
			FROM
				`office_protocol` AS `op` LEFT JOIN
				`office_documents` AS `od` ON	
					`op`.`document_id` = `od`.`id`
			WHERE
				`op`.`topic` = 'offer' AND
				`op`.`date` BETWEEN ".date("YmdHis", $iFrom)." AND ".date("YmdHis", $iTill)." AND
				(
					`op`.`state` = 'accepted'
				)
		";
		$aResult = DB::getQueryData($sSQL);

		return $aResult[0];
	}

	/**
	 * Returns the average times
	 *
	 * @return array $aResult
	 */
	public function getAverageTimes() {
		$aReturn = array();

		// Get average times
		$sSQL = "
			SELECT
				AVG(UNIX_TIMESTAMP(`op`.`created`) - UNIX_TIMESTAMP(`od`.`date`)) AS `average`,
				COUNT(DISTINCT od.id) AS c,
				SUM(UNIX_TIMESTAMP(`op`.`created`) - UNIX_TIMESTAMP(`od`.`date`)) AS s,
				`c_db`.`".$this->_arrConfig['field_matchcode']."` AS `company`,
				`od`.`customer_id` AS `customer`
			FROM
				`office_documents` AS `od` LEFT JOIN
				`office_payments` AS `op` ON
					`od`.`id` = `op`.`document_id` LEFT OUTER JOIN
				`customer_db_".$this->_arrConfig['database']."` AS `c_db` ON
					`od`.`customer_id` = `c_db`.`id`
			WHERE
				`od`.`state` = 'paid' AND
				`op`.`created` =
				(
					SELECT
						MAX(`created`)
					FROM
						`office_payments`
					WHERE
						`document_id` = `od`.`id` AND `amount` > 0
				)
			GROUP BY
				`od`.`customer_id`
			ORDER BY
				`average` DESC
		";

		$aResult = DB::getQueryData($sSQL);

		$aCustomers = array();
		foreach((array)$aResult as $aData) {
			$aCustomers[$aData['customer']] = $aData;
		}
		return $aCustomers;

	}

	/**
	 * Returns the average times
	 *
	 * @return array $aResult
	 */
	public function getPaymentOverview($iStart, $iEnd) {
		
		// Get average times
		$sSql = "
			SELECT
				UNIX_TIMESTAMP(`op`.`created`) `created`,
				`op`.`amount`,
				`op`.`text`,
				`c_db`.`".$this->_arrConfig['field_matchcode']."` AS `customer_name`,
				`c_db`.`".$this->_arrConfig['field_number']."` AS `customer_number`,
				`od`.`number` AS `document_number`
			FROM
				`office_payments` `op` LEFT JOIN
				`office_documents` AS `od` ON
					`op`.`document_id` = `od`.`id` LEFT JOIN
				`customer_db_".$this->_arrConfig['database']."` AS `c_db` ON
					`od`.`customer_id` = `c_db`.`id`
			WHERE
				`op`.`created` BETWEEN :start AND :end 
			ORDER BY
				`op`.`created` ASC
		";
		$aSql = array(
			'start'=>date('Y-m-d H:i:s', $iStart),
			'end'=>date('Y-m-d H:i:s', $iEnd)
		);
		$aResult = DB::getQueryData($sSql, $aSql);

		return $aResult;
	}

	/**
	 * Analyse all accepted offers and the linked invoices
	 * @return array with all accepted and not yet finished offers
	 */
	public function getCurrentOrderList() {
		
		$sSql = "
				SELECT 
					`d`.`price_net` price, 
					`d`.`number`, 
					`d`.`subject`, 
					UNIX_TIMESTAMP(`p`.`date`) date_accepted, 
					`c`.`".$this->_arrConfig['field_matchcode']."` AS `company`, 
					SUM( `d2`.`price_net` ) cleared
				FROM 
					`office_documents` d LEFT OUTER JOIN
					`office_protocol` p ON
						d.id = p.document_id AND
						p.topic = 'offer' AND
						p.state = 'accepted' LEFT OUTER JOIN
					`customer_db_".$this->_arrConfig['database']."` c ON
						`d`.`customer_id` = `c`.`id` LEFT OUTER JOIN 
					`office_documents_links` l ON 
						d.id = l.from_document LEFT OUTER JOIN 
					office_documents d2 ON 
						l.to_document = d2.id AND 
						d2.type = 'account' AND 
						d2.state != 'draft'
				WHERE 
					d.`type` = 'offer' AND 
					d.`state` = 'accepted'
				GROUP BY 
					d.id
				ORDER BY
					`company` ASC,
					`d`.`number` ASC
			";
		$aOffers = DB::getQueryData($sSql);

		return $aOffers;
		
	}

	/**
	 * Return the order balance
	 * 
	 * @return int $iBalancePrice
	 */
	public function getOrderBalance() {

		// Calculate the order balance
		$sSQL = "
			SELECT
				(
					SELECT
						SUM(`price_net`) AS `price`
					FROM
						`office_documents`
					WHERE
						`type`	= 'offer'
							AND
						`state`	= 'accepted'
				) - SUM(`price_net`) AS `price`
			FROM
				`office_documents`
			WHERE
				`id` IN (
					SELECT
						`to_document`
					FROM
						`office_documents_links`
					WHERE
							`from_document` IN
							(
								SELECT
									`id`
								FROM
									`office_documents`
								WHERE
									`type`	= 'offer'
										AND
									`state`	= 'accepted'
							)
						AND
							`to_document` IN
							(
								SELECT
									`id`
								FROM
									`office_documents`
								WHERE
									`type`	= 'account'
										AND
									`state`	!= 'draft'
							)
				)
		";
		$aResult = DB::getQueryData($sSQL);
		$iBalancePrice = number_format(floatVal($aResult[0]['price']), 2, ',', '.');

		return $iBalancePrice;
	}


	/**
	 * Returns the following array:
	 * 
	 * Array
	 * (
	 * 		[this] => Array
	 * 			(
	 * 			[accepted] => 0
	 * 			[released] => 0
	 * 			)
	 * 		[last] => Array
	 * 			(
	 * 				[accepted] => 0
	 * 				[released] => 0
	 * 			)
	 * 		[accepted] => 0
	 * 		[released] => 0
	 * )
	 * 
	 * @return array s.o.
	 * 
	 */
	public function getPeriodeData($iNow_From, $iNow_Till)
	{
		// Last year timestamps
		$iLast_From = strtotime('-1 year', $iNow_From);
		$iLast_Till = strtotime('-1 year', $iNow_Till);

		// Result
		$aResult = array();

		// This year
		//$aData = $this->getDocumentStats('offer', $iNow_From, $iNow_Till, array('show'=>array('accepted')));
		$aData = $this->countAcceptedOffers($iNow_From, $iNow_Till);
		$aData['sum_net'] == '' ? $aResult['this']['accepted'] = 0 : $aResult['this']['accepted'] = $aData['sum_net'];
		$aData = $this->getDocumentStats('account', $iNow_From, $iNow_Till, array('hide'=>array('draft'), 'OR_type' => array('credit', 'cancellation_invoice')));
		$aData['sum_net'] == '' ? $aResult['this']['released'] = 0 : $aResult['this']['released'] = $aData['sum_net'];

		// Last year
		//$aData = $this->getDocumentStats('offer', $iLast_From, $iLast_Till, array('show'=>array('accepted')));
		$aData = $this->countAcceptedOffers($iLast_From, $iLast_Till);
		$aData['sum_net'] == '' ? $aResult['last']['accepted'] = 0 : $aResult['last']['accepted'] = $aData['sum_net'];
		$aData = $this->getDocumentStats('account', $iLast_From, $iLast_Till, array('hide'=>array('draft'), 'OR_type' => array('credit', 'cancellation_invoice')));
		$aData['sum_net'] == '' ? $aResult['last']['released'] = 0 : $aResult['last']['released'] = $aData['sum_net'];

		// Calculate the difference in procent
		if($aResult['last']['accepted'] != 0)
		{
			$aResult['accepted'] = $aResult['this']['accepted'] / ($aResult['last']['accepted'] / 100) - 100;
		}
		else
		{
			if($aResult['this']['accepted'] == 0)
			{
				$aResult['accepted'] = 0;
			}
			else if($aResult['this']['accepted'] < 0)
			{
				$aResult['accepted'] = -100;
			}
			else
			{
				$aResult['accepted'] = 100;
			}
		}
		if($aResult['last']['released'] != 0)
		{
			$aResult['released'] = $aResult['this']['released'] / ($aResult['last']['released'] / 100) - 100;
		}
		else
		{
			if($aResult['this']['released'] == 0)
			{
				$aResult['released'] = 0;
			}
			else if($aResult['this']['released'] < 0)
			{
				$aResult['released'] = -100;
			}
			else
			{
				$aResult['released'] = 100;
			}
		}

		// Return formated result
		return $aResult;
	}

	public function getRevenueAccountsStats($sFrom, $sUntil) {

		$sSql = "
			SELECT
				`odi`.`revenue_account`,
				SUM(
					(
						(IF(`od`.`type`='credit',`odi`.`price`*-1,`odi`.`price`) * (1 - `odi`.`discount_item` / 100)) *
						(IF(`od`.`cash_discount_granted`=1,(1 - `od`.`cash_discount` / 100),1))
					) *
					`odi`.`amount` *
					(1 - `od`.`discount` / 100)
				) `sum`
			FROM
				`office_documents` `od`
			JOIN
				`office_document_items` `odi`
			ON
				`od`.`id` = `odi`.`document_id`
			WHERE
				DATE(`booking_date`) BETWEEN :from AND :until AND
				`od`.`active` = 1 AND
				`od`.`type` IN ('account', 'credit', 'cancellation_invoice') AND
				`od`.`state` != 'draft'
			GROUP BY
				`odi`.`revenue_account`
		";
		$aSql = array(
			'from' => $sFrom,
			'until' => $sUntil
		);

		$aRevenueAccountsStats = DB::getQueryPairs($sSql, $aSql);
 
		return $aRevenueAccountsStats;

	}
	
	public function getDocumentStats($strType, $intFrom, $intUntil, $arrOptions = array()) {

		$strSql = "
			SELECT 
				SUM(`price_reports`) `sum_net`,
				AVG(`price_reports`) `avg_net`,
				COUNT(`id`) `count`
			FROM
				`office_documents`
			WHERE
				UNIX_TIMESTAMP(`booking_date`) BETWEEN ".(int)$intFrom." AND ".(int)$intUntil." AND
				`active` = 1 AND
				(`type` = '".\DB::escapeQueryString($strType)."'
			";

		if(
			isset($arrOptions['OR_type']) && 
			is_array($arrOptions['OR_type'])
		) {
			$strSql .= " ";
			foreach((array)$arrOptions['OR_type'] as $strType) {
				$strSql .= " OR `type` = '".\DB::escapeQueryString($strType)."' ";
			}
			$strSql .= " OR 0)";
		} else {
			$strSql .= " )";
		}

		if(isset($arrOptions['show']) && is_array($arrOptions['show'])) {
			$strSql .= " AND ("; 
			foreach((array)$arrOptions['show'] as $strState) {
				$strSql .= " state = '".\DB::escapeQueryString($strState)."' OR ";
			}
			$strSql .= " 0)"; 
		}

		if(is_array($arrOptions['hide'])) {
			$strSql .= " AND ("; 
			foreach((array)$arrOptions['hide'] as $strState) {
				$strSql .= " state != '".\DB::escapeQueryString($strState)."' OR ";
			}
			$strSql .= " 0)";
		}

		$aResult = DB::getQueryRow($strSql);

		return $aResult;
	}

	public function getPaymentsInPeriod($intFrom, $intUntil) {

		$sSQL = "
			SELECT 
				SUM(`op`.`amount` * IF(od.type = 'credit', -1, 1)) `sum`,
				SUM(`op`.`amount` * (`od`.`price_net`/`od`.`price`) * IF(od.type = 'credit', -1, 1)) `sum_net`,
				COUNT(`op`.`id`) `count`
			FROM
				`office_payments` `op` JOIN 
				`office_documents` `od` ON
					`op`.`document_id` = `od`.`id`
			WHERE
				`op`.`created` BETWEEN ".date("YmdHis", $intFrom)." AND ".date("YmdHis", $intUntil)."
		";
		$aResult = DB::getQueryData($sSQL);

		return $aResult[0];
	}

	public function getDocumentStatsByCustomer($iFrom, $iTill) {

		$sSQL = "
			SELECT
				SUM(IF(`cash_discount_granted`=1,`price_cash_discount`,`price`)) `sum`,
				SUM(IF(`cash_discount_granted`=1,`price_cash_discount_net`,`price_net`)) `sum_net`,
				COUNT(d.id) `count`,
				`customer_id`,
				`c`.`".$this->_arrConfig['field_matchcode']."` `customer`
			FROM
				`office_documents` d LEFT JOIN
				`customer_db_".$this->_arrConfig['database']."` c ON
					d.customer_id = c.id
			WHERE
				`booking_date` BETWEEN '".date("Y-m-d", $iFrom)."' AND '".date("Y-m-d", $iTill)."' AND
				d.active = 1 AND
				(
					`type` = 'account' OR 
					`type` = 'credit' OR 
					`type` = 'cancellation_invoice'
				) AND
				`state` != 'draft'
			GROUP BY
				`customer_id`
			ORDER BY
				`sum_net` DESC
		";
		$aResult = DB::getQueryData($sSQL);

		return $aResult;
	}
	
	public function getOrderStats($iFrom=false, $iUntil=false) {
		
		$sPeriod = "";
		if($iFrom && $iUntil) {
			$sPeriod .= " `od`.`booking_date` BETWEEN '".date("Y-m-d H:i:s", $iFrom)."' AND '".date("Y-m-d H:i:s", $iUntil)."' AND ";
		}

		$aStats = array();
		
		$sSql = "
					SELECT 
						SUM(`price`) `sum`,
						SUM(`price_net`) `sum_net`,
						AVG(`price_net`) `avg_net`,
						COUNT(`id`) `count`
					FROM	
						office_documents AS `od`
					WHERE
						".$sPeriod."
						`active` = 1 AND
						`type` = 'offer' AND
						(
							`state` != 'draft'
						)
					";
		$aData = DB::getQueryData($sSql);
		$aStats['all'] = $aData[0];

		$sSql = "
					SELECT 
						SUM(`price`) `sum`,
						SUM(`price_net`) `sum_net`,
						AVG(`price_net`) `avg_net`,
						COUNT(`id`) `count`
					FROM	
						office_documents AS `od`
					WHERE
						".$sPeriod."
						`active` = 1 AND
						`type` = 'offer' AND
						(
							`state` = 'accepted' OR
							`state` = 'finished'
						)
					";
		$aDataAccepted = DB::getQueryData($sSql);
		$aStats['accepted'] = $aDataAccepted[0];

		$sSql = "
					SELECT 
						SUM(`price`) `sum`,
						SUM(`price_net`) `sum_net`,
						AVG(`price_net`) `avg_net`,
						COUNT(`id`) `count`
					FROM	
						office_documents AS `od`
					WHERE
						".$sPeriod."
						`active` = 1 AND
						`type` = 'offer' AND
						(
							`state` = 'declined'
						)
					";
		$aDataDeclined = DB::getQueryData($sSql);
		$aStats['declined'] = $aDataDeclined[0];
		
		$sSql = "
					SELECT 
						SUM(`price`) `sum`,
						SUM(`price_net`) `sum_net`,
						AVG(`price_net`) `avg_net`,
						COUNT(`id`) `count`
					FROM	
						office_documents AS `od`
					WHERE
						".$sPeriod."
						`active` = 1 AND
						`type` = 'offer' AND
						(
							`state` = 'released'
						)
					";
		$aDataReleased = DB::getQueryData($sSql);
		$aStats['released'] = $aDataReleased[0];
		
		$sSQL = "
					SELECT
						AVG(UNIX_TIMESTAMP(`op`.`date`) - UNIX_TIMESTAMP(`od`.`date`)) AS `average`, 
						COUNT(od.id) AS c
					FROM
						`office_documents` AS `od` LEFT JOIN
						`office_protocol` AS `op` ON
							`od`.`id` = `op`.`document_id` AND
							`op`.`state` = 'accepted'
					WHERE
						".$sPeriod."
						`od`.`type` = 'offer' AND
						`op`.`date` = 
						(
							SELECT
								MAX(`date`)
							FROM
								`office_protocol`
							WHERE
								`document_id` = `od`.`id` AND 
								`state` = 'accepted'
						)
					ORDER BY
						`average` DESC
		";
		$aDataDuration = DB::getQueryData($sSQL);
		$aStats['duration'] = $aDataDuration[0];
		return $aStats;
	}
	
	public function getOpenOffers() {
		$sSql = "
					SELECT 
						d.*,
						d.`price_net` price,						
						UNIX_TIMESTAMP(d.`date`) date, 						
						c.`".$this->_arrConfig['field_matchcode']."` AS `company`
					FROM	
						`office_documents` d LEFT OUTER JOIN
						`customer_db_".$this->_arrConfig['database']."` c ON
							`d`.`customer_id` = `c`.`id`
					WHERE
						`d`.`active` = 1 AND
						`d`.`type` = 'offer' AND
						(
							`d`.`state` = 'released'
						)
					ORDER BY
						`d`.`date` ASC
					";
		$aOpenOffers = DB::getQueryData($sSql);
		return $aOpenOffers;
	}
	
}
