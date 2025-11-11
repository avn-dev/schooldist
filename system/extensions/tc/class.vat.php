<?php 

class Ext_TC_Vat extends Ext_TC_Basic {

	const MODE_INCLUSIVE = 'inclusive';

	const MODE_EXCLUSIVE = 'exclusive';

	protected $_sTable = 'tc_vat_rates';
	protected $_sTableAlias = 'tc_vr';

	// Caching
	public static $aGetDefaultTaxCategory = array();

	protected static $aCache = array();

	/**
	 * @var array
	 */
	protected $_aAttributes = [
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	protected $_aJoinTables = array(
		'tc_vr_i18n' => array(
			'table' => 'tc_vat_rates_i18n',
			'foreign_key_field' => array('language_iso', 'note'),
			'primary_key_field' => 'vat_rate_id',
		)
	);

	protected $_aJoinedObjects = array(
		'rate_values' => array(
			'class' => 'Ext_TC_Vat_Value',
			'key' => 'rate_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		)
	);

	public function getShort(): string {
		return $this->short;
	}

	public function getNote($language = null) {
		return $this->getI18NName('tc_vr_i18n', 'note', $language);
	}

	public static function getSelectOptions(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true);
		return $aList;
	}

	public static function getSelectOptionsShort(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true, 'short');
		return $aList;
	}

	public static function getCategories($bForSelect=true, $iSchoolId=0, $sDate=false) {

		if(!$sDate) {
			$sDate = date('Y-m-d');
		}

		$sCacheKey = __METHOD__.'_'.$iSchoolId.'_'.$sDate;
		
		if(!isset(self::$aCache[$sCacheKey])) {

			$sSql = "
				SELECT
					`katc`.*,
					(
						SELECT
							`kat`.`rate`
						FROM
							`tc_vat_rates_values` `kat` 
						WHERE
							`katc`.`id` = `kat`.`rate_id` AND
							`kat`.`active` = 1 AND
							(
								:date BETWEEN `kat`.`valid_from` AND `kat`.`valid_until` OR
								(
									`kat`.`valid_until` = '0000-00-00' AND
									:date >= `kat`.`valid_from`
								) OR
								(
									`kat`.`valid_from` = '0000-00-00' AND
									`kat`.`valid_until` = '0000-00-00'
								)
							)
						ORDER BY
							`kat`.`valid_from` DESC
						LIMIT 1
					) `rate`
				FROM
					`tc_vat_rates` `katc`
				WHERE
					`katc`.`active` = 1
				GROUP BY
					`katc`.`id`
					";
			$aSql['date'] = $sDate;

			self::$aCache[$sCacheKey] = DB::getPreparedQueryData($sSql, $aSql);

		}

		$aResult = self::$aCache[$sCacheKey];

		if($bForSelect) {
			$aSelect = array();
			foreach((array)$aResult as $aData){
				$aSelect[$aData['id']] = $aData['name'].' ('. $aData['short'].')';
			}
			return $aSelect;
		} else {
			return $aResult;
		}

	}

	/**
	 * Diese Funktion bekommt das "[item]" Array einer Buchung und erweitert jede Rechnungsposition die entsprechenden Steuerbeträge
	 *
	 * @TODO Methode sollte zurück nach School
	 *
	 * @param array $aItems
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param string $sLanguage
	 * @param Ext_Thebing_Inquiry_Document_Version $oVersionMock
	 * @return array
	 */
	public static function addTaxRows($aItems, &$oInquiry, $sLanguage='', $oVersionMock=null) {

		// Schuleinstellung für Steuern
		$oSchool = $oInquiry->getSchool();
		//$iTax = (int)$oSchool->ext_341;
		//$aExclusive = (array)json_decode ($oSchool->ext_342);
		$aGeneral	= array();
		$oVersion	= null;
		$iTax		= 0; // 0 = keine Steuern, 1 = Inklusuv, 2 = exklusiv
		$aBack		= array();
		
		if(count($aItems) > 0){
			$aFirstItem = reset($aItems);
			if(
				isset($aFirstItem['version_id']) &&
				(int)$aFirstItem['version_id'] > 0
			){
				$oVersion = Factory::getInstance('Ext_TC_Document_Version', (int)$aFirstItem['version_id']);
			} else if($oVersionMock){
				$oVersion = $oVersionMock;
			}
		}
		
		if(is_object($oVersion)){
			$iTax = $oVersion->tax;
		}


		if($iTax < 1) { // Version hat keine Steuern
			$aBack['items'] = $aItems;
			$aBack['general'] = $aGeneral;
			return $aBack;
		}

		if($oInquiry instanceof Ext_TS_Inquiry) {
			$dVatDate = Ext_TS_Vat::getVATReferenceDateByDate($oSchool, new \Carbon\Carbon($oInquiry->service_from), new \Carbon\Carbon($oInquiry->service_until));
		} else {
			$dVatDate = new \Carbon\Carbon();
		}
		
		$iLine = 1;
		foreach((array) $aItems as $iPosition => $aItem) {
			
			// Jede Rechnungsposition sollte die Steuern mit in den Eigenschaften haben
			$aItems[$iPosition]['amount_vat']		= 0;
			$aItems[$iPosition]['amount_net_vat']	= 0;

			// gespeicherter Steuersatz
			$fTax = (float) $aItem['tax'];

			$iTaxCategoryId = (int)$aItem['tax_category'];

			$oTaxCategory = Ext_TS_VAT::getInstance($iTaxCategoryId);
			$sNote = $oTaxCategory->getI18NName('tc_vr_i18n', 'note', $sLanguage);
			
			// Neuer Steuersatz der benutzt werden würde
			$fTaxNew = 0;		
			if($iTaxCategoryId > 0) {
				$fTaxNew = self::getTaxRate($iTaxCategoryId, $oSchool->id, $dVatDate->toDateString());
			}

			$aItems[$iPosition]['tax_new']			= (float)$fTaxNew;

			if (!isset($aGeneral[$iTaxCategoryId])) {
				$aGeneral[$iTaxCategoryId] = array_fill_keys(['amount_vat', 'amount_net_vat', 'amount_commission_vat', 'amount', 'amountNet', 'amountProv'], 0);
			}

			if(
				$oVersionMock &&
				$iTax > 0	 &&
				$fTaxNew > 0	
			){
				$fTax = $fTaxNew;
			}
			
			if(
				$iTaxCategoryId > 0 &&
				$iTax > 0
			){
				$fAmount		= $aItems[$iPosition]['amount'];
				$fAmountNet		= $aItems[$iPosition]['amount_net'];
				$fAmountProv	= $aItems[$iPosition]['amount_provision'];
				////////////////////////////////////////////////////////////////////

				switch($iTax) {
					case 1:
						// Inclusive Steuern
						$aTaxAmount			= self::calculateInclusiveTaxes($fAmount, $fTax);
						$aTaxAmountNet		= self::calculateInclusiveTaxes($fAmountNet, $fTax);
						$aTaxAmountProv		= self::calculateInclusiveTaxes($fAmountProv, $fTax);

						$aItems[$iPosition]['amount_vat']				= $aTaxAmount['amount_tax_diff'];
						$aItems[$iPosition]['amount_net_vat']			= $aTaxAmountNet['amount_tax_diff'];
						$aItems[$iPosition]['amount_commission_vat']	= $aTaxAmountProv['amount_tax_diff'];
						
						// Von Welchem gesamtbetrag die Prozentsätze berechnet wurden
						if($aItem['onPdf'] == 1) {
							$aGeneral[$iTaxCategoryId]['tax_category'] = $iTaxCategoryId;
							$aGeneral[$iTaxCategoryId]['tax_rate'] = $fTax;
							$aGeneral[$iTaxCategoryId]['amount_vat']					+= $fAmount;
							$aGeneral[$iTaxCategoryId]['amount_net_vat']				+= $fAmountNet;
							$aGeneral[$iTaxCategoryId]['amount_commission_vat']		+= $fAmountProv;

							$aGeneral[$iTaxCategoryId]['description']			= \Ext_TC_Placeholder_Abstract::translateFrontend('inkl. VAT', $sLanguage) . ' ' . Ext_TC_Factory::executeStatic('Ext_TC_Format', 'Number', array($fTax, null, null, false, 3)) . '%';
							$aGeneral[$iTaxCategoryId]['amount']				+= $aTaxAmount['amount_tax_diff'];
							$aGeneral[$iTaxCategoryId]['amountNet']			+= $aTaxAmountNet['amount_tax_diff'];
							$aGeneral[$iTaxCategoryId]['amountProv']			+= $aTaxAmountProv['amount_tax_diff'];
							$aGeneral[$iTaxCategoryId]['note'] = $sNote;
							$aGeneral[$iTaxCategoryId]['lines'][] = $iLine;
						}
						
						break;
					case 2:
						// exclusive Steuern
						$aTaxAmount			= self::calculateExclusiveTaxes($fAmount, $fTax);
						$aTaxAmountNet		= self::calculateExclusiveTaxes($fAmountNet, $fTax);
						$aTaxAmountProv		= self::calculateExclusiveTaxes($fAmountProv, $fTax);

						// Spalte mit % Je Buchung
						$aItems[$iPosition]['amount_vat']				= $aTaxAmount['amount'];
						$aItems[$iPosition]['amount_net_vat']			= $aTaxAmountNet['amount'];
						$aItems[$iPosition]['amount_commission_vat']	= $aTaxAmountProv['amount'];

						// Je Prozentsatz eine Extrazeile unten drunter
						if($aItem['onPdf'] == 1) {
							$aGeneral[$iTaxCategoryId]['tax_category'] = $iTaxCategoryId;
							$aGeneral[$iTaxCategoryId]['tax_rate'] = $fTax;
							$aGeneral[$iTaxCategoryId]['description']			= \Ext_TC_Placeholder_Abstract::translateFrontend('zzgl. VAT', $sLanguage) . ' ' . Ext_TC_Factory::executeStatic('Ext_TC_Format', 'Number', array($fTax, null, null, false, 3)) . '%';

							// Von Welchem gesamtbetrag die Prozentsätze berechnet wurden
							$aGeneral[$iTaxCategoryId]['amount_vat']					+= $fAmount;
							$aGeneral[$iTaxCategoryId]['amount_net_vat']				+= $fAmountNet;
							$aGeneral[$iTaxCategoryId]['amount_commission_vat']		+= $fAmountProv;

							// Beträge Ja Prozentatz
							$aGeneral[$iTaxCategoryId]['amount']				+= $aTaxAmount['amount'];
							$aGeneral[$iTaxCategoryId]['amountNet']			+= $aTaxAmountNet['amount'];
							$aGeneral[$iTaxCategoryId]['amountProv']			+= $aTaxAmountProv['amount'];
							$aGeneral[$iTaxCategoryId]['note'] = $sNote;
							$aGeneral[$iTaxCategoryId]['lines'][] = $iLine;
						}
						
						break;
				}
			}
			
			if($aItem['onPdf'] == 1) {
				$iLine++;
			}
			
		}

		$aBack['items'] = $aItems;
		$aBack['general'] = $aGeneral;

		return $aBack;
	}
	
	public static function addGroupTaxRows($aItems, &$oInquiry, $sLanguage='') {

		// Steuereinträge der Haupt Items
		$aItems = self::addTaxRows($aItems, $oInquiry, $sLanguage);

		$aGeneral = $aItems['general'];
		$aItems = $aItems['items'];		

		// steuereinträge für die unter-items
		foreach((array)$aItems as $iKey => $aItem){
			if(
				isset($aItem['items']) &&
				is_array($aItem['items'])
			) {
				$aItemTemp = self::addTaxRows($aItem['items'], $oInquiry, $sLanguage);
				$aItems[$iKey]['items'] = $aItemTemp['items'];
			}
		}
		
		$aBack = array();
		$aBack['items'] = $aItems;
		$aBack['general'] = $aGeneral;

		return $aBack;
		
	}
	
	/*
	 * Funktion liefert den aktuellen Steuersatz zu einer Buchungs ID zurück
	 */
	public static function getTaxRate($iRateId, $iSchool, $sDate=null) {

		if($sDate === null) {
			$sDate = date('Y-m-d');
		}

		if(!isset(self::$aCache['tax_rate'][$iRateId][$sDate])) {

			$sSql = "SELECT
						`rate`
					FROM
						`tc_vat_rates_values` AS `kat`
					WHERE
						`kat`.`active` = 1 AND
						`kat`.`rate_id` = :rate_id AND
						`kat`.`valid_from` <= :time AND
						(
							`kat`.`valid_until` >= :time OR
							`kat`.`valid_until` = '0000-00-00'
						)
					ORDER BY
						`kat`.`valid_from` DESC
					LIMIT 1
					";
			$aSql = array();
			#$aSql['idSchool'] = (int) $iSchool;
			#$aSql['idClient'] = (int) $iClient;
			$aSql['rate_id'] = (int) $iRateId;
			$aSql['time'] = $sDate;
			
			$aResult = DB::getQueryRow($sSql,$aSql);

			$iTaxRate = 0;						
			if(!empty($aResult)){
				$iTaxRate = (float)$aResult['rate'];
			}

			self::$aCache['tax_rate'][$iRateId][$sDate] = $iTaxRate;

		}

		return self::$aCache['tax_rate'][$iRateId][$sDate];
	}
	
	/*
	 * Funktion berechnet die Inklusivsteuern eines Betrages ($fAmount ist brutto)
	 * @todo Die Funktionen machen beide das gleich für netto/brutto Beträge aber geben unterschiedliche Daten zurück :-(
	 */
	public static function calculateInclusiveTaxes($fAmount = 0, $fTax = 0){

		$aBack = array();
		if($fAmount == 0 && $fTax == 0){
			$aBack['amount_tax'] = $fAmount;
			$aBack['amount_tax_diff'] = $fTax;
			return $aBack;
		}
		$fAmountTax = $fAmount * (100 /  (100 + $fTax));
		$fAmountTaxDiff = $fAmountTax * ($fTax / 100);

		$aBack['amount_tax'] = round($fAmountTax, 5);
		$aBack['amount_tax_diff'] = round($fAmountTaxDiff, 5);

		return $aBack;
	}
	
	/*
	 * Funktion berechnet die Exklusivsteuern eines Betrages ($fAmount ist netto)
	 * @todo Die Funktionen machen beide das gleich für netto/brutto Beträge aber geben unterschiedliche Daten zurück :-(
	 */
	public static function calculateExclusiveTaxes($fAmount = 0, $fTax = 0){

		$aBack = array();

		// 5 Nachkommastellen, da es sonst zu rundungsfehlern kommt beim summieren
		$aBack['amount'] = round(($fAmount * ($fTax / 100)), 5);
		
		return $aBack;
	}

	/**
	 * Funktion liefert die Default Steuerkategorie einer Rechnungsposition anhand der type_id und der Tabelle wo die Pos. zu finden ist
	 *
	 * WURDE ERSETZT MIT Ext_TS_Vat::getDefaultCombination()
	 *
	 * @deprecated
	 *
	 * @param int $iItemId
	 * @param string $sTable
	 * @param int $iSchool_id
	 * @return mixed
	 */
//	public function getDefaultTaxCategory($iItemId, $sTable, $iSchool_id, $oInquiry = null){
//		global $user_data, $objWebDynamics;
//
//		$sKey = (int)$iItemId . $sTable . (int)$iSchool_id;
//
//		if(!isset(self::$aGetDefaultTaxCategory[$sKey])){
//
//			$iTaxCategoryId = 0;
//
//            $sSql = "SELECT
//                            `tax_kategory_id`
//                        FROM
//                            `kolumbus_accounting_allocation_accounts`
//                        WHERE
//                            `active` = 1 AND
//                            `allocation_id` = :item_id AND
//                            `allocation_db` = :table AND
//                            `idSchool` = :school_id AND
//                            `idClient` = :client_id AND
//                            `type` = 'return'
//                        LIMIT 1
//                    ";
//            $aSql = array();
//            $aSql['item_id'] = (int)$iItemId;
//            $aSql['table'] = $sTable;
//            $aSql['school_id'] = (int)$iSchool_id;
//            $aSql['client_id'] = (int)$user_data['client'];
//
//            $aResult = DB::getPreparedQueryData($sSql,$aSql);
//            $oDB = DB::getDefaultConnection();
//
//            if(!empty($aResult)){
//                $iTaxCategoryId = $aResult[0]['tax_kategory_id'];
//            }
//
//            self::$aGetDefaultTaxCategory[$sKey] = $iTaxCategoryId;
//
//		}
//
//        if($oInquiry){
//            $aHookData = array('service_table' => &$sTable, 'service_id' => $iItemId, 'inquiry' => $oInquiry, 'tax_category' => &self::$aGetDefaultTaxCategory[$sKey]);
//            \System::wd()->executeHook('ts_inquiry_document_build_items_tax', $aHookData);
//        }
//
//        return self::$aGetDefaultTaxCategory[$sKey];
//	}

	// Liefert ein Array aller Steuerkategorien und der jeweiligen aktuellen Tax-rate
	public static function getTaxCategoryRates($iSchool = null, $sDate=false) {

		if(empty($iSchool)) {
			$iSchool = $_SESSION['sid'];
		}

		if(!$sDate) {
			$sDate = date('Y-m-d');
		}

		$aTaxCategories = (array)self::getCategories(false, $iSchool, $sDate);
		$aBack = array();
		foreach($aTaxCategories as $aTaxCategory){
			$aBack[] = array($aTaxCategory['id'], $aTaxCategory['rate']);
		}

		return $aBack;
	}
	
}
