<?php

/**
 * @todo: getModel, getSingleton, getSchoolConfig einbauen
 * Test
 */
class Ext_Thebing_System {
	
	private static $aConfig = array();
	
	public static function clearConfigCache() {
		self::$aConfig = array();
	}

	/**
	 * Client Konfiguration per key bekommen
	 * @param <string> $sConfigKey
	 * @return mixed
	 */
	public static function getConfig($sConfigKey) {

		if(!isset(self::$aConfig[$sConfigKey])) {

			$oClient = self::getClient();
			$mValue = $oClient->getConfig($sConfigKey);

			$aHookData = array('key' => $sConfigKey, 'value' => &$mValue);
			\System::wd()->executeHook('ts_system_get_config', $aHookData);

			self::$aConfig[$sConfigKey] = $mValue;
		}

		return self::$aConfig[$sConfigKey];
	}

	/**
	 * @deprecated
	 *
	 * System Client bekommen
	 * @return Ext_Thebing_Client
	 */
	public static function getClient() {
		return Ext_Thebing_Client::getFirstClient();
	}

	/**
	 * @deprecated
	 *
	 * System Client ID bekommen
	 * @return int 
	 */
	public static function getClientId() {
		return Ext_Thebing_Client::getClientId();
	}

	/**
	 * Query Part für client Einstellung Schüler ab Rechnung/Proforma anzeigen
	 */
	public static function getWhereFilterStudentsByClientConfig($sTableAlias): string {

		$iConfig = self::getConfig('show_customer_without_invoice');

		$sWhere = " AND ".$sTableAlias.".`confirmed` > 0";

		if ($iConfig == 0) {
			$sWhere .= " AND (".$sTableAlias.".`has_invoice` = 1 OR ".$sTableAlias.".`has_proforma` = 1)";
		} elseif($iConfig == 2) {
			$sWhere .= " AND ".$sTableAlias.".`has_invoice` = 1";
		}

		return $sWhere;

	}

	/**
	 * Abfragen ob Client Buchhaltung Modul hat
	 * @return bool 
	 */
	public static function hasAccounting()
	{
		// Buchhaltung gibts nicht mehr
		return false;
		
		$iValueConfig = self::getConfig('accounting');

		if($iValueConfig == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Überprüfen ob man sich in der "all schools" Ansicht befindet
	 * 
	 * @return bool
	 */
	public static function isAllSchools()
	{
		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId	= (int)$oSchool->id;
		
		if($iSchoolId > 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Wenn man in all_schools ist alle Währungen, ansonsten der aktuellen Schule
	 * 
	 * @return array 
	 */
	public static function getCurrencyList()
	{
		if(self::isAllSchools())
		{			
			$oClient	= self::getClient();
			
			$aSchools	= $oClient->getSchoolListByAccess(false, true);
} 
		else
		{
			$oSchool	= Ext_Thebing_School::getSchoolFromSession();
			
			$aSchools	= array($oSchool);
		}
		
		$aCurrency		= array();
		
		foreach($aSchools as $oSchool)
		{
			$aSchoolCurrency = $oSchool->getSchoolCurrencyList();
			
			$aCurrency	+= $aSchoolCurrency;
		}
		
		return $aCurrency;
	}
	
	/**
	 *
	 * @param bool $bPrepareForSelect
	 * @return array 
	 */
	public static function getInboxList($bPrepareForSelect = false, $bCheckAccess = false)
	{
		$oClient		= self::getClient();
		
		$aInboxList		= $oClient->getInboxList($bPrepareForSelect, $bCheckAccess);
		
		return $aInboxList;
	}
	
    /**
     * 
     * @param bool $bCheckAccess
     * @param bool $bLabelItem
     * @return array
     */
	public static function getInboxListForSelect(bool $bCheckAccess = false, bool $bLabelItem = true): array
	{
		$oClient		= self::getClient();

		$aInboxList		= $oClient->getInboxList(true, $bCheckAccess);

        if($bLabelItem) {
           $aInboxList = Ext_Gui2_Util::addLabelItem($aInboxList, L10N::t('Inbox'));
        }
        
		return $aInboxList;
	}
    
	/**
	 * Alle Firmen im System
	 * 
	 * @param bool $bForSelect
	 * @return \TsAccounting\Entity\Company[]|array
	 */
	public static function getAccountingCompanies($bForSelect = false, $bAsObject = false) {

		if($bAsObject) {
			$bForSelect = false;
		}

		// Query benutzen, da Repository kein ORDER BY kann
		$sSql = "
			SELECT
				*
			FROM
				`ts_accounting_companies`
			WHERE
				`active` = 1
			ORDER BY
				`position`, `name`
		";

		$aCompanies = array_map(function($aRow) {
			return \TsAccounting\Entity\Company::getObjectFromArray($aRow);
		}, (array)DB::getQueryRows($sSql));

		if($bForSelect) {
			$aReturn = [];
			foreach($aCompanies as $oCompany) {
				$aReturn[$oCompany->id] = $oCompany->name;
			}

			return $aReturn;
		}
		
		return $aCompanies;
	}
	
	/**
	 *  
	 * @return bool 
	 */
	public static function hasAccountingCompanies()
	{
		if(Ext_Thebing_Access::hasRight('thebing_companies'))
		{
			$aCompanies = self::getAccountingCompanies();

			if(empty($aCompanies))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Überprüfen ob Inboxen benutzt werden
	 * 
	 * @return bool 
	 */
	public static function hasInbox()
	{
		$oClient = self::getClient();
		
		return $oClient->checkUsingOfInboxes();
	}
	
	public static function useFullPayment()
	{
		$bFullPayment = true;

		System::wd()->executeHook('use_full_payment', $bFullPayment);
		
		return $bFullPayment;
	}
	
	public static function ignoreServicePaymentCheck()
	{
		$bIgnoreServicePaymentCheck = false;

		System::wd()->executeHook('ignore_service_payment_check', $bIgnoreServicePaymentCheck);
		
		return $bIgnoreServicePaymentCheck;
	}
} 
