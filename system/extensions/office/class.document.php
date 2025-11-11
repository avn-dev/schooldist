<?php

include_once(\Util::getDocumentRoot().'system/extensions/office/office.inc.php');

/**
 * API to `office_documents`.
 * 
 * This class supports the fluid interface.
 */

final class Ext_Office_Document
{
	/**
	 * The document data
	 */
	private $_aDocument	= array(
		'id' => null,
		'currency' => null,
		'client_id' => 1,
		'customer_id' => null,
		'contact_person_id' => null,
		'editor_id' => null,
		'address' => null,
		'telefon' => null,
		'telefax' => null,
		'type' => null,
		'discount' => 0.00,
		'cash_discount' => 0.00,
		'number' => null,
		'date' => null,
		'booking_date' => null, 
		'contract_last' => null,
		'contract_start' => null,
		'contract_interval' => null,
		'contract_scale' => null,
		'price' => null,
		'price_net' => null,
		'price_cash_discount' => null,
		'price_cash_discount_net' => null,
		'fee' => null,
		'payment' => null,
		'state' => null,
		'subject' => null,
		'text' => null,
		'endtext' => null,
		'dunning_level' => null,
		'pdf_filename' => null,
		'hash' => null,
		'form_id' => null,
		'product_area_id' => null,
		'cash_discount_granted' => 0,
		'custom' => '',
		'purchase_order_number' => null
	);

	/**
	 * The document items
	 * 
	 * @access private
	 */
	private $_aItems	= array();

	/**
	 * Writing protection for document type
	 */
	private $_bTypeLock	= true;

	/**
	 * The pdf file
	 */
	private $_aFileLink	= array();

	/**
	 * The constructor
	 */
	public function __construct($iID = null)
	{
		if(is_numeric($iID))
		{
			// Set ID
			$this->_aDocument['id'] = (int)$iID;

			// Load the document data from the data base
			$this->_loadDocument();

			// Load the document items from the data base
			$this->_loadItems();

			// Check the existation of pdf file
			if(
				is_dir(\Util::getDocumentRoot()."storage/office/pdf/")
					&&
				!is_null($this->_aDocument['number'])
					&&
				!is_null($this->_aDocument['type'])
			)
			{
				$this->_sFileLink = \Util::getDocumentRoot()."storage/office/pdf/";
				$this->_sFileLink .= $this->pdf_filename . '.pdf';
			}
		}
		else
		{
			$this->_bTypeLock = false;
		}
	}


	/**
	 * Returns the document properties
	 *
	 * @param string $sName
	 * @return mixed
	 */
	public function __get($sName)
	{
		if(array_key_exists($sName, $this->_aDocument))
		{
			return $this->_aDocument[$sName];
		}
		else if($sName == 'aItems')
		{
			return $this->_aItems;
		}
		else
		{
			throw new Exception('Unknown field: '.$sName);
		}
	}


	/**
	 * Sets the document properties
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue)
	{
		global $aTypeNames, $aDocumentStates;

		if(array_key_exists($sName, $this->_aDocument) && $sName != 'id')
		{
			if(
				$sName == 'type' && 
				(
					intval($this->_aDocument['id']) > 0 || 
					$this->_bTypeLock
				)
			)
			{
				throw new Exception('The type of document cannot be changed!');
			}
			else
			{
				
				$aControlType	= array_keys($aTypeNames);
				$aControlState	= array_keys($aDocumentStates);

				// Check the document type
				if($sName == 'type' && !in_array($mValue, $aControlType))
				{
					throw new Exception('Unvailable document type: '.$mValue);
				}
				// Check the document state
				if($sName == 'state' && !in_array($mValue, $aControlState))
				{
					throw new Exception('Unvailable document state: '.$mValue);
				}
				if($sName == 'state' && intval($this->_aDocument['id']) <= 0)
				{
					throw new Exception('The document must be saved befor setting state');
				}
				else if(
					$sName == 'state'
						&&
					in_array($mValue, $aControlState)
						&&
					$mValue != $this->_aDocument[$sName]
						&&
					intval($this->_aDocument['id']) > 0
				)
				{
					$this->_logStateChanging($mValue);
				}

				// Set available property
				$this->_aDocument[$sName] = $mValue;
			}
		}
		else if($sName == '_sFileLink')
		{
			$this->_sFileLink = $mValue;
		}
		else if($sName == 'id')
		{
			throw new Exception('ID cannot be changed!');
		}
		else
		{
			throw new Exception('Unknown field: '.$sName);
		}
	}


	/**
	 * Adds items to a document
	 */
	public function addItems($aItem = array())
	{
		$this->_aItems[] = $aItem;

		// To allow the fluid interface
		return $this;
	}

	/**
	 * Kopiert Texte und Positionen von dem Dokument mit CopyId in dieses rein.
	 * @param type $iCopyId
	 * @return boolean
	 */
	public function copyContent($iCopyId, $bTruncate=false) {#
		
		$oCopy = new self($iCopyId);
		
		$aItems = $oCopy->aItems;

		// Aktuelle Items löschen
		if($bTruncate) {
			foreach($this->_aItems as $aItem) {
				$this->removeItem($aItem['id']);
			}
			$this->_aItems = array();
		}

		foreach($aItems as $aItem) {
			$aItem['id'] = 0;
			$this->_aItems[] = $aItem;
		}

		$this->text = $oCopy->text;
		$this->endtext = $oCopy->endtext;

		$this->save();

		return true;

	}

	/**
	 * Copy the document and set new document type
	 * 
	 * @param string $sNewType
	 */
	public function copyDocument($sNewType, $bReverseAmounts=false) {

		// Reset the IDs of items
		foreach((array)$this->_aItems as $iKey => $aValue) {
			$this->_aItems[$iKey]['id'] = 0;
			if($bReverseAmounts === true) {
				$this->_aItems[$iKey]['price'] *= -1;
			}
		}

		// Get the document ID
		$iDocumentID = $this->_aDocument['id'];

		// Reset the ID, state and number
		$this->_aDocument['id'] = null;
		$this->_aDocument['state'] = 'draft';
		$this->_aDocument['number'] = null;
		$this->_aDocument['dunning_level'] = null;
		$this->_aDocument['date'] = null;
		$this->_aDocument['booking_date'] = null;

		// Set new document type
		$this->_bTypeLock = false;
		$this->type = $sNewType;
		$this->_bTypeLock = true;

		// Save the copy
		$this->save();

		// Link documents
		$this->_linkDocuments($iDocumentID, $this->_aDocument['id']);

		// Return the ID of the new document
		return $this->_aDocument['id'];
	}


	/**
	 * Creates a pdf file on hard disk
	 * 
	 * @param int $iFormID
	 */
	public function createFile() {
		global $objOfficeDao;

		$aForm = $objOfficeDao->getForm((int)$this->form_id);

		Util::checkDir(Util::getDocumentRoot()."storage/office/pdf");

		// Create the pdf object
		$oPDF = new Ext_Office_PDF($this->form_id, $this->_aDocument['id'], true);

		// save filename in document if not draft
		if($this->state == 'draft') {
			$sPDFFilename = 'draft_'.$this->type.'_'.$this->id;
		} else {
			
			// get filename template
			$aSQL = array(
				'iFormID'	=> (int)$this->form_id
			);
			$sSQL = "
				SELECT
					`pdf_name`
				FROM
					`office_forms`
				WHERE
					`id` = :iFormID
			";
			$sPDFFilenameTemplate = DB::getQueryOne($sSQL, $aSQL);
			if(empty($sPDFFilenameTemplate)) {
				$sPDFFilenameTemplate = '{DocumentType}_{DocumentNumber}';
			}

			// get replaced filename
			$sPDFFilename = $oPDF->replaceAdditionalPlaceholdersInText($sPDFFilenameTemplate);

			// get clean filename
			$sPDFFilename = \Util::getCleanFileName($sPDFFilename);

		}

		$aConfig = Ext_Office_Config::getInstance();

		// Set the file link
		$this->_aFileLink['dir']	= Util::getDocumentRoot()."storage/office/pdf/";
		$this->_aFileLink['name']	= $sPDFFilename.'.pdf';

		// Save the pdf file on hard disk
		$oPDF->savePDFFile($this->_aFileLink['dir'].$this->_aFileLink['name']);

		if(!is_file($this->_aFileLink['dir'].$this->_aFileLink['name'])) {
			// Unset the file link
			$this->_sFileLink = array();
			$sPDFFilename = '';
		}

		$this->pdf_filename = $sPDFFilename;
		$this->save();

		// To allow the fluid interface
		return $this;
	}

	public function getFile() {

		$sPath = $this->getFilePath();
		
		$bExists = is_file($sPath);

		return $bExists;

	}

	public function getFilePath() {

		// Set the file link
		$this->_aFileLink['dir']	= Util::getDocumentRoot() . $this->getWebFileDir();
		$this->_aFileLink['name']	= $this->getPDFFilename();

		$sPath = $this->_aFileLink['dir'].$this->_aFileLink['name'];

		return $sPath;

	}

	/**
	 * Deletes the pdf file from hard disk
	 */
	public function deleteFile()
	{
		// Delete file
		if(!empty($this->_aFileLink))
		{
			unlink($this->_aFileLink['dir'].$this->_aFileLink['name']);
		}

		// File exists?
		if(is_file($this->_aFileLink['dir'].$this->_aFileLink['name']))
		{
			return false;
		}

		// Unset pdf file
		$this->_aFileLink = array();
		return true;
	}


	public function getCheckList()
	{
		$aItems = $this->_aItems;

		foreach((array)$aItems as $iKey => $aItem)
		{
			$sSQL = "
				SELECT *
				FROM
					`office_checklists`
				WHERE
					`active` = 1 AND
					`position_id` = " . (int)$aItem['id'] . "
				ORDER BY `id`
			";
			$aChecks = DB::getQueryData($sSQL);

			$aItems[$iKey]['checks'] = $aChecks;
		}

		return $aItems;
	}

	public function calculateTotalPrices() {
		$this->_calculateTotalPrices();
	}

	/**
	 * Calculates the total document prices
	 */
	private function _calculateTotalPrices() {

		// Get all vats
		$aVats = array();
		$fPriceReports = 0;
		foreach((array)$this->_aItems as $iKey => $aValue) {
			
			$oAccount = \Office\Entity\RevenueAccounts::getInstance($aValue['revenue_account']);
			
			$sKey = (string)$aValue['vat'];
			if(!isset($aVats[$sKey])) {
				$aVats[$sKey] = 0;
			}

			$fPrice = ($aValue['price'] * (1 - $aValue['discount_item'] / 100)) * $aValue['amount'];

			if($oAccount->exclude_from_reports != 1) {
				$fPriceReports = $fPriceReports + $fPrice;
			}

			$aVats[$sKey] += $fPrice;
		}

		// Minus discount
		if($this->_aDocument['discount'] > 0) {
			$fPriceReports = (float)$fPriceReports * (1 - (float)$this->_aDocument['discount'] / 100);
		}

		if($this->_aDocument['type'] == 'credit') {
			$fPriceReports *= -1;
		}

		// Calculate the prices
		$this->_aDocument['price_net'] = $this->_aDocument['price'] = 0;
		foreach((array)$aVats as $fKey => $fValue) {

			// Minus discount
			$fTmp = (float)$fValue * (1 - (float)$this->_aDocument['discount'] / 100);

			// Multiplicate with amount
			$this->_aDocument['price_net'] += (float)$fTmp;
			$this->_aDocument['price'] += $fTmp * (1 + (float)$fKey);

		}
		$this->_aDocument['price_net'] = round($this->_aDocument['price_net'], 2);
		$this->_aDocument['price'] = round($this->_aDocument['price'], 2);

		// Preise wenn Skonto gewährt wird
		$fCashDiscountFactor = (1 - ((float)$this->_aDocument['cash_discount'] / 100));
		$this->_aDocument['price_cash_discount_net'] = round(($this->_aDocument['price_net'] * $fCashDiscountFactor), 2);
		$this->_aDocument['price_cash_discount'] = round(($this->_aDocument['price'] * $fCashDiscountFactor), 2);

		if(bccomp($fCashDiscountFactor, 1) !== 0) {
			$fPriceReports = bcmul($fPriceReports, $fCashDiscountFactor);
			$fPriceReports = round($fPriceReports, 2);
		}

		$this->_aDocument['price_reports'] = round($fPriceReports, 2);

	}

	/**
	 * Links the documents after creation of copy
	 * 
	 * @param int $iFrom
	 * @param int $iTo
	 */
	private function _linkDocuments($iFrom, $iTo)
	{
		$sSQL = "
			INSERT INTO
				`office_documents_links`
			SET
				`created`		= NOW(),
				`from_document`	= :iFrom,
				`to_document`	= :iTo
		";
		$aSQL = array('iFrom' => $iFrom, 'iTo' => $iTo);

		DB::executePreparedQuery($sSQL, $aSQL);
	}


	/**
	 * Loads the document data from the data base
	 */
	private function _loadDocument()
	{
		if(is_numeric($this->_aDocument['id']))
		{
			// Get document by ID
			$sSQL = "
				SELECT
					*,
					UNIX_TIMESTAMP(`date`) AS `date`
				FROM
					`office_documents`
				WHERE
					`id` = :iID
				LIMIT
					1
			";
			$aSQL = array('iID' => $this->_aDocument['id']);
			$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

			// Document not found
			if(empty($aResult))
			{
				throw new Exception('Document not found!');
			}

			// Fill the document with data
			foreach((array)$aResult[0] as $sKey => $mValue)
			{
				if(array_key_exists($sKey, $this->_aDocument))
				{
					$this->_aDocument[$sKey] = $mValue;
				}
			}
		}
		else
		{
			throw new Exception('Wrong data type of document ID!');
		}
	}


	/**
	 * Loads the document items from the data base
	 */
	private function _loadItems()
	{
		// Get all items by document ID
		$sSQL = "
			SELECT
				*
			FROM
				`office_document_items`
			WHERE
				`document_id` = :iID
			ORDER BY
				`position`
		";
		$aSQL = array('iID' => $this->_aDocument['id']);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		// Fill the document with items
		foreach((array)$aResult as $iKey => $aValue)
		{
			$this->_aItems[] = $aValue;
		}

	}


	/**
	 * Loggs the changing of document state
	 * 
	 * @param string sState
	 */
	private function _logStateChanging($sState) {

		// Get available document states
		global $aDocumentStates;
		global $user_data;

		$sSQL = "
			INSERT INTO
				`office_protocol`
			SET
				`customer_id`	= :iCustomerID,
				`contact_id`	= :iContactID,
				`editor_id`		= :iEditorID,
				`document_id`	= :iDocumentID,
				`topic`			= :sTopic,
				`subject`		= :sSubject,
				`date`			= NOW(),
				`state`			= :sState
		";
		$aSQL = array(
			'iCustomerID'	=> $this->_aDocument['customer_id'],
			'iContactID'	=> $this->_aDocument['contact_person_id'],
			'iEditorID'		=> (int)$user_data['id'],
			'iDocumentID'	=> $this->_aDocument['id'],
			'sTopic'		=> $this->_aDocument['type'],
			'sSubject'		=> 'Status geändert auf "' . $aDocumentStates[$sState] . '"',
			'sState'		=> $sState
		);
		DB::executePreparedQuery($sSQL, $aSQL);

		// To allow the fluid interface
		return $this;
	}


	/**
	 * Sets the document state to released
	 * and generates a new document number
	 */
	public function release() {

		// Document exists?
		if(intval($this->_aDocument['id']) > 0) {

			// check if nummernkreis must be repeated each year
			$aSQL = array(
				'sKey' 		=> 'range_' . $this->_aDocument['type'] . '_repeat',
				'iClientID' => (int)$this->_aDocument['client_id']
			);

			$sSQL = "
				SELECT
					`value`
				FROM
					`office_config`
				WHERE
					`key` 		= :sKey AND
					`client_id`	= :iClientID
				LIMIT
					1
			";
			$iRepeat = DB::getQueryOne($sSQL, $aSQL);
			$iRepeat = (int)$iRepeat;

			$sAdditionalSql = "";
			if($iRepeat == 1) {
				$sAdditionalSql = "
						AND
					YEAR(`created`) = YEAR(NOW())
				";
			}
			
			$sSQL = "
				SELECT
					MAX(`number`) AS nr
				FROM
					`office_documents`
				WHERE
					`type` 		= :sType AND
					`client_id`	= :iClientID
				" . $sAdditionalSql . "
			";
			$aSQL = array(
				'sType' 	=> $this->_aDocument['type'],
				'iClientID'	=> (int)$this->_aDocument['client_id']
			);
			$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

			// Pre-set a new number
			$iTmpNumber = 0;
			if(isset($aResult[0]['nr'])) {
				$iTmpNumber = $aResult[0]['nr'];
			}

			if($iTmpNumber < 1) {
				$sSQL = "
					SELECT
						`value`
					FROM
						`office_config`
					WHERE
						`key` 		= :sKey AND
						`client_id`	= :iClientID
					LIMIT
						1
				";
				$aSQL = array(
					'sKey' 		=> 'range_'.$this->_aDocument['type'],
					'iClientID'	=> (int)$this->_aDocument['client_id']
				);
				$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

				$iNumber = $aResult[0]['value'];
			} else {
				$iNumber = $iTmpNumber + 1;
			}

			$sSQL = "
				UPDATE
					`office_documents`
				SET 
					`number`	= :iNumber,
					`state`		= 'released',
					`date`		= NOW()
				WHERE
					`id` = :iId
						AND
					`state` = 'draft'
				LIMIT
					1
			";
			$aSQL = array('iNumber' => $iNumber, 'iId' => $this->_aDocument['id']);

			$this->_aDocument['number'] = $iNumber;

			DB::executePreparedQuery($sSQL, $aSQL);
		} else {
			throw new Exception('Document cannot be released, because the document do not exists!');
		}

		// To allow the fluid interface
		return $this;
	}

	/**
	 * Removes an item from the items list and calculates the new document prices
	 * 
	 * @param int $iItemID
	 */
	public function removeItem($iItemID)
	{
		// Check the document
		if(!is_numeric($this->_aDocument['id']))
		{
			throw new Exception('Wrong document ID!');
		}

		$aItem = array();

		// Find the item and the properties of item
		foreach((array)$this->_aItems as $iKey => $aValue)
		{
			if($aValue['id'] == $iItemID)
			{
				// Set the properties
				$aItem = $aValue;

				// Unset the item
				unset($this->_aItems[$iKey]);

				break;
			}
		}

		if(empty($aItem))
		{
			throw new Exception('Item with ID '.$iItemID.' could not be found!');
		}

		// Calculate prices
		$iPriceStandard			= (float)($aItem['amount'] * $aItem['price']);
		$iPriceAfterDiscount	= (float)($iPriceStandard * (1 - $aItem['discount_item'] / 100));
		$iPriceAfterVat			= (float)($iPriceAfterDiscount * (1 + $aItem['vat']));

		// Remove item from the data base
		$sSQL = "
			DELETE FROM
				`office_document_items`
			WHERE
				`id` = :iItemID
			LIMIT
				1
		";
		$aSQL = array('iItemID' => $iItemID);
		DB::executePreparedQuery($sSQL, $aSQL);

		// Update the document prices
		$sSQL = "
			UPDATE
				`office_documents`
			SET
				`price_net`	= (`price_net`	- :iNet),
				`price`		= (`price`		- :iGross)
			WHERE
				`id` = :iDocumentID
			LIMIT
				1
		";
		$aSQL = array(
			'iDocumentID'	=> $this->_aDocument['id'],
			'iNet'			=> $iPriceAfterDiscount,
			'iGross'		=> $iPriceAfterVat
		);
		DB::executePreparedQuery($sSQL, $aSQL);
	}


	/**
	 * Saves the document properties and items in the data base
	 */
	public function save() {

		// Check the presence of document type
		if(
			$this->_aDocument['type'] == '' || 
			$this->_aDocument['type'] == null
		) {
			throw new Exception('Document cannot be saved, because the document type is not defined!');
		}

		// Lock the document type
		$this->_bTypeLock = true;

		// Unlock date field
		$bDateLock = false;

		// Create a new entry in the data base
		if(intval($this->_aDocument['id']) == 0) {

			$iClientID = (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id');
			if($iClientID == 0) {
				$iClientID = 1;
			}

			$sHash = md5(uniqid());
			$this->_aDocument['hash'] = $sHash;

			// Save new document
			$aSQL = array(
				'iClientID'	=> $iClientID,
				'hash'		=> $sHash
			);
			$sSQL = "
				INSERT INTO
					`office_documents`
				SET
					`created`		= NOW(),
					`client_id`		= :iClientID,
					`hash`			= :hash
			";

			DB::executePreparedQuery($sSQL, $aSQL);

			$this->_aDocument['id'] = (int)DB::fetchInsertID();

			// Lock fields with default properties if is a new document
			$bFieldsLock = true;
		}

		// Calculate the total document prices
		$this->_calculateTotalPrices();

		// Update a document
		$sSet = 'SET ';
		$iCount = count((array)$this->_aDocument);

		// Prepare date
		$aDocument = $this->_aDocument;
		
		// Defaultdatum
		if(empty($aDocument['date'])) {
			$aDocument['date'] = date('Y-m-d');
		} else {
			$aDocument['date'] = date('Y-m-d', $aDocument['date']);
		}

		// Wenn Buchungsdatum nicht angegeben, dann Buchungsdatum = Datum
		if(empty($aDocument['booking_date'])) {
			$aDocument['booking_date'] = $aDocument['date'];
		}

		$aSet = array();
		
		// Create the SET condition
		foreach((array)$aDocument as $sKey => $mValue)
		{

			// Ignore the fields
			if (
				$sKey == 'id' ||
				($sKey == 'state' && $bFieldsLock)||
				$sKey == 'hash'
			) {
				continue;
			}

			if(is_null($aDocument[$sKey])) {
				$aDocument[$sKey] = '';
			}

			if($mValue === null) {
				$aSet[] = '`'.$sKey.'` = NULL';
			} else {
				$aSet[] = '`'.$sKey.'` = :'.$sKey;
			}
			
		}

		$sSet .= implode(", ", $aSet);
		
		$sSQL = "
			UPDATE
				`office_documents`
			{SET}
			WHERE
				`id` = :iID
		";
		$aSQL = $aDocument;
		$aSQL['iID'] = (int)$this->_aDocument['id'];
		$sSQL = str_replace('{SET}', $sSet, $sSQL);

		DB::executePreparedQuery($sSQL, $aSQL);

		/* ********************************************************************** */

		// Save document items
		foreach((array)$this->_aItems as $iKey => $aValue)
		{
			// Create a new entry in the data base
			if(intval($aValue['id']) == 0)
			{
				// Save new item
				$sSQL = "
					INSERT INTO
						`office_document_items`
					SET
						`active` = 1
				";
				DB::executeQuery($sSQL);

				$aValue['id'] = (int)DB::fetchInsertID();
				$this->_aItems[$iKey]['id'] = $aValue['id'];
			}

			// Set the document ID to a new item
			$aValue['document_id'] = $this->_aDocument['id'];

			// Update an item
			$sSQL = "
				UPDATE
					`office_document_items`
				SET
					`document_id`	= :document_id,
					`position`		= :position,
					`number`		= :number,
					`product`		= :product,
					`amount`		= :amount,
					`unit`			= :unit,
					`description`	= :description,
					`price`			= :price,
					`discount_item`	= :discount_item,
					`vat`			= :vat,
					`only_text`		= :only_text,
					`groupsum`		= :groupsum,
					`group_display`	= :group_display,
					`revenue_account` = :revenue_account
				WHERE
					`id` = :iID
			";
			$aSQL = $aValue;
			$aSQL['iID'] = $aValue['id'];

			DB::executePreparedQuery($sSQL, $aSQL);
		}

		$this->_loadDocument();
		
		// To allow the fluid interface
		return $this;
	}

	public function sendFileByPost() {

		$bAusland = false;
		
		// Kundendaten prüfen
		$oCustomer = new Ext_Office_Customer(null, $this->_aDocument['customer_id']);
		$sCountry = $oCustomer->country;
		if(
			!empty($sCountry) &&
			$sCountry != 'Deutschland' &&
			$sCountry != 'Germany' &&
			$sCountry != 'DE'
		) {
			$bAusland = true;
		}

		// Check the existation of file
		$sFilePath = $this->getFilePath();
		
		// Check the existation of file
		if(
			empty($sFilePath) ||
			!is_file($sFilePath)
		) {
			throw new Exception('PDF file does not exists!');
		}

		$aConfig = Ext_Office_Config::getInstance();

		$aCode = array();
		$aCode['id'] = $this->id;
		$aCode['number'] = $this->number;
		$sCode = json_encode($aCode);

		$oPost = new Ext_Office_Smskaufen_Post($aConfig);

		$mResponse = $oPost->post($sFilePath, $sCode, $bAusland);

		return $mResponse;

	}

	public function sendFileByFax($sFaxNumber) {

		// Check the existation of file
		$sFilePath = $this->getFilePath();
		
		// Check the existation of file
		if(
			empty($sFilePath) ||
			!is_file($sFilePath)
		) {
			throw new Exception('PDF file does not exists!');
		}

		$aConfig = Ext_Office_Config::getInstance();

		$aCode = array();
		$aCode['id'] = $this->id;
		$aCode['number'] = $this->number;
		$sCode = json_encode($aCode);

		$oFax = new Ext_Office_Smskaufen_Fax($aConfig);

		$mResponse = $oFax->post($sFilePath, $sCode, $sFaxNumber);

		return $mResponse;

	}

	/**
	 * Sends the pdf file by e-mail
	 * 
	 * @param array $aMailConfig
	 */
	public function sendFile($aMailConfig) {
		global $objOfficeDao;
		
		// Check e-mail configuration array
		if(
			!is_array($aMailConfig) ||
			!isset($aMailConfig['to']) ||
			!isset($aMailConfig['subject']) ||
			!isset($aMailConfig['body'])
		) {
			throw new Exception('Wrong e-mail configuration!');
		}

		$sFilePath = $this->getFilePath();
		
		// Check the existation of file
		if(
			empty($sFilePath) ||
			!is_file($sFilePath)
		) {
			throw new Exception('PDF file does not exists!');
		}

		$aEditor = $objOfficeDao->getEditor($this->_aDocument['editor_id']);

		$mFrom = false;
		if(isset($aMailConfig['from'])) {
			$mFrom = $aMailConfig['from'];
		}
		
		if(isset($aMailConfig['reply_to'])) {
			$aEditor['email'] = $aMailConfig['reply_to'];
		}

		if(is_array($aMailConfig['to'])) {
			$aTo = $aMailConfig['to'];
		} else {
			$aTo = explode(',', $aMailConfig['to']);
		}

		$oEmail = new Office\Service\Email;
		$oEmail->setSubject($aMailConfig['subject']);
		$oEmail->setReplyTo($aEditor['email']);
		$oEmail->setText($aMailConfig['body']);
		$oEmail->addAttachment($sFilePath);

		$bSuccess = false;
		if(!empty($aTo)) {
			$bSuccess = $oEmail->send($aTo);
		}

		return $bSuccess;
	}

	/**
	 * Updates a field in an item
	 * 
	 * @param int $iKey
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function updateItem($iKey, $sName, $mValue)
	{
		if(isset($this->_aItems[$iKey][$sName]))
		{
			$this->_aItems[$iKey][$sName] = $mValue;
		}
		else
		{
			throw new Exception('Item with the key ['.$iKey.']['.$sName.'] could not be found!');
		}

		// To allow the fluid interface
		return $this;
	}
	
	public function replacePlaceholders($sContent, $aAdditionalPlaceholders=null) {
		global $objOfficeDao;

		$aContact = $objOfficeDao->getContact($this->contact_person_id);
		$aEditor = $objOfficeDao->getEditor($this->editor_id);
		$aCustomer = $objOfficeDao->getCustomer($this->customer_id);

		$sSalutation = Ext_Office_Config::getDefaultSalutation($aCustomer['language'], $aContact['sex']);

		$aCurrentPayments = $objOfficeDao->getPayments($this->id);

		// Bisherige Zahlungen von Bruttobetrag abziehen
		$fOutstanding = bcsub($this->price, $aCurrentPayments['sum'], 5);

		$aValues = array(
			'DocumentNumber' => $this->number,
			'DocumentPrice' => number_format($this->price, 2, ',', '.').' €',
			'DocumentDate' => strftime('%x', $this->date),
			'DocumentOutstanding' => number_format($fOutstanding, 2, ',', '.').' €',
			'DocumentPurchaseOrderNumber' => $this->purchase_order_number,

			'ContactName' => $aEditor['firstname'].' '.$aEditor['lastname'],
			'ContactFirstname' => $aEditor['firstname'],
			'ContactLastname' => $aEditor['lastname'],
			'ContactEmail' => $aEditor['email'],
			'ContactPhone' => $aEditor['phone'],

			'CustomerContactSalutation' => $sSalutation,
			'CustomerContactName' => $aContact['firstname'].' '.$aContact['lastname'],
			'CustomerContactEmail' => $aContact['email'],
			'CustomerContactPhone' => $aContact['phone'],
			'CustomerContactFirstname' => $aContact['firstname'],
			'CustomerContactLastname' => $aContact['lastname'],
			
			'CustomerName' => $aCustomer['company'],
			'CustomerNumber' => $aCustomer['number']
		);

		if(
			$aAdditionalPlaceholders !== null &&
			is_array($aAdditionalPlaceholders)
		) {
			$aValues += $aAdditionalPlaceholders;
		}
		
		foreach($aValues as $sKey=>$sValue) {
			$sContent = str_replace('{'.$sKey.'}', $sValue, $sContent);
		}
		
		return $sContent;
	}
	
	public static function displayPlaceholders($aPlaceholders) {
		?>
		
		<div class="divBoxContainer" style="margin-top: 20px;">
			<div class="divBoxHeader" onClick="switchBox('divPlaceHolders');">Verfügbare Platzhalter &raquo;</div> 
			<div class="divBoxContent" id="divPlaceHolders">
				<div>
				<?php
					foreach((array)$aPlaceholders as $sKey => $sValue) {
						echo $sKey.$sValue;
					}
				?>
					<br/>
					<br/>
					If-Abfragen:<br/>
					z.B. {if CustomerVatID}Ust.-ID: {CustomerVatID}{/if}<br/>
				</div>
			</div>
		</div>

		<script language="JavaScript" type="text/javascript">
			Element.hide('divPlaceHolders');
		</script>
			
		<?php
	}
	
	public static function getByTypeAndId($sType, $iId) {
		
		$sSql = "
			SELECT 
				*
			FROM
				`office_documents`
			WHERE
				`type` = :type AND
				`id` = :id
			";
		$aSql = array(
			'type' => $sType,
			'id' => $iId
		);
		
		$aResults = (array)DB::getQueryRows($sSql, $aSql);
		
		if(count($aResults) !== 1) {
			return false;
		} else {
			return reset($aResults);
		}
		
	}

	/**
	 * Gibt die Id der Rechnung zurück, die dem Hash angehört.
	 * 
	 * @param string $sHash Der Hash, der zu einer Rechnung gehört
	 * @return int|null <b>NULL</b> wenn kein Document mit diesem Hash gefunden wurde,
	 *					sonst die ID.
	 */
	static public function getIdFromHash($sHash){
		$sSql = "
			SELECT 
				`id`
			FROM
				`office_documents`
			WHERE
				`hash` = :hash
			";
		$aSql = array(
			'hash' => $sHash,
		);
		
		$aResults = DB::getQueryCol($sSql, $aSql);

		if($aResults !== null){
			$iId = (int) $aResults[0];
			return $iId;
		} else {
			return null;
		}
	}

	/**
	 * Gibt den relativen Pfad zum Verzeichniss der PDFs zurück.
	 * 
	 * @return string
	 */
	public function getWebFileDir(){
		return "storage/office/pdf/";
	}

	/**
	 * Gibt den Dateinamen der PDF der Rechnung zurück.
	 * 
	 * @return string
	 */
	public function getPDFFilename(){
		return $this->pdf_filename . '.pdf';
	}

	public function prepareEmail($emailType=null) {

		$aEmail = [
			'to' => []
		];

		$oCustomer = new Ext_Office_Customer(null, $this->_aDocument['customer_id']);
		
		$oContact = Ext_Office_Customer_Contact::getInstance($this->_aDocument['contact_person_id']);

		if(empty($oContact->email)) {
			$aEmail['to'][] = trim($oCustomer->email);
		} else {
			$aEmail['to'][] = trim($oContact->email);
		}
		
		$aContacts = $oCustomer->getContactsList();
		
		foreach($aContacts as $aContact) {
			if(
				$aContact['invoice_recipient'] == 1 &&
				!empty($aContact['email']) &&
				!in_array($aContact['email'], $aEmail['to'])
			) {
				$aEmail['to'][] = trim($aContact['email']);
			}
		}

		$aEmail['to'] = array_filter($aEmail['to']);
		
		$oOffice = new classExtension_Office;

		if($emailType === null) {
			$emailType = $this->type;
		}
		
		$aEmailTemplate = $oOffice->getEmailTemplate($emailType, $oCustomer->language);

		$aEmail['subject'] = $this->replacePlaceholders($aEmailTemplate['subject']);
		$aEmail['body'] = $this->replacePlaceholders($aEmailTemplate['body']);

		return $aEmail;
	}
	
}
