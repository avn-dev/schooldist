<?php

/**
 * @property integer $id 
 * @property string $changed 	
 * @property string $created 	
 * @property integer $active 	
 * @property integer $creator_id 	
 * @property integer $user_id 	
 * @property integer $client_id 	
 * @property integer $school_id 	
 * @property integer $currency_id 	
 * @property string $name 	
 * @property integer $price_package 	
 * @property integer $cost_package 	
 * @property integer $individually_transfer 	
 * @property string $time_from 	
 * @property string $time_until 	
 * @property float $amount_price 	
 * @property float $amount_price_two_way 	
 * @property float $amount_cost
 */
class Ext_Thebing_Transfer_Package extends Ext_Thebing_Basic {

	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'kolumbus_transfers_packages';

	/**
	 * Tabellenalias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'ktrp';

	/**
	 * Fehler wenn Preise innerhalb nicht vorhandener Season berechnet werden sollen
	 *
	 * @var array
	 */
	public static $aSeasonErrors = array();

	protected $_aJoinTables = array(
		'join_providers_transfer'=> array(
			'table'=>'kolumbus_transfers_packages_providers',
			'foreign_key_field'=>'provider_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_providers_accommodation' => [
			'table' => 'ts_transfers_packages_providers_accommodation_providers',
			'foreign_key_field' => 'provider_id',
			'primary_key_field' => 'package_id',
			'autoload' => false
		],
		'join_days'=> array(
			'table'=>'kolumbus_transfers_packages_days',
			'foreign_key_field'=>'day',
			'primary_key_field'=>'package_id',
			'format'=> 'Ext_Thebing_Gui2_Format_Day',
			'autoload' => false
		),
		'join_from_locations'=> array(
			'table'=>'kolumbus_transfers_packages_from_locations',
			'foreign_key_field'=>'location_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_from_accommodation_categories'=> array(
			'table'=>'kolumbus_transfers_packages_from_accommodations_categories',
			'foreign_key_field'=>'category_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_from_accommodation_providers'=> array(
			'table'=>'kolumbus_transfers_packages_from_accommodations_providers',
			'foreign_key_field'=>'provider_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_to_locations'=> array(
			'table'=>'kolumbus_transfers_packages_to_locations',
			'foreign_key_field'=>'location_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_to_accommodation_categories'=> array(
			'table'=>'kolumbus_transfers_packages_to_accommodations_categories',
			'foreign_key_field'=>'category_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_to_accommodation_providers'=> array(
			'table'=>'kolumbus_transfers_packages_to_accommodations_providers',
			'foreign_key_field'=>'provider_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_saisons_prices'=> array(
			'table'=>'kolumbus_transfers_packages_saisons_prices',
			'foreign_key_field'=>'saison_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'join_saisons_costs'=> array(
			'table'=>'kolumbus_transfers_packages_saisons_costs',
			'foreign_key_field'=>'saison_id',
			'primary_key_field'=>'package_id',
			'autoload' => false
		),
		'pdf_templates' => [
			'table' => 'kolumbus_pdf_templates_services',
			'class' => 'Ext_Thebing_Pdf_Template',
			'primary_key_field' => 'service_id',
			'foreign_key_field' => 'template_id',
			'static_key_fields'	=> ['service_type' => 'transfer'],
			'autoload' => false
		]
	);

	protected $_aAttributes = [
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {
		parent::manipulateSqlParts($aSqlParts, $sView);

		// Joins für Filter (nicht als autoload = true)
		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_transfers_packages_providers` `ktpp` ON
				`ktpp`.`package_id` = `ktrp`.`id` LEFT JOIN
			`kolumbus_transfers_packages_saisons_prices` `ktpsp` ON
				`ktpsp`.`package_id` = `ktrp`.`id` LEFT JOIN
			`kolumbus_transfers_packages_saisons_costs` `ktpsc` ON
				`ktpsc`.`package_id` = `ktrp`.`id`
		";

		$aSqlParts['groupby'] .= "`ktrp`.`id`";
		
	}


	/**
	 * @param string $sDescription
	 * @return array
	 */
	static public function getDayList($sDescription = ''){
		$aDays = Ext_Thebing_Util::getDays();
		$aDays[] = L10N::t('Feiertag', $sDescription);
		return $aDays;
	}

	/**
	 * Sucht das erstbeste Transferpaket, das für beide Transfers gültig ist
	 *
	 * @param Ext_TS_Inquiry_Journey_Transfer $oTransferArr
	 * @param Ext_TS_Inquiry_Journey_Transfer $oTransferDep
	 * @param array $aParams
	 * @return Ext_Thebing_Transfer_Package
	 * @throws Exception
	 */
	static public function searchPackageByTwoWayTransfer($oTransferArr, $oTransferDep, $aParams = array()) {

		$aTransfersArr = self::searchPackageByTransfer($oTransferArr, 0, true, $aParams);
		$aTransfersDep = self::searchPackageByTransfer($oTransferDep, 0, true, $aParams);

		if(
			!empty($aTransfersArr) &&
			!empty($aTransfersDep)
		) {
			// Suchen, ob es identische Pakete für An- und Abreise gibt
			foreach((array)$aTransfersArr as $aTransferArr){
				foreach((array)$aTransfersDep as $aTransferDep){
					if($aTransferArr['id'] == $aTransferDep['id']){
						return new self($aTransferArr['id']);
					}
				}
			}	
		}
		// @Todo: Es sollten keine zwei Returntypen in einer Methode existieren, wenn dann überhaupt null an dieser Stelle zurück geben
		return false;

	}

	/**
	 * @param Ext_TS_Inquiry_Journey_Transfer $oTransfer
	 * @param integer $iTransferProvider
	 * @param bool $bReturnAll
	 * @param array $aParams
	 * @return Ext_Thebing_Transfer_Package
	 * @throws Exception
	 */
	static public function searchPackageByTransfer($oTransfer, $iTransferProvider = 0, $bReturnAll = false, $aParams = array()){

		$bPrice = $iTransferProvider == 0; // Preispaket oder Kostenpaket

		if(!$oTransfer instanceof Ext_TS_Service_Interface_Transfer) {
			throw new Exception('This Methode need a Ext_TS_Service_Interface_Transfer interface!');
		}

		/** @var Ext_TS_Inquiry $oInquiry */
		$oInquiry = $oTransfer->getInquiry();

		// Werte definieren
		$iStartLocationId	= $oTransfer->start;
		$iStartLocationId	= explode('_', $iStartLocationId);
		$iStartLocationId	= end($iStartLocationId);
		$sStartType			= $oTransfer->start_type;
		
		$iToLocationId		= $oTransfer->end;
		$iToLocationId		= explode('_', $iToLocationId);
		$iToLocationId		= end($iToLocationId);
		
		$sToType			= $oTransfer->end_type;
		$sTransferTime		= $oTransfer->pickup;
		$iTransferType		= $oTransfer->transfer_type;

		if($sTransferTime == NULL){
			$sTransferTime	= $oTransfer->transfer_time;
		}
		$sTransferDate		= $oTransfer->transfer_date;

		if(
			$sTransferDate == "" ||
			strlen($sTransferDate) != 10
		){
			$sTransferDate = '0000-00-00';
		}

		$bSkipTimeCheck = false;
		if(
			$sTransferTime == "" ||
			$sTransferTime == NULL ||
			(
				strlen($sTransferTime) != 5 &&
				strlen($sTransferTime) != 8
			)
		){
			$sTransferTime = '00:00:00';
			$bSkipTimeCheck = true;
		} else if(strlen($sTransferTime) == 5){
			$sTransferTime .= ':00';
		}

		$sTransferDateTime = $sTransferDate.' '.$sTransferTime;

		// Query Daten holen
//		$oPackage	= new self(0);
//		$aQueryData = $oPackage->getListQueryData();
		$aQueryData = ['data' => []];

		// TIMESTAMP RECHNEN
		$oWDDate = new WDDate();
		$oWDDate->set($sTransferDateTime, WDDate::DB_TIMESTAMP);
		$iTransferDateTime = $oWDDate->get(WDDate::TIMESTAMP);

		// Wochentage auslesen
		$iDay = (int)Ext_Thebing_Util::getWeekDay(2, $sTransferDate, false);

		if(empty($aParams))
		{
			// Inquiry Daten holen
			$oInquiry = $oTransfer->getInquiry();

			// Währung
			$iCurrencyId = $oInquiry->getCurrency();

			// Schule
			$oSchool = $oInquiry->getSchool();
		}
		else
		{
			$iCurrencyId	= $aParams['currency_id'];
		
			$oSchool		= $aParams['school'];
		}
		/** @var Ext_Thebing_School $oSchool */

		// Saison suchen
		$oSaison = new Ext_Thebing_Saison($oSchool, true, false, false, false, false); 

		$oSaisonSearch 	= new Ext_Thebing_Saison_Search();
		if($bPrice){
			$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp($oSchool->id, $iTransferDateTime, $oInquiry->getCreatedForDiscount(), 'transfer', true, true);
		} else {
			$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp($oSchool->id, $iTransferDateTime, $oInquiry->getCreatedForDiscount(), 'transfer', true, false, false, true);
		}
		$iSaisonId 		= $aSaisonData[0]['id'];

		$bHoliday = $oSaison->checkHolidays($sTransferDate, $iSaisonId);

		// Fals Ferien -> Feiertag makieren
		if($bHoliday){
			$iDay = 8;
		}

		if($iSaisonId <= 0){

			// Saison nicht gefunden
			$aError = array();
			$aError['type'] = 'transfer';
			$aError['id'] = $oTransfer->id;
			if(!in_array($aError, self::$aSeasonErrors)){
				self::$aSeasonErrors[] = $aError;
			}

			return false;
		}

		$sWhereAddon = " `ktrp`.`active` = 1 AND ";

		if($iTransferType != 0){
			$sWhereAddon .= ' `ktrp`.`individually_transfer` = 0 AND';
		} else {
			$sWhereAddon .= ' `ktrp`.`individually_transfer` = 1 AND';
		}


		// Saisons
		if($bPrice){
			$sWhereAddon .= ' `join_saisons_prices`.`saison_id` = :saison_id AND';
			$aQueryData['data']['saison_id'] = (int)$iSaisonId;
		} else {
			$sWhereAddon .= ' `join_saisons_costs`.`saison_id` = :saison_id AND';
			$aQueryData['data']['saison_id'] = (int)$iSaisonId;
		}

		// Währung
		$sWhereAddon .= ' `ktrp`.`currency_id` = :currency_id  AND ';
		$aQueryData['data']['currency_id'] = (int)$iCurrencyId;

		// Time
		// Hier wird auch überprüft ob ein Paket gültig ist das über Mitternacht geht z.B. 22Uhr-02Uhr
		if(!$bSkipTimeCheck):
		$sWhereAddon .= " IF(
							`ktrp`.`time_from` <= `ktrp`.`time_until`,
							(:time >=  `ktrp`.`time_from` AND :time <= `ktrp`.`time_until`) ,
							(
								(:time BETWEEN `ktrp`.`time_from` AND :day_end) OR
								(:time BETWEEN :day_start AND `ktrp`.`time_until`)
							)
						) AND ";
		endif;
		
		
		$aQueryData['data']['time'] = $sTransferTime;
		$aQueryData['data']['day_start'] = '00:00:00';
		$aQueryData['data']['day_end'] = '23:59:59';

		// Day
		$sWhereAddon .= ' `join_days`.`day` = :day AND';
		$aQueryData['data']['day'] = (int)$iDay;

		// Location Start
		if(
			$iStartLocationId > 0 &&
			$sStartType == 'location'
		){
			$sWhereAddon .= ' `join_from_locations`.`location_id` = :location_start_id AND';
			$aQueryData['data']['location_start_id'] = (int)$iStartLocationId;
		} else if(
			$iStartLocationId > 0 &&
			$sStartType == 'accommodation'
		){
			$sWhereAddon .= ' `join_from_accommodation_providers`.`provider_id` = :location_start_id AND';
			$aQueryData['data']['location_start_id'] = (int)$iStartLocationId;
		} else if(
			$iStartLocationId <= 0 &&
			$sStartType == 'accommodation'
		){
			if(empty($aParams))
			{
				$aInquiryAccommodations = $oInquiry->getAccommodations(true);

				$oInquiryAccommodation = reset($aInquiryAccommodations);

				if($oInquiryAccommodation)
				{
					$iStartLocationId = $oInquiryAccommodation->accommodation_id;
				}
			}
			else
			{
				$iStartLocationId = $aParams['min_acc_id'];
			}

			if($iStartLocationId){
				$sWhereAddon .= ' `join_from_accommodation_categories`.`category_id` = :location_start_id AND';
				$aQueryData['data']['location_start_id'] = (int)$iStartLocationId;
			} else {
				$sWhereAddon .= ' /* 1 */ 1 = 0 AND';
			}
			
		}

		// Location End
		if(
			$iToLocationId > 0 &&
			$sToType == 'location'
		){
			$sWhereAddon .= ' `join_to_locations`.`location_id` = :location_to_id AND';
			$aQueryData['data']['location_to_id'] = (int)$iToLocationId;
		} else if(
			$iToLocationId > 0 &&
			$sToType == 'accommodation'
		){
			$sWhereAddon .= ' `join_to_accommodation_providers`.`provider_id` = :location_to_id AND';
			$aQueryData['data']['location_to_id'] = (int)$iToLocationId;
		} else if(
			$iToLocationId <= 0 &&
			$sToType == 'accommodation'
		){

			if(empty($aParams))
			{
				$aInquiryAccommodations = $oInquiry->getAccommodations(true);
				$oInquiryAccommodation = reset($aInquiryAccommodations);

				if($oInquiryAccommodation)
				{
					$iToLocationId = $oInquiryAccommodation->accommodation_id;
				}
			}
			else
			{
				$iToLocationId = $aParams['max_acc_id'];
			}

			if($iToLocationId){
				$sWhereAddon .= ' `join_to_accommodation_categories`.`category_id` = :location_to_id AND';
				$aQueryData['data']['location_to_id'] = (int)$iToLocationId;
			} else {
				$sWhereAddon .= ' /* 2 */ 1 = 0 AND';
			}
		}

		if(!$bPrice) {
			$aQueryData['data']['provider_id'] = (int)$iTransferProvider;
			if(
				$oTransfer instanceof Ext_TS_Inquiry_Journey_Transfer &&
				$oTransfer->provider_type === 'accommodation'
			) {
				$sWhereAddon .= ' `join_providers_accommodation`.`provider_id` = :provider_id AND';
			} else {
				$sWhereAddon .= ' `join_providers_transfer`.`provider_id` = :provider_id AND';
			}
		}

		// School/Client id
		$sWhereAddon .= ' `ktrp`.`school_id` = :school_id ';
		$aQueryData['data']['school_id'] = (int)$oSchool->id;

//		$aQueryData['sql'] = str_replace('WHERE', 'WHERE '.$sWhereAddon, $aQueryData['sql']);
//
//		$aResult = DB::getPreparedQueryData($aQueryData['sql'], $aQueryData['data']);

		$sSql = self::buildTransferPackageSearchQuery($sWhereAddon);
		$aResult = DB::getPreparedQueryData($sSql, $aQueryData['data']);

		if(empty($aResult)){
			return false;
		}

		if($bReturnAll){
			return $aResult;
		}

		// Es kann immer nur EIN Packet gültig sein, sonst Benutzerfehler
		$aPackage = reset($aResult);
		$iPackage = (int)$aPackage['id'];

		// TODO Könnte man mal auf getObjectFromArray() umstellen, $bReturnAll muss aber dann angepasst werden
		return self::getInstance($iPackage);

	}

	/**
	 * Mini-Querybuilder für JoinTables, da früher getListQueryData dafür benutzt wurde,
	 * autoload = true hat aber bei vielen Transferpaketen (11 Joins) die Liste lahmgelegt
	 *
	 * @param $sWhere
	 * @return string
	 */
	private static function buildTransferPackageSearchQuery($sWhere) {

		$sJoins = "";
		$oSelf = new self();
		foreach($oSelf->_aJoinTables as $sKey => $aJoinTable) {
			if(strpos($sWhere, '`'.$sKey.'`') !== false) {
				$sJoins .= " INNER JOIN
					`{$aJoinTable['table']}` `{$sKey}` ON
						`{$sKey}`.`{$aJoinTable['primary_key_field']}` = `ktrp`.`id`
				";
			}
		}

		$sSql = "
			SELECT
				`ktrp`.`id`
			FROM
				`kolumbus_transfers_packages` `ktrp`
				{$sJoins}
			WHERE
				{$sWhere}
		";

		return $sSql;

	}

	/**
	 * Liefert eine Beschreibung wenn das Packet ge-specialt wird
	 *
	 * @param int $iSchoolId
	 * @param string $sDisplayLanguage
	 * @return string
	 */
	public function getSpecialInfo($iSchoolId, $sDisplayLanguage) {

		$sName = \Ext_TC_Placeholder_Abstract::translateFrontend('Vergünstigung für:', $sDisplayLanguage) . ' ' . $this->name;

		return $sName;
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			// Klick auf All + verhindern
			if(count($this->pdf_templates) > System::d('ts_max_attached_additional_docments', Ext_Thebing_Document::MAX_ATTACHED_ADDITIONAL_DOCUMENTS)) {
				$mValidate = ['pdf_templates' => 'TOO_MANY'];
			}
		}

		return $mValidate;

	}

}
