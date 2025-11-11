<?php

/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Accounting_Manual_Version extends Ext_Thebing_Inquiry_Document_Version {

	// Tabellenname
	protected $_sTableAlias = 'kmv';
	
	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue) {

		switch($sName) {
			case 'manual_creditnote_id':
				break;
			default:
				parent::__set($sName, $mValue);
				break;
		}

	}

	/**
	 * @return string
	 */
	public function createPdf() {

		$oSchool = $this->getManualCreditnote()->getSchool();

		if($oSchool->id === 0) {
			// Hier darf nicht einfach getFirstSchool aufgerufen werden, da man als Select (Pflichtfeld) eine Schule angeben muss!
			// Man muss diese Schule die selektiert wurde auch verwenden!
			throw new LogicException('School not found!');
		}

		$oCN = $this->getManualCreditnote();
		$oTemplate = $this->getTemplate();
		$oAgency = $oCN->getJoinedObject('agency');
		
		$oPDF = new Ext_Thebing_Pdf_Basic($oTemplate->id, $oSchool->id, true);
		$oPDF->sDocumentType = 'manual_creditnote';
		$oPDF->setLanguage($oAgency->getLanguage());

		// Vorbereiten der Daten für PDF
		$this->createDocument($oPDF);
		
		// Dateinamen + Pfad bauen
		$aTemp = $this->buildFileNameAndPath();
		$sPath = $aTemp['path'];
		$sFileName = $aTemp['filename'];

		$sFilePath = $oPDF->createPdf($sPath, $sFileName);

		$this->log(Ext_Thebing_Log::PDF_CREATED);

		return $sFilePath;
	}

	/**
	 * @param Ext_Thebing_Pdf_Basic $oPdf
	 */
	public function createDocument(&$oPdf) {

		$aAdditional = array();
		$aAdditional['creditnote_id'] = $this->getManualCreditnote()->getId();
		$aAdditional['version_id'] = $this->id;

		$oPdf->createDummyDocument($this->aData, array(), array(), $aAdditional);

	}

	/**
	 * @return array
	 */
	public function buildFileNameAndPath() {

		#$oManualCreditnote = $this->getManualCreditnote();
		
		$oDocument	= $this->getDocument();
		
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($this->template_id);
		$oSchool	= Ext_Thebing_Client::getFirstSchool();
		
		// Name der PDF Vorlage
		$sNumber = $oTemplate->name;

		// CreditnoteId
		$sNumber .= '_'.$oDocument->document_number;

		$sFileName = \Util::getCleanFileName($sNumber);

		// version anhängen
		$sFileName .= '_v'.(int)$this->getVersion();
		
		$sPath = $oSchool->getSchoolFileDir()."/manual_creditnotes/";

		$aBack = array('path' => $sPath, 'filename' => $sFileName);

		return $aBack;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param bool $bLog
	 * @param bool $bCreateFile
	 * @return $this
	 */
	public function save($bLog=true, $bCreateFile=true) {

		$this->date	= $this->getDate();
		
		if($bCreateFile) {
			
			$oDocument = $this->getDocument();
			$oLastVersion = $oDocument->getLastVersion();

			$iVersion = 0;
			if($oLastVersion) {
				$iVersion = (int)$oLastVersion->version;
			}
			$iVersion++;

		}

		parent::save($bLog);

		if($bCreateFile) {

			$this->version = $iVersion;
			
			$sFilePath = $this->createPdf();

			$sFilePath = str_replace(\Util::getDocumentRoot(), '', $sFilePath);
			$sFilePath = str_replace('/storage', '', $sFilePath);
			$sFilePath = str_replace('storage', '', $sFilePath);

			$this->path = $sFilePath;

			$this->save(false, false);

		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName() {
		
		$oFormat = new Ext_Thebing_Gui2_Format_Timestamp();
		$oAmount = new Ext_Thebing_Gui2_Format_Amount();
		$oCN = $this->getManualCreditnote();
		
		$aTemp = array('currency_id' => $oCN->currency_id);
		
		$sName = $oCN->document_number;
		$sName .= ' ' . $oFormat->format($this->created);
		$sName .= ' ' . $oAmount->format($oCN->amount, $aTemp, $aTemp);
		$sName .= ' ' . $this->comment;
		
		return $sName;
	}
	
	/**
	 *
	 * @return Ext_Thebing_Agency_Manual_Creditnote 
	 */
	public function getManualCreditnote() {

		$sSql = "
			SELECT
				`manual_creditnote_id`
			FROM
				`ts_manual_creditnotes_to_documents`
			WHERE
				`document_id` = :document_id
		";
		
		$iManualCreditNoteId = (int)DB::getQueryOne($sSql, array(
			'document_id' => (int)$this->document_id
		));
		
		$oManualCreditNote = Ext_Thebing_Agency_Manual_Creditnote::getInstance($iManualCreditNoteId);
		
		return $oManualCreditNote;
	}

	/**
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		$aSqlParts['from'] .= ' INNER JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`id` = `kmv`.`document_id` AND
				`kid`.`active` = 1 INNER JOIN
			`ts_manual_creditnotes_to_documents` `ts_m_c_to_d` ON
				`ts_m_c_to_d`.`document_id` = `kid`.`id`
		';
	}
	
	/**
	 * Datum der Version, falls Datum leer wird das heutige Datum zurück gegeben
	 * 
	 * @return string 
	 */
	public function getDate() {

		$sDate = $this->date;
		
		if(empty($sDate) || $sDate == '0000-00-00') {
			$oDate	= new WDDate();
			$sDate	= $oDate->get(WDDate::DB_DATE);
		}
		
		return $sDate;
	}

}