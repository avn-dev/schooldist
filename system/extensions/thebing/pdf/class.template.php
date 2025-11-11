<?php

class Ext_Thebing_Pdf_Template extends Ext_Thebing_Basic {

	// The table name
	protected $_sTable = 'kolumbus_pdf_templates';

	// TODO : evtl löschen, da in der neuen Version die $_aAdditional benutzt wird. @AS
	protected $_aStaticElementValues = null;

	// The table alias
	protected $_sTableAlias = 'kpt';

	// The additional language data
	protected $_aAdditional = array();

	protected static $_aCache = array();

	protected $_bAdditionalLoaded = false;

	// Joined table data
	protected $_aJoinTables = array(
		'schools' => array(
			'table' => 'kolumbus_pdf_templates_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'template_id',
			'on_delete' => 'no_action'
		),
		'inboxes' => array(
			'table' => 'kolumbus_pdf_templates_inboxes',
			'foreign_key_field' => 'inbox_id',
			'primary_key_field' => 'template_id',
			'class' => 'Ext_Thebing_Client_Inbox',
			'on_delete' => 'no_action'
		),
		'languages' => array(
			'table' => 'kolumbus_pdf_templates_languages',
			'foreign_key_field' => 'iso_language',
			'primary_key_field' => 'template_id',
			'on_delete' => 'no_action'
		),
		'examination_templates'=>array(
			'table'=>'kolumbus_examination_templates',
			'primary_key_field'=>'pdf_template_id',
			'autoload'=>false,
			'delete_check'=>true,
			'check_active' => true,
		)
	);
	
	protected $_aJoinedObjects = array(
						'template_type'=>array(
							'class'=>'Ext_Thebing_Pdf_Template_Type',
							'key'=>'template_type_id'
						)
					);

	protected $_aAdditionalOriginal = array();

	protected $_aIntersectAdditional = array();

	protected $_aFlexibleFieldsConfig = [
		'pdf_templates' => []
	];

	/**
	 * Get the data
	 * 
	 * @param string $sName
	 * @return mixed
	 */
	public function __get($sName)
	{
		
		Ext_Gui2_Index_Registry::set($this);
		
		if(strpos($sName, 'lang_tab_default_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_default_', '', $sName);

			$aParts = explode('-', $sName);

			$sValue = $this->_aAdditional['default'][$aParts[0]][$aParts[1]];
		}
		else if(strpos($sName, 'lang_tab_elements_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_elements_', '', $sName);

			$aParts = explode('-', $sName);

			$sValue = $this->_aAdditional['elements'][$aParts[0]][$aParts[1]];
		}
		else if(strpos($sName, 'lang_tab_school_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_school_', '', $sName);

			$aParts = explode('-', $sName, 3);

			$sValue = $this->_aAdditional['school'][$aParts[0]][$aParts[1]][$aParts[2]];
		}
		else
		{
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}


	/**
	 * Set the data
	 * 
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue)
	{
		if(strpos($sName, 'lang_tab_default_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_default_', '', $sName);

			$aParts = explode('-', $sName);

			$this->_aAdditional['default'][$aParts[0]][$aParts[1]] = $mValue;
		}
		else if(strpos($sName, 'lang_tab_elements_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_elements_', '', $sName);

			$aParts = explode('-', $sName);

			$this->_aAdditional['elements'][$aParts[0]][$aParts[1]] = $mValue;
		}
		else if(strpos($sName, 'lang_tab_school_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_school_', '', $sName);

			$aParts = explode('-', $sName, 3);

			$this->_aAdditional['school'][$aParts[0]][$aParts[1]][$aParts[2]] = $mValue;
		}
		else
		{
			parent::__set($sName, $mValue);
		}
	}


	public function saveLanguages($aLanguages){
		$sSql = " DELETE FROM
						`kolumbus_pdf_templates_languages`
					WHERE
						`template_id` = :template_id";
		$aSql = array('template_id'=>(int)$this->id);
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach($aLanguages as $sLang){
		
			$sSql = " INSERT INTO 
							`kolumbus_pdf_templates_languages`
						SET
							`template_id` = :template_id,
							`iso_language` = :iso_language
							";
			$aSql['iso_language'] = $sLang;
			DB::executePreparedQuery($sSql, $aSql);
		}
	}


	public function saveSchools($aSchools){
		$sSql = " DELETE FROM
						`kolumbus_pdf_templates_schools`
					WHERE
						`template_id` = :template_id";
		$aSql = array('template_id'=>(int)$this->id);
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach($aSchools as $iSchool){
		
			$sSql = " INSERT INTO 
							`kolumbus_pdf_templates_schools`
						SET
							`template_id` = :template_id,
							`school_id` = :school_id
							";
			$aSql['school_id'] = (int)$iSchool;
			DB::executePreparedQuery($sSql, $aSql);
		}
	}


	// Array mit IDs
	public function getSchoolList(){
		$sSql = "
			SELECT
				`school_id`
			FROM
				`kolumbus_pdf_templates_schools`
			WHERE
				`template_id` = :template_id
		";
		$aSql = array('template_id'=>(int)$this->id);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$aBack = array();
		foreach($aResult as $aData){
			$aBack[] = $aData['school_id'];
		}
		return $aBack;
	}


	// Array mit ISO Codes
	public function getLanguageList() {

		$sSql = "
					SELECT
						`iso_language`
					FROM
						`kolumbus_pdf_templates_languages`
					WHERE
						`template_id` = :template_id";
		$aSql = array('template_id'=>(int)$this->id);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$aBack = array();
		foreach($aResult as $aData){
			$aBack[] = $aData['iso_language'];
		}

		return $aBack;

	}


	// Holt alle verfügbaren Vorlagen Typen des Mandanten
	static public function getAvailableTemplateTypes($bForSelect = true){

		$sSql = " SELECT 
						*
					FROM 
						`kolumbus_pdf_templates_types`
					WHERE 
						active = 1
					ORDER BY 
						name ";
		
		$aSql = array();

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if(!$bForSelect){
			return $aResult;
		}

		foreach($aResult as $aData){
			$aBack[$aData['id']] = $aData['name'];
		}
		return $aBack;

	}


	protected function _getStaticElementValues($sLanguage) {
		$sSql = "
					SELECT
						`element`,
						`value`
					FROM
						`kolumbus_pdf_templates_static_elements_values`
					WHERE
						`template_id` = :template_id AND
						`language_iso` = :language_iso
					";

		$aSql = array(
						'template_id'=> (int)$this->id,
						'language_iso' => $sLanguage
					);
		$aResult = DB::getQueryPairs($sSql, $aSql);

		$this->_aStaticElementValues[$sLanguage] = $aResult;

	}


	public function getStaticElementValue($sLang, $sElement) {

		if(!isset($this->_aStaticElementValues[$sLang])) {
			$this->_getStaticElementValues($sLang);
		}

		if(
			is_array($this->_aStaticElementValues[$sLang]) &&
			isset($this->_aStaticElementValues[$sLang][$sElement])
		) {
			return $this->_aStaticElementValues[$sLang][$sElement];
		}

		return '';

	}


	public function saveStaticElementValue($sLang, $sElement, $mValue){

		// Entfernt weil sonst Platzhalter im Code nicht überall möglich sind #1353
		//$mValue = Ext_Thebing_Purifier::p($mValue);

		$sSql = " REPLACE INTO
						`kolumbus_pdf_templates_static_elements_values`
					SET
						`template_id` = :template_id , 
						`language_iso` = :language_iso , 
						`element` = :element , 
						`value` = :value ";
		
		$aSql = array(
						'template_id'	=> (int)$this->id,
						'language_iso' => $sLang,
						'element' 	=> $sElement,
						'value' => (string)$mValue // Bei neuen Sprachen steht hier sonst NULL
					);

		DB::executePreparedQuery($sSql, $aSql);

	}


	/*
	 * Liefert die Werte die zum Template gespeichert wurden
	 * $bGetFile gibt an ob der Dateinamen oder die ID returned werden
	 * der hochgeladenen vorlagen (Hintergründe, Signaturen, Attachments)
	 */
	public function getOptionValue($sLang, $iSchool, $sOption, $bGetFile = true){

		$mValue = '';

		$aSql = array(
			'template_id'=> (int)$this->id,
			'language_iso' => $sLang,
			'school_id' => (int)$iSchool,
			'option' => $sOption
		);

		// Caching
		if(!isset(self::$_aCache['option_value'][$this->id])) {

			$sSql = "
						SELECT
							`kpto`.*,
							`kptoa`.`file_id`
						FROM
							`kolumbus_pdf_templates_options` `kpto` LEFT JOIN
							`kolumbus_pdf_templates_options_attachment` `kptoa` ON
								`kptoa`.`option_id` = `kpto`.`id`
						WHERE
							`kpto`.`template_id` = :template_id
						";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			$aItems = (array)$aResult;

			self::$_aCache['option_value'][$this->id] = array();
			foreach($aItems as $aItem) {
				self::$_aCache['option_value'][$this->id][$aItem['option']][$aItem['language_iso']][$aItem['school_id']][] = $aItem;
			}

		}

		// Nur weitermachen, wenn der Wert ein Array ist
		if(!is_array(self::$_aCache['option_value'][$this->id][$aSql['option']][$aSql['language_iso']][$aSql['school_id']])) {
			return;
		}

		if($sOption == 'attachments') {

//			$mValue = array();
//			foreach(self::$_aCache['option_value'][$this->id][$aSql['option']][$aSql['language_iso']][$aSql['school_id']] as $aData){
//				$mValue[] = (int)$aData['file_id'];
//			}

		} else {

			$aItem = reset(self::$_aCache['option_value'][$this->id][$aSql['option']][$aSql['language_iso']][$aSql['school_id']]);

			$mValue = $aItem['value'];

			// START Neue Dateistruktur für Uploads aus Zwischentabelle lesen
			// früher standen die pfade in der Tabelle
			if(
				$bGetFile &&
				is_numeric($mValue) &&
				(
					$sOption == 'first_page_pdf_template' ||
					$sOption == 'additional_page_pdf_template' ||
					$sOption == 'signatur_img'
				)
			){

				$oUpload = Ext_Thebing_Upload_File::getInstance($mValue);

				$mValue = $oUpload->filename;

			}
			// ENDE

		}

		return $mValue;
	}

	/**
	 * Alle Werte löschen von Schulen, die nicht mehr im Template eingestellt sind
	 */
	protected function cleanOptionValues() {

		$sSql = "
			DELETE FROM
				`kolumbus_pdf_templates_options`
			WHERE
				`template_id` = :template_id AND
				`school_id` NOT IN (:school_ids)
		";

		DB::executePreparedQuery($sSql, [
			'template_id' => $this->id,
			'school_ids' => $this->schools
		]);

	}

	public function saveOptionValue($sLang, $iSchool, $sOption, $mValue){


		## START Atachment speichern
		// TODO Attachment-Zeug komplett entfernen?
			if($sOption == 'attachments'){
				/*
				// Attachments werden in Verknüpfungstabelle gespeichert
				// alle bisherigen löschen
				$sSql = "SELECT
								`id`
							FROM
								`kolumbus_pdf_templates_options`
							WHERE
								`template_id` = :template_id AND
								`language_iso` = :language_iso AND
								`school_id` = :school_id AND
								`option` = :option
							LIMIT 1
							";

			$aSql = array(
							'template_id'	=> (int)$this->id,
							'language_iso' => (string)$sLang,
							'school_id' => (int)$iSchool,
							'option' 	=> (string)$sOption,
							'value' => (string)$mValue
						);
				$aData = DB::getQueryRow($sSql, $aSql);

				$iLastId = (int)$aData['id'];

				$sSql = "DELETE
							FROM
								`kolumbus_pdf_templates_options_attachment`
							WHERE
								`option_id` = :option_id
						";
				$aSql = array();
				$aSql['option_id'] = (int)$iLastId;

				DB::executePreparedQuery($sSql, $aSql);

				// neu speichern
				foreach((array)$mValue as $iFileId){
					$sSql = "INSERT INTO
										`kolumbus_pdf_templates_options_attachment`
									SET
										`option_id` = :option_id,
										`file_id`	= :file_id
								";

					$aSql = array();
					$aSql['option_id'] = (int)$iLastId;
					$aSql['file_id'] = (int)$iFileId;
					DB::executePreparedQuery($sSql, $aSql);
				}
				*/

		} else {

			// Entfernt weil sonst Platzhalter im Code nicht überall möglich sind #1353
			//$mValue = Ext_Thebing_Purifier::p($mValue);

			$sSql = " REPLACE INTO
							`kolumbus_pdf_templates_options`
						SET
							`template_id` = :template_id ,
							`language_iso` = :language_iso ,
							`school_id` = :school_id ,
							`option` = :option ,
							`value` = :value ";

			$aSql = array(
							'template_id'	=> (int)$this->id,
							'language_iso' => (string)$sLang,
							'school_id' => (int)$iSchool,
							'option' 	=> (string)$sOption,
							'value' => (string)$mValue
						);

			DB::executePreparedQuery($sSql, $aSql);

		}
		## ENDE
	}

	public function getDataForInquiry($iInquiry){
		global $user_data;
		
		$oInquiry 	= new Ext_TS_Inquiry($iInquiry);
		$oSchool	= $oInquiry->getSchool();
		$oCustomer 	= $oInquiry->getCustomer();
		$oReplace 	= new Ext_Thebing_Inquiry_Placeholder($iInquiry, $oCustomer->id);
		
		$aBack 		= array();
		
		$aBack['txt_subject'] 	= $oReplace->replace($this->getStaticElementValue($oCustomer->getLanguage(), 'subject'));
		$aBack['txt_intro'] 	= $oReplace->replace($this->getStaticElementValue($oCustomer->getLanguage(), 'text1'));
		$aBack['txt_outro'] 	= $oReplace->replace($this->getStaticElementValue($oCustomer->getLanguage(), 'text2'));
		$aBack['txt_address'] 	= $oReplace->replace($this->getStaticElementValue($oCustomer->getLanguage(), 'address'));
		$aBack['txt_signature'] = $this->getOptionValue($oCustomer->getLanguage(), $oSchool->id, 'signatur_text');
		$aBack['signature'] 	= $this->getOptionValue($oCustomer->getLanguage(), $oSchool->id, 'signatur_img');
		$aBack['txt_pdf'] 		= $this->getOptionValue($oCustomer->getLanguage(), $oSchool->id, 'first_page_pdf_template');
		if($this->user_signature == 1){
			$aBack['txt_signature'] = Ext_Thebing_User_Data::getData($user_data['id'], 'signature_pdf_' . $oCustomer->getLanguage());
			$aBack['signature'] 	= Ext_Thebing_User_Data::getData($user_data['id'], 'signature_img_' . $oSchool->id);
		}
		
		return $aBack;
	}

	/**
	 * @param bool $sApplication
	 * @return array
	 * @throws Exception
	 */
	public static function getApplications($sApplication=false) {

		$oClient = Ext_Thebing_Client::getFirstClient();

		$aApplications = array();
		$aApplications['document_invoice_customer']								= L10N::t('Inbox - Kundenrechnung', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_invoice_agency']								= L10N::t('Inbox - Agenturrechnung', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_invoice_storno']								= L10N::t('Inbox - Stonierung', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_invoice_credit']								= L10N::t('Inbox - Gutschrift', 'Thebing » Admin » Vorlagen Typen');

		if($oClient->inquiry_payments_receipt > 0) {
			$aApplications['document_invoice_customer_receipt']					= L10N::t('Inbox - Kundenzahlungsbeleg', 'Thebing » Admin » Vorlagen Typen');
			$aApplications['document_invoice_agency_receipt']					= L10N::t('Inbox - Agenturzahlungsbeleg', 'Thebing » Admin » Vorlagen Typen');
			$aApplications['document_invoice_agency_receipt_brutto']			= L10N::t('Inbox - Brutto-Agenturzahlungsbeleg', 'Thebing » Admin » Vorlagen Typen');
		}
		if($oClient->inquiry_payments_invoice > 0) {
			$aApplications['document_customer_document_payment']				= L10N::t('Inbox - Zahlungen einer Kundenrechnung', 'Thebing » Admin » Vorlagen Typen');
			$aApplications['document_agency_document_payment']					= L10N::t('Inbox - Zahlungen einer Agenturrechnung', 'Thebing » Admin » Vorlagen Typen');
		}
		if($oClient->inquiry_payments_overview > 0) {
			$aApplications['document_customer_document_payment_overview']		= L10N::t('Inbox - Zahlungen aller Kundenrechnungen', 'Thebing » Admin » Vorlagen Typen');
			$aApplications['document_agency_document_payment_overview']			= L10N::t('Inbox - Zahlungen aller Agenturrechnungen', 'Thebing » Admin » Vorlagen Typen');
		}

		if($oClient->inquiry_payments_creditnote_receipt > 0) {
			$aApplications['document_creditnote_receipt'] = L10N::t('Gutschrift - Agenturzahlungsbeleg', 'Thebing » Admin » Vorlagen Typen');
		}
		if($oClient->inquiry_payments_creditnote > 0) {
			$aApplications['document_creditnote_document_payment'] = L10N::t('Gutschrift - Zahlungen einer Agenturgutschrift', 'Thebing » Admin » Vorlagen Typen');
		}
		if($oClient->inquiry_payments_creditnote_overview > 0) {
			$aApplications['document_creditnote_document_payment_overview']	= L10N::t('Gutschrift - Zahlungen aller Agenturgutschriften', 'Thebing » Admin » Vorlagen Typen');
		}

		$aApplications['document_loa']											= L10N::t('Inbox - LOA', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_studentrecord_additional_pdf']					= L10N::t('Inbox - Weitere PDFs', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_studentrecord_visum_pdf']						= L10N::t('Inbox - Visum PDFs', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_student_cards']								= L10N::t('Schülerausweise', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_certificates']									= L10N::t('Zertifikate', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_insurances']									= L10N::t('Versicherungen', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_accommodation_communication']					= L10N::t('Unterkunft - Kommunikation', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_invoice_customer']								= L10N::t('Inbox - Kundenrechnung', 'Thebing » Admin » Vorlagen Typen');

		$aApplications['document_teacher_payment']								= L10N::t('Lehrer - Zahlung', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_teacher_contract_basic']						= L10N::t('Lehrer - Rahmenvertrag', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_teacher_contract_additional']					= L10N::t('Lehrer - Zusatzvertrag', 'Thebing » Admin » Vorlagen Typen');

		$aApplications['document_accommodation_payment']						= L10N::t('Unterkunft - Zahlung', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_accommodation_contract_basic']					= L10N::t('Unterkunft - Rahmenvertrag', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_accommodation_contract_additional']			= L10N::t('Unterkunft - Zusatzvertrag', 'Thebing » Admin » Vorlagen Typen');

		$aApplications['document_transfer_payment']								= L10N::t('Transfer - Zahlung', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_transfer_additional_pdf'] 						= L10N::t('Transfer - Zusätzliche Dokumente', 'Thebing » Admin » Vorlagen Typen');

		$aApplications['document_examination']									= L10N::t('Examen', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_student_requests']								= L10N::t('Schüleranfragen', 'Thebing » Admin » Vorlagen Typen');

		// Agentur CRM PDF
		if(Ext_Thebing_Access::hasRight('thebing_marketing_agency_crm')){
			$aApplications['agency_overview']									= L10N::t('Agentur Übersicht', 'Thebing » Admin » Vorlagen Typen');
		}

		if(Ext_Thebing_Access::hasRight('thebing_accounting_cheque')){
			$aApplications['cheque']											= L10N::t('Schecks', 'Thebing » Admin » Vorlagen Typen');
		}

		if(Ext_Thebing_Access::hasRight('thebing_accounting_manual_creditnote')){
			$aApplications['manual_creditnotes']											= L10N::t('Manuelle Creditnotes', 'Thebing » Admin » Vorlagen Typen');
		}
		
		if(Ext_Thebing_Access::hasRight('thebing_students_contact_gui')){
			$aApplications['document_offer_customer']						= L10N::t('Kundenangebot', 'Thebing » Admin » Vorlagen Typen');
			$aApplications['document_offer_agency']							= L10N::t('Agenturangebot', 'Thebing » Admin » Vorlagen Typen');
		}
		
		$aApplications['document_attendance']								= L10N::t('Klassenplanung - Anwesenheit', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_job_opportunity']							= L10N::t('Klassenplanung - Jobzuweisungen', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_course'] = L10N::t('Klassenplanung - Kurs', 'Thebing » Admin » Vorlagen Typen');
		$aApplications['document_teacher'] = L10N::t('Lehrer - Zusätzliche Dokumente', 'Thebing » Admin » Vorlagen Typen');

		if($sApplication !== false) {
			return $aApplications[$sApplication];
		} else {
			return $aApplications;
		}

	}

	/**
	 * Prüft, ob der Typ der Vorlage den Standardwert für die App-Freigabe erhalten darf
	 *
	 * @param string $sType
	 * @return boolean
	 */
	public static function checkStudentAppReleaseWhitelist($sType) {
		
		$aStudentAppReleaseWhitelist = array(
			'document_accommodation_communication',
			'document_loa',
			'document_studentrecord_visum_pdf',
			'document_studentrecord_additional_pdf',
			'document_examination',
			'document_certificates',
			'document_attendance',
			'document_insurances',
			'document_invoice_customer',
			'document_customer_document_payment_overview',
			'document_customer_document_payment',
			'document_invoice_customer_receipt',
			'document_student_cards'
		);
		
		$bShow = in_array($sType, $aStudentAppReleaseWhitelist);
		
		return $bShow;
	}

	/**
	 * Rechnungstypen, welche z.B. ein Datum im Layout benötigen
	 *
	 * @return array
	 */
	private static function getInvoiceTemplateTypes() {

		return [
			'document_invoice_agency',
			'document_invoice_credit',
			'document_invoice_customer',
			'document_invoice_storno',
			//'document_loa',
			'document_offer_agency',
			'document_offer_customer'
		];

	}
	
	public function  manipulateSqlParts(&$aSqlParts, $sView=null) {
		$aSqlParts['select'] .= ',
									`kptt`.`name` `template_type_name`,
 									GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`,
									GROUP_CONCAT(DISTINCT `inboxes`.`inbox_id`) AS `inboxes`,
									GROUP_CONCAT(DISTINCT `cdb2`.`ext_1`) AS `school_names`
								';
		$aSqlParts['from']   .= ' LEFT JOIN
									`customer_db_2` `cdb2` ON
										`cdb2`.`id` = `schools`.`school_id` LEFT JOIN
									`kolumbus_inboxlist` `k_i` ON
										`k_i`.`id` = `inboxes`.`inbox_id`
								  LEFT JOIN
									`kolumbus_pdf_templates_types` `kptt` ON
										`kptt`.`id` = `kpt`.`template_type_id` AND
										`kptt`.`active` = 1
								';
		}

	/**
	 * Get the placeholders by type
	 * 
	 * @param string $sType
	 */
	public static function getPlaceholderData($sType)
	{

		$aLinks = array(
			'document_invoice_customer'						=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => true
			),
			'document_invoice_agency'						=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => true
			),
			'document_invoice_storno'						=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => true
			),
			'document_invoice_credit'						=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => true
			),
			'document_invoice_customer_receipt'				=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_invoice_agency_receipt'				=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_invoice_agency_receipt_brutto'		=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_customer_document_payment'			=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_agency_document_payment'				=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_customer_document_payment_overview'	=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_agency_document_payment_overview'		=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_creditnote_receipt' => array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_creditnote_document_payment' => array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_creditnote_document_payment_overview' => array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => true,
				'pdf_placeholder' => false
			),
			'document_loa'									=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_studentrecord_additional_pdf'			=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_studentrecord_visum_pdf'				=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_student_cards'						=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_certificates'							=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_insurances'							=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_accommodation_communication'			=> array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_teacher_contract_basic'				=> array(
				'class'	=> 'Ext_Thebing_Contract_Placeholder'
			),
			'document_teacher_contract_additional'			=> array(
				'class'	=> 'Ext_Thebing_Contract_Placeholder'
			),
			'document_accommodation_contract_basic'			=> array(
				'class'	=> 'Ext_Thebing_Contract_Placeholder'
			),
			'document_accommodation_contract_additional'	=> array(
				'class'	=> 'Ext_Thebing_Contract_Placeholder'
			),
			'document_examination'							=> array(
				'class'	=> 'Ext_Thebing_Examination_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false
			),
			'document_student_requests'						=> array(
				'class'	=> 'Ext_TS_Enquiry_Placeholder',
			),
			'document_offer_agency'						=> array(
				'class'	=> 'Ext_TS_Enquiry_Offer_Placeholder',
			),
			'document_offer_customer'						=> array(
				'class'	=> 'Ext_TS_Enquiry_Offer_Placeholder',
			),
			'agency_overview'								=> array(
				'class'	=> 'Ext_Thebing_Agency_Placeholderoverview'
			),
			'cheque'										=> array(
				'class'	=> 'Ext_Thebing_Accounting_Cheque_Placeholder'
			),
			'manual_creditnotes'										=> array(
				'class'	=> 'Ext_Thebing_Agency_Manual_Creditnote_Placeholder'
			),
			'document_attendance' => array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder',
				'receipt_placeholder' => false,
				'pdf_placeholder' => false,
				'accommodation' => false,
				'transfer' => false,
				'agency_bank' => false,
				'invoice' => false,
				'accommodation_provider' => false,
				'accommodation_preference' => false,
				'links' => false,
				'insurance' => false,
			),
			'document_teacher_payment' => array(
				'class' => 'Ext_TS_Accounting_Provider_Grouping_Teacher_Placeholder'
			),
			'document_accommodation_payment' => array(
				'class' => 'Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder'
			),
			'document_transfer_payment' => array(
				'class' => 'Ext_TS_Accounting_Provider_Grouping_Transfer_Placeholder'
			),
			'document_transfer_additional_pdf' => array(
				'class'	=> 'Ext_Thebing_Inquiry_Placeholder'
			),
			'document_teacher' => array(
				'class'	=> 'Ext_Thebing_Teacher_Placeholder'
			),
		);

		return $aLinks[$sType];
	}

	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		// Datumsfeld darf im Layout nicht fehlen bei Rechnungstypen
		if($mValidate === true) {
			if(in_array($this->type, $this->getInvoiceTemplateTypes())) {
				$oLayout = Ext_Thebing_Pdf_Template_Type::getInstance($this->template_type_id); // Komische Methode arbeitet nicht mit _aData
				if(!$oLayout->element_date) {
					$mValidate = [];
					$mValidate['type'] = 'PDF_LAYOUT_DATE_ELEMENT_MISSING';
				}
			}
		}

		return $mValidate;

	}

	/**
	 * See parent
	 */
	public function save($bLog = true) { 

		$this->_loadAdditionalData();
		
		$aAdditional = $this->_aAdditional;

		// @TODO Analog dazu Smarty-Checkbox deaktivieren?
		// App-Freigabe Option prüfen
		$bStudentAppReleaseWhitelist = Ext_Thebing_Pdf_Template::checkStudentAppReleaseWhitelist($this->type);
		if($bStudentAppReleaseWhitelist === false) {
			$this->app_release = 0;
		}

		if($this->type == 'document_course') {
			$this->use_smarty = 1;
		}
		
		parent::save($bLog);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save additional language data

		$oType = new Ext_Thebing_Pdf_Template_Type($this->template_type_id);

		$aTypeElements = $oType->getElements();

		foreach((array)$this->languages as $sLanguage)
		{
			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Standard fields

			$this->saveStaticElementValue($sLanguage, 'date', $aAdditional['default']['date'][$sLanguage]);
			$this->saveStaticElementValue($sLanguage, 'address', $aAdditional['default']['address'][$sLanguage]);
			$this->saveStaticElementValue($sLanguage, 'subject', $aAdditional['default']['subject'][$sLanguage]);
			$this->saveStaticElementValue($sLanguage, 'text1', $aAdditional['default']['text1'][$sLanguage]);
			$this->saveStaticElementValue($sLanguage, 'text2', $aAdditional['default']['text2'][$sLanguage]);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Type elements fields

			foreach($aTypeElements as $oElement)
			{
				$oElement->saveValue($sLanguage, $this->id, $aAdditional['elements'][$oElement->id][$sLanguage]);
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // School fields

			$this->cleanOptionValues();

			foreach((array)$this->schools as $iSchoolId) {
				
				$this->saveOptionValue(
					$sLanguage,
					$iSchoolId,
					'filename',
					$aAdditional['school'][$iSchoolId][$sLanguage]['filename']
				);

				$this->saveOptionValue(
					$sLanguage,
					$iSchoolId,
					'first_page_pdf_template',
					$aAdditional['school'][$iSchoolId][$sLanguage]['first_page_pdf_template']
				);

				$this->saveOptionValue(
					$sLanguage,
					$iSchoolId,
					'additional_page_pdf_template',
					$aAdditional['school'][$iSchoolId][$sLanguage]['additional_page_pdf_template']
				);

				if($this->user_signature != 1) {
					$this->saveOptionValue(
						$sLanguage,
						$iSchoolId,
						'signatur_img',
						$aAdditional['school'][$iSchoolId][$sLanguage]['signatur_img']
					);

					$this->saveOptionValue(
						$sLanguage,
						$iSchoolId,
						'signatur_text',
						$aAdditional['school'][$iSchoolId][$sLanguage]['signatur_text']
					);
				}

//				$this->saveOptionValue(
//					$sLanguage,
//					$iSchoolId,
//					'attachments',
//					$aAdditional['school'][$iSchoolId][$sLanguage]['attachments']
//				);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		//Wichtig: Nicht die Werte nach dem Speichern aus dem Cache holen! -> Anstatt Cache reseten könnte
		//man auch beim Speichern die _aStaticElementValues mit den neuen Werten füllen
		$this->_aStaticElementValues = array();

		$this->_loadData($this->id);

		return $this;
	}

//	/**
//	 * See parent
//	 *
//	 * @param int $iDataID
//	 */
//	protected function _loadData($iDataID)
//	{
//		parent::_loadData($iDataID);
//
//		$oType = Ext_Thebing_Pdf_Template_Type::getInstance($this->template_type_id);
//
//		$this->_oTemplateType = $oType;
//
//	}

	protected function _loadAdditionalData() {

		$iDataID = (int) $this->id;
		
		// Load additional language data
		if(
			$iDataID > 0 &&
			$this->_bAdditionalLoaded === false
		) {
			
			$oType = Ext_Thebing_Pdf_Template_Type::getInstance($this->template_type_id);
			
			$aTypeElements = $oType->getElements();

			foreach((array)$this->languages as $sLanguage) {

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Standard fields

				$this->_aAdditional['default']['date'][$sLanguage] =
					$this->getStaticElementValue($sLanguage, 'date');
				$this->_aAdditional['default']['address'][$sLanguage] =
					$this->getStaticElementValue($sLanguage, 'address');
				$this->_aAdditional['default']['subject'][$sLanguage] =
					$this->getStaticElementValue($sLanguage, 'subject');
				$this->_aAdditional['default']['text1'][$sLanguage] =
					$this->getStaticElementValue($sLanguage, 'text1');
				$this->_aAdditional['default']['text2'][$sLanguage] =
					$this->getStaticElementValue($sLanguage, 'text2');

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Type elements fields

				foreach($aTypeElements as $oElement)
				{
					$this->_aAdditional['elements'][$oElement->id][$sLanguage] =
						$oElement->getValue($sLanguage, $this->id);
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // School fields

				foreach((array)$this->schools as $iSchoolId) {
					
					$oTempSchool = Ext_Thebing_School::getInstance($iSchoolId);

					$aFiles				= $oTempSchool->getSchoolFiles(1, $sLanguage, true);
					$aFilesSignatures	= $oTempSchool->getSchoolFiles(2, $sLanguage, true);
					//$aFilesAttachments	= $oTempSchool->getSchoolFiles(5, $sLanguage, true);

					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['filename'] = $this->getOptionValue($sLanguage, $oTempSchool->id, 'filename');

					foreach((array)$aFiles as $aFile) {
						if($this->getOptionValue($sLanguage, $oTempSchool->id, 'first_page_pdf_template', false) == $aFile['id']) {
							$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['first_page_pdf_template'] =
								$aFile['id'];
						}
						if($this->getOptionValue($sLanguage, $oTempSchool->id, 'additional_page_pdf_template', false) == $aFile['id']) {
							$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['additional_page_pdf_template'] =
								$aFile['id'];
						}
					}

					if($this->user_signature != 1) {

						foreach((array)$aFilesSignatures as $aFile) {
							if($this->getOptionValue($sLanguage, $oTempSchool->id, 'signatur_img', false) == $aFile['id'])
							{
								$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['signatur_img'] =
									$aFile['id'];
							}
						}

						$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['signatur_text'] =
							$this->getOptionValue($sLanguage, $oTempSchool->id, 'signatur_text');
					}

					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['attachments'] = array();

					$aTemp = (array)$this->getOptionValue($sLanguage, $oTempSchool->id, 'attachments', false);

//					foreach((array)$aFilesAttachments as $aFile)
//					{
//						if(in_array($aFile['id'], $aTemp))
//						{
//							$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['attachments'][] = (int)$aFile['id'];
//						}
//					}
				}
			}

			$this->_bAdditionalLoaded = true;

			$this->_aAdditionalOriginal = $this->_aAdditional;
		}

	}

	public function getTemplateType(): Ext_Thebing_Pdf_Template_Type {
		return $this->getJoinedObject('template_type');
	}

	public function canShowInquiryPositions()
	{
		$oTemplateType = $this->getTemplateType();
		if(is_object($oTemplateType) && $oTemplateType instanceof Ext_Thebing_Pdf_Template_Type)
		{
			$iInquiryPositions = (int)$oTemplateType->element_inquirypositions;

			if($iInquiryPositions > 0)
			{
				return true;
			}
		}

		return false;
	}

	//muss abgeleitet werden, weil noch additional Daten verglichen werden müssen
	public function getIntersectionData()
	{
		$aInterSectionData = (array)parent::getIntersectionData();

		$aAdditionalOriginal	= $this->_aAdditionalOriginal;
		$aAdditional			= $this->_aAdditional;

		$this->_checkIntersectionRec($aAdditional,$aAdditionalOriginal);

		$aDiff1 = (array)$this->_aIntersectAdditional;

		$this->_aIntersectAdditional = array();

		$this->_checkIntersectionRec($aAdditionalOriginal,$aAdditional);

		$aDiff2 = (array)$this->_aIntersectAdditional;

		if(!empty($aDiff1))
		{
			$aInterSectionData = array_merge($aInterSectionData,$aDiff1);
		}
		elseif(!empty($aDiff2))
		{
			$aInterSectionData = array_merge($aInterSectionData,$aDiff2);
		}

		return $aInterSectionData;
	}

	protected function _checkIntersectionRec($aArray,$aCompare,$mParentKey='')
	{
		foreach($aArray as $sKey => $mValue)
		{
			if(!empty($mParentKey))
			{
				$sIntersectKey = $mParentKey.'_'.$sKey;
			}
			else
			{
				$sIntersectKey = $sKey;
			}

			if(isset($aCompare[$sKey]))
			{
				$aCompareChild = $aCompare[$sKey];

				if(is_array($mValue))
				{
					$this->_checkIntersectionRec($mValue, $aCompareChild, $sIntersectKey);
				}
				else
				{
					if($mValue != $aCompare[$sKey])
					{
						$this->_aIntersectAdditional[$sIntersectKey] = $mValue;
					}
				}
			}
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getPlaceholderTabContent() {

		if($this->use_smarty) {
			$sHtml = Ext_TC_Pdf_Template_Gui2_Data::getPlaceholderTabContent($this->type);
		} else {
			$sHtml = Ext_Thebing_Pdf_Template_Gui2::getPlaceholderTabContent($this->type);
		}

		return $sHtml;
	}

	public function getSmartyPlaceholderTabContent() {
		return Ext_TC_Pdf_Template_Gui2_Data::getPlaceholderTabContent($this->type);
	}
	
}
