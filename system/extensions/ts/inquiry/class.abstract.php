<?php

use Communication\Interfaces\Model\CommunicationSubObject;
use Ts\Interfaces\Entity\DocumentRelation;
use TsPrivacy\Interfaces\Entity as PrivacyEntity;
use Tc\Service\LanguageAbstract;
use Communication\Interfaces\Model\HasCommunication;

/**
 * @property integer $id
 * @property integer $active
 * @property int $anonymized
 * @property mixed $created
 * @property integer $agency_id
 * @property integer $agency_contact_id
 * @property integer $payment_method
 *
 * @method Ext_Thebing_School getSchool()
 */
abstract class Ext_TS_Inquiry_Abstract extends Ext_Thebing_Basic implements PrivacyEntity, DocumentRelation, HasCommunication
{
	use \Core\Traits\WdBasic\TransientTrait,
		\Communication\Traits\Model\WithCommunicationMessages,
		 \Core\Traits\UniqueKeyTrait,
		\Ts\Traits\Entity\HasDocuments;

	/**
	 * null ist noch nicht abgerufen, leeres Array bedeutet keine Specials verfügbar für die Buchung
	 *
	 * @var \Ts\Model\Special\InquirySpecial[]
	 */
	public $inquirySpecials = null;

	public function getListQueryData($oGui = null) {
		$aQueryData = array('sql' => '', 'data' => array('table' => $this->_sTable));
		$aQueryData['sql'] = ' SELECT `id`, `created` FROM #table WHERE `active` = 1 ORDER BY `created` ASC ';
		return $aQueryData;
	}

    public function getAllocatedTransfers()
	{
        return array();
    }
    
	/**
	 * @param bool $mFor
	 * @return Ext_Thebing_Agency_Contact[]
	 */
	public function getAgencyContactsWithValidEmails($mFor = null) {

		$aContacts = array();

		/* @var $oAgency Ext_Thebing_Agency */
		$oAgency = $this->getAgency();
	
		if (is_object($oAgency) && $oAgency->id > 0) {
			
			// war so gewünscht, falls bei der Buchung eine Kontaktperson gewählt ist, alles andere ignorieren (#3415)
			$iAgencyContactId = (int)$this->getAgencyContactId();
			
			if($iAgencyContactId > 0) {
				$mFor = $iAgencyContactId;
			}

			$aContacts = $oAgency->getContacts(false, true, $mFor);
			
			// Adressen rausfiltern 
			foreach((array)$aContacts as $iKey =>  $oContact) {
				if(
					$oContact->email != '' &&
					!Util::checkEmailMX($oContact->email)
				) {
					unset($aContacts[$iKey]);
				}
			}
		}

		return $aContacts;

	}
	
	/**
	 * Agenturkontaktperson ID
	 * 
	 * @return int 
	 */
	public function getAgencyContactId()
	{
		// Spezieller Kontakt der Buchung
		$iContactForInquiry = (int)$this->agency_contact_id;
		
		return $iContactForInquiry;
	}
	
	/**
	 *
	 * @return Ext_Thebing_Agency_Contact
	 */
	public function getAgencyContact()
	{
		$iContactForInquiry = $this->getAgencyContactId();

		$oContact			= Ext_Thebing_Agency_Contact::getInstance($iContactForInquiry);
		return $oContact;
	}
	
	/**
	 * @return Ext_Thebing_Agency
	 */
	public function getAgency() {
	
		$oAgency = $this->getJoinedObject('agency');

		$oSchool = $this->getSchool();
        $oAgency->setSchool($oSchool);

		return $oAgency;
	}

	/**
	 * @return array
	 */
	public static function getAgencyListForSelect() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return $oClient->getAgencyLists(true);
	}

	/**
	 * Gibt ein Array mit allen oder ausgewählten Dokumenten zurück
	 * $draft = null -> Entwürfe und Normale
	 * $draft = false -> nur Normale
	 * $draft = true -> nur Entwürfe
	 *
	 * @param string $mType
	 * @param bool $bReturnAllData
	 * @param bool $bReturnObjects
	 * @param ?bool $draft
	 * @return int|Ext_Thebing_Inquiry_Document|Ext_Thebing_Inquiry_Document[]
	 * @throws Exception
	 * @see Ext_Thebing_Inquiry_Document_Type_Search::getSectionTypes()
	 *
	 */
	public function getDocuments($mType = 'all', $bReturnAllData = true, $bReturnObjects = false, ?bool $draft = false) {

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);
		$oSearch->setType($mType);
		$oSearch->setObjectType(Ext_TS_Inquiry::class);

		// Ein Offer gehört nie einer Buchung, sondern immer einem Journey
		if (
			$mType === 'all' ||
			in_array('offer', (array)$mType)
		) {
			$oSearch->addJourneyDocuments();
		}

		$oSearch->setDraft($draft);

		$mReturn = $oSearch->searchDocument($bReturnObjects, $bReturnAllData);

		if($bReturnAllData) {
			return (array)$mReturn;
		} else {
			if($bReturnObjects) {
				return $mReturn;
			} else {
				return (int)$mReturn;
			}
		}		

	}

	/**
	 * Liefert Dokumente über die ganze Gruppe
	 *
	 * @param string $mType
	 * @param bool $bReturnAllData
	 * @return Ext_Thebing_Inquiry_Document[]
	 */
	public function getGroupDocuments($mType='all', $bReturnAllData=true) {
		$aReturn = array();

		$oGroup = $this->getGroup();
		if($oGroup instanceof Ext_Thebing_Inquiry_Group) {
			$aInquiries = $oGroup->getInquiries();
		} else {
			$aInquiries = array($this);
		}

		foreach($aInquiries as $oInquiry) {

			$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($oInquiry, $mType, $bReturnAllData, true);

			// $bReturnAllData = false liefert kein Array, sondern ein Objekt direkt
			if(!is_array($aDocuments)) {
				$aDocuments = (array)$aDocuments;
			}

			foreach($aDocuments as $oDocument) {
				$aReturn[$oDocument->id] = $oDocument;
			}
		}

		return $aReturn;
	}

	/**
	 * Liefert einmalige Dokumente über die ganze Gruppe
	 *
	 * Beispielsweise wird für jedes Mitglied ein Bezahlbeleg erstellt,
	 * 	aber jeder Bezahlbeleg ist gleich (gleiches PDF)
	 *
	 * @param string $mType
	 * @param bool $bReturnAllData
	 * @return Ext_Thebing_Inquiry_Document[]
	 * @throws InvalidArgumentException
	 */
	public function getUniqueGroupDocuments($mType='all', $bReturnAllData=true) {
		$aReturn = array();

		$aDocuments = $this->getGroupDocuments($mType, $bReturnAllData);

		if(
			$mType === 'receipt_customer' ||
			$mType === 'receipt_agency'
		) {
			$aPaymentIds = array();

			foreach($aDocuments as $oDocument) {
				// Bezahlbelege sind über 1:1-Tabelle zu Bezahlungen zugewiesen
				$iPaymentId = reset($oDocument->payments);
				if(!in_array($iPaymentId, $aPaymentIds)) {
					$aReturn[] = $oDocument;
					$aPaymentIds[] = $iPaymentId;
				}
			}
		} else {
			throw new InvalidArgumentException();
		}

		return $aReturn;
	}

	/**
	 * Funktion liefert das letzte PDF(Pfad) zu einem Typ zurück
	 *
	 * @param string|array $mType
	 * @param array $aTemplateTypes
	 * @param Ext_Thebing_Inquiry_Document_Search $oSearch
	 * @return string|null
	 */
	public function getLastDocumentPdf($mType, $aTemplateTypes = [], $oSearch = null) {

		$sPdfPath = null;
		
		$oLastDocument = $this->getLastDocument($mType, $aTemplateTypes, $oSearch);

		if($oLastDocument) {
			$oLastVersion = $oLastDocument->getLastVersion();
			
			if($oLastVersion) {
				$sPdfPath = $oLastVersion->path;
			}
		}
		
		return $sPdfPath;

	}
	
	/**
	 * Buchungs/Anfragenwährung
	 * 
	 * @return int|Ext_Thebing_Currency
	 */
	public function getCurrency($bObject = false) {
		if (!$bObject) {
			return (int)$this->currency_id;
		}

		return Ext_Thebing_Currency::getInstance($this->currency_id);
	}
	
	/*
	 * Gibt an Vor ort Berechnet wird oder nicht
	 */
	public function getPaymentMethodLocal(){
		
		$bPaymentMethodLocal	= false;
		
		$oAgency				= $this->getAgency();
		
		
		// Schauen ob Bezahlmethode auf "Vorort" steht
		if(
			(
				is_object($oAgency) &&
				(
					(int)$oAgency->ext_26 == 2 || 
					(int)$oAgency->ext_26 == 3
				) &&
				$this->payment_method == -1
			) ||
			(
				$this->payment_method == 2 ||
				$this->payment_method == 3
			)
		){
			$bPaymentMethodLocal = true;
		}
		
		return $bPaymentMethodLocal;
	}
	
	/**
	 *
	 * @param type $sType
	 * @param type $iInquiryAccommodationId
	 * @param type $iFrom
	 * @param type $iUntil
	 * @param type $bForCost
	 * @return type 
	 */
	public function getExtraNights($sType = '', $mInquiryAccommodation = 0, $iFrom = 0, $iUntil = 0, $bForCost = false, $bFullLastWeek=false) {

		$aExtraNights = $this->getExtraNightsWithWeeks('forCalculate', $mInquiryAccommodation, $iFrom, $iUntil, $bForCost, $bFullLastWeek);

		$oSchool = $this->getSchool();
		$iNightsOfExtraWeek = $oSchool->extra_nights_price;
		if($bForCost) {
			$iNightsOfExtraWeek = $oSchool->extra_nights_cost;
		}
		if($iNightsOfExtraWeek <= 0){
			$iNightsOfExtraWeek = 7;
		}

		$mExtraNightsTotal = array();

		foreach($aExtraNights as &$mExtraNights){
			$mExtraNights['nights'] = $mExtraNights['nights']%$iNightsOfExtraWeek;
			if($mExtraNights['nights'] > 0){
				$mExtraNightsTotal[] = $mExtraNights;
			}
		}
		
		if($sType != 'forCalculate'){
			foreach((array)$mExtraNightsTotal as $aExtra ){
				$mExtraNightsNew += $aExtra['nights'];
			}
		} else {
			$mExtraNightsNew = $mExtraNightsTotal;
		}

		return $mExtraNightsNew;

	}

	/**
	 * Berechnet die Anzahld er Extranächte einschlieslich der extrawochen
	 * Liefert auf wunsch ein Array mit allen Informationen ( extranächte am anfang/ende etc,..)
	 *
	 * @param string $sType
	 * @param int|Ext_TS_Inquiry_Journey_Accommodation $mInquiryAccommodation
	 * @param int $iFrom
	 * @param int $iUntil
	 * @param bool $bForCost
	 * @return array
	 */
	public function getExtraNightsWithWeeks($sType = '', $mInquiryAccommodation = 0, $iFrom = 0, $iUntil = 0, $bForCost = false, $bFullLastWeek=false) {
		
		$iInquiryAccommodationId = 0;
		
		if(
			is_object($mInquiryAccommodation) &&
			$mInquiryAccommodation instanceof Ext_TS_Service_Interface_Accommodation
		) {
			$oAcco						= $mInquiryAccommodation;
			$iInquiryAccommodationId	= $mInquiryAccommodation->id;
		} elseif(is_numeric($mInquiryAccommodation) && !empty($mInquiryAccommodation)) {
			$iInquiryAccommodationId = $mInquiryAccommodation;
			//Abwärtskompabilität
			$oAcco = $this->getServiceObject('accommodation', $iInquiryAccommodationId);
		}

		$oWDDate = new WDDate();
		$aAccommodations = array();

		$oSchool = $this->getSchool();

		$iNightsOfExtraWeek = $oSchool->extra_nights_price;
		if($bForCost) {
			$iNightsOfExtraWeek = $oSchool->extra_nights_cost;
		}
		if($iNightsOfExtraWeek <= 0){
			$iNightsOfExtraWeek = 7;
		}
		
		// TODO: Dieser ID-Mist sollte schnellstmöglich raus, da das hier nicht mit ungespeicherten Objekten funktioniert…
		if($iInquiryAccommodationId <= 0) {
			$aAccommodations = $this->getAccommodations(false);
		} else {

			// $oAcco->getData() setzt eine Referenz auf $this, welche sich nicht mit unset() auflösen lässt.
			// Das Konvertieren in ein ArrayObject schafft es aber, die Referenz auszumerzen.
			$aAcco = new ArrayObject($oAcco->getData());

			if(
				WDDate::isDate($aAcco['from'], WDDate::DB_DATE) &&
				WDDate::isDate($aAcco['until'], WDDate::DB_DATE)
			) {
				$oWDDate->set($aAcco['from'], WDDate::DB_DATE);
				$aAcco['from'] = $oWDDate->get(WDDate::TIMESTAMP);

				$oWDDate->set($aAcco['until'], WDDate::DB_DATE);
				$aAcco['until'] = $oWDDate->get(WDDate::TIMESTAMP);

				if(
					$iFrom > 0 || 
					$iUntil > 0
				) {
					$aAcco['from'] = $iFrom;
					$aAcco['until'] = $iUntil;
					$iCurrentWeeks = Ext_Thebing_Accommodation_Amount::getWeekCount($iFrom, $iUntil, $oSchool->id, $bForCost);
					$aAcco['weeks'] = $iCurrentWeeks;
				}

				$aAccommodations = array($aAcco);	
			}

		}

		$aExtraNights = array();
		foreach((array)$aAccommodations as $aAccommodation) {

			$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($aAccommodation['accommodation_id']);
			
			$iFrom = (int)$aAccommodation['from'];
			$iUntil = (int)$aAccommodation['until'];

			$iWeeks = $aAccommodation['weeks'];
			
			// Anhand der Wochenzahl errechnetes Enddatum
			$oWDDate->set($iFrom, WDDate::TIMESTAMP);
			$oWDDate->add($iWeeks, WDDate::WEEK);

			// Wenn pro Nacht bezahlt wird
			if($oAccommodationCategory->hasNightPrice($oSchool)) {
				continue;
			}

			$iTime = $iUntil - $iFrom;
			$oWDDate->set($iUntil, WDDate::TIMESTAMP);
			$iNightsTotal = $oWDDate->getDiff(WDDate::DAY, $iFrom, WDDate::TIMESTAMP);

			// Nächte = Tage - 1 ( letzte woche weglassen da dies unten dazuaddiert wird
			$iNightsOfWeeks = 0;
			$iNightsOfLastWeek = 0;
			$iNightsOfWeeks = 0;

			if($iWeeks > 0) {
				$iNightsOfWeeks = (($iWeeks - 1) * 7);
				$iNightsOfLastWeek = 7;
				if($bFullLastWeek === false) {
					$iNightsOfLastWeek = $oAccommodationCategory->getAccommodationInclusiveNights($oSchool);
				}
				$iNightsOfWeeks = $iNightsOfWeeks + $iNightsOfLastWeek;
			}

			$iExtraNights = $iNightsTotal - $iNightsOfWeeks;

			/*
			 * @todo Zeiträume kleiner 1 Wochen werden ohne die Extrawochenabfrage komplett falsch abgerechnet. Diese Abfrage ist aber nicht für alle Fälle optimal
			 */
			if(
				$iNightsTotal < $iNightsOfExtraWeek &&
				$iNightsTotal < $iNightsOfLastWeek
			) {
				$iExtraNights = $iNightsTotal;
			}

			// Es gibt keine Minus Extranächte
			if($iExtraNights <= 0) {
				$iExtraNights = 0;
			}

			if($sType == 'forCalculate') {

				$iDaysBefor = $this->getDaysBeforAccommodationStart($iFrom, $oAccommodationCategory);

				if($iDaysBefor >= $iExtraNights){
					$iDaysBefor = $iExtraNights;
				}

				if($iDaysBefor > 0){
					$iExtraNightStart = $aAccommodation['from'];
					$iExtraNightsForAccommodation = $iDaysBefor;

					$aTemp = array();
					$aTemp['accommodation_id'] = $aAccommodation['accommodation_id'];
					$aTemp['meal_id'] = $aAccommodation['meal_id'];
					$aTemp['roomtype_id'] = $aAccommodation['roomtype_id'];
					$aTemp['nights'] = $iExtraNightsForAccommodation;
					$aTemp['from'] = $iExtraNightStart;
					$aTemp['type'] = 'nights_at_start';
					$aExtraNights[] = $aTemp;
				} 
	
				$oWDDate = new WDDate($iUntil, WDDate::TIMESTAMP);
				$oWDDate->sub(1, WDDate::DAY);

				// 1 Tag abziehen da man das Datum der NACHT braucht
				$iExtraNightStart = $oWDDate->get(WDDate::TIMESTAMP);
				$iExtraNightsAfterAccommodation = $iExtraNights - $iDaysBefor;

				if($iExtraNightsAfterAccommodation > 0) {
					$aTemp = array();
					$aTemp['accommodation_id'] = $aAccommodation['accommodation_id'];
					$aTemp['meal_id'] = $aAccommodation['meal_id'];
					$aTemp['roomtype_id'] = $aAccommodation['roomtype_id'];
					$aTemp['nights'] = $iExtraNightsAfterAccommodation;
					$aTemp['from'] = $iExtraNightStart;
					$aTemp['type'] = 'nights_at_end';
					$aExtraNights[] = $aTemp;
				}	
				
			} else {
				$aExtraNights[] = $iExtraNights;
			}
			
		}

		return $aExtraNights;		
	}
	
	/**
	 * Liefert die Anzahl der Tage bis zum angegebenen Unterkunftsstarttag
	 *
	 * @deprecated Wird an einer einzigen Stelle und auch merkwürdigen Methode verwendet und hat Redundanzen
	 *
	 * @param $iFrom
	 * @return int
	 */
	public function getDaysBeforAccommodationStart($iFrom, \Ext_Thebing_Accommodation_Category $category){

		// starttag von Unterkünften
		$sStartDay = $category->getAccommodationStart($this->getSchool());

		$iWeekDay = date("w", $iFrom);

		switch($sStartDay){

			case 'mo':
				$iSchoolSartDay = 1;
				break;
			case 'di':
				$iSchoolSartDay = 2;
				break;
			case 'mi':
				$iSchoolSartDay = 3;
				break;
			case 'do':
				$iSchoolSartDay = 4;
				break;
			case 'fr':
				$iSchoolSartDay = 5;
				break;
			case 'sa':
				$iSchoolSartDay = 6;
				break;
			case 'so':
				$iSchoolSartDay = 0;
				break;
			default:
				$iSchoolSartDay = 1;
				break;
		}

		$iDaysBefor = $iWeekDay - $iSchoolSartDay;
		if($iDaysBefor > 0){
			$iDaysBefor = 7 - $iDaysBefor;
		}else{
			$iDaysBefor = abs($iDaysBefor);
		}

		if($iDaysBefor <= 0){
			$iDaysBefor = 0;
		}

		return $iDaysBefor;
	}

	/**
	 * @param string $sType
	 * @param int|Ext_TS_Inquiry_Journey_Accommodation $iInquiryAcco
	 * @param int $iFrom
	 * @param int $iUntil
	 * @param bool $bForCost
	 * @return array|int
	 */
	public function getExtraWeeks($sType = '', $iInquiryAcco = 0, $iFrom = 0, $iUntil = 0, $bForCost = false) {
				
		$aExtraNights = $this->getExtraNightsWithWeeks('forCalculate', $iInquiryAcco, $iFrom, $iUntil, $bForCost);

		$oSchool = $this->getSchool();
		$iNightsOfExtraWeek = $oSchool->extra_nights_price;
		if($bForCost){
			$iNightsOfExtraWeek = $oSchool->extra_nights_cost;
		}
		if($iNightsOfExtraWeek <= 0){
			$iNightsOfExtraWeek = 7;
		}

		$mExtraWeeks = array();

		foreach($aExtraNights as $mExtraNights){
			$mExtraNights['nights'] = floor($mExtraNights['nights']/$iNightsOfExtraWeek);
			if($mExtraNights['nights'] > 0){
			$mExtraWeeks[] = $mExtraNights;
		}
		}

		if($sType != 'forCalculate'){
			$mExtraWeeksNew = 0;
			foreach((array)$mExtraWeeks as $aExtra ){
				$mExtraWeeksNew += $aExtra['nights'];
			}
		} else {
			$mExtraWeeksNew = $mExtraWeeks;
		}

		return $mExtraWeeksNew;
	}
		
	/*
	 * Holt alle InquirySpecialPositionen
	 * $mUsed = Sind noch in Gebrauch
	 */
	public function getSpecialData($bCheckValid = true) {
		
		$currentSpecialPositions = $this->getJoinTableObjects('special_position_relation');
		
		$aBack = [];
		
		foreach($currentSpecialPositions as $currentSpecialPosition) {
			if($bCheckValid) {
				$oSpecial = $currentSpecialPosition->getSpecial();
				
				if(is_object($oSpecial)) {
					$mAvailableSpacials = $oSpecial->getAvailable();
					if(
						$mAvailableSpacials < 1 
//							&&
//						$currentSpecialPosition->used != 1 // wenn Special bereits verwendet wurde, darf es auch weiterhin verwend. werden.
					) {
						continue; // Keine Freien Plätze im Special
					}
				} else {
					continue; // Special nicht gefunden -> Special kann nicht benutzt werden
				}
				
			}
			$aBack[] = $currentSpecialPosition;
		}

		return $aBack;
	}
	
	/**
	 *check if group exist
	 * @return boolean
	 */
	public function hasGroup(){
		$oGroup = $this->getGroup();
		if(
			$oGroup &&
			$oGroup->active == 1
		){
			return true;
		}
		return false;
	}

	/**
	 * Gruppenname, und falls Guide wird noch ein "*" Zeichen angehangen.
	 * In der Enquiry wird die Methode mit $bCheckForGuide = false aufgerufen!
	 *
	 * @param bool $bCheckForGuide
	 * @return string
	 */
	public function getGroupShortName($bCheckForGuide = true) {

		$sGroupShortName	= '';
		$oGroup = $this->getGroup();

		if($oGroup) {
			$sGroupShortName = $oGroup->getShortName();
			if(
				$bCheckForGuide &&
				$this->isGuide()
			) {
				$sGroupShortName .= ' *';
			}
		}

		return $sGroupShortName;
	}

	/**
	 * Gibt den Gruppenname zurück. Falls die aktuelle Inquiry ein Guide ist,
	 * wird noch ein "*" Zeichen angehangen.
	 *
	 * @param bool $bCheckForGuide
	 * @return string
	 */
	public function getGroupName($bCheckForGuide = true) {

		$sGroupName	= '';
		$oGroup = $this->getGroup();

		if($oGroup) {
			$sGroupName = $oGroup->getName();
			if(
				$bCheckForGuide &&
				$this->isGuide()
			) {
				$sGroupName .= ' *';
			}
		}

		return $sGroupName;
	}

	/**
	 *
	 * @return bool 
	 */
	public function hasAgency()
	{
        	
		if($this->agency_id <= 0)
		{
			return false;
		}
		
		$iAgencyId	= 0;
		$oAgency	= $this->getAgency();

		if($oAgency && $oAgency instanceof Ext_Thebing_Agency)
		{
			$iAgencyId = (int)$oAgency->id;
		}
		
		if($iAgencyId > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Prüft ob Buchung Netto oder Brutto Bezahlmethode hat
	 *
	 * Das Benutzen dieser Methode ist in der Regel falsch!
	 * Das muss sich nach den jeweiligen Dokumenten richten.
	 *
	 * @internal
	 * @return bool
	 */
	public function hasNettoPaymentMethod() {

		if(
			$this->payment_method == 0 ||
			$this->payment_method == 2
		) {
			return true;
		} else {
			return false;
		}

	}
	
	/**
	 * Anmerkung: Selbes Verhalten existiert auch im neuen Anmeldeformular
	 * @see Ext_TS_Frontend_Combination_Inquiry_Abstract::_pricesAjax()
	 * @todo Ist das richtig, dass hier nur Kurs und UK berücksichtigt wird?
	 * @return int 
	 */
	public function getSaisonFromFirstService() {

		// Datum der ersten Leistung ermitteln
		$iCourseStart = (int)$this->getFirstCourseStart(true);
		$iAccommodationStart = (int)$this->getFirstAccommodationStart(); // TODO Hier wird nur reset() gemacht…

		$oSchool = $this->getSchool();

		if(
			$iCourseStart > 0 &&
			$iAccommodationStart > 0
		){
			$iFirst = min($iCourseStart, $iAccommodationStart);
		}elseif($iCourseStart > 0){
			$iFirst = $iCourseStart;
		}else{
			$iFirst = $iAccommodationStart;
		}
		
		/*
		 * Keine Leistung? Dann heutiges Datum nehmen
		 * @todo Unix-Timestamp loswerden!
		 */
		if(empty($iFirst)) {
			$iFirst = time();
		}

		// TODO: Fehlt hier nicht der Frühbucherrabatt?
		$oSaison = new Ext_Thebing_Saison($oSchool->id);
		$iSaisonId = $oSaison->search($iFirst);

		return (int)$iSaisonId;
	}

	/**
	 * @param string $sType
	 * @param int $iId
	 * @throws Exception
	 * @return Ext_TS_Service_Abstract|Ext_TS_Service_Interface_Course|Ext_TS_Service_Interface_Accommodation|Ext_TS_Service_Interface_Transfer|Ext_TS_Service_Interface_Insurance|Ext_TS_Service_Interface_Activity
	 */
	final public function getServiceObject($sType, $iId) {
		$aServiceObjectClasses = (array)$this->getServiceObjectClasses();
		
		if(isset($aServiceObjectClasses[$sType])) {
			$oService = call_user_func(array($aServiceObjectClasses[$sType], 'getInstance'), (int)$iId);
		} else {
			throw new InvalidArgumentException('Service "' . $sType . '" not defined in "' . $this->getClassName() . '"!');
		}
		
		return $oService;
	}
	
	/**
	 * Kann abgeleitet werden, falls $_aInstance manipuliert werden soll
	 * 
	 * @return Ext_TS_Inquiry_Abstract 
	 */
	public function manipulateInstance()
	{
		return $this;
	}
		
	public function getSpecials() {
		
		if($this->inquirySpecials === null) {
			
			$this->inquirySpecials = [];
			
			$specialPositions = $this->getJoinTableObjects('special_position_relation');
			
			foreach($specialPositions as $specialPosition) {
				$this->inquirySpecials[] = \Ts\Model\Special\InquirySpecial::buildFromPosition($specialPosition);
			}
			
		}
		
		return $this->inquirySpecials;
	}
	
	/**
	 * Sucht die Specials und speichert sie optional
	 * 
	 * @param bool $bSave
	 */
	public function findSpecials($bSave = false) {

		$bChanged = false;
		
		// Ermittelte Specials zurücksetzen
		$this->inquirySpecials = [];
		
		$specialService = new \Ts\Service\Inquiry\Special($this);
		$foundSpecials = $specialService->find();

		/** @var Ext_Thebing_Inquiry_Special_Position[] $aOldRelations */
		$currentSpecialPositions = $this->getJoinTableObjects('special_position_relation');

		$currentSpecialPositions = array_combine(
			array_map(function($currentSpecialPosition) {
				return implode('_', [
					$currentSpecialPosition->special_block_id,
					$currentSpecialPosition->special_id,
					$currentSpecialPosition->type,
					$currentSpecialPosition->type_id,
				]); // Beispielhafte Änderung der Keys
			}, $currentSpecialPositions),
			$currentSpecialPositions
		);

		$deleteSpecialPositions = $currentSpecialPositions;

		// Jointable Objekte erzeugen
		foreach($foundSpecials as $foundSpecial) {

			// Special ist schon gespeichert im Objekt
			if(array_key_exists($foundSpecial->getKey(), $currentSpecialPositions)) {
				unset($deleteSpecialPositions[$foundSpecial->getKey()]);
				$specialPosition = $currentSpecialPositions[$foundSpecial->getKey()];
			} else {
				$specialPosition = $this->getJoinTableObject('special_position_relation');
				$bChanged = true;
			}
			
			// Werte setzen oder aktualisieren
			$foundSpecial->fillPositionObject($specialPosition);			
			
		}

		// Specials die nicht mehr vorkommen und nicht verwendet wurden löschen
		foreach($deleteSpecialPositions as $deleteSpecialPosition) {

			if($deleteSpecialPosition->used == 0) {
				$this->removeJoinTableObject('special_position_relation', $deleteSpecialPosition);
				$bChanged = true;
			}
			
		}

		// Nochmal gesondert die Transferobjekte hier setzen
		$this->inquirySpecials = $foundSpecials;

		if(
			$bSave &&
			$bChanged
		) {
			$this->save();
		}

	}
	
	public function getPayedAmount(){
		return 0;
	}
	
//	public function getCreditAmount(){
//		return 0;
//	}
	
	/**
	 *
	 * Definieren welcher Numberrange-Typ geholt werden muss anhand des Dokumenttypes
	 * 
	 * @param string $sDocumentType
	 * @return string 
	 */
	public function getTypeForNumberrange($sDocumentType, $mTemplateType=null) {

		// Dies war früher dadurch gelöst, indem die Methode einfach abgeleitet wurde
		if (
			$mTemplateType === 'document_offer_customer' ||
			$mTemplateType === 'document_offer_agency'
		) {
			return 'enquiry';
		}

		if(
			$sDocumentType === 'additional_document' &&
			$mTemplateType === 'document_certificates'
		) {
			return 'certificate';
		}
		
		return $sDocumentType;
	}
	
	/**
	 *
	 * @param bool $bAsObject
	 * @return mixed 
	 */
	public function getState($bAsObject = false){
		
		$oState = Ext_Thebing_Marketing_Studentstatus::getInstance((int)$this->status_id);
		
		if(
			$bAsObject
		){
			return $oState;
		}else{
			return $oState->text;
		}
	}
	
	/**
	 * @deprecated
	 *
	 * @param type $sLanguage
	 * @return Ext_Thebing_Course_Util 
	 */
	public function getCourse($sLanguage = '') {
		
		if ($sLanguage == '') {
			$oContact = $this->getCustomer();
			$sLanguage = $oContact->getLanguage();
		}
		$oSchool = $this->getSchool();
		if (is_object($oSchool)) {
			$oCourse = new Ext_Thebing_Course_Util($oSchool, $sLanguage);
		} else {
			$oCourse = new Ext_Thebing_Course_Util('noData', $sLanguage);
		}

		$aJourneyCourses = (array)$this->getCourses();
		if(!empty($aJourneyCourses)) {
			$oCourseObject = reset($aJourneyCourses)->getCourse();
			$oCourse->setCourse($oCourseObject->id);
		}

		return (object)$oCourse;
	}
	
	/**
	 * @deprecated
	 *
	 * @param type $sLanguage
	 * @return Ext_Thebing_Accommodation_Util 
	 */
	public function getAccommodation($sLanguage = ''){

		$oContact = $this->getCustomer();
		$oSchool = $this->getSchool();

		$oAccommodation = new Ext_Thebing_Accommodation_Util($oSchool, $oContact->getLanguage());
		$aAccommodations = $this->getAccommodations();
		
		$oAccommodation->setAccommodationCategorie((int)($aAccommodations[0]->accommodation_id ?? 0));
		return (object)$oAccommodation;
	}
	
	/**
	 * berechnet von allen gebuchten Kursen die Wochenanzahl
	 * wenn $iCurrentWeek übergeben wird, bekommt man ein von/bis string zurück
	 * @param <array> $aInquiriesCourses
	 * @param <int> $iCurrentWeek
	 * @return <int> / <string>
	 */
	public function getAllInquiryCourseWeeks($aInquiriesCourses=array(), $iCurrentWeek=false, $aInquiriesHolidays=array())
	{
		if(empty($aInquiriesCourses))
		{
			$aInquiriesCourses	= $this->getCourses(false);
		}

		$aInquiriesCourses	= (array)$aInquiriesCourses;
		$aInquiriesHolidays	= (array)$aInquiriesHolidays;

		$aPeriods			= array_merge($aInquiriesCourses,$aInquiriesHolidays);	
		$aDates				= Ext_Thebing_Util::getPeriodDates($aPeriods);

		$iWeeks		= 0;
		$iWeekFrom	= 0;

		$oWdDate = new WDDate();

		foreach($aDates as $iKey => $aCourseDates)
		{
			$oWdDate->set($aCourseDates['until'], WDDate::TIMESTAMP);
			if($oWdDate->get(WDDate::WEEKDAY)!=1)
			{
				$oWdDate->add(1, WDDate::WEEK);
				$oWdDate->set(1, WDDate::WEEKDAY);
			}
			$iEnd = $oWdDate->get(WDDate::TIMESTAMP);


			$oDateStart = new WDDate($aCourseDates['from']);
			if($oDateStart->get(WDDate::WEEKDAY)!=1)
			{
				$oDateStart->add(1, WDDate::WEEK);
				$oDateStart->set(1, WDDate::WEEKDAY);
			}
			$iStart = $oDateStart->get(WDDate::TIMESTAMP);

			$iDiff = (int)$oWdDate->getDiff(WDDate::WEEK,$iStart,WDDate::TIMESTAMP);

			$iWeeks += $iDiff;

			if($iCurrentWeek)
			{
				if(
					$iCurrentWeek < $iEnd &&
					$iCurrentWeek >= $iStart
				)
				{
					$oWdDate->set($iCurrentWeek, WDDate::TIMESTAMP);
					$iDiff2 = (int)$oWdDate->getDiff(WDDate::WEEK,$iStart,WDDate::TIMESTAMP);
					$iDiff2 += 1;
					$iWeekFrom += $iDiff2;
				}
				elseif
				(
					$iCurrentWeek >= $iEnd
				)
				{
					$iWeekFrom += $iDiff;
				}
			}

		}

		if($iCurrentWeek)
		{
			if($iWeekFrom<=0)
			{
				$iWeekFrom = 1;
			}
			
			$aTemp = array();
			$aTemp['week_from'] = (int)$iWeekFrom;
			$aTemp['week_until'] = (int)$iWeeks;
			
			$oFormat = new Ext_Thebing_Gui2_Format_FromUntil('week_from', 'week_until'); 
			
			return $oFormat->format($aTemp, $aTemp, $aTemp);
		}
		else
		{
			return $iWeeks;
		}
	}
	
	/**
	 * Kurstabelle pro Objekt Typ
	 * 
	 * @return string
	 */
	public function getServiceTable($sType)
	{
		$aServiceObjectClasses = $this->getServiceObjectClasses();
		
		if(isset($aServiceObjectClasses[$sType]))
		{
			$sServiceObject = $aServiceObjectClasses[$sType];
		}
		else
		{
			throw new Exception('Service Object Class "' . $sType . '" not found!');
		}
		
		$oServiceObject = new $sServiceObject();
		
		if(
			is_object($oServiceObject) &&
			$oServiceObject instanceof WDBasic
		)
		{
			$sTable = $oServiceObject->getTableName();
			
			return $sTable;
		}
		else
		{
			throw new Exception('Service Class has to be an instance of WDBasic!');
		}
	}
	
	/**
	 *
	 * Anzahl der Lektionen zählen
	 * 
	 * @param mixed $mCourseCategory
	 * @return int 
	 */
	public function getAllocatedLessonsCount($mCourseCategory=false)
	{
		$iLessons = 0;
		
		$aCourses = $this->getCourses();

		$matchCategory = function ($oCourse) use ($mCourseCategory) {
			if ($mCourseCategory && $mCourseCategory != $oCourse->getCategory()->id) {
				return false;
			}
			return true;
		};

		foreach($aCourses as $oJourneyCourse) {

			$oCourse = $oJourneyCourse->getCourse();
			
			if(!$matchCategory($oCourse)) {
				continue;
			}

			$oProgramServices = $oJourneyCourse->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE)
				->filter(fn ($oProgramService) => $matchCategory($oProgramService->getService()));

			$iSubLessons = $oProgramServices->map(fn ($oProgramService) => $oJourneyCourse->getLessonsContingent($oProgramService)->absolute)
				->sum();

			$iLessons += $iSubLessons;
		}

		return $iLessons;
	}

	public function getAllocations(){
        return array();
    }
	
	/**
	 * Referrer/Source: Wie sind Sie auf uns aufmerksam geworden?
	 *
	 * @return Ext_TS_Referrer|null
	 */
	public function getReferrer() {

		if(empty($this->referer_id)) {
			// null für Index (Sortierung)
			return null;
		}

		return Ext_TS_Referrer::getInstance($this->referer_id);

	}

	public static function getAgenciesForSelect() {
		
		$school = Ext_Thebing_School::getSchoolFromSession();
		
		if(!$school->exist()) {
			$school = null;
		}
		
		$oClient = Ext_Thebing_Client::getFirstClient();
		return $oClient->getAgencies(true, false, true, $school);
	}

	/**
	 * Gibt alle Agenturgruppen wieder für ein Select
	 *
	 * @return array
	 */
	public static function getAgencyGroupsForSelect() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return $oClient->getAgencyGroups(true);
	}

	public function getNationality($sLang = '')
	{
		$oCustomer			= $this->getCustomer();
		
		$sNationality		= $oCustomer->nationality;
		
		$aNatinonalitys = Ext_Thebing_Nationality::getNationalities(true, $sLang, 0);
		
		if(isset($aNatinonalitys[$sNationality]))
		{
			return $aNatinonalitys[$sNationality];
		}
		else
		{
			return null;
		}
		
	}

	public function getFirstCourseStart($bReturnUnixTimestamp=false) {

		$sFirst = null;
		
		$aCourses = $this->getCourses();

		foreach((array)$aCourses as $oJourneyCourse) {
			if(empty($sFirst)) {
				$sFirst = $oJourneyCourse->from;
			} elseif(strcmp($oJourneyCourse->from, $sFirst) < 0) {
				$sFirst = $oJourneyCourse->from;
			}
		}

		if($bReturnUnixTimestamp === true) {
			if(
				!is_null($sFirst) &&
				WDDate::isDate($sFirst, WDDate::DB_DATE)
			) {
				$oDate = new WDDate($sFirst, WDDate::DB_DATE);
				return $oDate->get(WDDate::TIMESTAMP);
			}
		} else {
			return $sFirst;
		}
	}

	public function getLastCourseEnd($bReturnUnixTimestamp=false) {

		$sLast = null;

		$aCourses = $this->getCourses();

		foreach((array)$aCourses as $oJourneyCourse) {
			if(empty($sLast)){
				$sLast = $oJourneyCourse->until;
			} elseif(strcmp($oJourneyCourse->until, $sLast) > 0) {
				$sLast = $oJourneyCourse->until;
			}
		}

		if($bReturnUnixTimestamp === true) {
			if(
				!is_null($sLast) &&
				WDDate::isDate($sLast, WDDate::DB_DATE)
			) {
				$oDate = new WDDate($sLast, WDDate::DB_DATE);
				return $oDate->get(WDDate::TIMESTAMP);
			}
		} else {
			return $sLast;
		}
	}

	public function getCreatorIdForIndex() {

		$creator = $this->creator_id;

		if ($this->frontend_log_id !== null) {
			$creator = -1;
		}

		System::wd()->executeHook('ts_inquiry_get_creator', $this, $creator);

		return $creator;

	}

	public function getLanguageData() {

		$oCustomer = $this->getCustomer();

		return $oCustomer->language;
	}

	public function getDocumentLanguage() {
		return $this->getCustomer()->getLanguage();
	}

	/**
	 * Buchungen: Irgendeines der über 40 DB-Felder sorgt ohnehin immer dafür, dass changed aktualisiert wird,
	 * obwohl getIntersectionData() keine Änderung erkennt.
	 *
	 * Anfragen: Hier gibt es für das Inquiry-Verhalten zu wenig Felder, aber das Objekt ist auch ziemlich komplex
	 * und daher soll hier auch einfach immer changed aktualisiert werden.
	 *
	 * @return bool
	 */
	public function checkUpdateUser() {
		return true;
	}

	/**
	 * @return Ext_TS_Inquiry_Contact_Abstract
	 */
	abstract public function getCustomer();

	/**
	 * @return Ext_TS_Inquiry_Journey_Course[]|Ext_TS_Enquiry_Combination_Course[]
	 */
	abstract public function getCourses();

	/**
	 * @param bool $bAsObjectArray
	 * @return Ext_TS_Inquiry_Journey_Accommodation[]|Ext_TS_Enquiry_Combination_Accommodation[]
	 */
	abstract public function getAccommodations($bAsObjectArray = true);

	/**
	 * @return Ext_TS_Inquiry_Journey_Insurance[]|Ext_TS_Enquiry_Combination_Insurance[]
	 */
	abstract public function getInsurances();

	/**
	 * @return Ext_TS_Inquiry_Journey_Activity[]
	 */
	abstract public function getActivities();

	/**
	 * @param string $sFilter
	 * @param bool $bIgnoreBookingStatus
	 * @return Ext_TS_Inquiry_Journey_Transfer[]|Ext_TS_Enquiry_Combination_Transfer[]
	 */
	abstract public function getTransfers($sFilter = '', $bIgnoreBookingStatus = false);

	abstract public function getJourneyTravellerOption($sOption);

	abstract public function isGuide(Ext_TS_Contact $oContact = null);

	// TODO Entfernen (wird nur noch für getInfo verwendet und benötigt gespeicherte Objekte)
	abstract public function getInsurancesWithPriceData($sDisplayLanguage = null);

	/**
	 * @return Ext_Thebing_Inquiry_Group|Ext_TS_Enquiry_Group
	 */
	abstract public function getGroup();

	abstract public function getSpecialPositionRelationTableData();

	#abstract public function saveSpecialPositionRelation($bSave = false);

	abstract public function getSpecialRelationObject();

	/**
	 * @param string|array $mType
	 * @param array $aTemplateTypes
	 * @param Ext_Thebing_Inquiry_Document_Search $oSearch
	 * @return Ext_Thebing_Inquiry_Document|null
	 */
	abstract public function getLastDocument($mType, $aTemplateTypes = [], $oSearch = null);

	/**
	 * @param bool $bTimestamps Altes Verhalten (DEPRECATED)
	 * @return string|int|null
	 */
	abstract public function getFirstAccommodationStart($bTimestamps = true);

	abstract public function countAllMembers();

	abstract public function getServiceObjectClasses();

	abstract public function setGroup(Ext_TS_Group_Interface $oGroup);

	abstract public function hasSameData($sType);

	/**
	 * @return Ext_TS_Contact|Ext_TS_Inquiry_Contact_Abstract
	 */
	abstract public function getTraveller();

	abstract public function hasTraveller();

	/**
	 * @return Ext_TS_Inquiry_Contact_Traveller|Ext_TS_Enquiry_Contact_Traveller
	 */
	abstract public function getFirstTraveller();

	abstract public function getAllGroupMembersForDocument(Ext_Thebing_Inquiry_Document $oDocument);

	/**
	 * @param array $aParams
	 * @return Ext_Thebing_Inquiry_Placeholder|Ext_TS_Enquiry_Placeholder|Ext_TS_Enquiry_Offer_Placeholder
	 */
	abstract public function createPlaceholderObject($aParams);

	abstract public function getTransferLocations($sType = '', $sLang = '');

	abstract public function canShowPositionsTable();

	abstract public function getCreatedForDiscount();

	abstract public function getCompleteServiceTimeframe($services = true, $onlyVisible = true): ?\Carbon\CarbonPeriod;

	/**
	 * Hat diese Buchung automatische E-Mails aktiviert?
	 *
	 * @return bool
	 */
	public function isReceivingAutomaticEmails() {
		$oTraveller = $this->getFirstTraveller();
		if($oTraveller !== null) {
			return $oTraveller->isReceivingAutomaticEmails();
		}

		return false;
	}

	/**
	 * Gibt den Sales Person wieder.
	 *
	 * @return Ext_Thebing_User|null
	 */
	public function getSalesPerson() {

		if($this->sales_person_id != 0) {
			return Ext_Thebing_User::getInstance($this->sales_person_id);
		}

		return null;

	}

	/**
	 * 
	 */
	public function allocateSalesperson($bOverwrite=false) {
		
		// Der Wert 0 bedeutet dass nicht mehr gesucht werden soll (Wert wurde schon gespeichert).
		if(
			$bOverwrite === true ||	
			empty($this->sales_person_id)
		) {
			$oSelection = new Ext_Thebing_Gui2_Selection_User_SalesPerson();
			$oSalesPerson = $oSelection->getMatchingSalesPerson($this);
			if($oSalesPerson instanceof Ext_Thebing_User) {
				$this->sales_person_id = $oSalesPerson->id;
			} else {
				$this->sales_person_id = 0;
			}
		}

		return $this->sales_person_id;
	}

	/**
	 * @return Ext_TS_Payment_Condition|null
	 */
	public function getPaymentCondition() {

		if(
			$this->hasAgency() &&
			$this->hasNettoPaymentMethod()
		) {
			return $this->getAgency()->getValidPaymentCondition($this->getSchool()->id);
		} else {
			return $this->getSchool()->getDefaultPaymentCondition();
		}

	}

	/**
	 * @param bool $bAnonymize
	 * @return array
	 */
	abstract protected function getPurgeDocumentTypes($bAnonymize);

	/**
	 * Datenschutz: Daten löschen, die in Inquiry und Enquiry vorkommen (Redundanz vermeiden)
	 * @see purge()
	 *
	 * @param bool $bAnonymize
	 * @throws Exception
	 */
	protected function purgeCommonData($bAnonymize) {

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($this);
		$oSearch->setObjectType(get_class($this));
		$oSearch->setType($this->getPurgeDocumentTypes($bAnonymize));
		$oSearch->searchAlsoInactive();
		$aDocuments = $oSearch->searchDocument();
		foreach($aDocuments as $oDocument) {
			$oDocument->enablePurgeDelete();
			$oDocument->delete();
		}

		// E-Mails in jedem Fall löschen
		$oMessageRepository = Ext_TC_Communication_Message::getRepository();
		$aLogs = $oMessageRepository->searchByEntityRelation($this);
		foreach($aLogs as $oLog) {
			$oLog->enablePurgeDelete();
			$oLog->delete();
		}

	}

	/**
	 * Methode wird von Enquiry UND Inquiry benutzt!
	 *
	 * @param string $sType
	 * @return string
	 */
	public function getCorrespondenceLanguage($sType = 'customer'): string|array {

		$sLanguage = false;

		if($sType === 'customer') {
			$oContact = $this->getCustomer();

			if($oContact) {
				$sLanguage = $oContact->corresponding_language;
			}

		} elseif($sType === 'school') {
			$oSchool = $this->getSchool();

			if($oSchool) {
				$sLanguage = $oSchool->getCorrespondenceLanguage();
			}
		}

		return $sLanguage;
	}
	
	/**
	 * Get Timestamp of last course end
	 *
	 * @return int timestamp of first course start
	 */
	public function getLastAccommodationEnd() {

		$aAccommodations = $this->getAccommodations(false);
		$aAccommodation = end($aAccommodations);
		
		$mReturn = $aAccommodation['until'];
	
		return $mReturn;

	}

	public function getCommunicationDefaultApplication(): string
	{
		return \Ts\Communication\Application\Booking::class;
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getSchool();
	}

	public function getCommunicationLabel(LanguageAbstract $l10n): string
	{
		$label = ($this->type & \Ext_TS_Inquiry::TYPE_BOOKING) ? 'Buchung' : 'Anfrage';

		$number = empty($number = $this->getNumber())
			? $this->getCustomer()->getCustomerNumber()
			: $number;

		if (empty($number)) {
			return $l10n->translate($label);
		}

		return sprintf('%s: %s', $l10n->translate($label), $number);
	}

}
