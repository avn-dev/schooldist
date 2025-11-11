<?php

/**
 * @property int $id
 * @property int $version_id
 * @property string $index_from (DATE)
 * @property string $index_until (DATE)
 * @property int $parent_id
 * @property string $parent_type
 * @property int $parent_booking_id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property string $type
 * @property string $description
 * @property string $old_description
 * @property float $amount
 * @property float $amount_net
 * @property float $amount_provision
 * @property int $initalcost
 * @property int $calculate TODO: Entfernen (heutzutage dasselbe wie onPdf)
 * @property int $onPdf
 * @property int $active
 * @property int $creator_id
 * @property int $position
 * @property int $count
 * @property int $type_id
 * @property int $nights TODO: Entfernen (Was hat das mit dem Item zu tun? Steht mittlerweile in additional_info drin)
 * @property int $old
 * @property float $amount_discount
 * @property string $description_discount
 * @property int $tax_category
 * @property float $tax
 * @property string|array $additional_info Über __get() ist das ein Array!
 * @property float $index_special_amount_gross
 * @property float $index_special_amount_net
 * @property float $index_special_amount_gross_vat
 * @property float $index_special_amount_net_vat
 * @property float $index_special_amount_vat
 * @property int $type_object_id
 * @property int $type_parent_object_id
 * @property int $contact_id
 */
class Ext_Thebing_Inquiry_Document_Version_Item extends Ext_Thebing_Basic {
	
	// Tabelle
	protected $_sTable = 'kolumbus_inquiries_documents_versions_items';
	protected $_sTableAlias = 'kidvi';

	protected $_sPlaceholderClass = \Ts\Entity\Inquiry\Document\Version\Item\Placeholder::class;
	
	// Tabelle statisch
	protected static $_sStaticTable = 'kolumbus_inquiries_documents_versions_items';

	// Hier wird beim speichern die alte ID abgelegt damit ich danach wieder darauf zugreifen kann
	// nötig für payment
	public $iOldItemId = 0;
	
	// der Status der alten Pos. ( new, delete,.. )
	public $sOldItemStatus = '';

	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
		),
		'created' => array(
			'format' => 'TIMESTAMP'
		),
		'type' => array(
			'required' => true
		),
		'version_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		),
		'contact_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		)
	);
	
	protected $_aJoinedObjects = array(
		'version' => [
			'class' => 'Ext_Thebing_Inquiry_Document_Version',
			'key' => 'version_id',
			'type' => 'parent',
		]
    );
	
	protected $_aJoinTables = array( 
		'specials' => array(
			'table'					=> 'kolumbus_inquiries_documents_versions_items_specials',
			'primary_key_field'		=> 'item_id',
			'foreign_key_field'		=> 'special_block_id', # Ist eigentlich eine special_position_id
			'autoload'				=> false
		)
	);

	/**
	 * Item-Typ, Journey-Relation, Journey-Service-FK
	 *
	 * @link https://redmine.fidelo.com/projects/schule/wiki/DB-Felder_der_Document-Items
	 */
	const RElATION_MAPPING = [
		['course', 'courses', 'type_id'],
		['accommodation', 'accommodations', 'type_id'],
		['accommodation', 'accommodations', 'additional_info.accommodation_id'],
		['transfer', 'transfers', 'type_id'],
		['transfer', 'transfers', 'additional_info.transfer_arrival_id'],
		['transfer', 'transfers', 'additional_info.transfer_departure_id'],
		['insurance', 'insurances', 'type_id'],
		['activity', 'activities', 'type_id'],
		['extra_nights', 'accommodations', 'type_id'],
		['extra_weeks', 'accommodations', 'type_id'],
		['additional_course', 'courses', 'parent_booking_id'],
		['additional_accommodation', 'accommodations', 'parent_booking_id'],
		['additional_general'],
		['extraPosition']
	];

	/**
	 * Matching: Item-Typ zu Leistungstyp
	 */
	const SERVICE_MAPPING = [
		'course' => 'course',
		'accommodation' => 'accommodation',
		'extra_nights' => 'accommodation',
		'extra_weeks' => 'accommodation',
		'transfer' => 'transfer',
		'insurance' => 'insurance',
		'activity' => 'activity',
		'additional_general' => 'additional_general',
		'additional_course' => 'additional_course',
		'additional_accommodation' => 'additional_accommodation',
		'extraPosition' => 'extraPosition'
	];

	public function __set($sName, $mValue) {

		if($sName == 'additional_info') {
			$this->_aData['additional_info'] = json_encode($mValue);
		} else {
			parent::__set($sName, $mValue);
		}

	}

	public function __get($sName){

		Ext_Gui2_Index_Registry::set($this);
		
		$mValue = '';

		if($sName == 'additional_info') {
			$mValue = json_decode($this->_aData['additional_info'], true);
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;
	}

	public function delete() {

		$deleted = parent::delete();

		if ($deleted === true) {
			$this->deletePaymentData();
		}

		return $deleted;

	}

	/**
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function getVersion() {
		return $this->getJoinedObject('version');
	}

	/**
	 * Get the Document of the Version
	 * @param type $bUseCache Ob Caching verwendet werden darf oder nicht
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getDocument($bUseCache = true){
		$oVersion = $this->getVersion();
		$oDoc = $oVersion->getDocument($bUseCache);
		return (object)$oDoc;
	}

	public function getInquiry(){
		$oDoc = $this->getDocument();
		$oInquiry = $oDoc->getInquiry();
		return $oInquiry;
	}

	/**
	 * Achtung: Methode ist quasi redundant mit Ext_Thebing_Agency::getNewProvisionAmountByType()
	 *
	 * @TODO Vereinen mit Ext_Thebing_Agency::getNewProvisionAmountByType()
	 * @see Ext_Thebing_Agency::getNewProvisionAmountByType()
	 * @param int $fAmount
	 * @return float
	 * @throws Exception
	 */
	public function getNewProvisionAmount($fAmount = 0, $type=null){

		$oWDDate = new WDDate();

		$oDoc = $this->getDocument();

		$oInquiry = $oDoc->getInquiry();

		$iSaisonCourse = 0;
		$iSaisonAcco = 0;

		$iFirstCourseFrom = 0;
		$iFirstAccommodationFrom = 0;

		// Item Variablen zwischenspeichern, da diese unten ggf. umgeschreiben werden (Special)
		$sItemType = $this->type;
		$iItemTypeId = $this->type_id;
		$fItemAmount = $this->amount;
		$iItemParentId = $this->parent_id;

		if($fAmount == 0){
			$fAmount = $fItemAmount;
		}

		if($type === 'creditnote_subagency') {
			
			$commissionAmount = 0;
			
			if(
				$sItemType === 'course' &&
				$oInquiry->hasSubAgency()
			) {
				
				$agency = Ext_Thebing_Agency::getInstance($oInquiry->agency_id);
				$commissionAmount = $fAmount * $agency->subagency_commission/100;

			}
			
			return round($commissionAmount, 2);
		}
		
		if($sItemType == 'course' && $iItemTypeId > 0){

			$oInquiryCourse = $oInquiry->getServiceObject('course', $iItemTypeId);
			$iFirstCourseFrom = $oInquiryCourse->from;

			$oWDDate->set($iFirstCourseFrom, WDDate::DB_DATE);
			$iFirstCourseFrom = $oWDDate->get(WDDate::TIMESTAMP);

		} else if($sItemType == 'accommodation' && $iItemTypeId > 0){

			$oInquiryAccommodation = $oInquiry->getServiceObject('accommodation', $iItemTypeId);
			$iFirstAccommodationFrom = $oInquiryAccommodation->from;

			$oWDDate->set($iFirstAccommodationFrom, WDDate::DB_DATE);
			$iFirstAccommodationFrom = $oWDDate->get(WDDate::TIMESTAMP);

		} else {
			$iFirstCourseFrom			= (int)$oInquiry->getFirstCourseStart(true);
			$iFirstAccommodationFrom	= (int)$oInquiry->getFirstAccommodationStart();
			
		}

		// wenn keine Unterkunft und kein Kurs gebucht dann nehme das heutige Datum
		if($iFirstCourseFrom <= 0 && $iFirstAccommodationFrom <= 0){
			$iFirstCourseFrom = time();
		}

		$oSchool = $oInquiry->getSchool();

		$oAgency = Ext_Thebing_Agency::getInstance($oInquiry->agency_id);
		$oAgency->setSchool($oSchool);
	
		if($iFirstCourseFrom > 0){
			$oSaison 		= new Ext_Thebing_Saison($oSchool->id);
			$oSaisonSearch 	= new Ext_Thebing_Saison_Search();
			$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp($oSchool->id, $iFirstCourseFrom, $oInquiry->getCreatedForDiscount(), 'course');

			$iSaisonCourse 		= $aSaisonData[0]['id'];
		}

		if($iFirstAccommodationFrom > 0){
			$oSaison 		= new Ext_Thebing_Saison($oSchool->id);
			$oSaisonSearch 	= new Ext_Thebing_Saison_Search();
			$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp($oSchool->id, $iFirstAccommodationFrom, $oInquiry->getCreatedForDiscount(), 'accommodation');

			$iSaisonAcco 		= $aSaisonData[0]['id'];
		}

		if($iSaisonAcco <= 0){
			$iSaisonAcco = $iSaisonCourse;
		}

		if($iSaisonCourse <= 0){
			$iSaisonCourse = $iSaisonAcco;
		}


		if($fAmount != 0){

			//wenn kein Fall zutrifft, keine Provision berechnen
			$oProvision = null;
			
			// Da Special Pos genau so behandelt werden sollen wie Ihre "Basis" Position, auf die sie sich beziehen
			// Muss dafür gesoft werden, dass sie in den passenden case reinfallen!
			if($sItemType == 'special'){
				$oBaseItem = self::getInstance($this->parent_id);
				$sItemType = $oBaseItem->type;
				$iItemTypeId = $oBaseItem->type_id;
				$iItemParentId = $oBaseItem->parent_id;
			}

			switch($sItemType){

				case 'course':
					$oSchoolProvisionCourse = $oAgency->getSchoolProvisions($iSaisonCourse);
					$oInquiryCourse = $oInquiry->getServiceObject($sItemType, $iItemTypeId);
					$oProvision = $oSchoolProvisionCourse->getCourseProvision($oInquiryCourse->course_id);
					break;
				case 'accommodation':
					$oSchoolProvisionAccommodation = $oAgency->getSchoolProvisions($iSaisonAcco);
					$oInquiryAcco = $oInquiry->getServiceObject($sItemType, $iItemTypeId);
					$oProvision = $oSchoolProvisionAccommodation->getAccommodationProvision($oInquiryAcco->accommodation_id, $oInquiryAcco->roomtype_id, $oInquiryAcco->meal_id);
					break;
				case 'additional_course':
					$oSchoolProvisionCourse = $oAgency->getSchoolProvisions($iSaisonCourse);
					$oProvision = $oSchoolProvisionCourse->getAdditionalProvision($iItemTypeId, $iItemParentId, 'course');
					break;
				case 'additional_accommodation':
					$oSchoolProvisionAccommodation = $oAgency->getSchoolProvisions($iSaisonAcco);
					$oProvision = $oSchoolProvisionAccommodation->getAdditionalProvision($iItemTypeId, $iItemParentId, 'accommodation');
					break;
				case 'additional_general':
				case 'additional':
					// TODO In der getNewProvisionAmountByType() wird hier der erstbeste Kurs / die erstbeste Unterkunft / jetzt genommen
					$oSchoolProvisionCourse = $oAgency->getSchoolProvisions($iSaisonCourse);
					$oProvision = $oSchoolProvisionCourse->getGeneralProvision($iItemTypeId);
					break;
				case 'extra_night':
				case 'extra_nights':
					$oSchoolProvisionAccommodation = $oAgency->getSchoolProvisions($iSaisonAcco);
					$oInquiryAcco = new Ext_TS_Inquiry_Journey_Accommodation($iItemTypeId);
					$oProvision = $oSchoolProvisionAccommodation->getExtraNightProvision($oInquiryAcco->accommodation_id, $oInquiryAcco->roomtype_id, $oInquiryAcco->meal_id);
					break;
				case 'transfer':

					$oTransfer = $oInquiry->getServiceObject($sItemType, $iItemTypeId);
					
					// Festlegen ob hin&Rückreise
					if($iItemTypeId == 0) {
						$bTwoWay = true;
					} else {
						$bTwoWay = false;
					}

					$oSchoolProvision = $oAgency->getSchoolProvisions($iSaisonCourse);
					$oProvision = $oSchoolProvision->getTransferProvision($oTransfer, $bTwoWay);

					break;
				case 'special':
					
					break;
				case 'extraPosition':
					$oSchoolProvisionCourse = $oAgency->getSchoolProvisions($iSaisonCourse);
					$oProvision = $oSchoolProvisionCourse->getExtraPositionProvision($iItemTypeId);
					break;
				case 'activity':
					$oSchoolProvisionCourse = $oAgency->getSchoolProvisions($iSaisonCourse);
					$oInquiryActivity = $oInquiry->getServiceObject($sItemType, $iItemTypeId);
					$oProvision = $oSchoolProvisionCourse->getActivityCommission($oInquiryActivity->getActivity());
					break;
			}

			$iPriceNetto = $fAmount;

			if ($oProvision) {
				$iPriceNetto = $iPriceNetto - $oProvision->calculate((float)$fAmount);
			}

			$fProvisionAmount = $fAmount - $iPriceNetto;

		} else {
			$fProvisionAmount = 0;
		}

		// Hook existiert auch in Ext_Thebing_Agency::getNewProvisionAmountByType()
		$aHookData = ['item' => $this->getData(), 'commission' => &$fProvisionAmount];
		System::wd()->executeHook('ts_inquiry_document_get_item_commission', $aHookData);

		// Betrag runden (analog zu Ext_Thebing_Agency::getNewProvisionAmountByType())
		return round($fProvisionAmount, 2);
	}


	/**
	 * @TODO Sollte dringend entfernt werden, da das schwere Bugs auslösen kann
	 *
	 * @var array
	 */
	protected static $aPayedAmountCache = array();

	/**
	 * @TODO Sollte dringend entfernt werden, da das schwere Bugs auslösen kann
	 *
	 * Leert den Cache
	 */
	public static function truncatePayedAmountCache() {
		self::$aPayedAmountCache = array();
	}

	/**
	 * @TODO Wofür wird $iCurrencyId hier noch benötigt? Man kann bei einer Buchung nicht nachträglich die Währung ändern
	 *
	 * @param int $iCurrencyId
	 * @param int $iType
	 * @param int[] $aPaymentIds
	 * @return float
	 */
	public function getPayedAmount($iCurrencyId, $iType=0, $aPaymentIds=[]) {

		$aSql = array();

		$sPaymentIds = join('_', $aPaymentIds);
		$aCache = self::$aPayedAmountCache[$this->id][$iCurrencyId][$iType][$sPaymentIds] ?? null;

		if(!empty($aCache)) {
			return $aCache;
		}

		// Welche Art Bezahlt geholt werden soll
		$sWhereAddon = '';
		if($iType != 0) {
			$sWhereAddon .= " AND `kip`.`type_id` = :type ";
			$aSql['type'] = (int)$iType;
		}

		// Bezahlter Betrag nur aus den übergebenen Zahlungen
		if(!empty($aPaymentIds)) {
			$sWhereAddon .= " AND `kip`.`id` IN (:payment_ids) ";
			$aSql['payment_ids'] = $aPaymentIds;
		}

		$sSql = " 
			SELECT
				SUM(`kipi`.`amount_inquiry`) `amount`
			FROM
				`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
				`kolumbus_inquiries_payments` `kip` ON
					`kip`.`id` = `kipi`.`payment_id`
			WHERE
				`kipi`.`item_id` = :item_id AND
				`kipi`.`currency_inquiry` = :currency_inquiry AND
				`kip`.`active` = 1 AND
				`kipi`.`active` = 1 " . $sWhereAddon;

		$aSql['item_id'] = (int) $this->id;
		$aSql['currency_inquiry'] = (int) $iCurrencyId;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		self::$aPayedAmountCache[$this->id][$iCurrencyId][$iType][$sPaymentIds] = (float)$aResult[0]['amount'];

		return (float)$aResult[0]['amount'];
	}

	public function getAllocatedPaymentReceiptNumbers(): array {

		$sSql = "
			SELECT
				DISTINCT kid.document_number
			FROM
				kolumbus_inquiries_payments_items kipi INNER JOIN
				kolumbus_inquiries_payments kip ON
					kip.id = kipi.payment_id AND
					kip.active = 1 INNER JOIN
				kolumbus_inquiries_payments_documents kipd ON
					kipd.payment_id = kip.id INNER JOIN
				kolumbus_inquiries_documents kid ON
					kid.id = kipd.document_id AND
					kid.type = 'receipt_customer' AND
					kid.active = 1
			WHERE
				kipi.item_id = :id
		";

		return (array)\DB::getQueryCol($sSql, ['id' => $this->id]);

	}

	/**
	 * Offener Betrag dieses Items
	 *
	 * @return float
	 */
	public function getOpenAmount() {

		$oDocument = $this->getDocument();
		$oSchool = $oDocument->getSchool();
		$fItemAmount = (float)$this->getTaxDiscountAmount($oSchool->id, '', true);
		$fPayedAmount = (float)$this->getPayedAmount($oDocument->getCurrencyId());

		return $fItemAmount - $fPayedAmount;

	}

	/**
	 * Ordnet die Zahlungen dieses Vorgängeritems dem aktuellen Item zu
	 *
	 * @param self $oNewItem
	 * @param bool $bIgnoreSameDocument
	 * @return bool
	 */
	public function refreshPaymentData($oNewItem, $bIgnoreSameDocument = false) {

		// Es dürfen nur Items vom selben Dokument umgeschrieben werden! #9077
		// Bei CNs besteht bspw. eine Verknüpfung zur Ursprungsrechnung, aber die CN darf nicht die Bezahlungen übernehmen…
		if(
			$bIgnoreSameDocument === false &&
			$this->getDocument()->id != $oNewItem->getDocument()->id
		) {
			return false;
		}

		if($oNewItem->onPdf == 0) {
			$this->deletePaymentData();
		} else {
			
			$aPaymentItems = $this->getPaymentItems();
			
			foreach($aPaymentItems as $oPaymentItem) {
				$oPaymentItem->item_id = (int)$oNewItem->id;
				$oPaymentItem->save();
			}
		}

		return true;

	}

	/**
	 * Holt die Zahlungens-Items zu diesem Item
	 *
	 * @return Ext_Thebing_Inquiry_Payment_Item[]
	 */
	public function getPaymentItems() {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_payments_items`
			WHERE
				`item_id` = :id AND
				`active` = 1
		";

		$aResult = (array)DB::getQueryRows($sSql, ['id' => $this->id]);

		$aReturn = array_map(function($aItem) {
			return Ext_Thebing_Inquiry_Payment_Item::getObjectFromArray($aItem);
		}, $aResult);

		return $aReturn;

	}

	/**
	 * Löscht Zahlungen zu diesem Item und speichert den Betrag als Überzahlung 
	 */
	public function deletePaymentData() {

		$oDocument = $this->getDocument();
		$aPayments = [];

		$aPaymentItems = $this->getPaymentItems();

		foreach ($aPaymentItems as $oItem) {

			$oOverpayment = Ext_Thebing_Inquiry_Payment_Overpayment::query()
				->where('payment_id', $oItem->payment_id)
				->where('inquiry_document_id', $oDocument->active ? $oDocument->id : null)
				->where('currency_inquiry', $oItem->currency_inquiry)
				->where('currency_school', $oItem->currency_school)
				->first();

			if ($oOverpayment === null) {
				$oOverpayment = new Ext_Thebing_Inquiry_Payment_Overpayment();
				$oOverpayment->payment_id = $oItem->payment_id;
				$oOverpayment->inquiry_document_id = $oDocument->active ? $oDocument->id : null;
				$oOverpayment->currency_inquiry = $oItem->currency_inquiry;
				$oOverpayment->currency_school = $oItem->currency_school;
			}

			$oOverpayment->amount_inquiry = (float)$oOverpayment->amount_inquiry + $oItem->amount_inquiry;
			$oOverpayment->amount_school = (float)$oOverpayment->amount_school + $oItem->amount_school;

			$oOverpayment->save();

			$oOverpayment->saveRebooking($oItem->id);
					
			$oItem->active = 0;
			$oItem->save();

			$oPayment = $oOverpayment->getPayment();
			$aPayments[$oPayment->id] = $oPayment;

		}

		foreach ($aPayments as $oPayment) {
			if (!$oPayment->checkAmount()) {
				throw new \RuntimeException('deletePaymentData: Payment amounts do not match');
			}
		}

	}
	
	public function getAmount($sAmountType = 'brutto', $bDiscount = true, $bTax = true){
 
		$fAmount = $this->amount;

		if($bDiscount){
			$fAmount = $this->getDiscountAmount($sAmountType);
		} elseif($sAmountType === 'commission') {
			$fAmount = $this->amount_provision;
		} else if($sAmountType != 'brutto') {
			$fAmount = $this->amount_net;
		}

		if($bTax){
			$fAmount = $this->getTaxAmount(0, $fAmount);
		}

		return $fAmount;
		
	}

	/**
	 * Berechnet den Steuerbetrag der Position. Option kann ein Betrag übergeben werden. 
	 * Das ist wichtig falls nur für einen Teil des Betrags der Steuerbetrag ermittelt werden soll (Monatssplittung)
	 * @param float $amount
	 * @return float
	 */
    public function getOnlyTaxAmount(float $amount=null) {
		
        $fTaxAmount     = 0;
        $oVersion       = $this->getVersion();
        $iTax = 0;
        
        if($oVersion){
            $iTax = $oVersion->tax;
        }

		if($amount === null) {
			$oDocument = $this->getDocument();
			if($oDocument->type === 'creditnote') {
				$amount = $this->amount_provision;
			} else {
				$amount = $this->amount;
			}

			// Auch von der Steuer muss der Rabatt abgezogen werden!
			if($this->amount_discount > 0) {
				$amount -= ($amount / 100) * $this->amount_discount;
			}
		}

        switch ($iTax) {
            case 1:
                $aTaxAmount = Ext_TC_Vat::calculateInclusiveTaxes($amount, $this->tax);
                $fTaxAmount = $aTaxAmount['amount_tax_diff'];
                break;
            case 2:
                $aTaxAmount = Ext_TC_Vat::calculateExclusiveTaxes($amount, $this->tax);
                $fTaxAmount = $aTaxAmount['amount'];
                break;
        }
        
        return $fTaxAmount;
    }
    
	/**
	 * Liefert den reinen Steuer Betrag
	 */
	public function getTaxAmount($iSchool = null, $fAmount = null, $bRound=true){

		// OHNE Cach! Ganz Wichtig, für Anfragen, umwandlung
		$oDocument = $this->getDocument(false);
		$oVersion = $this->getVersion();
		// Tax Einstellung wurde in der Version gecached 
		$iTax = $oVersion->tax;

		if(empty($iSchool)) {
			$oInquiry = $oDocument->getInquiry();
			if(!$oInquiry) {
				return $fAmount;
			}
			$oSchool = $oInquiry->getSchool();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}

		// Nur bei Exklusivsteuern Betrag hinzufügen
		if(
			$iTax == 2 && 
			$this->tax_category > 0
		){
			// Steuersatz (gespeicherten Wert nehmen, nicht neu auslesen)
			$fTax = (float)$this->tax;

			if($fAmount === null) {
				// Wenn kein Betrag übergeben wurde wird er ermittelt
				$bNetto = $oDocument->isNetto();
                
				if($bNetto){
					$fAmount = $this->amount_net;
				}else{
					$fAmount = $this->amount;
				}
			}

			$fAmountTax = $fAmount * $fTax / 100;
			
			if($bRound){
				$fAmountTax = round($fAmountTax,2);
			}
			
			$fAmount += $fAmountTax;

		}

		return $fAmount;
	}

	/**
	 * Liefert den Betrag, reduziert durch den Discount
	 * @TODO Sollte refaktorisiert werden
	 *
	 * @param string $sAmountType brutto|netto|commission|???
	 * @return float
	 */
	public function getDiscountAmount($sAmountType = '', bool $round=false) {
		
		$bCreditNote = false;

		if($sAmountType === 'netto') {
			$bNetto	= true;
		} elseif(
			$sAmountType === 'brutto' ||
			$sAmountType === 'commission'
		) {
			$bNetto	= false;
		} else {
			$oDocument = $this->getDocument();
			$bNetto	= $oDocument->isNetto();
			if($oDocument->type === 'storno') {
				// Bei Storno immer Nettobeträge, da eine Storno nicht brutto oder netto ist
				// Bei Bruttobeträgen steht im Nettobetrag immer derselbe Wert drin
				$bNetto = true;
			} elseif($oDocument->type === 'creditnote') {
				$bCreditNote = true;
			}
		}

		if($bNetto) {
			$fAmount = $this->amount_net;
		} else {
			if($sAmountType === 'commission') {
				$fAmount = $this->amount_provision;
			} else {
				$fAmount = $this->amount;
			}
		}

		if($this->amount_discount > 0) {
			$fAmount -= ($fAmount / 100) * $this->amount_discount;
		}

		if($bCreditNote && !$bNetto) {
			$fAmountNet = $this->getDiscountAmount('netto');
			$fAmount = $fAmount - $fAmountNet;
		}
	
		if($round){
			$fAmount = round($fAmount, 2);
		}
		
		return (float)$fAmount;
	}

	/*
	 * Liefert den NEUEN Amount Abzüglich Rabatt und zzgl. Steuern
	 * wenn $sType == '' wird netto bzw. brutto bestimmt ansonsten wird explizit dieser Betrag geholt
	 * $sType = brutto || netto
	 */
	public function getTaxDiscountAmount($iSchool = null, $sType = '', $bRound=true) {
		
		$fDiscount = $this->getDiscountAmount($sType, $bRound);

		$fTaxDiscount = $this->getTaxAmount($iSchool, $fDiscount, $bRound);

		return $fTaxDiscount;
	}
	
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			if(
				// Gelöschte Elemente müssen nicht mehr überprüft werden.
				$this->isActive() &&
				(
					!Core\Helper\DateTime::isDate($this->index_from, 'Y-m-d') ||
					!Core\Helper\DateTime::isDate($this->index_until, 'Y-m-d')
				)
			) {
				// WDValidate DATE lässt auch 0000-00-00 zu
				$mValidate = [
					'index_from' => 'WRONG_INDEX_TIMEFRAME_VALUE'
				];
			}
		}

		return $mValidate;

	}

	/**
	 * @TODO validate() einbauen und je nach Typ benötigte weitere IDs prüfen, z.B. type_object_id
	 *
	 * @inheritdoc
	 */
	public function save($bLog = true) {

//		// @TODO Das sollte eigentlich auch bei den Anfragen (Angeboten) gemacht werden…
//		$oInquiry = $this->getInquiry();
//		if($oInquiry) {
//			$this->updateItemCache();
//		}
		
		return parent::save($bLog);
		
	}
	
	/**
	 * @deprecated
	 *
	 * @TODO Darf nicht mehr ausgeführt werden, weil from/until eigentlich immer da sein müssen
	 *
	 * Speichert den Item Cache
	 *
	 * Wichtig! Diese Methode darf NICHT mit $bForce auf jedes Dokument angewendet werden,
	 * da dann die Zeiträume bei allen Items falsch wären, bei denen die Leistungszeiträume
	 * verändert wurden und die ursprüngliche Info nur noch im Item steht!
	 *
	 * @param bool $bForce
	 */
	public function updateItemCache($bForce = false) {
		
		$sFrom = $sUntil = '';

		// Datum nur füllen wenn nicht schon gefüllt
		if(
			!$bForce &&
			Core\Helper\DateTime::isDate($this->index_from, 'Y-m-d') &&
			Core\Helper\DateTime::isDate($this->index_until, 'Y-m-d') /*&&
			$this->type != 'special'*/			# Special Items müssen immer neu gebildet werden, da sie auf IDs anderer
												# Items verweisen die erst gespeichert werden müssen
		) {
			return;
		}

		// Diese Methode sollte gar nichts mehr machen, das hier sind aber noch elende Sonderfälle
		if(
			!$bForce /*&& // Bei Angeboten immer
			$this->type !== 'additional_general' &&
			$this->type !== 'extraPosition' &&
			$this->type !== 'special'*/
		) {
			throw new LogicException('updateItemCache: Item from/until must be set in dialog!');
		}

		/**
		 * Kurs/Unterkunft und Zusatzgebühren sollten bereits über die buildItems 
		 * befüllt sein und sollten hier nicht reinkommen
		 */
		
		switch($this->type) {
			case 'course':
				// Kursdatum ermitteln
				if(
					Core\Helper\DateTime::isDate($this->additional_info['from'], 'Y-m-d') &&
					Core\Helper\DateTime::isDate($this->additional_info['until'], 'Y-m-d')
				) {
					$sFrom = $this->additional_info['from'];
					$sUntil = $this->additional_info['until'];
				} else {
					// Alte Items
					$oJourneyCourse = $this->getInquiry()->getServiceObject('course', $this->type_id);
					$sFrom	= $oJourneyCourse->from;
					$sUntil = $oJourneyCourse->until;
				}
				break;
			case 'accommodation':
			case 'extra_nights':
			case 'extra_weeks':
				if(
					Core\Helper\DateTime::isDate($this->additional_info['from'], 'Y-m-d') &&
					Core\Helper\DateTime::isDate($this->additional_info['until'], 'Y-m-d')
				) {
					// Zeitraum aus from/until mit/ohne Extrazeug (wie es auch in description drin steht)
					$sFrom = $this->additional_info['from'];
					$sUntil = $this->additional_info['until'];
				} else {
					// Alte Items
					$oJourneyAccommodation = $this->getInquiry()->getServiceObject('accommodation', $this->type_id);
					$sFrom	= $oJourneyAccommodation->from;
					$sUntil = $oJourneyAccommodation->until;
				}
				break;
			case 'additional_course':
				$oJourneyCourse = $this->getInquiry()->getServiceObject('course', $this->parent_booking_id);
				if($oJourneyCourse->exist()) {
					// Das ist nicht mehr korrekt, sobald die Leistung in der Buchung verändert wird
					$sFrom = $oJourneyCourse->from;
					$sUntil = $oJourneyCourse->until;
				} else {
					// Veraltete Items, wird nur bei SEHR alten Rechnungen nötig seit ca. < 2010
					$oInquiry = $this->getInquiry();
					$aCourses = $oInquiry->getCourses(true);

					if(count($aCourses) > 0){
						$oJourneyCourse = reset($aCourses);
						$sFrom	= $oJourneyCourse->from;
						$sUntil = $oJourneyCourse->until;
					}					
				}
				break;
			case 'additional_accommodation':
				$oJourneyAccommodation = $this->getInquiry()->getServiceObject('accommodation', $this->parent_booking_id);
				if($oJourneyAccommodation->exist()) {
					// Das ist nicht mehr korrekt, sobald die Leistung in der Buchung verändert wird
					$sFrom = $oJourneyAccommodation->from;
					$sUntil = $oJourneyAccommodation->until;
				} else {
					// Veraltete Items, wird nur bei SEHR alten Rechnungen nötig seit ca. < 2010
					$oInquiry = $this->getInquiry();
					$aAccommodations = $oInquiry->getAccommodations(true);

					if(count($aAccommodations) > 0){
						$oJourneyAccommodation = reset($aAccommodations);
						$sFrom	= $oJourneyAccommodation->from;
						$sUntil = $oJourneyAccommodation->until;
					}					
				}
				break;
			case 'additional_general':
			case 'extraPosition':
			case 'storno':
			case 'paket':

				// Bei Teilzahlung darf nur min/max aller Items verwendet werden, da ansonsten der Abrechnungszeitraum falsch ist
				if($this->getDocument()->partial_invoice) {

					$dMinFrom = null;
					$dMaxUntil = null;
					$aItems = $this->getVersion()->getJoinedObjectChilds('items', true); /** @var self[] $aItems */
					foreach($aItems as $oItem) {
						if(
							Core\Helper\DateTime::isDate($oItem->index_from, 'Y-m-d') &&
							Core\Helper\DateTime::isDate($oItem->index_until, 'Y-m-d')
						) {
							if($dMinFrom === null) {
								$dMinFrom = $oItem->getFrom();
							}
							if($dMaxUntil === null) {
								$dMaxUntil = $oItem->getUntil();
							}
							$dMinFrom = min($dMinFrom, $oItem->getFrom());
							$dMaxUntil = min($dMaxUntil, $oItem->getUntil());
						}
					}

					if(
						$dMinFrom !== null &&
						$dMaxUntil !== null
					) {
						$sFrom = $dMinFrom->format('Y-m-d');
						$sUntil = $dMaxUntil->format('Y-m-d');
					}

				}

				// Kompletter Leistungszeitraum
				if(
					empty($sFrom) ||
					empty($sUntil)
				) {
					$oInquiry = $this->getInquiry();
					$oDateRange = $oInquiry->getCompleteServiceTimeframe();
					if($oDateRange !== null) {
						$sFrom = $oDateRange->start->toDateString();
						$sUntil = $oDateRange->end->toDateString();

						if($this->getDocument()->partial_invoice) {
							$sFrom = $sUntil;
						}
					}
				}

				break;
			case 'insurance':
				$oJourneyInsurance = $this->getInquiry()->getServiceObject('insurance', $this->type_id);
				$sFrom = $oJourneyInsurance->from;
				$sUntil = $oJourneyInsurance->until;

				break;
			case 'special':
				if(
					$this->parent_type == 'item_id' &&
					$this->parent_id > 0
				) {
					// Item auf das sich das special bezieht
					$oItem = self::getInstance($this->parent_id);
					$sFrom = $oItem->index_from;
					$sUntil = $oItem->index_until;
				}
				break;
			case 'transfer':
				$oInquiry = $this->getInquiry();
				if($this->type_id == 0) {
					$oArrival = $oInquiry->getTransfers('arrival', true);
					$oDeparture = $oInquiry->getTransfers('departure', true);
					$sFrom	= $oArrival->transfer_date;
					$sUntil = $oDeparture->transfer_date;
				} else {
					$oTransfer = $oInquiry->getServiceObject('transfer', $this->type_id);
					$sFrom	= $oTransfer->transfer_date;
					$sUntil = $oTransfer->transfer_date;
				}
				break;
		}

		// TODO Exception einbauen
		if(
			!Core\Helper\DateTime::isDate($sFrom, 'Y-m-d') ||
			!Core\Helper\DateTime::isDate($sUntil, 'Y-m-d')
		) {
			// Das hier Dürfte NIE passieren, außer beim 1. SPeichern der Special Positionen, da diese erst
			// beim 2. speichern (item_id wird ergänzt) ihr Datum bekommen
			// Rechnungsdatum
			$oVersion = $this->getVersion();
			$sFrom = $oVersion->date;
			$sUntil = $oVersion->date;
		}

		$this->index_from = $sFrom;
		$this->index_until = $sUntil;

	}

	/**
	 * @return Ext_TS_Inquiry_Contact_Traveller|null
	 */
	public function getContact() {

		if($this->contact_id == 0) {
			return null;
		}

		return Ext_TS_Inquiry_Contact_Traveller::getInstance($this->contact_id);

	}
	
    /**
     * @return \DateTime 
     */
    public function getFrom(){
        $oDate = new DateTime($this->index_from);
        return $oDate;
    }
	
    /**
     * @return \DateTime 
     */
    public function getUntil(){
        $oDate = new DateTime($this->index_until);
        return $oDate;
    }
    
    /**
	 * @TODO \Ext_Thebing_Inquiry_Document_Version::$itemRelationMapping
	 * @TODO Redundanz
	 * @see \Ext_TS_Inquiry_Abstract::getServiceObject()
	 *
     * @return Ext_TS_Inquiry_Journey_Course|Ext_TS_Inquiry_Journey_Accommodation|Ext_TS_Inquiry_Journey_Insurance|Ext_TS_Inquiry_Journey_Transfer
     */
    public function getJourneyService(){
        $oService = null;
        switch ($this->type) {
            case 'course':
                $oService = Ext_TS_Inquiry_Journey_Course::getInstance($this->type_id);
                break;
            case 'accommodation':
			case 'extra_nights':
   			case 'extra_weeks':
   				// Anmerkung: extra_nights / extra_weeks dürfte hier bei sehr alten Items nicht funktionieren
                $oService = Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->type_id);
                break;
            case 'insurance':
                $oService = Ext_TS_Inquiry_Journey_Insurance::getInstance($this->type_id);
                break;
            case 'transfer':
            	// Achtung, das funktioniert nur bei einem Einweg-Transfer!
				// Ansonsten müssen die Daten in additional_data geprüft werden…
                $oService = Ext_TS_Inquiry_Journey_Transfer::getInstance($this->type_id);
                break;
        }
        return $oService;
    }
    
    public function getService() {

        $oService = null;

        switch ($this->type) {
            case 'course':
				//$oJourneyService = Ext_TS_Inquiry_Journey_Course::getInstance($this->type_id);
                //$oService = $oJourneyService->getCourse();
				$oService = Ext_Thebing_Tuition_Course::getInstance($this->type_object_id);
                break;
            case 'accommodation':
            case 'extra_nights':
            case 'extra_weeks':
                //$oJourneyService = Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->type_id);
                //$oService = $oJourneyService->getCategory();
				$oService = Ext_Thebing_Accommodation_Category::getInstance($this->type_object_id);
                break;
            case 'insurance':
                //$oJourneyInsurance = Ext_TS_Inquiry_Journey_Insurance::getInstance($this->type_id);
                //$oService = $oJourneyInsurance->getInsurance();
				$oService	= Ext_Thebing_Insurance::getInstance($this->type_object_id);
                break;
            case 'transfer':
				$oPackage = Ext_Thebing_Transfer_Package::getInstance($this->additional_info['transfer_package_id']);
				if ($oPackage->exist()) {
					$oService = $oPackage;
				}
                break;
	        case 'additional_general':
	        case 'additional_course':
	        case 'additional_accommodation':
		        $oService = Ext_Thebing_School_Additionalcost::getInstance($this->type_id);
		        break;
			case 'activity':
				$oService = TsActivities\Entity\Activity::getInstance($this->type_object_id);
				break;
        }

        return $oService;
    }

    public function getTypeName(\Tc\Service\LanguageAbstract $oLanguage = null): string {

		if ($oLanguage === null) {
			$oLanguage = new \Tc\Service\Language\Backend(\System::getInterfaceLanguage());
			$oLanguage->setContext(Ext_Thebing_Document::$sL10NDescription);
		}

		$bBackend = $oLanguage instanceof \Tc\Service\Language\Backend;

		return match ($this->type) {
			'course' => $oLanguage->translate($bBackend ? 'Kurs' : 'Course'),
			'accommodation' => $oLanguage->translate($bBackend ? 'Unterkunft' : 'Accommodation'),
			'extra_nights' => $oLanguage->translate($bBackend ? 'Extranacht' : 'Extra night'),
			'extra_weeks' => $oLanguage->translate($bBackend ? 'Extrawoche' : 'Extra week'),
			'insurance' => $oLanguage->translate($bBackend ? 'Versicherung' : 'Insurance'),
			'transfer' => $oLanguage->translate('Transfer'),
			'activity' => $oLanguage->translate($bBackend ? 'Aktivität' : 'Activity'),
			'special' => $oLanguage->translate($bBackend ? 'Angebot' : 'Offer'),
			'additional_general' => $oLanguage->translate($bBackend ? 'Generelle Zusatzgebühr' : 'General fee'),
			'additional_course' => $oLanguage->translate($bBackend ? 'Kursgebühr' : 'Course fee'),
			'additional_accommodation' => $oLanguage->translate($bBackend ? 'Unterkunftsgebühr' : 'Accommodation fee'),
			'extraPosition' => $oLanguage->translate($bBackend ? 'Manuelle Position' : 'Manual position'),
			'storno' => $oLanguage->translate($bBackend ? 'Stornierung' : 'Cancellation'),
			'deposit', 'deposit_credit' => $oLanguage->translate($bBackend ? 'Anzahlung' : 'Deposit'),
			default => $oLanguage->translate($bBackend ? 'Unbekannt' : 'Unknown'),
		};

    }

	/**
	 * Parent-Item holen
	 * @return Ext_Thebing_Inquiry_Document_Version_Item
	 */
	public function getParentItem() {

		if(
			$this->parent_type === 'item_id' &&
			$this->parent_id > 0
		) {
			return static::getInstance($this->parent_id);
		}

		return null;
	}

	/**
	 * Namen des Services dieses Items ermitteln (z.B. für den Buchungsstapel)
	 *
	 * @param bool $bShort
	 * @return null|string
	 */
	public function getServiceName($bShort=false) {

		// Sonderfall manuelle Position: Hier gibt es keinen Service, es soll die Beschreibung genommen werden
		if($this->type === 'extraPosition') {
			return $this->description;
		}

		$oService = $this->getService();

		if($oService && !$bShort) {
			return $oService->getName();
		} elseif($oService && method_exists($oService, 'getShortName')) {
			return $oService->getShortName();
		} elseif(!$bShort) {
			return $this->getTypeName();
		}

		return null;

	}

	/**
	 * Daten von einem anderen Item in dieses Item setzen
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 */
	public function setOtherItemData(Ext_Thebing_Inquiry_Document_Version_Item $oItem) {

		$this->type = $oItem->type;
		$this->type_id = $oItem->type_id;
		$this->description = $oItem->description;
		$this->amount = $oItem->amount;
		$this->amount_net = $oItem->amount_net;
		$this->amount_provision	= $oItem->amount_provision;
		$this->amount_discount = $oItem->amount_discount;
		$this->description_discount = $oItem->description_discount;
		$this->initalcost = $oItem->initalcost;
		$this->onPdf = $oItem->onPdf;
		$this->calculate = $oItem->calculate;
		$this->active = $oItem->active;
		$this->position = $oItem->position;
		$this->count = $oItem->count;
		$this->nights = $oItem->nights;
		$this->tax_category = $oItem->tax_category;
		$this->tax = $oItem->tax;
		$this->parent_id = $oItem->parent_id;
		$this->parent_type = $oItem->parent_type;
		$this->parent_booking_id = $oItem->parent_booking_id;
		$this->type_object_id = $oItem->type_object_id;
		$this->type_parent_object_id = $oItem->type_parent_object_id;
		$this->index_from = $oItem->index_from;
		$this->index_until = $oItem->index_until;
		$this->additional_info = $oItem->additional_info;
		$this->contact_id = $oItem->contact_id;

	}

	public static function createFromArray(array $aData) {

		// Intersection statt direkter aufruf von getObjectFromArray, da $aData allerlei weitere Keys enthalten kann
		$oItem = new self();
		$aItem = array_intersect_key($aData, $oItem->_aData);
		$aItem['id'] = $aData['id'] ?? 0;

		$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getObjectFromArray($aItem);

		// json_encode triggern
		$oItem->additional_info = $aData['additional_info'] ?? [];

		return $oItem;

	}

	/**
	 * Wird aktuell nur für die Stornogebühren benutzt. Überschneidungen mit Ext_Thebing_School::getStornoTypeFromOptions()
	 *
	 * @return array
	 */
	public function getServiceTags(): array {

		$tags = [];

		if (str_starts_with($this->type, 'additional_')) {
			$tags = [$this->type, 'additional_cost', 'additional_cost_'.$this->type_id];
		} else if ($this->type === 'course') {
			$course = $this->getService();
			$tags = [$this->type, 'course_'.$course->id, 'course_category_'.$course->category_id];
		} else if ($this->type === 'accommodation') {
			$tags = [$this->type, 'accommodation_category_'.$this->getService()->id];
		}

		return $tags;
	}

	public function getAdditionalFeeID() {

		$service = $this->getService();

		if ($service instanceof Ext_Thebing_School_Additionalcost) {
			return $service->id;
		}

		return '';
	}

}
