<?php

use \Carbon\Carbon;

abstract class Ext_TS_Inquiry_Journey_Service extends Ext_TS_Service_Abstract {

	use \Core\Traits\WdBasic\TransientTrait;

	/**
	 * Flag für vom RegForm V3 erstellte Services (nur flüchtig)
	 */
	const TRANSIENT_FORM_SERVICE = 'form_service';
	
	public function __construct($iDataID = 0, $sTable = null) {
		
		parent::__construct($iDataID, $sTable);

		$this->_aJoinedObjects['journey'] = array(
			'class' => 'Ext_TS_Inquiry_Journey',
			'key'	=> 'journey_id'
		);

	}

	/**
	 * @var null|Ext_TS_Inquiry_Abstract
	 */
	protected $_oInquiry = null;

	public $aErrors;

	abstract public function getKey();

	/**
	 * @TODO Sollte eigentlich abstrakt sein, wurde aber nie korrekt implementiert
	 *
	 * @return Ext_Thebing_Teacher_Payment[]|Ext_Thebing_Accommodation_Payment[]
	 */
	public function getPayments() {
		return [];
	}

	/**
	 *
	 * @return Ext_TS_Inquiry_Journey
	 */
	public function getJourney() {
		$oJourney = $this->getJoinedObject('journey');
		return (object)$oJourney;
	}

	/**
	 * @deprecated
	 */
	public function getFrom() {
		
		if(!isset($this->_aData['from'])) {
			return null;
		}
		
		$sFrom = $this->from;
		//// wegen index
		// 0000-00-00 darf es nicht geben und ist gleichbedeuten mit
		// existiert nicht daher "null"
		if($sFrom == '0000-00-00'){
			$sFrom = null;
		}
		return $sFrom;
	}

	/**
	 * @deprecated
	 */
	public function getUntil(){
		
		if(!isset($this->_aData['until'])) {
			return null;
		}
		
		$sUntil = $this->until;
		// wegen index
		// 0000-00-00 darf es nicht geben und ist gleichbedeuten mit
		// existiert nicht daher "null"
		if($sUntil == '0000-00-00'){
			$sUntil = null;
		}
		return $sUntil;
	}

	public function createPeriod(): \Spatie\Period\Period {

		$from = $this->getFrom();
		$until = $this->getUntil();

		if (!$from || !$until) {
			throw new \DomainException('Invalid from or until for service period');
		}
		
		$school = $this->getSchool();

		// Zeitzone muss für den Vergleich korrekt gesetzt werden
		return \Spatie\Period\Period::make(\Carbon\Carbon::parse($from, $school->getTimezone()), \Carbon\Carbon::parse($until, $school->getTimezone()), \Spatie\Period\Precision::DAY());
	}

	/**
	 * @deprecated
	 */
    public function getWeeks(){
        if(!isset($this->_aData['weeks'])) {
			return null;
		}
        return $this->weeks;
    }

	/**
	 * Gibt die Kommunikationssprache des zugehörigen Kunden zurück
	 * @return <string>
	 */
	public function getLanguage() {
		$oInquiry = $this->getInquiry();
		$sLanguage = $oInquiry->getLanguage();
		return $sLanguage;
	}

	/**
	 * @deprecated
	 * {@inheritdoc}
	 */
	public function getInquiry() {

		if($this->_oInquiry === null) {
			$this->setInquiry($this->getJourney()->getInquiry());
		}

		return $this->_oInquiry;

	}

	/**
	 * @deprecated
	 * @param Ext_TS_Inquiry_Abstract $oInquiry 
	 */
	public function setInquiry(Ext_TS_Inquiry_Abstract $oInquiry) {
		$this->_oInquiry = $oInquiry;
	}

	/**
	 * Liefert die Schule.
	 *
	 * @return Ext_Thebing_School
	 */
	public function getSchool(){
		return $this->getInquiry()->getSchool();
	}

	public function __get($sName)
	{
		Ext_Gui2_Index_Registry::set($this);
		
		if(
			$sName == 'inquiry_id'
		)
		{
			$mValue		= 0;
			$oJourney	= $this->getJourney();

			if(
				is_object($oJourney) &&
				$oJourney instanceof Ext_TS_Inquiry_Journey
			)
			{
				$mValue = $oJourney->inquiry_id;
			}
		}
		else
		{
			$mValue = parent::__get($sName);
		}

		return $mValue;
	}

	/**
	 * Liefert alle Payments dieses Services
	 * Optional, können 2 DB_Date Parameter übergeben werden in denen NICHT auf existierende Zahlungen
	 * gerpüft werden soll
	 *
	 * @param string $sFilterFrom
	 * @param string $sFilterUntil
	 * @throws Exception
	 * @return Ext_Thebing_Accommodation_Payment
	 */
	public function checkPaymentStatus($sFilterFrom = '', $sFilterUntil = ''){

		$aPayments = array();

		$bUseFilter = false;
		
		// Aktive Zahlungen für diese Unterkunft (Fixe bezahlungen werden hier nicht berücksichtigt)
		$aAllPayments = $this->getPayments();

		// Sollten es Zahlungen geben und man versucht
		// den Kurs auf inaktiv zu schalten darf
		// dies nicht funktionieren
		if(
			!empty($aAllPayments) &&
			(int)$this->visible === 0
		) {
			foreach($aAllPayments as $oPayment) {
				$aPayments[$oPayment->id] = $oPayment;
			}
			return $aPayments;
		}

		// Filterzeitpunkt prüfen
		if(
			(
				!empty($sFilterFrom) &&		
				!WDDate::isDate($sFilterFrom, WDDate::DB_DATE) 
			)||(
				!empty($sFilterUntil) &&
				!WDDate::isDate($sFilterUntil, WDDate::DB_DATE) 
			)
		){
			throw new Exception('Wrong date format for paymentstatus function (accommodations)!');	
		}elseif(
			WDDate::isDate($sFilterFrom, WDDate::DB_DATE) &&
			WDDate::isDate($sFilterUntil, WDDate::DB_DATE) 
		){
			
			// Wenn es FilterZeiträume gibt, dann müssen die/der (1 oder 2) Zeiträume gefunden werden, die überprüft werden sollen
			$aPeriodSecond = array();
			$aPeriodSecond['from'] = $sFilterFrom;
			$aPeriodSecond['until'] = $sFilterUntil;
						
			$bUseFilter = true;
		}

		$aPeriodFirst = array();
		$aPeriodFirst['from'] = $this->_aOriginalData['from'];
		$aPeriodFirst['until'] = $this->_aOriginalData['until'];

		if($bUseFilter){
			// Zeitraum/räume ermitteln
			$aCheckDates = Ext_TC_Util::getDatePeriodOverlapDiff($aPeriodFirst, $aPeriodSecond);
		}else{
			// Kompletten zeitraum checken
			$aCheckDates[] = $aPeriodFirst;
		}

		foreach($aAllPayments as $oPayment){		

			$oDatePaymentDateFrom	= $oPayment->getFromDate();
			$oDatePaymentDateUntil	= $oPayment->getUntilDate();

			if(!$oDatePaymentDateUntil){
				continue;
			}
			
			// Wenn sich der Kurs komplett geändert hat, fliegt der Kunde aus der Klassenplanung komplett raus
			// Deshalb sind die Zeiträume hier egal und es müssen ALLE Payments angezeigt werden!!! #1843
			if(
				$this->getKey() === 'course' &&
				$this->_aOriginalData['course_id'] != $this->course_id
			){
				$aPayments[$oPayment->id] = $oPayment;
				continue;
			}

			// prüfen ob die Bezahlung in dem/den zu prüfenden Zeiträumen liegt
			// TODO Auf DateTime umstellen
			foreach($aCheckDates as $aDates){
				$oDateCheckFrom = new WDDate($aDates['from'], WDDate::DB_DATE);
				$oDateCheckUntil = new WDDate($aDates['until'], WDDate::DB_DATE);

				$iComp1 = $oDateCheckFrom->compare($oDatePaymentDateUntil, WDDate::DB_DATE);
				$iComp2 = $oDateCheckUntil->compare($oDatePaymentDateFrom, WDDate::DB_DATE);	

			
				if(
					$iComp1 <= 0 &&
					$iComp2 >= 0
				){
					// Zahlung liegt im zu prüfenden Intervall
					$aPayments[$oPayment->id] = $oPayment;
				}
				
			}
			
			
		}

		// Achtung: Es gibt zwei
		// Returns in dieser Methode
		return $aPayments;
	}
	
    public function isEmpty(){
        $aData = $this->_aData;
        unset(
			$aData['id'],
			$aData['active'],
			$aData['created'],
			$aData['changed'],
			$aData['user_id'],
			$aData['editor_id'],
			$aData['creator_id'],
			$aData['journey_id'],
			$aData['visible'],
			$aData['calculate'],
			$aData['for_matching'],
			$aData['for_tuition'],
			$aData['transfer_type'],
			$aData['booked'],
			$aData['changes'],
			$aData['program_id']
		);

        $bEmpty = true;
        foreach($aData as $sKey => $mValue){
             if(
				!empty($mValue) &&
				$mValue !== '0.00' && // units seit #13717
				$mValue !== '0000-00-00' &&
				$mValue !== '0000-00-00 00:00:00'
			) {
				$bEmpty = false;
                break;
            }
        }

        return $bEmpty;
    }
    
	public function validate($bThrowExceptions = false)
	{
		$mError = parent::validate($bThrowExceptions);

		if($mError === true)
		{
			$mError = array();
		}
		
		if($this->id > 0 && empty($mError))
		{
			$mValidatePayment = $this->validatePayment();
			$mError = array_merge($mError, $mValidatePayment);	
		}

		if(empty($mError)) {
			$mError = $this->validateDates();
		}
		
		if(empty($mError))
		{
			$mError = true;
		}
		
		return $mError;
	}
	
	public function validateDates() {
		$mError = true;
		
		$sFrom = $this->getFrom();
		$sUntil = $this->getUntil();

		if(
			!empty($sFrom) &&
			!empty($sUntil)
		) {
			$oDateFrom = new DateTime($sFrom);
			$oDateUntil = new DateTime($sUntil);

			if($oDateFrom > $oDateUntil) {
				$mError = array();
				$mError[$this->_sTableAlias.'.until'][] = L10N::t('Das Von-Datum sollte vor dem Bis-Datum liegen.');
			}
		}
		
		return $mError;
	}
	
	public function validatePayment()
	{
		$mError			= array();

		if(Ext_Thebing_System::ignoreServicePaymentCheck())
		{
			return $mError;
		}

		$aPaymentCheck = $this->checkPaymentStatus($this->from, $this->until);

		if(!empty($aPaymentCheck))
		{
            $sField = $this->getKey();
            $sField = $this->_sTableAlias.'.'.$sField.'_id';
            $mError[$sField][] = L10N::t('Für die Leistung gibt es bereits Bezahlungen.');
		}
		
		return $mError;
	}
	
	/**
	 * Abgeleitet für das Aktualisieren des Leitungszeitraumes
	 * @param bool $bLog 
	 */
//	public function save($bLog = true) {
//
//		$mReturn = parent::save($bLog);
//
//		$oInquiry = $this->getInquiry();
//		$oInquiry->refreshServicePeriod();
//
//		return $mReturn;
//	}
    
    /**
     * prüft ob ein Service Object die gleichen Daten wie ein anderes hat
     * OHNE auf die ZEITEN zu schauen
     * @param Ext_TS_Inquiry_Journey_Service $oService
     * @return boolean
     */
    public function isSameWithoutTimeData(Ext_TS_Inquiry_Journey_Service $oService){
        $aData = $this->_aData;
        $aData2 = $oService->getData();
        
        unset($aData['id'], $aData2['id']);
        unset($aData['changed'], $aData2['changed']);
        unset($aData['created'], $aData2['created']);
        unset($aData['user_id'], $aData2['user_id']);
        unset($aData['editor_id'], $aData2['editor_id']);
        unset($aData['creator_id'], $aData2['creator_id']);
        unset($aData['journey_id'], $aData2['journey_id']);
        unset($aData['calculate'], $aData2['calculate']);
        unset($aData['active'], $aData2['active']);
        unset($aData['from'], $aData2['from']);
        unset($aData['until'], $aData2['until']);
        unset($aData['weeks'], $aData2['weeks']);
        
        $aDiff = array_diff_assoc($aData, $aData2);
        
        if(empty($aDiff)){
            return true;
        }
        
        return false;
    }

	/**
	 * Services, die über Ferien mit diesem Service verknüpft sind
	 *
	 * Da \Ext_TS_Inquiry_Journey::getRelatedServices() die Services in Gruppen sortiert,
	 * kommt hier auch immer der eigene Service zurück!
	 *
	 * @param DateTime|null $dFromWeek
	 * @param string|null $sType
	 * @return static[]
	 */
	public function getRelatedServices(\DateTime $dFromWeek = null, $sType = null) {

		if(!in_array($this->getKey(), ['course', 'accommodation'])) {
			throw new LogicException('No related services for '.$this->getKey());
		}

		$aRelatedServices = $this->getJourney()->getRelatedServices($this->getKey(), $sType);

		foreach($aRelatedServices as $aGroup) {

			// Mit IDs vergleichen, da WDBasic irgendwie unterschiedliche Instanzen zurückgibt
//			$aIds = array_map(function($oJourneyService) {
//				return $oJourneyService->id;
//			}, $aGroup);
//
//			if(in_array($this->id, $aIds)) {
//				$aJourneyServices = $aGroup;
//				break;
//			}

			if(in_array($this, $aGroup, true)) {
				$aJourneyServices = $aGroup;
				break;
			}

		}

		if(empty($aJourneyServices)) {
			throw new RuntimeException('Course/accommodation not found in related services groups!');
		}

		if($dFromWeek !== null) {
			$aJourneyServices = array_filter($aJourneyServices, function(Ext_TS_Inquiry_Journey_Service $oJourneyService) use($dFromWeek) {
				$dFrom = new DateTime($oJourneyService->from);
				return $dFrom >= $dFromWeek;
			});
		}

		return $aJourneyServices;

	}

	/**
	 * Holiday-Splittings finden, welche diese Leistung betreffen (beide Seiten)
	 *
	 * @return array
	 */
	public function getHolidaySplittings() {

		if(!in_array($this->getKey(), ['course', 'accommodation'])) {
			throw new LogicException('No holiday splittings for '.$this->getKey());
		}

		$sIdField = 'journey_'.$this->getKey().'_id'; // journey_course_id, journey_accommodation_id
		$sSplitIdField = 'journey_split_'.$this->getKey().'_id'; // journey_split_course_id, journey_split_accommodation_id

		$sSql = "
			SELECT
				`ts_ihs`.`{$sIdField}`,
				`ts_ihs`.`{$sSplitIdField}`,
				`ts_ih`.`type`
			FROM
				`ts_inquiries_holidays_splitting` `ts_ihs` INNER JOIN
				`ts_inquiries_holidays` `ts_ih` ON
					`ts_ih`.`id` = `ts_ihs`.`holiday_id` AND
					`ts_ih`.`active` = 1
			WHERE
				`ts_ihs`.`active` = 1 AND (
					`ts_ihs`.`{$sIdField}` = :id OR
					`ts_ihs`.`{$sSplitIdField}` = :id
				)
		";

		return (array)DB::getQueryRows($sSql, [
			'id' => $this->id
		]);

	}
	
	/**
	 * @param array $additionalCosts
	 */
	protected function checkAdditionalServicesValidity(array &$additionalCosts) {
		
		$from = new Carbon($this->from);
		$until = new Carbon($this->until);
		
		$additionalCosts = array_filter($additionalCosts, function(Ext_Thebing_School_Additionalcost $additionalCost) use($from, $until) {
			
			if($additionalCost->limited_availability) {
				
				$validity = Ts\Entity\Additionalcost\Validity::getValidEntry($additionalCost, $from, $until);
				if($validity === null) {
					return false;
				}
				
			}
			
			return true;
		});
		
	}

	public function addStudentListParts(&$aSqlParts) {

		$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();
		$sWhereShowWithoutInvoice = Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ts_i`');

		$aSqlParts['select'] .= ",
			`ts_i`.`id` `inquiry_id`,
			`ts_i`.`editor_id`,
			`ts_i`.`inbox`,
			`tc_c`.`firstname`,
			`tc_c`.`lastname`, 
			`tc_c`.`birthday`,
			`tc_c`.`gender`,
			`tc_c`.`language` `mother_tongue`,
			`dl_mothertongue`.`name_".$sInterfaceLanguage."` `customer_mother_tongue`,
			`dc_nationality`.`nationality_".$sInterfaceLanguage."` `nationality`,
			 getAge(`tc_c`.`birthday`) `age`,
			`tc_cn`.`number` `customer_number`,
			`kg`.`short` `group_short`,
			`kg`.`name` `group_name`,
			`tc_ea`.`email`,
			`ts_an`.`number` `agency_number`,
			`ka`.`ext_1`,
			`k_il`.`name` `inbox_name`,
			`tc_a`.`address`,
			`tc_a`.`address_addon`,
			`tc_a`.`zip`,
			`tc_a`.`city`,
			`tc_a`.`country_iso`,
			`tc_cd_phone_private`.`value` `phone_private`,
			`tc_cd_phone_mobile`.`value` `phone_mobile`,
			`tc_ea_group`.`email` `group_email`,
			`k_ss`.`text` `student_status`,
			GROUP_CONCAT(
				DISTINCT CONCAT(`tc_cd_details`.`type`, '{|}', `tc_cd_details`.`value`)
			 	SEPARATOR '{||}'
			) `contact_details`
		";

		$aSqlParts['from'] .= " INNER JOIN
			`ts_inquiries_journeys` `ts_ij` ON
				`ts_ij`.`id` = `$this->_sTableAlias`.`journey_id` AND
				`ts_ij`.`active` = 1 AND
				`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
			`customer_db_2` `cdb2` ON
				`cdb2`.`id` = `ts_ij`.`school_id` AND
				`cdb2`.`active` = 1 INNER JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
				`ts_i`.`active` = 1 
				$sWhereShowWithoutInvoice INNER JOIN 
			`kolumbus_inboxlist` `k_il` ON
				`k_il`.`short` = `ts_i`.`inbox` AND
				`k_il`.`active` = 1 LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` LEFT JOIN
			(
				`tc_contacts` `tc_c_group` INNER JOIN
				`tc_contacts_to_emailaddresses` `tc_ctea_group` INNER JOIN
				`tc_emailaddresses` `tc_ea_group`
			) ON
				`tc_c_group`.`id` = `kg`.`contact_id` AND
				`tc_ctea_group`.`contact_id` = `tc_c_group`.`id` AND
				`tc_ea_group`.`id` = `tc_ctea_group`.`emailaddress_id` AND
				`tc_ea_group`.`master` = 1 AND
				`tc_ea_group`.`active` = 1 LEFT JOIN 
			`ts_companies` `ka` ON
				`ka`.`id` = `ts_i`.`agency_id` AND
				`ka`.`active` = 1 LEFT JOIN
			`ts_companies_numbers` `ts_an` ON
				`ts_an`.`company_id` = `ka`.`id` INNER JOIN 
			`ts_inquiries_to_contacts` `ts_i_t_c` ON
				`ts_i_t_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_t_c`.`type` = 'traveller' INNER JOIN 
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_i_t_c`.`contact_id` AND
				`tc_c`.`active` = 1 LEFT JOIN
			`data_countries` AS `dc_nationality` ON
				`dc_nationality`.`cn_iso_2` = `tc_c`.`nationality` LEFT JOIN
			`data_languages` `dl_mothertongue` ON
				`dl_mothertongue`.`iso_639_1` = `tc_c`.`language` LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_contacts_to_emailaddresses` `tc_c_t_ea` ON
				`tc_c_t_ea`.`contact_id` = `tc_c`.`id` LEFT JOIN 
			`tc_emailaddresses` `tc_ea` ON
				`tc_ea`.`id` = `tc_c_t_ea`.`emailaddress_id` AND
				`tc_ea`.`master` = 1 AND
				`tc_ea`.`active` = 1 LEFT JOIN 
			`tc_contacts_to_addresses` `tc_c_t_a` ON
				`tc_c_t_a`.`contact_id` = `tc_c`.`id` LEFT JOIN 
			`tc_addresses` `tc_a` ON 
				`tc_a`.`id` = `tc_c_t_a`.`address_id` AND
				`tc_a`.`active` = 1 LEFT JOIN 
			`tc_contacts_details` `tc_cd_details` ON
				`tc_cd_details`.`contact_id` = `tc_c`.`id` AND
				`tc_cd_details`.`active` = 1 AND
				`tc_cd_details`.`type` IN ('".implode("','", ['place_of_birth', 'country_of_birth'])."')	AND
				`tc_cd_details`.`value` != '' LEFT JOIN
				
			/* TODO Umstellen auf `tc_cd_details` */
				 
			`tc_contacts_details` `tc_cd_phone_private` ON
				`tc_cd_phone_private`.`contact_id` = `tc_c`.`id` AND
				`tc_cd_phone_private`.`active` = 1 AND
				`tc_cd_phone_private`.`type` = '".\Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE."' LEFT JOIN 
			`tc_contacts_details` `tc_cd_phone_mobile` ON
				`tc_cd_phone_mobile`.`contact_id` = `tc_c`.`id` AND
				`tc_cd_phone_mobile`.`active` = 1 AND
				`tc_cd_phone_mobile`.`type` = '".\Ext_TC_Contact_Detail::TYPE_PHONE_MOBILE."' LEFT JOIN 
			`kolumbus_student_status` `k_ss` ON
				`k_ss`.`id` = `ts_i`.`status_id` AND
				`k_ss`.`active` = 1
		";

	}

}
