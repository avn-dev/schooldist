<?php

class Ext_TS_NumberRange extends Ext_TC_NumberRange {
	
	/** 
	 * Inbox für die der Nummernkreis geholt werden soll
	 * @var Ext_Thebing_Client_Inbox 
	 */
	protected static $_oInbox = null;
	
	protected static $companyId = null;
	protected static $currencyId = null;

	public static function setCompany($companyId) {
		self::$companyId = $companyId;
	}
	
	public static function setCurrency($currencyId) {
		self::$currencyId = $currencyId;
	}
	
	/**
	 * setzt die Inbox für die der Nummernkreis geholt werden soll
	 * @param Ext_Thebing_Client_Inbox $oInbox
	 */
	public static function setInbox($oInbox) {
		self::$_oInbox = $oInbox;
	}
	
	/**
	 * Inbox des Nummernkreis
	 * @return Ext_Thebing_Client_Inbox
	 */
	public static function getInbox() {
		$oInbox = self::$_oInbox;
		return $oInbox;
	}
	
	/**
	 * bittet die Möglichkeit, den Query der Numberranges zu manipulieren
	 * @param string $sSql
	 * @param array $aSql
	 */
	public static function manipulateSqlNumberRangeQuery(&$sSql, &$aSql) {

		if(!empty(self::$companyId)) {
			
			$sWhere = " 
				LEFT JOIN `ts_number_ranges_allocations_sets_companies` `ts_nrasc` ON
					`ts_nrasc`.`set_id` = `tc_nras`.`id` 					
				WHERE
					(
						`ts_nrasc`.`company_id` = :company_id OR
						`ts_nrasc`.`company_id` IS NULL
					) AND
			";
			
			$sSql = str_replace('WHERE', $sWhere, $sSql);
			
			$aSql['company_id'] = (int)self::$companyId;
			
		}
		
		if(!empty(self::$currencyId)) {
			
			$sWhere = " 
				LEFT JOIN `ts_number_ranges_allocations_sets_currencies` `ts_nrascu` ON
					`ts_nrascu`.`set_id` = `tc_nras`.`id`					
				WHERE
					(
						`ts_nrascu`.`currency_id` = :currency_id OR
						`ts_nrascu`.`currency_id` IS NULL
					) AND
			";
			
			$sSql = str_replace('WHERE', $sWhere, $sSql);
			
			$aSql['currency_id'] = (int)self::$currencyId;

		}
		
		if(
			self::$_oInbox !== null &&
			self::$_oInbox->exist()
		) {
			
			$sWhere = " 
				INNER JOIN `ts_number_ranges_allocations_sets_inboxes` `ts_nrasi` ON
					`ts_nrasi`.`set_id` = `tc_nras`.`id` AND
					`ts_nrasi`.`inbox_id` = :inbox_id
				WHERE
			";
			
			$sSql = str_replace('WHERE', $sWhere, $sSql);
			
			$aSql['inbox_id'] = self::$_oInbox->id;
			
		}
				
	}
	
	public static function getReceiptAllocations() {
		$aAllocations = Ext_TS_NumberRange_Allocation::getReceiptAllocations(self::$_oInbox);
		return $aAllocations;
	}
	
	/**
	 * Nummernkreise anhand der Kategorie laden, funktioniert nur bei Nummernkreisen ohne "Zuweisung", wie 
	 * z.B. die Nummernkreise mit der Kategorie "Konten"
	 * 
	 * @param string $sCategory
	 * @param bool $bForSelect
	 * @param bool $bAsObject
	 * @return array
	 */
	public static function getNumberrangesByCategory($sCategory, $bForSelect = true, $bAsObject = false)
	{
		$sSql = '
			SELECT
				*
			FROM
				`tc_number_ranges`
			WHERE
				`active` = 1 AND
				`category` = :category
		';
		
		$aSql = array(
			'category' => $sCategory
		);
		
		$aResult	= DB::getPreparedQueryData($sSql, $aSql);
		
		$aBack		= array();
		
		foreach($aResult as $aRowData)
		{
			if($bForSelect)
			{
				$mData = $aRowData['name'];
			}
			elseif($bAsObject)
			{
				$mData = self::getInstance($aRowData['id']);
			}
			else
			{
				$mData = $aRowData;
			}
			
			$aBack[$aRowData['id']] = $mData;
		}
		
		return $aBack;
	}
	
}
