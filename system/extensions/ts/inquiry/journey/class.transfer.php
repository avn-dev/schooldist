<?php

use Carbon\Carbon;
use Communication\Interfaces\Model\CommunicationSubObject;
use TsRegistrationForm\Interfaces\RegistrationInquiryService;

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $booked
 * @property int $user_id
 * @property int|string $transfer_type
 * @property int $journey_id
 * @property int $start
 * @property int $end
 * @property string $start_type
 * @property string $end_type
 * @property string $transfer_date (DATE)
 * @property string $transfer_time (TIME)
 * @property string $comment
 * @property int $start_additional
 * @property int $end_additional
 * @property string $airline
 * @property string $flightnumber
 * @property string $pickup (TIME)
 * @property string $accommodation_confirmed (TIMESTAMP)
 * @property string $provider_updated (TIMESTAMP)
 * @property string $provider_confirmed (TIMESTAMP)
 * @property int $provider_id
 * @property string $provider_type
 * @property int $driver_id
 * @property string $customer_agency_confirmed (TIMESTAMP)
 * @property string $updated (TIMESTAMP)
 */
class Ext_TS_Inquiry_Journey_Transfer extends Ext_TS_Inquiry_Journey_Service implements Ext_TS_Service_Interface_Transfer, RegistrationInquiryService, \Communication\Interfaces\Model\HasCommunication {

	/**
	 * Transfer-Art: Individuell
	 *
	 * @var integer
	 */
	const TYPE_ADDITIONAL = 0;

	/**
	 * Transfer-Art: Anreise
	 *
	 * @var integer
	 */
	const TYPE_ARRIVAL = 1;

	/**
	 * Transfer-Art: Abreise
	 *
	 * @var integer
	 */
	const TYPE_DEPARTURE = 2;

	const REGISTRATION_FORM_FIELDS = [
		'type' => 'Typ',
//		'locations' => 'Orte',
//		'date' => 'Datum',
//		'time' => 'Uhrzeit',
//		'airline' => 'Fluglinie',
//		'flight_number' => 'Flugnummer',
//		'comment' => 'Kommentar'
	];

	// Wenn Transfer sich verändert hat
	protected $checkchanged = false;
	protected $_sChangedField = '';

	protected $_sTable = 'ts_inquiries_journeys_transfers';

	protected $_sTableAlias = 'ts_ijt';

	protected $sInfoTemplateType = 'transfer';

	protected $_sPlaceholderClass = Ext_TS_Inquiry_Journey_Transfer_Placeholder::class;
	
	protected $_aFormat = array(
			'changed' => array(
				'format' => 'TIMESTAMP'
			),
			'created' => array(
				'format' => 'TIMESTAMP'
			),
			'transfer_date' => array(
				'format' => 'DATE',
				'validate' => 'DATE'
			),
			'transfer_time' => array(
				'format' => 'TIME',
				'validate' => 'TIME'
			),
			'pickup' => array(
				'format' => 'TIME',
				'validate' => 'TIME'
			)
	);

	protected $_aJoinTables = [
		'accounting_payments' => [
			'table' => 'kolumbus_transfers_payments',
			'foreign_key_field' => '',
			'primary_key_field' => 'inquiry_transfer_id',
			'check_active' => true
		],
		'transfer_requests' => [
			'table' => 'kolumbus_inquiries_transfers_provider_request',
			'foreign_key_field' => '',
			'primary_key_field' => 'transfer_id'
		],
		// Wird nur bei Anfragen verwendet
		'travellers' => [
			'table' => 'ts_inquiries_journeys_transfers_to_travellers',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'journey_transfer_id',
			'class' => Ext_TS_Inquiry_Contact_Traveller::class,
			'autoload' => false
		]
	];

	protected $_aJoinedObjects = [
        'accounting_payments_active' => [
			'class' => 'Ext_Thebing_Transfer_Payment',
			'key' => 'inquiry_transfer_id',
			'check_active' => true,
			'type' => 'child'
		]
	];

	/**
	 * 
	 * @param string $sLang
	 * @param boolean $bFrontend
	 * @return array
	 */
	public static function getTransferTypes($mLang = '', $bFrontend=false) {

		if(!$mLang instanceof \Tc\Service\LanguageAbstract) {
			if($bFrontend === false) {
				$mLang = new \Tc\Service\Language\Backend($mLang);
				$mLang->setPath('Thebing » Transfer');
			} else {
				$mLang = new \Tc\Service\Language\Frontend($mLang);
			}
		}

		$aTypes = array(
			self::TYPE_ADDITIONAL => $mLang->translate('Individually'),
			self::TYPE_ARRIVAL => $mLang->translate('Arrival'),
			self::TYPE_DEPARTURE => $mLang->translate('Departure')
		);

		return $aTypes;
	}

	// Gibt den Titel für einen Inquiry Transfer
	public function getName($oCalendarFormat = null, $sView = 1, $mLang = '', $withFlightNumber = false) {

		$oInquiry		= $this->getInquiry();
		$oSchool		= $oInquiry->getSchool();
		$sDate			= '';
		
		$mLang = Ext_Thebing_Util::getLanguageObject($mLang, 'Thebing » Transfer');

		if($oCalendarFormat) {
			$sDate		= $oCalendarFormat->format($this->transfer_date);
		} else {
			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			$aTemp['school_id'] = $oSchool->id;
			$sDate		= $oDateFormat->format($this->transfer_date, $aTemp, $aTemp);
		}

		$sWeekday		= Ext_Thebing_Util::getWeekDay(2, $this->transfer_date, true, $mLang->getLanguage());
		$sTime			= substr($this->transfer_time, 0, 5);

		$aTransferTypes = self::getTransferTypes($mLang);
		$sType			= $aTransferTypes[$this->transfer_type];
		
		// Verschiedene Ansichten
		$sName = '';

		switch($sView){
			// Location [Terminal] - Location [Terminal] (Date)
			case 2:

				$sStart			= $this->getLocationName('start', true, $mLang);
				$sEnd			= $this->getLocationName('end', true, $mLang);

				$sStart			= trim($sStart);
				$sEnd			= trim($sEnd);
				
				if(!empty($sStart)){
				$sName .= $sStart;
				}

				if(!empty($sEnd)){
				$sName .= ' - ';
				$sName .= $sEnd;
				}

				if(!empty($sDate)){
				$sName .= ' (' . $sWeekday . ' ' . $sDate . ')';
				}
				break;
			// Arrival: Location - Location (Date)
			case 1:
			default:

				$sStart			= $this->getLocationName('start', false, $mLang);
				$sEnd			= $this->getLocationName('end', false, $mLang);

				$sName .= $sType;

				if(
					$sStart ||
					$sEnd
				) {
					$sName .= ': ';
					$sName .= $sStart;
					if(
						$sStart &&
						$sEnd
					) {
						$sName .= " - ";
					}
					$sName .= $sEnd;
				}

				$sDateName = trim($sWeekday . ' ' . $sDate . ' ' . $sTime);
				$sName .= ' ('.$sDateName;

				if ($withFlightNumber) {
					$sName .= ' '.$this->flightnumber;
				}

				$sName .= ')';

				break;
		}

		return $sName;
	}

	/**
	 * Gibt den Namen einer Transferlocation
	 * 
	 * @param string $sType
	 * @param boolean $bTerminals
	 * @param string $sLang
	 * @param boolean $bFrontend
	 * @return string
	 */
	public function getLocationName($sType = 'start', $bTerminals = false, $mLang = '') {

		$oInquiry = $this->getInquiry();

		if(empty($mLang)) {
			$sLanguage = System::d('systemlanguage');
			$mLang = new \Tc\Service\Language\Backend($sLanguage);
			$mLang->setPath('Thebing » Transfer');
		}

		if(!$mLang instanceof \Tc\Service\LanguageAbstract) {
			$mLang = new \Tc\Service\Language\Backend($mLang);
			$mLang->setPath('Thebing » Transfer');
		}
		
		// Buchungsbezogene An/Abreise Orte
		$aTransfer = $oInquiry->getTransferLocations('', $mLang);

		// Sonderfall Unterkunft hinzufügen da NUR bei An bzw. Abreise verfügbar
		$aTransfer['accommodation_0'] = $mLang->translate('Unterkunft');

		$sName = '';

		switch($sType){
			case 'start':
				$sName	= $aTransfer[$this->start_type . '_' . $this->start];
				break;
			case 'end':
				$sName	= $aTransfer[$this->end_type . '_' . $this->end];
				break;
		}

		if($bTerminals){
			$sName .= ' ' .$this->getTerminalName($sType);
		}

		return $sName;
	}

	// Gibt das Terminal zurück eines Transfers
	public function getTerminalName($sType = 'start', $sLanguage = '') {

		$sName = '';
		switch($sType) {
			case 'start':
				if($this->start_type == 'location'){
					$oTerminal = Ext_TS_Transfer_Location_Terminal::getInstance($this->start_additional);
					$sName = $oTerminal->getName($sLanguage);
				}
				break;
			case 'end':
				if($this->end_type == 'location'){
					$oTerminal = Ext_TS_Transfer_Location_Terminal::getInstance($this->end_additional);
					$sName = $oTerminal->getName($sLanguage);
				}
				break;
		}

		return $sName;
	}


	/*
	 * Funktion liefert alle möglichen Transferprovider für diesen Transfer
	 */
	public function getTransferProvider(){

		// Rückgabe
		$aReturn = array();
		if($this->id <= 0){
			return $aReturn;
		}

		$oInquiry = $this->getInquiry();
		$oSchool = $oInquiry->getSchool();


		// Erste-Letzte Unterkunft bestimmen da bei An-Abreise keine spezielle Fam. gewählt werden kann
		$aAccommodations = $oInquiry->getFirstLastMatchedAccommodation();

		// Transferprovider der Schule
		$aProviders = $oSchool->getTransferProvider();

		foreach((array)$aProviders as $aResult){
			
			$aStartLocations		= (array)json_decode($aResult['provider_airports_from']);
			$aEndLocations			= (array)json_decode($aResult['provider_airports_to']);

			$aStartAccommodations	= (array)json_decode($aResult['provider_accommodations_from']);
			$aEndAccommodations		= (array)json_decode($aResult['provider_accommodations_to']);

			$bContinue = false;

			// Filter anwenden
			switch($this->start_type){
				case 'location':
						if(!in_array($this->start, $aStartLocations)){
							$bContinue = true;
						}
						break;
				case 'accommodation':
					if(
						$this->start == '0' &&
						$this->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE
					){
						// Bei Abreise muss letzte Unterkunft vom Provider angefahren werden können
						if(
							!is_object($aAccommodations['last']) ||
							!in_array($aAccommodations['last']->id, $aStartAccommodations)
						){
							if(!$aResult['provider_to_all_accommodations'])
							{
								$bContinue = true;
							}
						}

					}elseif(!in_array($this->start, $aStartAccommodations)){
					// Spezielle Unterkunft (individueller)
						$bContinue = true;
					}
					break;
				default:
			}

			switch($this->end_type){
				case 'location':
						if(!in_array($this->end, $aEndLocations)){
							$bContinue = true;
						}
						break;
				case 'accommodation':
					if(
						$this->end == '0' &&
						$this->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL
					){
						// Bei Anreise muss erste Unterkunft vom Provider angefahren werden können
						if(
							!is_object($aAccommodations['first']) ||
							!in_array($aAccommodations['first']->id, $aEndAccommodations)
						){
							if(!$aResult['provider_from_all_accommodations'])
							{
								$bContinue = true;
							}
						}
						
					}elseif(!in_array($this->end, $aEndAccommodations)){
							$bContinue = true;
						}
						break;
				default:
			}

			if($bContinue){
				continue;
			}

			$aReturn[(int)$aResult['provider_id']]['name'] = $aResult['provider_name'];
			$aReturn[(int)$aResult['provider_id']]['email'] = $aResult['provider_email'];
			$aReturn[(int)$aResult['provider_id']]['driver'][$aResult['driver_id']]['name'] = $aResult['driver_name'];
		}

		// auch prüfen ob Unterkunft den Transfer übernehmen kann
		$aCheckAccommodationTransfer = $this->checkAccommodationTransfer();
		foreach($aCheckAccommodationTransfer as $aProvider) {
			// Unterkunft kann fahren
			$sKey = (int)$aProvider['family_id'] * (-1);
			$aReturn[$sKey]['name'] = $aProvider['family_name'];
			$aReturn[$sKey]['email'] = $aProvider['family_email'];
		}

		return $aReturn;
	}

	/*
	 * Prüft ob die zugewiesenen Unterkünfte den kundentransfer übernehmen
	 * Ankunft Kriterien: Die 1. zugew. Familie muss den Transfer übernehmen wollen
	 * Abreise Kriterien: Die letzte zugew. Fam. muss den Trans. übernehmen sollen
	 *
	 * TODO: Refaktorisieren
	 */
	public function checkAccommodationTransfer(){
		$mReturn = false;

		$oInquiry = $this->getInquiry();
		// Welche Art Transfer es ist
		if($this->transfer_type == self::TYPE_ADDITIONAL){
			return $this->checkAccommodationIndividualTransfer();
		}elseif($this->transfer_type == self::TYPE_ARRIVAL){
			$iStatus = 1;
		}elseif($this->transfer_type == self::TYPE_DEPARTURE){
			$iStatus = 2;
		}

		// Gematchte Unterkünfte (Erste - Letzte)
		$aAccommodations = $oInquiry->getFirstLastMatchedAccommodation();

		if($iStatus == 1){
			$oAccommodation = $aAccommodations['first'];
			if(
				is_object($oAccommodation) &&
				$oAccommodation->transfer_arrival == 1
			){
				$mReturn['family_name']		= $oAccommodation->ext_33;
				$mReturn['family_email']	= $oAccommodation->email;
				$mReturn['family_id']		= $oAccommodation->id;
			}
		}elseif($iStatus == 2){
			$oAccommodation = $aAccommodations['last'];
			if(
				is_object($oAccommodation) &&
				$oAccommodation->transfer_departure == 1
			){
				$mReturn['family_name']		= $oAccommodation->ext_33;
				$mReturn['family_email']	= $oAccommodation->email;
				$mReturn['family_id']		= $oAccommodation->id;
			}
		}

		if($mReturn === false) {
			return [];
		}

		return [$mReturn];
	}

	/**
	 * Bei individuellen Transfers werden die Anbieter direkt zugewiesen, das fehlt oben
	 *
	 * @return array
	 */
	private function checkAccommodationIndividualTransfer() {

		$aReturn = [];
		$oAddEntry = function(Ext_Thebing_Accommodation $oProvider) use (&$aReturn) {
			if(
				$oProvider->transfer_arrival == 1 ||
				$oProvider->transfer_departure
			) {
				$aReturn[] = [
					'family_name' => $oProvider->ext_33,
					'family_email' => $oProvider->email,
					'family_id' => $oProvider->id
				];
			}
		};

		if(
			$this->start_type === 'accommodation' &&
			$this->start > 0
		) {
			$oProvider = Ext_Thebing_Accommodation::getInstance($this->start);
			$oAddEntry($oProvider);
		}

		if(
			$this->end_type === 'accommodation' &&
			$this->end > 0
		) {
			$oProvider = Ext_Thebing_Accommodation::getInstance($this->end);
			$oAddEntry($oProvider);
		}

		return $aReturn;

	}

	//
	/**
	 * Liefert den zugewiesenen Provider für jede Inquiry-transfer ID
	 *
	 * Eine Methode mit entsprechenden Instanzen gibt es in der Ext_TS_Inquiry.
	 *
	 * @param $aInquiryTransferIds
	 * @return array
	 */
	public static function getProvider($aInquiryTransferIds){
		$aProvider = array();
		foreach((array)$aInquiryTransferIds as $iSelectedId) {
			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
			$oInquiry = $oTransfer->getInquiry();
			$oSchool = $oInquiry->getSchool();

			if(
				$oTransfer->provider_id > 0 &&
				$oTransfer->provider_type == 'provider'
			){
				// Provider
				$oProvider = Ext_Thebing_Pickup_Company::getInstance($oTransfer->provider_id);
				$aProvider[$oTransfer->provider_id] = $oProvider;
			}elseif(
				$oTransfer->provider_id > 0 &&
				$oTransfer->provider_type == 'accommodation'
			){
				// Unterkunft
				$oAccommodation = Ext_Thebing_Accommodation::getInstance($oTransfer->provider_id);
				$aProvider[$oTransfer->provider_id * (-1)] = $oAccommodation;
			}
		}
		return $aProvider;
	}

	// Liefert alle passende Provider für Inquiry-tranfer IDs
	public static function getAllProvider($aInquiryTransferIds){
		$aProvider = array();
		foreach((array)$aInquiryTransferIds as $iSelectedId) {
			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iSelectedId);
			$aTempProvider = $oTransfer->getTransferProvider();

			// Es dürfen nur Transferanbieter zur verfügung stehen die ALLE Transfers übernehmen können
			if(empty($aProvider)){
				$aProvider += $aTempProvider;
			}else{
				$aTempAllProvider = array();
				foreach((array)$aTempProvider as $iId => $aProv){
					if(array_key_exists($iId, $aProvider)){
						$aTempAllProvider[$iId] = $aProvider[$iId];
					}
				}
				$aProvider = $aTempAllProvider;
			}
		}
		
		return $aProvider;
	}

	// Liefert Die Mailadressen aller passender Provider zurück für mehrere Inquiry-transfer IDs
	// $bCommunicationFormated formatiert die adressen für die communikation da hier Provider UND Accommodations
	// enthalten sein können
	public static function getAllProviderMails($aInquiryTransferIds){

		$aProvider = self::getAllProvider($aInquiryTransferIds);

		$aAccProviderEmails = array();
		foreach((array)$aProvider as $iKey => $aProv){
			if(!\Util::checkEmailMX($aProv['email'])){
				continue;
			}

			if($iKey < 0){
				// Unterkunft
				$aInfo = array();
				$aInfo['object'] = 'Ext_Thebing_Accommodation';
				$aInfo['object_id'] = abs($iKey);
				$aInfo['email'] = $aProv['email'];
				$aInfo['name'] = $aProv['name'] . ' ('.$aProv['email'].')';
			}else{
				// Provider
				$aInfo = array();
				$aInfo['object'] = 'Ext_Thebing_Pickup_Company';
				$aInfo['object_id'] = abs($iKey);
				$aInfo['email'] = $aProv['email'];
				$aInfo['name'] = $aProv['name'] . ' ('.$aProv['email'].')';
			}

			$aAccProviderEmails[] = $aInfo;
		}

		return $aAccProviderEmails;
	}
	
	// Liefert die Mailadressen zugewiesener Provider für mehrere Inquiries
	public static function getAllProviderConfirmMails($aInquiryTransferIds){

		$aProvider = self::getProvider($aInquiryTransferIds);

		$aAccProviderEmails = array();
		// Mails für dei Kommunikation formatieren
		foreach((array)$aProvider as $iKey => $oProv){
			if(!\Util::checkEmailMX($oProv->email)){
				continue;
			}

			if($iKey > 0){
				// Provider
				$aInfo = array();
				$aInfo['object'] = 'Ext_Thebing_Pickup_Company';
				$aInfo['object_id'] = abs($iKey);
				$aInfo['email'] = $oProv->email;
				$aInfo['name'] = $oProv->name;
			}else{
				// Unterkunft
				$aInfo = array();
				$aInfo['object'] = 'Ext_Thebing_Accommodation';
				$aInfo['object_id'] = abs($iKey);
				$aInfo['email'] = $oProv->email;
				$aInfo['name'] = $oProv->ext_33;
			}
			$aAccProviderEmails[] = $aInfo;
		}

		return $aAccProviderEmails;
	}

	/**
	 * Passende Unterkunftsprovider für Anfang oder Ende des Transfers, benötigt für Kommunikation
	 *
	 * start_type und end_type können niemals gleichzeitig auf accommodation stehen, außer bei individuellen Transfers.
	 * start und end sind bei start_type = accommodation immer 0, außer bei individuellen Transfers.
	 *
	 * @return Ext_Thebing_Accommodation[]
	 */
	public function getMatchingAccommodationProviders() {

		$aAccommodations = [];

		if(
			$this->start_type === 'accommodation' &&
			$this->start > 0
		) {
			// Individueller Transfer (start ansonsten immer 0)
			$oAccommodation = Ext_Thebing_Accommodation::getInstance($this->start);
			$aAccommodations[] = $oAccommodation;
		} elseif($this->transfer_type == self::TYPE_DEPARTURE) {
			// Bei Departure: Unterkunftsprovider am Ende der Buchung
			$aFirstLastAccommodation = $this->getInquiry()->getFirstLastMatchedAccommodation();
			if($aFirstLastAccommodation['last'] instanceof Ext_Thebing_Accommodation) {
				$aAccommodations[] = $aFirstLastAccommodation['last'];
			}
		}

		if(
			$this->end_type === 'accommodation' &&
			$this->end > 0
		) {
			// Individueller Transfer (end ansonsten immer 0)
			$oAccommodation = Ext_Thebing_Accommodation::getInstance($this->end);
			$aAccommodations[] = $oAccommodation;
		} elseif($this->transfer_type == self::TYPE_ARRIVAL) {
			// Bei Arrival: Unterkunftsprovider am Anfang der Buchung
			$aFirstLastAccommodation = $this->getInquiry()->getFirstLastMatchedAccommodation();
			if($aFirstLastAccommodation['first'] instanceof Ext_Thebing_Accommodation) {
				$aAccommodations[] = $aFirstLastAccommodation['first'];
			}
		}

		return $aAccommodations;

	}

	/**
	 * Kommunikation: transfer_customer_accommodation_information
	 *
	 * @return array
	 */
	public function getMatchingAccommodationProvidersMails() {

		$aAccommodations = $this->getMatchingAccommodationProviders();
		$aAccommodationMails = [];

		foreach((array)$aAccommodations as $iKey => $oAccommodation) {

			if(!\Util::checkEmailMX($oAccommodation->email)) {
				continue;
			}

			$aAccommodationMails[] = [
				'object' => 'Ext_Thebing_Accommodation',
				'object_id' => $oAccommodation->id,
				'email' => $oAccommodation->email,
				'name' => $oAccommodation->ext_33.' ('.$oAccommodation->email.')'
			];

		}

		return $aAccommodationMails;

	}

	/*
	 * Liefert die Mailadressen der Kunden ODER Agenturen die Transfer gebucht haben für mehrere Inquiries
	 * 
	 * $sReturnType = 'agency' || 'customer'
	 * $bShowBoth = true <- Es werden auch Schüleradressen angegeben wenn Agentur vorhanden
	 */
	public static function getAllCustomerConfirmMails($aInquiryTransferIds, $sReturnType = '', $bShowBoth = false) {

		$aCustomerAgencies = self::getCustomerAgencies($aInquiryTransferIds, $bShowBoth);

		// Mails für Kommunikation formatieren
		$aBack = array();
		foreach((array)$aCustomerAgencies as $iKey => $aObjs){

			if(
				$iKey < 0 &&
				(
					$sReturnType == '' ||
					$sReturnType == 'agency'
				)
			){
				// Agentur
				// 1. Kontaktperson
				/* @var \Ext_TS_Inquiry $oInquiry */
				$oInquiry	= $aObjs['inquiry'];
				$oAgency	= $aObjs['agency'];
				
				// Unterkunftskontakte
				// Falls InquiryObjekt vorhanden, dann immer diese Funktion für die Agenturemails verwenden!
				$aContacts = $oInquiry->getAgencyContactsWithValidEmails('transfer');

				foreach((array)$aContacts as $oContact){
					$aInfo = array();
					$aInfo['object']	= 'Ext_Thebing_Agency_Contact';
					$aInfo['object_id'] = (int)$oContact->id;
					$aInfo['email']		= $oContact->email;
					$aInfo['name']		= $oContact->name_description;
					$aBack[] = $aInfo;
				}

			}elseif(
				$sReturnType == '' ||
				$sReturnType == 'customer'
			){
				// Kunden Mails
				$oInquiry = Ext_TS_Inquiry::getInstance(abs($iKey));
				$aBack = $oInquiry->getCustomerEmails();
			}

		}

		return $aBack;
	}
	
	// Kunden/Agenturobjekte der übergebenen Transfer IDs
	public static function getCustomerAgencies($aInquiryTransferIds, $bShowBoth = false){
		$aBack = array();
		foreach((array)$aInquiryTransferIds as $aTransferId){
			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($aTransferId);
			$oInquiry = $oTransfer->getInquiry();
			
			if(
				$oInquiry->agency_id > 0 ||
				$bShowBoth === true
			){
				// Agenturkunde
				$aBack[$oInquiry->agency_id * (-1)]['agency'] = $oInquiry->getAgency();
				$aBack[$oInquiry->agency_id * (-1)]['inquiry'] = $oInquiry;
			}
			if(
				$oInquiry->agency_id <= 0 ||
				$bShowBoth === true
			){
				// Direktkunde
				$aBack[$oInquiry->id] = $oInquiry->getCustomer();
			}
		}
		return $aBack;
	}

	// Liefert ein leeres Anfrage Objekt dieses Transfers zurück
	public function getNewProviderRequest(){
		$oProviderRequest = new Ext_Thebing_Inquiry_Provider_Request();
		$oProviderRequest->transfer_id = $this->id;

		return $oProviderRequest;
	}

	// Löscht alle Provideranfragen für diesen Transfer
	public function deleteProviderRequests(){
		$aRequests = $this->getProviderRequests();

		foreach((array)$aRequests as $oRequest){
			$oRequest->active = 0;
			$oRequest->save();
		}
	}

	public function getFirstProviderRequest(): ?Ext_Thebing_Inquiry_Provider_Request {
		$aRequests = $this->getProviderRequests();
		return \Illuminate\Support\Arr::first($aRequests);
	}

	// Alle Anfragen zu diesen Transfer(){
	public function getProviderRequests(){
		$sSql = "SELECT
						*
					FROM
						`kolumbus_inquiries_transfers_provider_request`
					WHERE
						`transfer_id`	= :transfer_id AND
						`active`		= 1";
		$aSql = array();
		$aSql['transfer_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$aBack = array();
		foreach((array)$aResult as $aData){
			$aBack[] = Ext_Thebing_Inquiry_Provider_Request::getInstance($aData['id']);
		}

		return $aBack;
	}

	/**
	 * Ersetzt in der Transfer Kommunikation den Platzhalter {transfer_communication} mit der jeweiligen Tabelle
	 * ACHTUNG: NICHT MEHR VERWENDEN!
	 * @see Ext_Thebing_Inquiry_Transfer_Placeholder
	 * @param <type> $sContent
	 * @param <type> $sApplication
	 * @param <type> $aSelectedIds
	 * @param <type> $iSessionId
	 * @param <type> $iHtml
	 * @return <type>
	 */
	public static function replaceTransferCommunicationPlaceholder($sContent, $sApplication, $aSelectedIds, $iSessionId, $iHtml){

		// Aktuelle Object ID
		$iObjectId		= Ext_Thebing_Communication::getSessionInformation($iSessionId, 'type_id');
		// Aktueller Objecttyp
		$sObjectType	= Ext_Thebing_Communication::getSessionInformation($iSessionId, 'type');

		$aTransfers = array();
		foreach((array)$aSelectedIds as $iTransferId){
			$aTransfers[] = self::getInstance($iTransferId);
		}

		// ProviderInformationen mitschicken in der Platzhaltertabelle
		$bShowProviderInformation = false;

		$sPlaceholder = '';

		switch($sApplication){
			case 'transfer_customer_agency_information':
					// Nur wenn Transfer dem gewählten Kunden bzw. Agentur zugeordnet werden können
					foreach((array)$aTransfers as $iKey => $oTransfer){
						$oInquiry = $oTransfer->getInquiry();
						$oCustomer = $oInquiry->getCustimer();
						if($oInquiry->agency_id > 0){
							// Agentur
							$sType = 'agency';
							$iType_id = $oInquiry->agency_id;
						}else{
							// Direktkunde
							$sType = 'customer';
							$iType_id = $oCustomer->id;
						}

						if(
							$sType != $sObjectType ||
							$iType_id != $iObjectId
						){
							unset($aTransfers[$iKey]);
						}
					}

					break;
			case 'transfer_customer_accommodation_information':
					// Nur wenn Zielpunkt des Transfers die aktuelle Familie ist an die geschickt wird
					foreach((array)$aTransfers as $iKey => $oTransfer){
						if(
							$oTransfer->end_type != $sObjectType ||
							$oTransfer->end != $iObjectId
						){
							unset($aTransfers[$iKey]);
						}
					}
					break;
			case 'transfer_provider_confirm':
					// Nur die Transfers bestätigen die auch aktuellen Provider sind
					foreach((array)$aTransfers as $iKey => $oTransfer){
						if(
							$oTransfer->provider_id != $iObjectId ||
							$oTransfer->provider_type != $sObjectType
						){
							unset($aTransfers[$iKey]);
						}
					}
					$bShowProviderInformation = true;
					break;
			case 'transfer_provider_request':
					// Da jeder Transferanbieter hier JEDEN Transfer übernehmen kann bekommt
					// jeder auch alles zugeschickt :P
					break;
			default:
					break;
		}

		$sPlaceholder = self::getCommunicationTable($aTransfers, $bShowProviderInformation, $iHtml);

		$sContent = str_replace('{transfer_communication}', $sPlaceholder, $sContent);

		return $sContent;
	}

	// Erstellt eine Tabelle der Transfers für die Transferkommunikation
	public static function getCommunicationTable($aTransfers, $bShowProviderInformation = false, $iHtml = 1){

		$sHtml = '';
		if(count($aTransfers) < 1){
			return $sHtml;
		}

		$oProviderFormat	= new Ext_Thebing_Gui2_Format_Transfer_ProviderName();
		$oDriverFormat		= new Ext_Thebing_Gui2_Format_Transfer_Driver();

		// Prüfen ob einer der Trnsfers An bzw. Abreise ist
		$bShowAdditionalData = false;
		// Prüfen ob AnreiseTerminal existiert
		$ShowStartTerminal = false;
		// Prüfen ob AbreiseTerminal existiert
		$ShowEndTerminal = false;
		// Prüfen ob Provider existiert
		$ShowProvider = false;
		// Prüfen ob Fahrer existiert
		$ShowDriver = false;
		// Prüfen ob Kommentar existiert
		$bShowComment = false;


		foreach((array)$aTransfers as $oTransfer){
			if(
				$oTransfer->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL ||
				$oTransfer->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE
			){
				$bShowAdditionalData = true;
			}

			if($oTransfer->start_additional > 0){
				$ShowStartTerminal = true;
			}
			if($oTransfer->end_additional > 0){
				$ShowEndTerminal = true;
			}
			if($oTransfer->provider_id > 0){
				$ShowProvider = true;
			}
			if($oTransfer->driver_id){
				$ShowDriver = true;
			}

			if($oTransfer->comment){
				$bShowComment = true;
			}
		}

		$sHtml .= '<table class="table" style="border: 1px">';
			$sHtml .= '<tr>';
				$sHtml .= '<th>';
					$sHtml .= L10N::t('Kunde');
				$sHtml .= '</th>';
				$sHtml .= '<th>';
					$sHtml .= L10N::t('Transfer');
				$sHtml .= '</th>';
				if($ShowStartTerminal){
					$sHtml .= '<th>';
						$sHtml .= L10N::t('Start Platform');
					$sHtml .= '</th>';
				}
				if($ShowEndTerminal){
					$sHtml .= '<th>';
						$sHtml .= L10N::t('End Platform');
					$sHtml .= '</th>';
				}
				if($bShowAdditionalData){
					$sHtml .= '<th>';
						$sHtml .= L10N::t('Flugnummer');
					$sHtml .= '</th>';
					$sHtml .= '<th>';
						$sHtml .= L10N::t('Fluglinie');
					$sHtml .= '</th>';
					$sHtml .= '<th>';
						$sHtml .= L10N::t('Abholung');
					$sHtml .= '</th>';
				}
				if($bShowComment){
					$sHtml .= '<th>';
						$sHtml .= L10N::t('Kommentar');
					$sHtml .= '</th>';
				}
				if($bShowProviderInformation){
					if($ShowProvider){
						$sHtml .= '<th>';
							$sHtml .= L10N::t('Provider');
						$sHtml .= '</th>';
					}
					if($ShowDriver){
						$sHtml .= '<th>';
							$sHtml .= L10N::t('Fahrer');
						$sHtml .= '</th>';
					}
				}
			$sHtml .= '</tr>';

		foreach((array)$aTransfers as $oTransfer){
			$sHtml .= '<tr>';
				$sHtml .= '<td>';
					$sHtml .= $oTransfer->getInquiry()->getCustomer()->name;
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sHtml .= $oTransfer->getName();
				$sHtml .= '</td>';
				if($ShowStartTerminal){
					$sHtml .= '<td>';
						$sHtml .= $oTransfer->getStartTerminal();
					$sHtml .= '</td>';
				}
				if($ShowEndTerminal){
					$sHtml .= '<td>';
						$sHtml .= $oTransfer->getEndTerminal();
					$sHtml .= '</td>';
				}
				if($bShowAdditionalData){
					$sHtml .= '<td>';
						$sHtml .= $oTransfer->flightnumber;
					$sHtml .= '</td>';
					$sHtml .= '<td>';
						$sHtml .= $oTransfer->airline;
					$sHtml .= '</td>';
					$sHtml .= '<td>';
						$sHtml .= $oTransfer->pickup;
					$sHtml .= '</td>';
				}
				if($bShowComment){
					$sHtml .= '<td>';
						$sHtml .= $oTransfer->comment;
					$sHtml .= '</td>';
				}
				if($bShowProviderInformation){
					if($ShowProvider){
						$sHtml .= '<td>';
							$aTemp = array();
							$aTemp['provider_id'] = $oTransfer->provider_id;
							$aTemp['provider_type'] = $oTransfer->provider_type;
							$sHtml .= $oProviderFormat->format($aTemp, $aTemp, $aTemp);
						$sHtml .= '</td>';
					}
					if($ShowDriver){
						$sHtml .= '<td>';
							$aTemp = array();
							$aTemp['inquiry_transfer_id'] = $oTransfer->id;
							$aTemp['provider_id'] = $oTransfer->provider_id;
							$sHtml .= $oDriverFormat->format($oTransfer->driver_id, $aTemp, $aTemp);
						$sHtml .= '</td>';
					}
				}
			$sHtml .= '</tr>';
		}
		$sHtml .= '</table>';

		// Nicht HTML Mails formatieren
		if($iHtml != 1){
			// Gibts noch nicht
		}


		return $sHtml;
	}

	/*
	 * Start Terminal
	 */
	public function getStartTerminal(){

		if(
			$this->start_type == 'location' &&
			$this->start > 0 &&
			$this->start_additional > 0
		){
			$oAirportAdditional = Ext_TS_Transfer_Location_Terminal::getInstance($this->start_additional);
			return $oAirportAdditional->description;
		}
		return '';
	}

	/*
	 * End Terminal
	 */
	public function getEndTerminal(){

		if(
			$this->end_type == 'location' &&
			$this->end > 0 &&
			$this->end_additional > 0
		){
			$oAirportAdditional = Ext_TS_Transfer_Location_Terminal::getInstance($this->end_additional);
			return $oAirportAdditional->description;
		}
		return '';
	}

	// Liefert den Ankunftsort eines Transfers
	public function getStartLocation($oLanguage=null) {
		$oInquiry = $this->getInquiry();
		$aLocations = $oInquiry->getTransferLocations('arrival', $oLanguage);

		return  $aLocations[$this->start_type . '_' . $this->start];
	}

	// Liefert den Abreiseort eines Transfers
	public function getEndLocation($oLanguage=null) {
		$oInquiry = $this->getInquiry();
		$aLocations = $oInquiry->getTransferLocations('departure', $oLanguage);
		
		return  $aLocations[$this->end_type . '_' . $this->end];
	}

	/**
	 * {@inheritdoc}
	 */
    public function isChanged($sField = '') {
        
        if($sField == 'transfer_time'){
            
            $sOriginalData = $this->getOriginalData($sField);

			// Wert kann null sein
			if(!empty($sOriginalData)) {
				$sTransfertimeOriginal = $sOriginalData;
				$sTransfertimeOriginal = explode(':', $sTransfertimeOriginal);
				$sTransfertimeOriginal = $sTransfertimeOriginal[0].':'.$sTransfertimeOriginal[1];
			} else {
				$sTransfertimeOriginal = '00:00';
			}

            $sTransfertime = $this->transfer_time;
            if(empty($sTransfertime)){
                $sTransfertime = '00:00';
            }

			$bChanged = $sTransfertime != $sTransfertimeOriginal;
            
        } else if($sField == 'transfer_date'){
			
			$sOriginalData			= $this->getOriginalData($sField);
            $sData = $this->getData($sField);
			
            if(empty($sData)){
                $sData = '0000-00-00';
            }
			
            if(empty($sOriginalData)){
                $sOriginalData = '0000-00-00';
            }
			
            $bChanged = false;
			
            if($sOriginalData != $sData){
                $bChanged = true;
            }
			
        } else {
            $bChanged = parent::isChanged($sField);
        }
        
        return $bChanged;
    }

	// Vergleicht DIESEN Transfer mit einam anderen und prüft auf änderungen (Gruppen)
	public function checkForChange( $oTransfer = null, $sModus = 'complete') {
        
		if($this->id <= 0) {
			return false;
		}

		if($oTransfer == null) {
			$aOriginalData = $this->getOriginalData();
		} else {
			$aOriginalData = $oTransfer->getData();
		}
		
		$aOriginalDataOld		= $this->_aOriginalData;
		
		$this->_aOriginalData	= $aOriginalData;
		
		$bChanged = false;

		if($sModus == 'complete'){ 
            
			if(
				$this->isChanged('start') ||
				$this->isChanged('end') ||
				$this->isChanged('start_type') ||
				$this->isChanged('end_type') ||
				$this->isChanged('transfer_date') ||
				$this->isChanged('transfer_time') ||
				$this->isChanged('comment') ||
				$this->isChanged('start_additional')  ||
				$this->isChanged('end_additional') ||
				$this->isChanged('airline')  ||
				$this->isChanged('flightnumber') ||
				$this->isChanged('pickup') ||
				$this->isChanged('active')
			){
				$bChanged = true;
			}
            
		} elseif($sModus == 'invoice'){ 
			if(
				$this->isChanged('start') ||
				$this->isChanged('end') ||
				$this->isChanged('start_type') ||
				$this->isChanged('end_type') ||
				$this->isChanged('transfer_date') ||
				$this->isChanged('transfer_time') ||
				$this->isChanged('start_additional')  ||
				$this->isChanged('end_additional') ||
				$this->isChanged('pickup') ||
				$this->isChanged('active')
			){
				$bChanged = true;
			}
		} elseif($sModus == 'only_time'){ 
			if(
				$this->isChanged('transfer_date') ||
				$this->isChanged('transfer_time')
			){
				$bChanged = true;
			}
		}
		
		$this->_aOriginalData = $aOriginalDataOld;


		return $bChanged;
	}

	public function setChanged($bForce = false) {

		if ($bForce) {
			$bStatus = true;
		} else {
			$bStatus = $this->checkForChange(null, 'invoice');
		}

		$sStatus = false;

		if (
			$bStatus &&
			$this->id > 0
		) {

			$sStatus = 'edit';
			if(!$this->isActive()) {
				$sStatus = 'deleted';
			}

		} else if($this->id <= 0) {
			$sStatus = 'new';
		}

		if (!$sStatus) {
			return;
		}

		// #4011 - Transferort abändern = Bestätigung wird nicht gelb gefärbt
		$this->updated = time();

		if (!$sStatus) {
			$sStatus = 'edit';
		}
		##
		// Wenn der Transfer gebucht ist
		// anreise ist und anreise oder an&abreise gebucht ist
		// oder
		// abreise und abreise oder an&abreise gebucht ist
		// oder
		// indiv. ist
		if(
			(
				self::checkIfBooked((int)$this->transfer_type, (int)$this->getJourney()->transfer_mode) ||
				$this->transfer_type == self::TYPE_ADDITIONAL
			) &&
			$this->booked == 1
		) {
			Ext_Thebing_Inquiry_Document_Version::setChange($this->getJourney()->getInquiry()->id, $this->id, 'transfer', $sStatus);
		}

	}

	// gibt den Status des Transfers wieder ob er verändert wurde
	public function checkchanged(){
		
		/**
		 * Redmine #4007
		 * Ist eine Uhrzeit bei Abholung eingetragen, dann soll sich beim verändern der Anreisezeit die Spalte nicht 
		 * gelb färben. Ist keine Uhrzeit bei "Abholung" eingepflegt, dann soll sich beim verändern der Anreisezeit die 
		 * spalte gelb färben.
		 */		
		if($this->_sChangedField != '') {
			if(
				$this->_sChangedField == 'transfer_time' &&
				$this->pickup != NULL
			) {
				return false;
			}
		}
		
		return $this->checkchanged;
	}
	
	/**
	 * Prüft ob der Transfer auf Veränderungen geprüft werde soll
	 * @return boolean 
	 */
	protected function _checkForChange(){
		
		$bCheck = false;
		
		$oJourney = $this->getJourney();
		
		// Ein Transfer soll nur als "verändert" markiert werden, wenn er auch gebucht ist!
		if(
			(
				$oJourney->transfer_mode & $oJourney::TRANSFER_MODE_ARRIVAL &&
				$this->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL
			) || (
				$oJourney->transfer_mode & $oJourney::TRANSFER_MODE_DEPARTURE &&
				$this->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE
			) || (
				$this->transfer_type == Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL
			)
		){
			$bCheck = true;
		}
		
		return $bCheck;
	}

	/**
	 * @TODO Kann man diese Methode hier nicht komplett refaktorisieren und entfernen?
	 *
	 * @param string $sField
	 * @param mixed $mValue
	 */
	public function __set($sField, $mValue) {

		// Gucken ob auf Veränderungen gepfüft werden soll
		// TODO Das muss anderswo gelöst werden (siehe auch nachfolgenden Kommentar)
		$bCheck = false;
		if($this->exist()) {
			// Nur bei existierenden, sonst wird bei neuen Objekten einfach eine leere Journey/Inquiry ins Objekt gesetzt
			// Oder auch: Das funktioniert nur mal wieder mit einer ID
			// Das und _checkForChange() wuden in #2759 eingebaut
			$bCheck = $this->_checkForChange();
		}

		// Wird geprüft ob Transfer sich geändert hat
		if(
			(
				$sField == 'start' ||
				$sField == 'end' ||
				$sField == 'start_type' ||
				$sField == 'end_type' ||
				$sField == 'transfer_date' ||
				$sField == 'transfer_time' ||
				$sField == 'pickup' ||
				$sField == 'start_additional' ||
				$sField == 'end_additional'
			) && (
				$this->id > 0 &&
				$bCheck
			)
		) {

			$mValueFormat = $mValue; 
			
			if(
				$sField == 'transfer_date' &&
				is_numeric($mValue) &&
				$mValue != ""
			){
				$oDate = new WDDate($mValue);
				$mValueFormat = $oDate->get(WDDate::DB_DATE);
			} else if(
				$sField == 'transfer_date' &&
				$mValue == ""
			){
				$mValueFormat = '0000-00-00';
			}

			// Minuten hinzufügen zum vergleichen
			if(
				(
					$sField == 'transfer_time' ||
					$sField == 'pickup'
					
				)&&	
				$mValue != "" &&
				strlen($mValue) < 5
			){
				if($mValue > 0){
					$mValue .= ':00:00';
				}
				$mValueFormat = $mValue;
			} else if(
				(
					$sField == 'transfer_time' ||
					$sField == 'pickup'
					
				)&&	
				$mValue != "" &&
				strlen($mValue) == 5
			) {
				if($mValue > 0){
					$mValue .= ':00';
				}
				$mValueFormat = $mValue;
			} else if(
				(
					$sField == 'transfer_time' ||
					$sField == 'pickup'
					
				)&&
				$mValue == ""
			){
				$mValueFormat = NULL;
			}

			// TODO Was hat das im Setter zu suchen?
			if($this->$sField != $mValueFormat){
				// Transfer hat sich verändert
				$this->checkchanged = true;
				$this->_sChangedField = $sField;
			}

			// Minuten wieder entfernen
			if($sField == 'transfer_time'){
				if($mValue > 0){
					$mValue = substr($mValue, 0, 5);
				}
			}
		}

		parent::__set($sField, $mValue);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getCommunicationTemplateKey($aItem = null, $sApplication = '') {

		if($sApplication == 'transfer_customer_agency_information') {
			if(
				is_array($aItem) &&
				$aItem['object'] == 'Ext_Thebing_Agency'
			) {
				$sKey = 'agency';
			} else {
				$sKey = 'customer';
			}
		} else {
			$sKey = parent::getCommunicationTemplateKey($aItem, $sApplication);
		}

		return $sKey;

	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($bLog=true) {

		$bSuccess = parent::delete($bLog);

		// alte Anfragen mit löschen
		$aRequests = Ext_Thebing_Util::convertDataIntoObject($this->transfer_requests, 'Ext_Thebing_Inquiry_Provider_Request');

		foreach((array)$aRequests as $oRequest){
			$oRequest->delete();
		}

		return $bSuccess;
	}


	public function deletePaymentData(){

		foreach((array)$this->accounting_payments as $aData){
			$oPayment = Ext_Thebing_Transfer_Payment::getInstance((int)$aData['id']);
			$oPayment->delete();
		}
	
	}

	public function save($bLog = true) {

		$bNew = false;
		if (
			// Auch leere Transfers werden gespeichert, schauen ob wirklich was gebucht wurde
			(empty($this->_aOriginalData['start']) && !empty($this->start)) ||
			(empty($this->_aOriginalData['end']) && !empty($this->end))
		) {
			$bNew = true;
		}

		$mReturn = parent::save($bLog);

		if ($bNew && $this->exist()) {
			\Ts\Events\Inquiry\Services\NewJourneyTransfer::dispatch($this->getJourney()->getInquiry(), $this);
		}

		return $mReturn;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);
		
		if($mValidate === true){
			
			$mValidate	= array();

			if(
				(
					$this->_aOriginalData['driver_id'] != $this->driver_id ||
					$this->_aOriginalData['provider_type'] != $this->provider_type
				)&&
				$this->id > 0
			){
				// auf vorhandene Bezahlungen prüfen, wurde bezahlt darf der Provider  nicht geändert werden
				$aPayments = $this->getJoinedObjectChilds('accounting_payments_active');
				
				if(!empty($aPayments)){
					$mValidate['provider_id'] = 'TRANSFER_PROVIDER_PAYED';
				}
				
			}

			if(empty($mValidate)) {
				$mValidate = $this->_validateAirports();
			}
			
			if(empty($mValidate)){
				$mValidate = true;
			}
		}
		
		return $mValidate;
	}
	
	/**
	 * prüft, ob die angebenen Airports an dem Transferdatum verfügbar sind
	 * @return array
	 */
	protected function _validateAirports() {
		$aErrors = array();
				
		$sTransferDate = $this->transfer_date;
		if(
			$this->transfer_date != '' &&
			$this->transfer_date != '0000-00-00'
		) {
			
			if($this->start_type == 'location') {
				$iId = (int) $this->start;
				$oAirportStart = Ext_TS_Transfer_Location::getInstance($iId);

				if(!$oAirportStart->isValid($sTransferDate)) {
					$aErrors[$this->_sTableAlias.'.start'][] = 'INVALID_AIRPORT';
				}				
			}

			if($this->end_type == 'location') {
				$iId = (int) $this->end;
				$oAirportEnd = Ext_TS_Transfer_Location::getInstance($iId);
				
				if(!$oAirportEnd->isValid($sTransferDate)) {
					$aErrors[$this->_sTableAlias.'.end'][] = 'INVALID_AIRPORT';
				}				
			}
		}
		
		return $aErrors;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getKey() {
		return 'transfer';
	}

	/**
	 * {@inheritdoc}
	 */
	public function validatePayment() {
		return array();
	}

	public function getIndexTransferDateTime() {

		$sTime = $this->transfer_time;
		$sReturn = $this->transfer_date;

        if(empty($sReturn) || $sReturn == '0000-00-00'){
            return null; // wichtig da 0000-00-00 nicht indiziert werden kann und es wegen dem "T" nicht von der GUI abgefangen wird
        }

		if(!empty($sTime)) {
			$sReturn .= 'T'.$sTime;
		}

		return $sReturn;

	}

	/**
	 * Errechnet den Transferpreis für die aktuellen Einstellungen.
	 *
	 * Die Methode berücksichtigt den Frühbucherrabatt ausgehend vom aktuellen Datum.
	 *
	 * Es wird nur der Ein-Wege-Transfer berücksichtigt!
	 *
	 * Der zurückgegebene Preis beinhaltet die Umsatzsteuer.
	 *
	 * @param array $aTransferPackageSearchParams
	 * @return float
	 */
	public function getTransferPrice($aTransferPackageSearchParams = array()) {

		$oInquiry = $this->getInquiry();
		$oCurrency = Ext_Thebing_Currency::getInstance($oInquiry->currency_id);
		$oJourney = $oInquiry->getJourney();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($oJourney->school_id);

		if(!is_array($aTransferPackageSearchParams)) {
			$aTransferPackageSearchParams = array();
		}
		$aTransferPackageSearchParams['currency_id'] = $oCurrency->id;
		$aTransferPackageSearchParams['school'] = $oSchool;

		$oPackage = Ext_Thebing_Transfer_Package::searchPackageByTransfer($this, 0, false, $aTransferPackageSearchParams);
		if(!($oPackage instanceof Ext_Thebing_Transfer_Package)) {
			return 0.0;
		}

		$fPrice = $oPackage->amount_price;

		if($oSchool->getTaxStatus() == Ext_Thebing_School::TAX_EXCLUSIVE) {

			$iTaxRate = 0;
			$iTaxCategory = Ext_TS_Vat::getDefaultCombination('TRANSFER', -1, $oSchool->id);
			if($iTaxCategory > 0) {
				$iTaxRate = Ext_TS_Vat::getTaxRate($iTaxCategory, $oSchool->id);
			}

			$aTax = Ext_TS_Vat::calculateExclusiveTaxes($fPrice, $iTaxRate);
			$fPrice += $aTax['amount'];

		}

		return (float)$fPrice;

	}

	/**
	 * @return null|DateTime
	 */
	public function getTransferDate() {

		if(
			empty($this->transfer_date) ||
			$this->transfer_date === '0000-00-00'
		) {
			return null;
		}

		$dDate = new DateTime($this->transfer_date);
		return $dDate;

	}

	/**
	 * Unterkunftszuweisung, die in den Zeitraum des Transferdatums reinfällt
	 *
	 * Das sollte eigentlich immer nur eine einzige Zuweisung sein.
	 *
	 * @return Ext_Thebing_Accommodation_Allocation|null
	 */
	public function getAccommodationAllocationWithinTransferDate() {

		$sSql = "
			SELECT
				`kaa`.*
			FROM
				`ts_inquiries_journeys_accommodations` `ts_ija` INNER JOIN
				`kolumbus_accommodations_allocations` `kaa` ON
					`kaa`.`inquiry_accommodation_id` = `ts_ija`.`id` AND
					`kaa`.`active` = 1 AND
					`kaa`.`status` = 0 AND
					`kaa`.`active_storno` = 1
			WHERE
				`ts_ija`.`journey_id` = :journey_id AND
				`ts_ija`.`active` = 1 AND
				:transfer_date BETWEEN `kaa`.`from` AND `kaa`.`until`
			LIMIT
				1
		";

		$aAllocation = DB::getQueryRow($sSql, $this->_aData);
		if(!empty($aAllocation)) {
			return Ext_Thebing_Accommodation_Allocation::getObjectFromArray($aAllocation);
		}

		return null;

	}

	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {
		
	}

	/**
	 * Start und Ende werden in Formularen gemerged verwendet, wird in der DB aber separat gespeichert
	 *
	 * @param string $sField
	 * @param string $sLocationString
	 */
	public function setLocationByMergedString(string $sField, string $sLocationString) {

		// location_0 / school_1 / accommodation_123
		[$sType, $iId] = explode('_', $sLocationString, 2);

		$this->$sField = (string)(int)$iId; // $this->start / $this->end
		$this->{$sField.'_type'} = $sType; // $this->start_type / $this->end_type

	}

	public function buildLocationMergedString(string $sField): string {

		if (empty($this->{$sField.'_type'})) {
			return '';
		}

		return $this->{$sField.'_type'}.'_'.(int)$this->$sField;

	}

	public function getRegistrationFormData(): array {

		$origin = null;
		if (!empty($this->start_type)) {
			$origin = $this->start_type.'_'.$this->start;
		}

		$destination = null;
		if (!empty($this->end_type)) {
			$destination = $this->end_type.'_'.$this->end;
		}

		return [
			'type' => match ((int)$this->transfer_type) {
				\Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL => 'arrival',
				\Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE => 'departure',
				default => null
			},
			'origin' => $origin,
			'destination' => $destination,
			'airline' => !empty($this->airline) ? $this->airline : null,
			'flight_number' => !empty($this->flightnumber) ? $this->flightnumber : null,
			'date' => $this->transfer_date !== null ? 'date:'.Carbon::parse($this->transfer_date)->toDateString() : null,
			'time' => !empty($this->transfer_time) ? substr($this->transfer_time, 0, 5) : null,
			'comment' => !empty($this->comment) ? $this->comment : null,
			'mode' => (int)$this->getJourney()->transfer_mode
		];

	}

	public static function checkIfBooked(int $transferType, int $transferMode) {

		return (
			(
				$transferMode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL &&
				$transferType === self::TYPE_ARRIVAL
			) ||
			(
				$transferMode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE &&
				$transferType === self::TYPE_DEPARTURE
			)
		);

	}

	public function hasProviderAndDriver(): bool {
		return ($this->provider_id > 0 && $this->driver_id > 0);
	}

	public function getProviderUpdatedDate(): ?Carbon {

		if (!empty($this->provider_updated)) {
			return Carbon::createFromTimestamp($this->provider_updated);
		}

		return null;
	}

	public function getProviderConfirmedStatus(): \Ts\Dto\Transfer\ConfirmationStatus {

		$date = (!empty($this->provider_confirmed))
			? Carbon::createFromTimestamp($this->provider_confirmed)
			: null;

		return new \Ts\Dto\Transfer\ConfirmationStatus($this, $date);
	}

	public function getCustomerAgencyConfirmedStatus(): \Ts\Dto\Transfer\ConfirmationStatus {

		$date = (!empty($this->customer_agency_confirmed))
			? Carbon::createFromTimestamp($this->customer_agency_confirmed)
			: null;

		return new \Ts\Dto\Transfer\ConfirmationStatus($this, $date);
	}

	public function getAccommodationConfirmedStatus(): \Ts\Dto\Transfer\ConfirmationStatus {

		$date = (!empty($this->accommodation_confirmed))
			? Carbon::createFromTimestamp($this->accommodation_confirmed)
			: null;

		return new \Ts\Dto\Transfer\ConfirmationStatus($this, $date);
	}

	public function getUpdatedDate(): ?Carbon {

		if (!empty($this->updated)) {
			return Carbon::createFromTimestamp($this->updated);
		}

		return null;
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \Ts\Communication\Application\Transfer\ProviderRequest::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $this->getName();
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getJourney()->getSchool();
	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getJourney()->getInquiry()
		];
	}
}
