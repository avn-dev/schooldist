<?php


class Ext_TS_Enquiry_Offer extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'ts_enquiries_offers';

	protected $_sTableAlias = 'ts_eo';
	
	// Format
	protected $_aFormat = array(
		'enquiry_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE',
		)
	);
	
	//JoinTables
	protected $_aJoinTables = array(
		'combination_courses' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_courses',
			'foreign_key_field'		=> array('combination_course_id', 'contact_id'),
			'primary_key_field'		=> 'offer_id',
			'autoload'				=> false
		),
		'combination_accommodations' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_accommodations',
			'foreign_key_field'		=> array('combination_accommodation_id', 'contact_id'),
			'primary_key_field'		=> 'offer_id',
			'autoload'				=> false
		),
		'combination_transfers' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_transfers',
			'foreign_key_field'		=> array('combination_transfer_id', 'contact_id'),
			'primary_key_field'		=> 'offer_id',
			'autoload'				=> false
		),
		'combination_insurances' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_insurances',
			'foreign_key_field'		=> array('combination_insurance_id', 'contact_id'),
	 		'primary_key_field'		=> 'offer_id',
			'autoload'				=> false
		),
		'combination_courses_objects' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_courses',
			'foreign_key_field'		=> 'combination_course_id',
			'primary_key_field'		=> 'offer_id',
			'class'					=> 'Ext_TS_Enquiry_Combination_Course',
			'readonly'				=> true,
			'autoload'				=> false,
		),
		'combination_accommodations_objects' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_accommodations',
			'foreign_key_field'		=> 'combination_accommodation_id',
			'primary_key_field'		=> 'offer_id',
			'class'					=> 'Ext_TS_Enquiry_Combination_Accommodation',
			'readonly'				=> true,
			'autoload'				=> false,
		),	
		'combination_transfers_objects' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_transfers',
			'foreign_key_field'		=> 'combination_transfer_id',
			'primary_key_field'		=> 'offer_id',
			'class'					=> 'Ext_TS_Enquiry_Combination_Transfer',
			'readonly'				=> true,
			'autoload'				=> false,
		),
		'combination_insurances_objects' => array(
			'table'					=> 'ts_enquiries_offers_to_combinations_insurances',
			'foreign_key_field'		=> 'combination_insurance_id',
			'primary_key_field'		=> 'offer_id',
			'class'					=> 'Ext_TS_Enquiry_Combination_Insurance',
			'readonly'				=> true,
			'autoload'				=> false,
		),
		'special_position_relation' => array(
			'table'					=> 'ts_enquiries_offers_to_special_positions',
			'primary_key_field'		=> 'enquiry_offer_id',
			'foreign_key_field'		=> 'special_position_id',
			'autoload'				=> false,
			'class' => 'Ext_Thebing_Inquiry_Special_Position',
			'on_delete' => 'no_action'
		),
		'documents'					=> array(
			'table'					=> 'ts_enquiries_offers_to_documents',
			'primary_key_field'		=> 'enquiry_offer_id',
			'foreign_key_field'		=> 'document_id',
			'class'					=> 'Ext_Thebing_Inquiry_Document',
			'on_delete'				=> false // Hier darf gar nichts gelöscht werden, ansonsten ist das Dokument eine Leiche
		),
		'inquiries' => array( // Alle Buchungen die mit diesem Angebot Umgewandelt worden sin
			'table'					=> 'ts_enquiries_offers_to_inquiries',
			'primary_key_field'		=> 'enquiry_offer_id',
			'foreign_key_field'		=> 'inquiry_id',
			'class'					=> 'Ext_TS_Inquiry',
			'autoload' => false,
		)
	);
	
	protected $_aJoinedObjects = array(
		'enquiry' => array(
			'class' => 'Ext_TS_Enquiry',
			'key'	=> 'enquiry_id'
		)
	);
	
	protected $_aAttributes = array(
		//ob Kursdaten für Mitglieder gleich/glieche Zeiträume/individuell
		'course_data' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar',
		),
		//ob Unterkunftsdaten für Mitglieder gleich/glieche Zeiträume/individuell
		'accommodation_data' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar',
		),
		//ob Transferdaten für Mitglieder gleich/glieche Zeiträume/individuell
		'transfer_data' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar',
		),
		//ob Kursdaten für Guides identisch/unterschiedlich mit Mitgliedern
		'course_guide' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar',
		),
		//ob Unterkunftsdaten für Guides identisch/unterschiedlich mit Mitgliedern
		'accommodation_guide' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar',
		),
	);

	// Bearbeiter Spalte
	protected $_sEditorIdColumn = 'editor_id';
	
	public function __get($sName)
	{
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sName == 'document_number')
		{
			$oOfferDocument = $this->getOfferDocument();
			$mValue			= $oOfferDocument->document_number;
		}
		else
		{
			$mValue = parent::__get($sName);
		}
		
		return $mValue;
	}
	

	/**
	 * @return Ext_TS_Enquiry_Combination_Course <array>
	 */
	public function getCourses()
	{
		$aCombinationCoursesObjects = $this->getServiceObjects('course');

		return $aCombinationCoursesObjects;
	}
	
	/**
	 * @return Ext_TS_Enquiry_Combination_Accommodation <array>
	 */
	public function getAccommodations($bAsObjectArray = true)
	{
		$aCombinationCourses = $this->getServiceObjects('accommodation');

		if(!$bAsObjectArray)
		{
			$aBack = array();

			foreach($aCombinationCourses as $oCombinationCourse)
			{ 
				$aBack[] = $oCombinationCourse->getData();
			}
		}
		else
		{
			$aBack = $aCombinationCourses;
		}
		
		return $aBack;
	}
	
	/**
	 * Wenn Filter arrival oder departure ist, wird statt ein array ein objekt zurückgegeben, weil das bei den inquiries genau
	 * so funktioniert musste ich diese verrückte sache übernehmen
	 * 
	 * @return Ext_TS_Enquiry_Combination_Transfer <array> | Ext_TS_Enquiry_Combination_Transfer
	 */
	public function getTransfers($sFilter = '', $bIgnoreBookingStatus = false)
	{
		$aCombinationTransfers	= $this->getServiceObjects('transfer');

		$aFilteredCombinationTransfers = Ext_TS_Enquiry_Combination_Helper::filterTransfers($aCombinationTransfers, $sFilter, $bIgnoreBookingStatus);

		return $aFilteredCombinationTransfers;
	}

	/**
	 * @return Ext_TS_Enquiry_Combination_Insurance[]
	 */
	public function getInsurances() {
		return $this->getServiceObjects('insurance');
	}

	/**
	 * @param string $sDisplayLanguage
	 * @return mixed[]
	 */
	public function getInsurancesWithPriceData($sDisplayLanguage = null) {
		$aInsurances = $this->getServiceObjects('insurance');
		$oEnquiry = $this->getEnquiry();
		return Ext_TS_Enquiry_Combination_Helper::getInsurancesWithPriceData($oEnquiry, $aInsurances, $sDisplayLanguage);
	}

	/**
	 * @return Ext_TS_Enquiry 
	 */
	public function getEnquiry() {
		return Ext_TS_Enquiry::getInstance($this->enquiry_id);
	}

	/**
	 * Liefert die Schule des Angebotes
	 *
	 * @return Ext_Thebing_School
	 */
	public function getSchool() {
		return $this->getEnquiry()->getSchool();
	}

	/**
	 *
	 * Verbindungstabelle für Specialpositionen
	 * 
	 * @return array 
	 */
	public function getSpecialPositionRelationTableData()
	{
		if(isset($this->_aJoinTables['special_position_relation']))
		{
			return $this->_aJoinTables['special_position_relation'];
		}
		else
		{
			throw new Exception('Position Relation Table not found!');
		}
	}
	
	public function getAllocatedContacts() {
		
		$aContacts = array();
		
		$aCombinationCourses = $this->combination_courses;
		$this->_addContact($aCombinationCourses, $aContacts);

		$aCombinationAccommodations = $this->combination_accommodations;
		$this->_addContact($aCombinationAccommodations, $aContacts);
		
		$aCombinationTransfers = $this->combination_transfers;
		$this->_addContact($aCombinationTransfers, $aContacts);
		
		$aCombinationInsurances = $this->combination_insurances;
		$this->_addContact($aCombinationInsurances, $aContacts);
		
		return $aContacts;
	}

	protected function _addContact($aData, &$aContacts){
		
		foreach($aData as $aObjectData) {
			
			$iContactId = (int) $aObjectData['contact_id'];	
			if(empty($aContacts[$iContactId])) {
				$oContact = Ext_TS_Group_Contact::getInstance($iContactId);
				$aContacts[$iContactId] = $oContact;
			}
			
		}
	}
	
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		parent::manipulateSqlParts($aSqlParts, $sView);
		
		$sTableAlias = $this->_sTableAlias;

		if(empty($sTableAlias)) {
			$sTableAlias = $this->_sTable;
		}
		
		//$sSelectServices = Ext_TS_Enquiry_Combination::buildColumnsSelect();
		
		$oSchool			= Ext_Thebing_Client::getFirstSchool();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$sNameField			= '`name_'.$sInterfaceLanguage.'`';
		$sShortField		= '`short_'.$sInterfaceLanguage.'`';
		
		$aSqlParts['select'] .= "
			, `kidv`.`id` `document_version_id`
			, `ts_in`.`id` `inquiry_id`
			, `kid`.`document_number` `document_number`
			, IF(
				`ts_en_of_to_i`.`inquiry_id` IS NULL,
				'',
				GROUP_CONCAT(
					DISTINCT `ts_en_of_to_i`.`inquiry_id`
				) 
			) `offer_inquiries`			

			, (
				SELECT
					GROUP_CONCAT(
						DISTINCT CONCAT_WS(
							'{|}',
							'id', `ts_ecc`.`id`, 
							'name', COALESCE(`kolumbus_tc`.".$sNameField.", ''),
							'short', COALESCE(`kolumbus_tc`.`name_short`, ''),
							'from', `ts_ecc`.`from`,
							'until', `ts_ecc`.`until`,
							'level', COALESCE(`kolumbus_tl`.".$sNameField.", ''),
							'weeks', `ts_ecc`.`weeks`,
							'units', IF(
								`kolumbus_tc`.`per_unit` = 1,
								(`ts_ecc`.`units`),
								(
									SELECT 
											SUM(`ktc_program`.`lessons_per_week`)
										FROM
											`ts_tuition_courses_programs` `ts_tcp`  INNER JOIN
											`ts_tuition_courses_programs_services` `ts_tcps` ON
												`ts_tcps`.`program_id` = `ts_tcp`.`id` AND
												`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
												`ts_tcps`.`active` = 1 INNER JOIN
											`kolumbus_tuition_courses` `ktc_program` ON
												`ktc_program`.`id` = `ts_tcps`.`type_id` AND
												`ktc_program`.`active` = 1
										WHERE 
											`ts_tcp`.`course_id` = `kolumbus_tc`.`id` AND
											`ts_tcp`.`active` = 1										
									) * `ts_ecc`.`weeks`
								)
							)
						)
						SEPARATOR '{||}'
					)
				FROM
					`ts_enquiries_offers_to_combinations_courses` `combination_courses` LEFT JOIN
					`ts_enquiries_combinations_courses` `ts_ecc` ON
						`ts_ecc`.`id` = `combination_courses`.`combination_course_id` AND
						`ts_ecc`.`active` = 1  
					".Ext_TS_Enquiry_Combination::getCourseJoins()."
				WHERE
					`combination_courses`.`offer_id` = `ts_eo`.`id`
			) `courses`
			
			, (
				SELECT
					GROUP_CONCAT(
						DISTINCT CONCAT_WS(
							'{|}',
							'id', `ts_eca`.`id`,
							'category', COALESCE(`kolumbus_ac`.".$sNameField.", ''),
							'meal', COALESCE(`kolumbus_am`.".$sNameField.", ''),
							'room', COALESCE(`kolumbus_ar`.".$sNameField.", ''),
							'category_short', COALESCE(`kolumbus_ac`.".$sShortField.", ''),
							'meal_short', COALESCE(`kolumbus_am`.".$sShortField.", ''),
							'room_short', COALESCE(`kolumbus_ar`.".$sShortField.", ''),
							'description_short', CONCAT(`kolumbus_ac`.".$sShortField.", '/', `kolumbus_am`.".$sShortField.", '/', `kolumbus_ar`.".$sShortField."),
							'description', CONCAT(`kolumbus_ac`.".$sNameField.", '/', `kolumbus_am`.".$sNameField.", '/', `kolumbus_ar`.".$sNameField."),
							'from', `ts_eca`.`from`,
							'until', `ts_eca`.`until`,
							'weeks', `ts_eca`.`weeks`
						)
						SEPARATOR '{||}'
					)
				FROM
					`ts_enquiries_offers_to_combinations_accommodations` `combination_accommodations` LEFT JOIN
					`ts_enquiries_combinations_accommodations` `ts_eca` ON
						`ts_eca`.`id` = `combination_accommodations`.`combination_accommodation_id` AND
						`ts_eca`.`active` = 1 
					".Ext_TS_Enquiry_Combination::getAccommodationJoins()."
				WHERE
					`combination_accommodations`.`offer_id` = `ts_eo`.`id`
			) `accommodations`

			, (
				SELECT
					GROUP_CONCAT(
						DISTINCT CONCAT(
							`ts_ect`.`combination_id`,
							'_',
							`ts_ect`.`transfer_type`,
							'_',
							`ts_ect`.`start_type`,
							'_',
							`ts_ect`.`end_type`,
							'_',
							`ts_ect`.`start`,
							'_',
							`ts_ect`.`end`,
							'_',
							`ts_ect`.`transfer_date`,
							'_',
							`ts_en_c`.`transfer_mode`
						)
					)
				FROM
					`ts_enquiries_offers_to_combinations_transfers` `combination_transfers` LEFT JOIN
					`ts_enquiries_combinations_transfers` `ts_ect` ON
						`ts_ect`.`id` = `combination_transfers`.`combination_transfer_id` AND
						`ts_ect`.`active` = 1 AND
						`ts_ect`.`transfer_type` IN(1,2) LEFT JOIN
					`ts_enquiries_combinations` `ts_en_c` ON
						`ts_en_c`.`id` = `ts_ect`.`combination_id` AND
						`ts_en_c`.`active` = 1
				WHERE
					`combination_transfers`.`offer_id` = `ts_eo`.`id`
			) `transfer_information`

			, (
				SELECT
					GROUP_CONCAT(
						DISTINCT CONCAT_WS(
							'{|}',
							'id', `ts_eci`.`id`, 
							'name', COALESCE(`kolumbus_i`.".$sNameField.", ''),
							'from', `ts_eci`.`from`,
							'until', `ts_eci`.`until`
						) 
						SEPARATOR '{||}'
					)
				FROM
					`ts_enquiries_offers_to_combinations_insurances` `combination_insurances` LEFT JOIN
					`ts_enquiries_combinations_insurances` `ts_eci` ON
						`ts_eci`.`id` = `combination_insurances`.`combination_insurance_id` AND
						`ts_eci`.`active` = 1 
					".Ext_TS_Enquiry_Combination::getInsuranceJoins()."
				WHERE
					`combination_insurances`.`offer_id` = `ts_eo`.`id`
			) `insurances`




		";

		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`id` = `documents`.`document_id` AND
				`kid`.`active` = 1 LEFT JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kidv`.`document_id` = `kid`.`id` AND
				`kidv`.`id` = `kid`.`latest_version` AND
				`kidv`.`active` = 1 LEFT JOIN
			`ts_enquiries_offers_to_inquiries` `ts_en_of_to_i` ON
				`ts_en_of_to_i`.`enquiry_offer_id` = `".$sTableAlias."`.`id` LEFT JOIN
			`ts_inquiries` `ts_in` ON
				`ts_in`.`id` = `ts_en_of_to_i`.`inquiry_id` AND
				`ts_in`.`active` = 1 
		";
	}

	/**
	 * Liefert die Leistungen des Angebots (Kurse/Unterkünfte/Versicherungen/Transfer)
	 *
	 * @param $sKey
	 * @param bool $bSort
	 * @return Ext_TS_Enquiry_Combination_Service
	 */
	public function getServiceObjects($sKey, $bSort = true)
	{
		$aFilteredServices = array();
		
		$sKeyMultiple		= $sKey . 's';
		$sJoinKey			= 'combination_'.$sKeyMultiple;
		$sJoinObjectKey		= $sJoinKey.'_objects';

		$aServiceObjects	= (array)$this->getJoinTableObjects($sJoinObjectKey);

		$oEnquiry			= $this->getEnquiry();
		
		if($oEnquiry->hasTraveller())
		{
			$aServices		= $this->$sJoinKey;
			$oGroupMember	= $oEnquiry->getTraveller();

			foreach($aServices as $iKey => $aService)
			{
				if($aService['contact_id'] == $oGroupMember->id)
				{
					$sKeyRelation			= 'combination_' . $sKey . '_id';
					$aFilteredServices[]	= $aServiceObjects[$aService[$sKeyRelation]];
				}
			}
		}
		else
		{
			$aFilteredServices = $aServiceObjects;
		}
		
		// wenn sortiert wird werden die keys neu geschrieben
		// beim umwandeln brauchen wir es nicht sortiert dafür die id als key
		// daher hab ich das mal über einen Parameter steuern lassen
		if($bSort){
			usort($aFilteredServices, array(
				'Ext_TS_Service_Helper',
				'sortServices'
			));
		}
		
		return $aFilteredServices;
	}
	
	/**
	 * Liefert alle erstellten Angebote (zZ ist nur eines möglich)
	 * @return Ext_Thebing_Inquiry_Document 
	 */
	public function getDocuments(){
		$aDocuments = $this->getJoinTableObjects('documents');
		return $aDocuments;
	}
	
	
	/**
	 * Liefert alle Kontakte zu einem Combination Service Obj
	 * @param Ext_TS_Enquiry_Combination_Service $oService
	 * @return Ext_TS_Contact 
	 */
	public function getContacts(Ext_TS_Enquiry_Combination_Service $oService){
		$aContacts = array();
		
		$aCombinationData = array();
		
		if($oService instanceof Ext_TS_Enquiry_Combination_Course){
			$aCombinationData = $this->combination_courses;
			$sKey = 'combination_course_id';
		} elseif ($oService instanceof Ext_TS_Enquiry_Combination_Accommodation){
			$aCombinationData = $this->combination_accommodations;
			$sKey = 'combination_accommodation_id';
		} elseif ($oService instanceof Ext_TS_Enquiry_Combination_Transfer){
			$aCombinationData = $this->combination_transfers;
			$sKey = 'combination_transfer_id';
		} elseif ($oService instanceof Ext_TS_Enquiry_Combination_Insurance){
			$aCombinationData = $this->combination_insurances;
			$sKey = 'combination_insurance_id';
		}
		
		
		foreach($aCombinationData as $aData){
			if($aData[$sKey] == $oService->id){
				$aContacts[$aData['contact_id']] = new Ext_TS_Contact($aData['contact_id']);
			}
		}		
		
		return $aContacts;
	}
	
	/**
	 * check if the transfers are legal
	 * @return boolean 
	 */
	public function checkTransfers(){		

		$aTransfermodes = $this->getTransferModes();

		foreach($aTransfermodes as $iContact => $aTransfertypes){
			// Schauen welche Art von Transfer wie oft vorkommt
			// keine Art darf mehrmals vorkommen
			// und wenn Art arr_dep vorkommt darf gar keine andere mehr vorkommen
			$aKeysA = array_keys($aTransfertypes, 'arr_dep');
			$aKeysB = array_keys($aTransfertypes, 'arrival');
			$aKeysC = array_keys($aTransfertypes, 'departure');

			if(
				count($aKeysA) > 1 ||
				count($aKeysB) > 1 ||
				count($aKeysC) > 1 ||
				(
					count($aKeysA) == 1 &&
					(
						count($aKeysB) == 1 ||
						count($aKeysC) == 1		
					)
				)
			){
				return false;
			}
			
		}

		return true;
	}
	
	/**
	 * Alle Transfertypen der Kombinationen gruppiert nach den Kontakten
	 * 
	 * @return array 
	 */
	public function getTransferModes()
	{
		$aTransfers		= $this->combination_transfers;
		$aTransfermodes = array();
		
		foreach($aTransfers as $aTransfer){
			$iTransfer		= $aTransfer['combination_transfer_id'];
			$oTransfer		= Ext_TS_Enquiry_Combination_Transfer::getInstance($iTransfer);
			if($oTransfer->getTransferMode() != 0){
				$oCombination	= $oTransfer->getCombination();
				$sMode			= $oCombination->transfer_mode;
				$iContact		= $aTransfer['contact_id'];
				$aTransfermodes[$iContact][$oCombination->id] = $sMode; 
			}
		}
		
		return $aTransfermodes;
	}
	
	/**
	 *
	 * Erstelltes Angebotsdokument
	 * 
	 * @return Ext_Thebing_Inquiry_Document 
	 */
	public function getOfferDocument()
	{
		$oDocument	= null;
		$aDocuments = (array)$this->getJoinTableObjects('documents');
		
		if(!empty($aDocuments))
		{
			$oDocument = reset($aDocuments);
		}
		
		return $oDocument;
	}
	
	/**
	 * Liefert den Dokumententyp den die finale Rechnung dann haben wird
	 * @return string 
	 */
	public function _getDocumentTypeForInvoice($sType = ''){
		
		$oDocument = $this->getOfferDocument();		
		
		$bNetto = false;
		if(strpos($oDocument->type, 'netto') !== false){
			$bNetto = true;
		}
		
		switch($sType){
			case'proforma':
				if($bNetto){
					$sDocumentType = 'proforma_netto';
				}else{
					$sDocumentType = 'proforma_brutto';
				}
				break;
			case 'invoice':
			default:
				if($bNetto){
					$sDocumentType = 'netto';
				}else{
					$sDocumentType = 'brutto';
				}
		}
			
		return $sDocumentType;
	}
	
	/**
	 * Liefert alle Buchungen die mit diesem Angebot umgewandelt wurden
	 * @return Ext_TS_Inquiry
	 */
	public function getConvertedInquiries(){
		$aInquiries = $this->getJoinTableObjects('inquiries');
		
		return $aInquiries;
	}
	
	/**
	 * Gibt Information darüber ob es zu dem Angebot bereits Umwandalungen gibt
	 * @return bool
	 */
	public function isConvertedToInquiry(){
		$aInquiries = $this->getConvertedInquiries();
	
		if(!empty($aInquiries)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Liefert alle Combinationen die die in diesem Angebot verwendet werden
	 * @return Ext_TS_Enquiry_Combination  
	 */
	public function getCombinations(){

		// Alle Leistungen des Angebotes
		$aServices						= array();
		$aServices						+= $this->getServiceObjects('course');
		$aServices						+= $this->getServiceObjects('accommodation');
		$aServices						+= $this->getServiceObjects('transfer');
		$aServices						+= $this->getServiceObjects('insurance');

		$aCombinations					= array();
		foreach($aServices as $oService){
			$oCombination = $oService->getCombination();
			$aCombinations[$oCombination->id] = $oCombination;
		}
		
		return $aCombinations;
	}
	
	/**
	 * Liefert ein bestimmtes Feld des Angebotes, 
	 * er setzt sich aus den Feldern aller verwendeter Kombinationen zusammen
	 * @param type $sType
	 * @return type 
	 */
	public function getMergedFieldData($sType){
		$sValue = '';
		
		$aCombinations = $this->getCombinations();

		$aData = array();
		foreach($aCombinations as $oCombination){
			if($oCombination->$sType != ''){
				$aData[] = $oCombination->$sType;
			}
		}
			
		$sValue = implode(', ', $aData);
		return $sValue;
	}

	/**
	 * Liefert den Transfer Typen der sich aus allen verwendeten Kombinatioen zusammensetzt
	 * @return string 
	 */
	public function getTransferType(){
		$sType = 'no';
		
		$aCombinationTransfers = $this->getServiceObjects('transfer');
		
		foreach($aCombinationTransfers as $oCombinationTransfer){
			switch($oCombinationTransfer->transfer_type){
				case 1: // Arrival
					if($sType == 'departure'){
						$sType = 'arr_dep';
					}else{
						$sType = 'arrival';
					}
					break;
				case 2: // departure
					if($sType == 'arrival'){
						$sType = 'arr_dep';
					}else{
						$sType = 'departure';
					}
					break;
			}
		}
		
		return $sType;
	}
	
	/**
	 * Liefert ein Array mit allen Templates in die ein Angebotsdokument convertiert werden kann
	 * @return type 
	 */
	public function getTemplateOptionsForConvert(){

		$aTemplates			= array();
		
		$oEnquiry			= $this->getEnquiry();
		$oSchool			= $oEnquiry->getSchool();
		$oContact			= $oEnquiry->getFirstTraveller();		
		
		// Select Optionen für Templats und Nummernkreise mitschicken
		$oDocument = $this->getOfferDocument();

		$oInquiry = new Ext_TS_Inquiry;
		
		$sSearchType = 'gross';
		
		if(
			strpos($oDocument->type, 'netto') !== false
		){
			$sSearchType = 'net';
		}
		
		// "normale" Templatetypen
		$aTemplatetypes['invoice'] = $oInquiry->getDocumentTemplateType($sSearchType);

		// "proforma" Templatetypen
		$aTemplatetypes['proforma'] = $oInquiry->getDocumentTemplateType($sSearchType);

		foreach($aTemplatetypes as $sType => $sTemplatetype){
			$aTemplates_ = Ext_Thebing_Pdf_Template_Search::s($sTemplatetype, $oContact->getLanguage(), $oSchool->id);

			foreach((array)$aTemplates_ as $oTemplateTmp) {
				$aInboxes = $oTemplateTmp->getJoinTableObjects('inboxes');
				foreach($aInboxes as $oInbox) {
					$aTemplates[$sType][$oInbox->id][$oTemplateTmp->id] =  $oTemplateTmp->name;
				}
			}
		}
		return $aTemplates;
	}
	
	/**
	 *
	 * TransferTyp (Anreise/Abreise/An- und Abreise)
	 * 
	 * @return type 
	 */
	public function getTransferMode()
	{
		$sTransferMode	= '';
		$aTransferModes = $this->getTransferModes();
		
		foreach($aTransferModes as $iContactId => $aTransfertypes)
		{
			$aKeysArrivalAndDeparture	= array_keys($aTransfertypes, 'arr_dep');
			$aKeysArrival				= array_keys($aTransfertypes, 'arrival');
			$aKeysDeparture				= array_keys($aTransfertypes, 'departure');
			
			if(count($aKeysArrivalAndDeparture) > 0)
			{
				//Wenn An- und Abreise vorhanden, dann ist das der endgültiger Transfertyp
				$sTransferMode = 'arr_dep';
			}
			elseif(
				count($aKeysArrival) > 0 &&
				count($aKeysDeparture) > 0
			)
			{
				//Wenn Anreise & Abreise vorhanden, dann Typen in An- und Abreise umändern
				$sTransferMode = 'arr_dep';
			}
			elseif(count($aKeysArrival) > 0)
			{
				//Nur Anreise vorhanden
				$sTransferMode = 'arrival';
			}
			elseif(count($aKeysDeparture) > 0)
			{
				//Nur Abreise vorhanden
				$sTransferMode = 'departure';
			}
		}
		
		return $sTransferMode;
	}

	public function delete() {

		// Generierte Dokumente ebenso löschen (nicht über JoinedObject, da JoinTable vorhanden)
		$aDocuments = (array)$this->documents;
		foreach($aDocuments as $iDocumentId) {
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
			$oDocument->delete();
		}

		$mDelete = parent::delete();

		return $mDelete;
	}

	/**
	 * Berechnet die Gesamtzahl der Wochen
	 *
	 * @return int
	 */
	public function getCourseWeeks() {
		$aCourses = $this->getCourses();

		$iWeeks = 0;
		foreach($aCourses as $oCourse) {
			$iWeeks = $iWeeks + $oCourse->weeks;
		}

		return $iWeeks;
	}

}
