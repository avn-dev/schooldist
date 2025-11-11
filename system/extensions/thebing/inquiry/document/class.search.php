<?php

class Ext_Thebing_Inquiry_Document_Search {

	protected Ext_TS_Inquiry_Abstract|int $inquiry;
	protected $_aType = array();
	protected $_aTemplateTypes = array();
	protected $_aGuiLists = array();
	protected $_sAdditionalWherePart = '';
	protected $_bSearchAlsoInactiveDocuments = false;
	protected $_iIsCredit = null;
	protected $_sNumber = '';
	protected $_sObjectType;
	protected $_aTemplateInboxes = array();
	protected $bPartialInvoice = false;
	protected $bJourneyDocuments = false;
	protected $companyId;
	// null = alle, false = nur Normale, true = nur Entwürfe.
	// Default ist false, da Entwürfe ein neues Feature sind.
	protected ?bool $draft = false;

	public $bEmptyGuiListSearch = false;

	/**
	 * @TODO Kann auch jedes andere Objekt sein, läuft aber aktuell auf int
	 * @TODO Alle Stellen mit int ersetzen
	 */
	public function  __construct(Ext_TS_Inquiry_Abstract|int $inquiry) {
		$this->inquiry = $inquiry;
	}

	public function setDraft(?bool $draft): void {
		$this->draft = $draft;
	}

	public function setCompanyId($companyId) {
		$this->companyId = $companyId;
	}
	
	public function setAdditionalWhere($sPart){
		$this->_sAdditionalWherePart = $sPart;
	}

	public function setType($mType){
		$aTypes = self::getTypeData($mType);
		$this->_aType = $aTypes;
	}

	public function setTemplateTypes(array $aTypes) {
		$this->_aTemplateTypes = $aTypes;
	}

	public function setTemplateInboxes(array $aInboxes) {
		$this->_aTemplateInboxes = $aInboxes;
	}

	/**
	 * Erwartet Array mit Arrays:
	 *
	 * array(
	 * 		array('ts_inquiry', 'inquiry')
	 * )
	 *
	 * @param array $aLists
	 */
	public function setGuiLists(array $aLists) {
		$this->_aGuiLists = $aLists;
	}

	public function searchAlsoInactive(){
		$this->_bSearchAlsoInactiveDocuments = true;
	}
	
	public function setCredit($iIsCredit){
		$this->_iIsCredit = (int)$iIsCredit;
	}

	public function setPartialInvoice(bool $bPartialInvoice) {
		$this->bPartialInvoice = $bPartialInvoice;
	}

	// Das Suchen für spezielle Document Nummmern ist insbesondere erforderlich bei GruppenRechnungen
	public function setDocumentNumber($sNumber){
		$this->_sNumber = $sNumber;
	}

	public function addJourneyDocuments() {
		$this->bJourneyDocuments = true;
	}

	/**
	 * @param bool $bReturnObjects
	 * @param bool $bReturnAllData
	 * @return int|int[]|Ext_Thebing_Inquiry_Document|Ext_Thebing_Inquiry_Document[]
	 * @throws Exception
	 * @todo Super inperformant, muss optimiert werden.
	 */
	public function searchDocument($bReturnObjects = true, $bReturnAllData = true) {

		$sJoinAddon = '';
//		if($this->_sObjectType == 'Ext_TS_Enquiry') {
//			$sJoinAddon .= ' LEFT JOIN
//				`ts_enquiries_to_documents` `ts_en_to_d` ON
//					`ts_en_to_d`.`document_id` = `kid`.`id`
//			';
//
//			$sWhere = '
//				`ts_en_to_d`.`enquiry_id` = :inquiry_id
//			';
//		} else {
//			$sWhere = '
//				`kid`.`inquiry_id` = :inquiry_id
//			';
//		}

		if (
			$this->_sObjectType === Ext_TS_Inquiry_Journey::class ||
			$this->_sObjectType === \TsCompany\Entity\JobOpportunity\StudentAllocation::class ||
			$this->_sObjectType === Ext_Thebing_Tuition_Course::class ||
			$this->_sObjectType === Ext_Thebing_Teacher::class
		) {
			$sWhere = " `kid`.`entity` = :object_type AND `kid`.`entity_id` = :inquiry_id ";
		} else {
			$sWhere = " ( ";
			$sWhere .= " ( `kid`.`entity` = '".Ext_TS_Inquiry::class."' AND `kid`.`entity_id` = :inquiry_id ) ";

			if (
				$this->bJourneyDocuments && (
					// Optimierung für bspw. NYLC, da der OR-Part langsam ist und das aktuell nur bei Anfragen Relevanz hat
					is_int($this->inquiry) ||
					$this->inquiry->type & \Ext_TS_Inquiry::TYPE_ENQUIRY
				)
			) {
				$sJoinAddon .= " LEFT JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = :inquiry_id AND
						`ts_ij`.`active` = 1
				";
				$sWhere .= " OR ( `kid`.`entity` = '".Ext_TS_Inquiry_Journey::class."' AND `kid`.`entity_id` = `ts_ij`.`id` ) ";
			}

			$sWhere .= " ) ";
		}

		if(
			!empty($this->_aGuiLists) ||
			$this->bEmptyGuiListSearch
		) {
			$sJoinAddon .= " LEFT JOIN
				`ts_documents_to_gui2` `ts_d_t_g` ON
					`ts_d_t_g`.`document_id` = `kid`.`id`
			";
		}

		$aSql = array();
		$aSql['inquiry_id'] = $this->inquiry instanceof Ext_TS_Inquiry_Abstract ? $this->inquiry->id : (int)$this->inquiry;
		$aSql['object_type'] = $this->_sObjectType;

		$sSelect = "";
		if(!$bReturnObjects) {
			$sSelect = ",
				UNIX_TIMESTAMP(`kid`.`created`) `created`,
				UNIX_TIMESTAMP(`kid`.`changed`) `changed`,
				MAX(kidv.id) `latest_version_id`
			";
		}

		$sSql = "
			SELECT
				`kid`.*,
				CASE
					WHEN `kid`.`entity` = 'Ext_TS_Inquiry_Journey' THEN 3
					WHEN INSTR(`kid`.`type`, 'proforma') THEN 2
					ELSE 1
				END `sort_type`
				{$sSelect}
			FROM
				`kolumbus_inquiries_documents` `kid` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kid`.`id` = `kidv`.`document_id` LEFT JOIN
				`kolumbus_pdf_templates` `kpt` ON
					`kpt`.`id` = `kidv`.`template_id` LEFT JOIN
				`kolumbus_pdf_templates_inboxes` `kpti` ON
					`kpti`.`template_id` = `kpt`.`id`
				".$sJoinAddon."
			WHERE
				".$sWhere."
		";

		if(!$this->_bSearchAlsoInactiveDocuments){
			$sSql .= " AND `kid`.`active` = 1 ";
			$sSql .= " AND (`kidv`.`active` = 1 OR `kidv`.`id` IS NULL)";
		}
		
		if(!is_null($this->_iIsCredit)){
			$sSql .= " AND `kid`.`is_credit` = " . $this->_iIsCredit;
		}

		if($this->bPartialInvoice) {
			$sSql .= " AND `kid`.`partial_invoice` = 1";
		}
		
		if(!empty($this->companyId)) {
			$aSql['company_id'] = (int)$this->companyId;
			$sSql .= " AND `kidv`.`company_id` = :company_id";
		}
		
		if(!empty($this->_sNumber)){
			$aSql['document_number'] = $this->_sNumber;
			$sSql .= " AND `kid`.`document_number` = :document_number";
		}

		if ($this->draft !== null){
			$aSql['draft'] = (int)$this->draft;
			$sSql .= " AND `kid`.`draft` = :draft";
		}

		$sSql .= $this->_sAdditionalWherePart;

		// Suche nach Typ des Templates ermöglichen
		if(!empty($this->_aTemplateTypes)) {
			$aSql['template_types'] = $this->_aTemplateTypes;
			$sSql .= " AND `kpt`.`type` IN (:template_types) ";
		}

		// Suche nach Template-Inboxen ermöglichen
		if(!empty($this->_aTemplateInboxes)) {
			$aSql['template_types'] = $this->_aTemplateInboxes;
			$sSql .= " AND `kpti`.`inbox_id` IN (:template_types) ";
		}

		// Suche nach Dokumenten nach GUI-Zuweisungen ermöglichen
		if(
			!empty($this->_aGuiLists) ||
			$this->bEmptyGuiListSearch
		) {

			$aBuilder = array();
			foreach($this->_aGuiLists as $aGui) {
				$i = count($aBuilder);

				$aSql['gui_name_'.$i] = $aGui[0];
				$aSql['gui_set_'.$i] = $aGui[1];

				$aBuilder[] = " (
						`ts_d_t_g`.`name` = :gui_name_$i AND
						`ts_d_t_g`.`set` = :gui_set_$i
				) ";
			}

			// Auch erlauben, Dokumente ohne Zuweisung zu einer Liste zu suchen
			// Das ist beispielsweise für alte Dokumente nötig, die gar keine Zuweisung haben
			if($this->bEmptyGuiListSearch) {
				$aBuilder[] = " (
						`ts_d_t_g`.`name` IS NULL AND
						`ts_d_t_g`.`set` IS NULL
				) ";
			}

			$sSql .= "
				AND (
					".join(" OR ", $aBuilder)."
				)
			";
		}

		// Klappt scheinbar wegen typ casting
		if($this->_aType == 'loa'){
			// Hier muss auf die Templates gejoint werden da der Typ nur so bestimmt werden kann der additional_documents
			$sSql .= " AND `kpt`.`type` = 'document_loa' ";
		} else if(
			!empty($this->_aType) &&
             !in_array('all', $this->_aType)
		) {

			$sSql .= " AND `kid`.`type` IN (:types) ";
			$aSql['types'] = $this->_aType;

//			$sSql .= "  AND ( ";
//
//			#$aSql = array('inquiry_id' => $this->_iInquiry);
//
//			// Für denjenigen, der das umsetzte, musste IN eine unbekannte Funktion sein
//			foreach((array)$this->_aType as $iKey => $sType){
//				$sSql .= "	`kid`.`type`= :type_".$iKey." ";
//				$sSql .= "	OR ";
//				$aSql['type_'.$iKey] = $sType;
//			}
//
//			$sSql = rtrim($sSql, 'OR ');
//			$sSql .= " ) ";

		}

		// Proformas nach unten sortieren, damit (durch Import) später erstellte Proformas nicht $bLastDocument = true sind
		$sSql .= "
			GROUP BY
				`kid`.`id`
			ORDER BY
				`sort_type` ASC,
				`created` DESC,
				`id` DESC
		";

		if(!$bReturnAllData) {
			$sSql .= " LIMIT 1";
		}

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bReturnObjects) {
			$aResult = array_map(function(array $aData) {
				unset($aData['sort_type']);
				return Ext_Thebing_Inquiry_Document::getObjectFromArray($aData);
			}, $aResult);
		}

		if(!$bReturnAllData) {
			if(!$bReturnObjects) {
				// Komisches, altes Verhalten
				return (int)$aResult[0]['id'];
			}

			return $aResult[0] ?? null;
		}

		return $aResult;

	}

//	/**
//	 * Wurde ersetzt
//	 * @see \Ext_Thebing_Inquiry_Document::getDocumentsOfSameNumber()
//	 * @deprecated
//	 *
//	 * @param $iInquiryId
//	 * @param $sDocumentNumber
//	 * @return mixed
//	 */
//	public static function searchByNumber($iInquiryId, $sDocumentNumber){
//		$sSql = " SELECT
//						`id`
//					FROM
//						`kolumbus_inquiries_documents`
//					WHERE
//						`inquiry_id` = :inquiry_id AND
//						`document_number` = :document_number AND
//						`active` = 1
//					ORDER BY
//						`created` DESC  ";
//		$aSql = array('inquiry_id'=>(int)$iInquiryId, 'document_number'=>$sDocumentNumber);
//		$aResult = DB::getPreparedQueryData($sSql,$aSql);
//		return $aResult[0]['id'];
//	}

	/**
	 * @see Ext_Thebing_Inquiry_Document_Type_Search::getSectionTypes()
	 * @param array|string $mType
	 * @return array
	 */
	public static function getTypeData($mType){ 

		$mReturn = array();

		if(!is_object($mType)){
            
            if(!is_array($mType)) {
                $mType = array($mType);
            }

            $oTypeSearch = new Ext_Thebing_Inquiry_Document_Type_Search();

            foreach((array)$mType as $sType) {
                $oTypeSearch->addSection($sType);
            }
            
            $mReturn = $oTypeSearch->getTypes();
            
        } else if($mType instanceof Ext_Thebing_Inquiry_Document_Type_Search){
            $mReturn = $mType->getTypes();
        }
        
		return $mReturn;

	}

	/**
	 * Liefert getTypeData() konkateniert
	 * @param mixed $mType
	 * @return string
	 */
	public static function getTypeDataAsString($mType) {

		$aTypes = self::getTypeData($mType);

		$aTypes = array_map(function($sType) {
			return "'".$sType."'";
		}, $aTypes);

		return join(', ', $aTypes);
	}

	public static function search($iInquiryId, $mType = 'brutto', $bReturnAllData = false, $bReturnObjects = false) {

		$oSelf = new self($iInquiryId);
		$oSelf->setType($mType);
		return $oSelf->searchDocument($bReturnObjects, $bReturnAllData);

	}

	/**
	 * @TODO Entfernen
	 */
	public function setObjectType($sClassName)
	{
		$this->_sObjectType = $sClassName;
	}
		
}
