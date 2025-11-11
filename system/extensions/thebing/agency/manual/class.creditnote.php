<?php

class Ext_Thebing_Agency_Manual_Creditnote extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_agencies_manual_creditnotes';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kamc';

	/**
	 * @var bool
	 */
    protected $_bDelete = false;

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'agency' => array(
			'class' => 'Ext_Thebing_Agency',
			'key' => 'agency_id'
		),
		'template'=>array(
			'class'	=> 'Ext_Thebing_Pdf_Template',
			'key' => 'template_id'
		),
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'documents' => array(
			'table' => 'ts_manual_creditnotes_to_documents',
			'foreign_key_field'	=> 'document_id',
			'primary_key_field'	=> 'manual_creditnote_id',
			'class'	=> 'Ext_Thebing_Inquiry_Document',
			'on_delete' => 'no_action'
		),
	);

	/**
	 * @param string $sField
	 * @return mixed|string
	 * @throws ErrorException
	 */
	public function __get($sField) {

		Ext_Gui2_Index_Registry::set($this);
		
		$mValue = '';
		
		if(
			$sField == 'txt_intro' ||
			$sField == 'txt_outro' ||
			$sField == 'txt_subject' ||
			$sField == 'txt_address' ||
			$sField == 'date' ||
			$sField == 'txt_signature' ||
			$sField == 'signature' 
		) {

			$oLastVersion = $this->getLastVersion();
			if(is_object($oLastVersion)) {
				$mValue = $oLastVersion->$sField;
			}

		} elseif($sField == 'document_number') {
			$oDocument = $this->getDocument();
			$mValue	= $oDocument->document_number;
		}  else {
			$mValue = parent::__get($sField);
		}

		return $mValue;
	}
	
	/**
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function getLastVersion() {
	
		$oDocument = $this->getDocument();
		$oLastVersion = $oDocument->getLastVersion();
		
		return $oLastVersion;
	}

	/**
	 * @return bool
	 */
	public function isStorno() {

		$sSql = "
            SELECT
				*
			FROM
				`kolumbus_agencies_manual_creditnotes`
			WHERE
				`storno_id` = :creditnote_id AND
				`active` = 1
		";

		$aResult = DB::getPreparedQueryData($sSql, array(
			'creditnote_id' => (int)$this->id
		));

		if(empty($aResult)) {
			return false;
		}
		
		return true;
	}

	/**
	 * @return array
	 */
	public function getPayments() {
		$sSql = "
            SELECT
				`kamcp`.*
			FROM
				`kolumbus_agencies_manual_creditnotes_payments` `kamcp`
			WHERE
				`kamcp`.`creditnote_id` = :creditnote_id AND
				`kamcp`.`active` = 1
		";

		$aResult = DB::getPreparedQueryData($sSql, array(
			'creditnote_id' => (int)$this->id
		));

		return $aResult;
	}

	/**
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	public function delete() {

		// Zahlungen löschen
		$aResult = $this->getPayments();
		foreach((array)$aResult as $aData) {
			$oPayment = Ext_Thebing_Agency_Manual_Creditnote_Payment::getInstance($aData['id']);
			$oPayment->delete();
		}

		// Dokument ebenso löschen
		$oDocument = $this->getDocument();
		$oDocument->delete();

        $this->_bDelete = true;
        
		return parent::delete();
	}

	/**
	 * @param string $sComment
	 * @param int $iReasonId
	 */
	public function storno($sComment, $iReasonId = 0, $note = null) {
		
		if(
			$this->storno_id <= 0 &&
			$this->school_id > 0
		) {

			$oNewCn = new self();
			$oNewCn->agency_id = (int)$this->agency_id;
			$oNewCn->type = $this->type;
			$oNewCn->currency_id = (int)$this->currency_id;
			$oNewCn->amount	= (float)$this->amount * -1;
			$oNewCn->comment = (string)$sComment;
			$oNewCn->note = $note;
			$oNewCn->reason_id = (int)$iReasonId;
			$oNewCn->school_id = (int) $this->school_id;
			$oNewCn->inbox_id = (int) $this->inbox_id;
			$oNewCn->numberrange_id = (int) $this->numberrange_id;
			
			$oNewCn->save();
			
			$this->storno_id = $oNewCn->id;
			$this->save();

			$oDocument = $this->getDocument();
			$oStornoDocument = $oNewCn->getDocument();

			if(
				$oDocument &&
				$oStornoDocument && 
				$oDocument->id > 0 && 
				$oStornoDocument->id > 0
			) {
				$oStornoDocument->parent_documents_creditnote = array($oDocument->id);
				$oStornoDocument->save();
			}

		}

	}

	/**
	 * Creditnote-Verrechnung speichern (relevant für Agenturzahlungen)
	 */
	public function saveCreditnotePayment($fAmount, $fCurrencyFactor, Ext_Thebing_Inquiry_Payment $oInquiryPayment, Ext_Thebing_Agency_Payment $oAgencyPayment = null) {

		$oInquiry = Ext_TS_Inquiry::getInstance($oInquiryPayment->inquiry_id);

		$oPayment = new Ext_Thebing_Agency_Manual_Creditnote_Payment();
		$oPayment->amount = (float)$fAmount;
		$oPayment->amount_school = (float)($fAmount * $fCurrencyFactor);
		//die Währung aus der Buchung übernehmen, die Währung aus der Agengturzahlung
		//muss nicht der Währung aus dem Creditnote entsprechen
		$oPayment->currency_id = (int)$oInquiry->getCurrency();
		// Schulwährung kann man ruhig übernehmen, die muss immer übereinstimmen
		$oPayment->currency_school_id = $oAgencyPayment ? $oAgencyPayment->amount_school_currency : $oInquiryPayment->currency_school;
		$oPayment->agency_payment_id = $oAgencyPayment ? $oAgencyPayment->id : 0;
		$oPayment->creditnote_id = $this->id;
		$oPayment->payment_id = $oInquiryPayment->id;
		//$oPayment->payment_item_id = $iPaymentItemId; // War mal für die Verknüpfung nötig, als es noch keine payment_id gab (#6359)

		$mValidate = $oPayment->validate();
		if($mValidate !== true) {
			return $mValidate;
		}

		$oPayment->save();

		// Dokument im Index aktualisieren, damit Payment-Spalten aktualisiert werden
		$oDocument = $this->getDocument();
		$oDocument->updateIndexStack(false, true);

		return $oPayment;

	}

	/**
	 * @return int
	 */
	public function getAllocatedAccountingAmount() {

		$fAmount = 0;
		$aResult = $this->getPayments();

		foreach((array)$aResult as $aData){
			$fAmount += $aData['amount'];
		}

		return $fAmount;
	}

	/**
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			COALESCE(
				( SELECT
						SUM(`kamcp2`.`amount`)
					FROM
						`kolumbus_agencies_manual_creditnotes_payments` `kamcp2`
					WHERE
						`kamcp2`.`creditnote_id` = `kamc`.`id` AND
						`kamcp2`.`active` = 1
				)
			, 0 ) `amount_used`,
			IF(
				`kidv`.`id` IS NOT NULL,
				`kidv`.`comment`,
				`kamc`.`comment`
			) `comment`,
			`kidv`.`txt_intro`,
			`kidv`.`txt_outro`,
			`kidv`.`txt_subject`,
			`kidv`.`txt_address`,
			`kidv`.`date`,
			`kidv`.`txt_signature`,
			`kidv`.`signature`,
			`kid`.`document_number` `document_number`,
			GROUP_CONCAT(DISTINCT `kid_invoice`.`document_number` SEPARATOR ', ')  `invoice_numbers`,
			`ts_d_r`.`created` `release_time`,
			`ts_d_r`.`creator_id` `release_time_by`,
			`kidv`.`path` `path`,
			`ka`.`ext_1` `agency`,
			`ka`.`ext_2` `agency_short`
		";
		
		$aSqlParts['from'] .= " INNER JOIN
			`ts_companies` as `ka` ON
				`ka`.`id` = `kamc`.`agency_id` AND
				`ka`.`active` = 1 LEFT JOIN
			`ts_manual_creditnotes_to_documents` `ts_m_c_to_d` ON
				`ts_m_c_to_d`.`manual_creditnote_id` = `kamc`.`id` LEFT JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`id` = `ts_m_c_to_d`.`document_id` AND
				`kid`.`active` = 1 LEFT JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kidv`.`id` = `kid`.`latest_version` AND
				`kidv`.`active` = 1 LEFT JOIN
			`ts_documents_release` `ts_d_r` ON
				`ts_d_r`.`document_id` = `kid`.`id` LEFT JOIN
			`kolumbus_agencies_manual_creditnotes_payments` `kamcp` ON
				`kamcp`.`creditnote_id` = `kamc`.`id` LEFT JOIN
			`kolumbus_inquiries_payments_items` `kipi` ON
				`kipi`.`payment_id` = `kamcp`.`payment_id` LEFT JOIN
			`kolumbus_inquiries_documents_versions_items` `kidvi` ON
				`kidvi`.`id` = `kipi`.`item_id` LEFT JOIN
			`kolumbus_inquiries_documents_versions` `kidv_invoice` ON
				`kidv_invoice`.`id` = `kidvi`.`version_id` LEFT JOIN
			`kolumbus_inquiries_documents` `kid_invoice` ON
				`kid_invoice`.`id` = `kidv_invoice`.`document_id`
		";

	}
	
	/**
	 *
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function newVersion() {
		
		 $oDocument = $this->getDocument();
		 
		 $oVersion = new Ext_Thebing_Accounting_Manual_Version();
		 $oVersion->document_id = (int)$oDocument->id;
		 
		 return $oVersion;
	}
	
	/**
	 *
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getDocument() {

		$aDocuments = (array)$this->getJoinTableObjects('documents');
		
		if(!empty($aDocuments)) {
			$oDocument = reset($aDocuments);
		} else {
			$oDocument = new Ext_Thebing_Inquiry_Document();
			$oDocument->type = 'manual_creditnote';
		}
		
		//$oDocument->setAllocationObject($this);
		
		return $oDocument;
	}
	
	/**
	 * @param bool $bLog
	 * @return Ext_Thebing_Agency_Manual_Creditnote 
	 */
	public function save($bLog = true, $bSaveDocument = true) {

		if(
			$bSaveDocument &&
			!$this->_bDelete
		) {
			
			// Immer ein Dokument abspeichern
			$oDocument = $this->getDocument();				
			$oDocument->save(false, (int)$this->numberrange_id);
			
			// Numberrange speichern
			if($this->numberrange_id == 0) {
				$this->numberrange_id = $oDocument->numberrange_id;
			}
			
			// Zwischentabelle befüllen
			$this->documents = array($oDocument->id);

		}

        $mReturn = parent::save($bLog);
        
		return $mReturn;
	}
	
	/**
	 * Typ-Key für Nummernkreis
	 * 
	 * @return string 
	 */
	public function getTypeForNumberrange($sDocumentType, $mTemplateType=null) {
		return 'manual_creditnote';
	}
	
	/**
	 *
	 * @return Ext_Thebing_Agency 
	 */
	public function getAgency() {
		return $this->getJoinedObject('agency');
	}
    
    /**
     * @return \TsAccounting\Entity\Company
     */
    public function getCompany() {

        $oCompany = null;
        if($this->company_id > 0){
            $oCompany = \TsAccounting\Entity\Company::getInstance($this->company_id);
        }

        return $oCompany;
    }
    
    /**
     * @return Ext_Thebing_Client_Inbox
     */
	public function getInbox() {
		
		$oInbox = null;
		if($this->inbox_id > 0){
            $oInbox = Ext_Thebing_Client_Inbox::getInstance($this->inbox_id);
        }
				
		return $oInbox;
	}	
	
	/**
	 * 
	 * @return Ext_Thebing_Agency_Manual_Creditnote
	 */
	public function getStorno() {
		
		$oStorno = null;
		if($this->storno_id > 0) {
			$oStorno = Ext_Thebing_Agency_Manual_Creditnote::getInstance($this->storno_id);
		}
	
		return $oStorno;
	}

	/**
	 * Provisionsbetrag
	 *
	 * @return float
	 */
	public function getCommissionAmount() {
		return (float)$this->amount;
	}

	/**
	 * Gibt die Schule zurück
	 *
	 * @return Ext_Thebing_School
	 * @throws Exception
	 */
	public function getSchool() {
		return Ext_Thebing_School::getInstance($this->school_id);
	}

}
