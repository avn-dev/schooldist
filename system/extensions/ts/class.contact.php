<?php

use TsPrivacy\Interfaces\Purge as PrivacyPurge;

class Ext_TS_Contact extends Ext_TC_Contact implements PrivacyPurge {

	/**
	 * @TODO: Entfernen (das sollte nur für den SR relevant sein und demnach in der Inquiry-Ableitung)
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'cdb1';

	protected $_aJoinedObjects = array(
		'login_data' => array(
			'class' => 'Ext_TS_Inquiry_Contact_Login',
			'key' => 'contact_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'on_delete' => 'cascade'
		),
		'details' => array(
			'class'=>'Ext_TC_Contact_Detail',
			'key'=>'contact_id',
			'type'=>'child',
			'check_active'=>true,
			'on_delete' => 'cascade'
		)
	);

	/**
	 * Nicht nur Newsletter, sondern automatische E-Mails generell!
	 *
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const DETAIL_NEWSLETTER = 'newsletter';

	/**
	 * @see Ext_TC_Contact_Detail::$type
	 * @var string
	 */
	const DETAIL_COMMENT = 'comment';

	const DETAIL_TOS = 'tos';

	public function __construct(int $iDataID = 0, string $sTable = null) {

		$this->_aJoinTables['sponsors'] = [
			'table' => 'ts_sponsors_to_contacts',
			'foreign_key_field' => 'sponsor_id',
			'primary_key_field' => 'contact_id',
			'class' => '\TsSponsoring\Entity\Sponsor'
		];

		parent::__construct($iDataID, $sTable);

	}

	public function __get($sField){
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sField == 'name' || $sField == 'contact_name'){
			return $this->getName();
		}elseif($sField == 'email'){
			return $this->getEmail();
		}elseif($sField == 'nickname'){
			// Wenn leer, gibt es vielleicht kein Objekt (wird hier NICHT automatisch generiert)
			$oLoginData = $this->getLoginData();
			return $oLoginData->nickname;
		}else{
			return parent::__get($sField);
		}
	}
	
	public function __set($sName, $mValue) {
		if(
			$sName == 'email'
		){
			$oFirstEmail = $this->getFirstEmailAddress();
			$oFirstEmail->email = $mValue;
		}else{
			parent::__set($sName, $mValue);
		}
	}

	/*
	 * Kontaktmailadressen für die richtige Form in der Kommunikation
	 */
	public function getEmails() {
		$aEmails = array();
		$aEmailAddresses = (array)$this->getEmailAddresses(true);

		foreach($aEmailAddresses as $oEmailAddress) {
			if(
				$oEmailAddress->email &&
				Util::checkEmailMX($oEmailAddress->email)
			) {
				$aInfo = array();
				$aInfo['object'] = $this->getClassName();
				$aInfo['object_id'] = (int)$this->id;
				$aInfo['email'] = $oEmailAddress->email;
				$aInfo['name'] = $this->name . ' ('.$oEmailAddress->email.')';
				$aEmails[] = $aInfo;
			}
		}

		return $aEmails;
	}

	/**
	 * In der Tabelle tc_conctacts gibt die Spalte "language" und stellt die Muttersprache dar,
	 * in der Schulsoftware jedoch wurde diese Funktion immer für die Korrespondenzsprache benutzt,
	 * deshalb habe ich diese Funktion jetzt mit optionalen Parameter erstellt, damit man genau weiß
	 * was man zurück bekommt und man beide Sprachen mit einer Funktion bekommen kann.
	 * @param string $sType
	 * @return string
	 */
	public function getLanguage($sType = 'corresponding')
	{
		if(
			$sType == 'corresponding'
		)
		{
			//Korrespondenzsprache
			return $this->corresponding_language;
		}
		else
		{
			//Muttersprache
			return $this->language;
		}
	}

	/**
	 * Es gibt ein paar Stellen wo diese Funktion verwendet wird, habe $bAsUnixTimestamp als Standard definiert,
	 * weil bis jetzt die Funktion immer einen Timestamp zurück geliefert hat. Für den reinen Wert den __get benutzen
	 * oder den Parameter auf "false" stellen
	 * @param bool $bAsUnixTimestamp
	 * @return mixed
	 */
	public function getBirthday($sPart=WDDate::DB_DATE)
	{
		$sBirthday = '';
		
		try
		{
			$oDate = new WDDate($this->birthday, WDDate::DB_DATE);

			if(WDDate::isDate($oDate->get(WDDate::DB_DATE), WDDate::DB_DATE))
			{
				$sBirthday = $oDate->get($sPart);
			}			
		}
		catch(Exception $e)
		{
			
		}

		return $sBirthday;
	}

	/**
	 * Es gibt zwar eine Spalte für salutation in tc_contacts, nur wird sie in der Schulsoftware nicht befüllt
	 * und in der Schulsoftware wird nur Herr/Frau erwartet
	 * @return <type>
	 */
	public function getSalutaion()
	{
		$aTitles = Ext_Thebing_Util::getPersonTitles();

		return $aTitles[$this->gender];
	}

	public function getDetail($sKey, $bAsObject=false, $createNew=true) {

		$oDetail	= null;
		$aChilds	= (array)$this->getJoinedObjectChilds('details', true);

		if(!empty($aChilds)) {
			foreach($aChilds as $oChild) {
				if($oChild->type == $sKey) {
					$oDetail = $oChild;
					break;
				}
			}
		}
	
		if(
			!is_object($oDetail) ||
			!($oDetail instanceof Ext_TC_Contact_Detail)
		) {
			$oDetail = $this->getJoinedObjectChild('details');
			$oDetail->type = $sKey;

			// Default-Wert ist hier 1, aber default_value scheint in der GUI nicht mehr zu funktionieren
			if(
				!$this->exist() &&
				$sKey === Ext_TS_Contact::DETAIL_NEWSLETTER
			) {
				$oDetail->value = 1;
			}
		}
		
		if(
			$bAsObject
		) {
			return $oDetail;
		} else {
			return $oDetail->value;
		}

		return false;
	}

	/**
	 * Speichert ein Detail
	 * @param string $sKey
	 * @param mixed $sValue
	 */
	public function setDetail($sKey, $sValue) {

		// Gibt es den Wert schon?
		$detail = $this->getDetail($sKey, true, false);
		
		
		if($detail) {
			// Wert entfernen, wenn vorhanden und neuer Wert leer
			if(empty($sValue)) {
				$this->deleteJoinedObjectChild('details', $detail);
				return;
			}
		} else {
			// Kein alter Wert, kein neuer Wert > Nix machen
			if(empty($sValue)) {
				return;
			// Kein alter Wert, aber neuer Wert, euen Eintrag erstellen
			} else {
				$detail = $this->getDetail($sKey, true, true);
			}			
		}
		
		$detail->value = $sValue;
		
	}
	
	/**
	 * Liefert ein Adressobjekt dieses Kontaktes
	 * @param type $sType
	 * @return Ext_TC_Address
	 */
	public function getAddress($sType = 'contact', $createNew=true) {
		global $user_data;
		
		$oAddress = null;

		switch($sType){
			case'contact':
			case'billing':			
				$aAddresses = $this->getAddresses();
				foreach($aAddresses as $oAddressTemp){
					$oLabel = $oAddressTemp->getJoinedObject('label');	
					if($oLabel->type ==  $sType . '_address'){
						$oAddress = &$oAddressTemp;
						break;
					}
				}
				break;
			default:
				$aAddresses = $this->getAddresses();
				foreach($aAddresses as $oAddressTemp){
					$oAddress = &$oAddressTemp;
					break;
				}
				break;
		}	

		if(
			$createNew &&
			!($oAddress instanceof Ext_TC_Address)
		) {

			$iLabelId = 0;
			$oAddress = $this->getJoinTableObject('contacts_to_addresses', 0);

			if(
				$sType == 'contact'
			){
				$iLabelId = Ext_TS_AddressLabel::getContactAdressLabelId();
			}
			elseif(
				$sType == 'billing'	
			){
				$iLabelId = Ext_TS_AddressLabel::getBillingAdressLabelId();
			}

			$oAddress->label_id = (int)$iLabelId;
			$oAddress->creator_id = (int)$user_data['id'];
			$oAddress->editor_id = (int)$user_data['id'];
		}

		return $oAddress;
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public function getEmail() {

		$oFirstMail = $this->getFirstEmailAddress();
		$sEmail = $oFirstMail->email;

		return $sEmail;
	}

	/**
	 * @return bool
	 */
	public function isReceivingAutomaticEmails(): bool {

		$detail = $this->getDetail(self::DETAIL_NEWSLETTER, true);

		if ((int)$detail->value === 1) {
			return true;
		}

		return false;
	}

	public function save($bLog = true) {

		$oFirstMail = $this->getFirstEmailAddress(false);
		if($oFirstMail !== null) {
			$oFirstMail->master = 1;
		}

		return parent::save($bLog);
	}

	/**
	 * Kontakt für GDPR bereinigen: E-Mails, Adressen, Details
	 *
	 * @TODO Methoden beachten active, sollte aber hier egal sein
	 *
	 * @param bool $bAnonymize
	 */
	public function purge($bAnonymize = false) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		// E-Mail-Adressen in jedem Fall löschen (JoinTable)
		$aEmails = $this->getEmailAddresses();
		foreach($aEmails as $oEmail) {
			$oEmail->enablePurgeDelete();
			$oEmail->delete();
		}

		// Adressen in jedem Fall »löschen« (JoinTable)
		$aAddresses = $this->getAddresses();
		foreach($aAddresses as $oAddress) {
			if(!$bAnonymize) {
				$oAddress->enablePurgeDelete();
				$oAddress->delete();
			} else {
				// Land muss für »statistische Informationen« erhalten bleiben
				$oAddress->address = '';
				$oAddress->address_addon = '';
				$oAddress->state = '';
				$oAddress->zip = '';
				$oAddress->city = '';
				$oAddress->company = '';
			}
		}

		// Details in jedem Fall löschen
		$aDetails = $this->getDetails();
		foreach($aDetails as $oDetail) {
			$oDetail->enablePurgeDelete();
			$oDetail->delete();
		}

		if(!$bAnonymize) {
			$this->enablePurgeDelete();
			$this->delete();
		} else {
			$this->firstname = ucfirst(strtolower(Util::generateRandomString(8, ['no_numbers' => true])));
			$this->lastname = 'Anonym';
			$this->save();
		}

	}

	/**
	 * Liefert die WD-Basic des Login Objektes
	 *
	 * @param bool $bGenerate
	 * @return Ext_TS_Inquiry_Contact_Login
	 */
	public function getLoginData($bGenerate=false) {

		$oLoginData = null;
		$aLoginData = $this->getJoinedObjectChilds('login_data');
		
		if(!empty($aLoginData)) {
			$oLoginData	= reset($aLoginData);
		}

		// Das Objekt darf auf keinen Fall geholt werden, wenn es nicht generiert werden soll
		// Ansonsten ist die WDBasic so unendlich schlau, das Objekt dennoch speichern zu wollen, was ohne generateLogin()
		//   dafür sorgt, dass nickname ein leerer String ist und dann ein UNIQUE-Fehler auftritt.
		if(!is_object($oLoginData) && $bGenerate) {
			/** @var Ext_TS_Inquiry_Contact_Login $oLoginData */
			$oLoginData = $this->getJoinedObjectChild('login_data');
			$oLoginData->generateLogin();
		}

		// Username neu generieren wenn sich dieser verändert hat – Wunsch aus #12296
		if(
			$bGenerate &&
			$oLoginData instanceof Ext_TS_Inquiry_Contact_Login &&
			!$oLoginData->credentials_locked
		) {
			$sUserName = $oLoginData->generateUsername();
			if($oLoginData->nickname !== $sUserName) {
				$oLoginData->nickname = $sUserName;
				$oLoginData->save();
			}
		}

		if (!$oLoginData instanceof Ext_TS_Inquiry_Contact_Login) {
			// Fake-Objekt OHNE VERKNÜPFUNG für Legacy-Code
			$oLoginData = new Ext_TS_Inquiry_Contact_Login();
		}
		
		return $oLoginData;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return bool
	 */
	public function validate($bThrowExceptions = false){
		$mValidate = parent::validate($bThrowExceptions);
	
		// Fehler der Contact Details müssen ignoriert werden!
		if(is_array($mValidate)){
			foreach($mValidate as $sKey => $aError){
				if(strpos($sKey, 'contacts_details') !== false){
					unset($mValidate[$sKey]);
				}
			}
			
			if(empty($mValidate)){
				$mValidate = true;
			}
		}
		
		return $mValidate;
	}
	
	/**
	 * Überprüfen ob eine Menge an Kontakten die gleiche Korrespondenzsprache haben
	 * 
	 * @param string $sType ('enquiry','inquiry')
	 * @param array $aSelectedIds
	 * @return string | bool
	 */
	public static function getUnityLanguagesForContacts($sType, $aSelectedIds) {

//		if($sType == 'inquiry') {
//			$sTableRelation = 'ts_inquiries_to_contacts';
//			$sTableMail		= 'ts_inquiries';
//			$sColumn		= 'inquiry_id';
//		} elseif($sType == 'enquiry') {
//			$sTableRelation = 'ts_enquiries_to_contacts';
//			$sTableMail		= 'ts_enquiries';
//			$sColumn		= 'enquiry_id';
//		} else {
//			return false;
//		}

		if(count($aSelectedIds) > 0) {

			$sSql = "
				SELECT
					DISTINCT `tc_c`.`corresponding_language`
				FROM
					`tc_contacts` `tc_c` INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`contact_id` = `tc_c`.`id` INNER JOIN
					`ts_inquiries` `ts_i` ON 
						`ts_i`.`id` = `ts_i_to_c`.`inquiry_id`
				WHERE				  
					 `ts_i`.`id` IN ( :selected_ids ) AND
					 `ts_i_to_c`.`type` <> 'emergency' AND
					 `tc_c`.`corresponding_language` <> ''
			";

			$aSql = array(
				'selected_ids'		=> $aSelectedIds,
//				'main_table'		=> $sTableMail,
//				'relation_table'	=> $sTableRelation,
//				'column'			=> $sColumn,
			);
			
			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			if(1 == count($aResult)) {
				$aData = reset($aResult);
				
				return $aData['corresponding_language'];
			}
		}
		
		return false;
	}

	/**
	 * In der Liste keine Einträge ohne Name anzeigen
	 * @param array $aSqlParts
	 * @param string $sView
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= " ,
			`tc_ea`.`email`,
			`tc_cn`.`number`,
			!ISNULL(`ts_aptc`.`accommodation_provider_id`) `is_accommodation`,
			`ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_ENQUIRY." `is_enquiry`,
			`ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING." `is_booking`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `cdb1`.`id` LEFT JOIN 
			`tc_contacts_to_emailaddresses` `tc_ctea` ON 
				`tc_ctea`.`contact_id` = `cdb1`.`id` LEFT JOIN
			`tc_emailaddresses` `tc_ea` ON
				`tc_ea`.`id` = `tc_ctea`.`emailaddress_id` AND
				`tc_ea`.`master` = 1 LEFT JOIN 
			`ts_accommodation_providers_to_contacts` `ts_aptc` ON
				`ts_aptc`.`contact_id` = `cdb1`.`id` LEFT JOIN
			`ts_inquiries_to_contacts` `ts_itc` ON
				`ts_itc`.`contact_id` = `cdb1`.`id` LEFT JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `ts_itc`.`inquiry_id`
		";

		$aSqlParts['where'] .= "
			AND (
				`{$this->_sTableAlias}`.`firstname` != '' OR
				`{$this->_sTableAlias}`.`lastname` != ''
			)
		";

	}

}
