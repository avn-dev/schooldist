<?php

class Ext_Thebing_Agency_Placeholder extends Ext_Thebing_Placeholder {

	protected $_oAgencyContact;
	protected $_oAgency;
	protected $oAgencyCommentLoop = null;

	public $_oAgencyStaff;
	// Wird benötigt da die Inquiry auch diese Klasse benutzt
	// und der MasterContact evtl. abhängig vom Inquiry ist
	public $_oAgencyMasterContact = null;

	//alle selektierten 
	public $aInquiryIds = null;

	// Einzelne Inquiry-ID, die gesetzt wird, wenn Inquiry-Placeholder hier benutzt werden ohne Loop
	// Diese kann aber auch von außen gesetzt werden, wie beispielsweise in der Kommunikation
	public $iSingleInquiryId;

	// Cache für Inquiry Placeholder Klassen
	static protected $_aInquiryPlaceholderCache = array();

	public function __construct($iObjectId = 0, $sType = 'contact') { 

		if($sType == 'contact') {
			$this->_oAgencyContact	= Ext_Thebing_Agency_Contact::getInstance($iObjectId);
			$this->_oAgency			= Ext_Thebing_Agency::getInstance($this->_oAgencyContact->company_id);
			$this->_sSection		= false;
		} elseif($sType == 'agency') {
			$this->_oAgency			= Ext_Thebing_Agency::getInstance($iObjectId);
			$this->_sSection		= 'agencies';
			$this->_iFlexId			= $this->_oAgency->id;
		}

		parent::__construct();

	}

	// Schleifen
	protected function _helperReplaceVars($sText, $iOptionalId = 0) {

		// Standardschleifen die überall verfügbar sind
		$sText = parent::_helperReplaceVars($sText, $iOptionalId);

		$sText = preg_replace_callback('@\{start_loop_agency_staffmembers\}(.*)\{end_loop_agency_staffmembers\}@ims',array( $this, "_helperReplaceAgencyStaffLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_agency_notes(?:\|(.+?):(.+?))?\}(.*)\{end_loop_agency_notes\}@ims', [$this, 'helperReplaceAgencyCommentsLoop'], $sText);

		$sText = preg_replace_callback('@\{start_loop_manual_credit_notes(?:\|(.+?):(.+?))?\}(.*)\{end_loop_manual_credit_notes\}@ims', [$this, 'helperReplaceAgencyManualCreditnotesLoop'], $sText);

		$sText = preg_replace_callback('@\{start_loop_agency_complaints(?:\|(.+?):(.+?))?\}(.*)\{end_loop_agency_complaints\}@ims', [$this, 'helperReplaceAgencyComplaintsLoop'], $sText);

		$sText = preg_replace_callback('@\{start_loop_agency_specials\}(.*)\{end_loop_agency_specials\}@ims',array( $this, "helperReplaceAgencySpecialsLoop"),$sText);

		$sText = preg_replace_callback('@\{start_loop_students\}(.*)\{end_loop_students\}@ims',array( $this, "_helperReplaceStudentsLoop"),$sText);

		$sText = $this->_helperReplaceVars2($sText, $iOptionalId);

		return $sText;
	}

	public function replace($sText = '',$iPlaceholderLib = 1,$iOptionalId = 0){

		$this->_iPlaceholderLib = $iPlaceholderLib;
		$sReturn = $this->_helperReplaceVars($sText,$iOptionalId);

		return $sReturn;

	}

	// Wird nur abgeleitet um innerhalb der Schleife die Platzhalter zu ersetzen
	protected function _getPlaceholderValue($sField, $iOptionalParentId = 0, $aPlaceholder=array()) {

		$mValue = $this->_getReplaceValue($sField, $aPlaceholder);

		return $mValue;

	}

	protected function _helperReplaceAgencyStaffLoop($aText){

		$this->addMonitoringEntry('start_loop_agency_staffmembers');

		$sText = "";

		$oAgency = $this->_oAgency;

		$aContacts = array();

		if($oAgency){
			$aContacts = $oAgency->getContacts(false, true);
		}

		foreach((array)$aContacts as $oContact){
			$this->_oAgencyStaff = $oContact;

			$oPlaceholder = new Ext_Thebing_Agency_Member_Placeholder($oContact->id);
			$sTextTemp = $oPlaceholder->replace($aText[1]);

			$sText .= $this->_helperReplaceVars($sTextTemp);
			
		}

		// wieder reseten damit nicht schleifen platzhalter normal ersetzt werden
		$this->_oAgencyStaff = null;

		return $sText;

	}

	/**
	 * @param $aText
	 * @return string
	 */
	protected function helperReplaceAgencyCommentsLoop($aText) {
		$sText = '';

		$sPlaceholder = 'start_loop_agency_notes';
		if(!empty($aText[1])) {
			$sPlaceholder .= '|'.$aText[1].':'.$aText[2];
		}
		$this->addMonitoringEntry($sPlaceholder);

		if(!$this->_oAgency) {
			return $sText;
		}

		/** @var \TsCompany\Entity\Comment[] $aComments */
		$aComments = $this->_oAgency->getJoinTableObjects('comments');
		$aComments = $this->applyTimeframeModifier($aText, $aComments);

		foreach($aComments as $oComment) {
			$this->oAgencyCommentLoop = $oComment;
			$sText .= $this->_helperReplaceVars($aText[3]);
		}

		$this->oAgencyCommentLoop = null;

		return $sText;
	}

	/**
	 * @param $aText
	 * @return string
	 */
	protected function helperReplaceAgencyManualCreditnotesLoop($aText) {
		$sText = '';

		$sPlaceholder = 'start_loop_manual_credit_notes';
		if(!empty($aText[1])) {
			$sPlaceholder .= '|'.$aText[1].':'.$aText[2];
		}
		$this->addMonitoringEntry($sPlaceholder);

		if(!$this->_oAgency) {
			return $sText;
		}

		/** @var \TsCompany\Entity\Comment[] $aComments */
		$aManualCreditnotes = $this->_oAgency->getJoinedObjectChilds('creditnotes', true);
		$aManualCreditnotes = $this->applyTimeframeModifier($aText, $aManualCreditnotes);

		foreach($aManualCreditnotes as $oManualCreditnote) {
			$oReplace = new Ext_Thebing_Agency_Manual_Creditnote_Placeholder($oManualCreditnote);
			$sTextTmp = $oReplace->replace($aText[3]);
			$sText .= $this->_helperReplaceVars($sTextTmp);
		}

		return $sText;
	}

	/**
	 * @param $aText
	 * @return string
	 */
	protected function helperReplaceAgencyComplaintsLoop($aText) {
		$sText = '';

		$sPlaceholder = 'start_loop_agency_complaints';
		if(!empty($aText[1])) {
			$sPlaceholder .= '|'.$aText[1].':'.$aText[2];
		}
		$this->addMonitoringEntry($sPlaceholder);

		if(!$this->_oAgency) {
			return $sText;
		}

		$aComplaints = $this->_oAgency->getComplaints();
		$aComplaints = $this->applyTimeframeModifier($aText, $aComplaints);

		foreach($aComplaints as $oComplaint) {
			$oReplace = new \TsComplaints\Service\OldPlaceholder($oComplaint);
			$sTextTmp = $oReplace->replace($aText[3]);
			$sText .= $this->_helperReplaceVars($sTextTmp);
		}

		return $sText;
	}

	/**
	 * @param $aText
	 * @return string
	 */
	protected function helperReplaceAgencySpecialsLoop($aText) {
		$sText = '';

		$this->addMonitoringEntry('start_loop_agency_specials');

		if(!$this->_oAgency) {
			return $sText;
		}

		$aSpecials = $this->_oAgency->getSpecials();

		foreach($aSpecials as $oSpecial) {
			$oReplace = new Ext_Thebing_Special_Placeholder($oSpecial);
			$sTextTmp = $oReplace->replace($aText[1]);
			$sText .= $this->_helperReplaceVars($sTextTmp);
		}

		return $sText;
	}

	/**
	 * Modifier: |time_frame:P12M
	 *
	 * @param array $aText
	 * @param WDBasic[] $aItems
	 * @return Ext_Thebing_Basic[]
	 */
	protected function applyTimeframeModifier(array $aText, $aItems) {

		// Modifier: |time_frame:P12M
		if($aText[1] === 'time_frame') {
			try {
				$oDateInterval = new DateInterval($aText[2]);
				$aItems = array_filter($aItems, function($oComment) use($oDateInterval) {
					$dCreated = Core\Helper\DateTime::createFromLocalTimestamp($oComment->created);
					return $dCreated >= (new DateTime())->sub($oDateInterval);
				});

			} catch(Exception $e) {
				__pout($e);
			}
		}

		return $aItems;

	}

	protected function _helperReplaceStudentsLoop($aText){
		
		$sText = '';
		$aInquiryIds = $this->_getInquiryIds();

		$this->addMonitoringEntry('start_loop_students');

		foreach($aInquiryIds as $mInquiry)
		{			
			if(is_object($mInquiry)){
				$oInquiry = $mInquiry;
			}else{
				$oInquiry = Ext_TS_Inquiry::getInstance($mInquiry);
			}
			
			if($oInquiry->getAgency() !== $this->_oAgency){
				//kann passieren wenn $this->aInquiryIds !== null, dann sind es die selektierten Schüler
				continue;
			}
			
			$iInquiryId = (int)$oInquiry->id;
			$oInquiryPlaceHolder = $this->_getInquiryPlaceholderClass($iInquiryId);
			$sTextTemp = $oInquiryPlaceHolder->replace($aText[1]);
			
			$sText .= $this->_helperReplaceVars($sTextTemp);
		}

		return $sText;
	}

	/**
	 * Versucht irgendwie InquiryIds zu ermitteln
	 * Methode war zuvor Teil der _helperReplaceStudentsLoop()
	 *
	 * @return array
	 */
	protected function _getInquiryIds() {
		if($this->aInquiryIds !== null) {
			$aInquiryIds = (array)$this->aInquiryIds;
		} elseif(is_object($this->_oAgencyContact) && $this->_oAgencyContact instanceof Ext_Thebing_Agency_Contact){
			$aInquiryIds = (array)$this->_oAgencyContact->getInquiries();
		} else{
			$aInquiryIds = (array)$this->_oAgency->getInquiries();
		}

		return $aInquiryIds;
	}

	protected function _getInquiryPlaceholderClass($iInquiryId) {

		if(!isset(static::$_aInquiryPlaceholderCache[$iInquiryId])) {
			static::$_aInquiryPlaceholderCache[$iInquiryId] = new Ext_Thebing_Inquiry_Placeholder($iInquiryId);
		}
		return static::$_aInquiryPlaceholderCache[$iInquiryId];

	}

	/**
	 * Get the list of available placeholders
	 *
	 * @param string $sType
	 * @return array
	 */
	public function getPlaceholders($sType = '')
	{
		$aPlaceholders = array(
			array(
				'section'		=> L10N::t('Agenturen', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
				'placeholders'	=> array(
					'agency'								=> L10N::t('Agentur: Name', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_number'							=> L10N::t('Agentur: Nummer', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_abbreviation'					=> L10N::t('Agentur: Abkürzung', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_address'						=> L10N::t('Agentur: Addresse', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_zip'							=> L10N::t('Agentur: Zip', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_city'							=> L10N::t('Agentur: Stadt', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_country'						=> L10N::t('Agentur: Land', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_groups'							=> L10N::t('Agentur: Gruppe', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_category'						=> L10N::t('Agentur: Kategorie', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_tax_number'						=> L10N::t('Agentur: Steuernummer', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					//'agency_nickname' => L10N::t('Benutzername für das Agentur-Portal für diese Agentur', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_activation_key' => L10N::t('Aktivierungsschlüssel für Agentur-Portal. Beispiel Link: https://{example-school-url}?&task=changePassword&activation_key={agency_activation_key}', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					//'agency_person'						=> L10N::t('Hauptansprechpartner', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					//'agency_user_firstname'				=> L10N::t('Hauptansprechpartner Vorname', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					//'agency_user_surname'					=> L10N::t('Hauptansprechpartner Nachname', Ext_Thebing_Agency_Gui2::getDescriptionPart())
					'agency_state'							=> L10N::t('Agentur: Staat', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_note'							=> L10N::t('Agentur: Kommentar', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_payment_terms'					=> L10N::t('Agentur: Bezahlinformation', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_current_commission_category' => L10N::t('Agentur: Aktuelle Provisionskategorie', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_current_payment_category' => L10N::t('Agentur: Aktuelle Bezahlkategorie', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_current_cancellation_category' => L10N::t('Agentur: Aktuelle Stornokategorie', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'start_loop_students}.....{end_loop_students' => L10N::t('Durchläuft alle Kunden der Agentur', Ext_Thebing_Agency_Gui2::getDescriptionPart())
				)
			),
			array(
				'section'		=> L10N::t('Agenturansprechpartner', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
				'placeholders'	=> array(
					'agency_staffmember_salutation'			=> L10N::t('Anrede', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_firstname'			=> L10N::t('Vorname', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_surname'			=> L10N::t('Name', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_email'				=> L10N::t('E-Mail', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_phone'				=> L10N::t('Telefon', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_fax'				=> L10N::t('Fax', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_skype'				=> L10N::t('Skype', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_department'			=> L10N::t('Department', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_staffmember_responsability'		=> L10N::t('Zuständigkeit', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'start_loop_agency_staffmembers}.....{end_loop_agency_staffmembers' => L10N::t('Durchläuft alle Agenturansprechpartner', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					//'start_loop_students}.....{end_loop_students' => L10N::t('Durchläuft alle Kunden der Agenturansprechpartner', Ext_Thebing_Agency_Gui2::getDescriptionPart())
				)
			),
			array(
				'section'		=> L10N::t('Agentur Bankinformationen', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
				'placeholders'	=> array(
					'agency_account_holder'					=> L10N::t('Kontoinhaber', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_bank_name'						=> L10N::t('Name der Bank', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_bank_code'						=> L10N::t('BLZ', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_account_number'					=> L10N::t('Kontonummer', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_swift'							=> L10N::t('SWIFT', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_iban'							=> L10N::t('IBAN', Ext_Thebing_Agency_Gui2::getDescriptionPart())
			)
			),
			[
				'section' => L10N::t('Agentur-Kommentare', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
				'placeholders' => [
					'agency_note_title' => L10N::t('Titel', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_note_subject' => L10N::t('Betreff', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_note_type_of_contact' => L10N::t('Kontaktart', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_note_text' => L10N::t('Text', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_note_date' => L10N::t('Datum', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'start_loop_agency_notes}.....{end_loop_agency_notes' => L10N::t('Durchläuft alle Kommentare der Agentur (eingrenzbar)', Ext_Thebing_Agency_Gui2::getDescriptionPart())
				]
			]
		);

		$oManualCreditnotePlaceholder = new Ext_Thebing_Agency_Manual_Creditnote_Placeholder();
		$aManualCreditnotesPlaceholder = $oManualCreditnotePlaceholder->getOwnPlaceholders();
		$aManualCreditnotesPlaceholder[0]['placeholders']['start_loop_manual_credit_notes}.....{end_loop_manual_credit_notes'] = L10N::t('Durchläuft alle manuellen Gutschriften der Agentur (eingrenzbar)', Ext_Thebing_Agency_Gui2::getDescriptionPart());
		$aPlaceholders = array_merge($aPlaceholders, $aManualCreditnotesPlaceholder);

		$oSpecialPlaceholder = new Ext_Thebing_Special_Placeholder();
		$aSpecialPlaceholders = $oSpecialPlaceholder->getPlaceholders();
		$aSpecialPlaceholders[0]['placeholders']['start_loop_agency_specials}.....{end_loop_agency_specials'] = L10N::t('Durchläuft alle Angebote der Agentur', Ext_Thebing_Agency_Gui2::getDescriptionPart());
		$aPlaceholders = array_merge($aPlaceholders, $aSpecialPlaceholders);

		$oComplaintsPlaceholder = new \TsComplaints\Service\OldPlaceholder();
		$aComplaintsPlaceholder = $oComplaintsPlaceholder->getPlaceholders();
		$aComplaintsPlaceholder[0]['placeholders']['start_loop_agency_complaints}.....{end_loop_agency_complaints'] = L10N::t('Durchläuft alle Beschwerden der Schüler der Agentur', Ext_Thebing_Agency_Gui2::getDescriptionPart());
		$aPlaceholders = array_merge($aPlaceholders, $aComplaintsPlaceholder);

		return $aPlaceholders;
	}


	protected function _getReplaceValue($sField, array $aPlaceholder) {

		$mValue			= false;
		$oMasterContact = null;
		$oAgency		= $this->_oAgency;

		$oAgencyContact	= $this->_oAgencyContact;

		// MasterContact bestimmen
		if(
			$this->_oAgencyMasterContact !== null
		){
			$oMasterContact = $this->_oAgencyMasterContact;

		}elseif(is_object($oAgency)){
			$oMasterContact = $oAgency->getMasterContact();
		}

		switch ($sField) {

			case 'agency':
				if(is_object($oAgency)) {
					$mValue = $oAgency->ext_1;
				}
				break;
			case 'agency_groups':
				if(is_object($oAgency)) {
					$aGroups = $oAgency->getJoinTableObjects('groups');
					
					foreach($aGroups as $oGroup) {
						$mValue .= ', '. $oGroup->getName();
					}
					$mValue = ltrim($mValue, ', ');
				}
				break;
			case 'agency_category':
				if(is_object($oAgency)) {
					$mValue = $oAgency->getCategoryName();
				}
				break;
			case 'agency_number':
				if(is_object($oAgency)) {
					$mValue = $oAgency->getNumber();
				}
				break;
			case 'agency_abbreviation':
				if(is_object($oAgency)) {
					$mValue = $oAgency->ext_2;
				}
				break;
			case 'agency_address':
				if(is_object($oAgency)) {
					if($oAgency->ext_35 != '' && $oAgency->ext_3 != '') {
						// 2 Zeilige Adresse ersetzen
						$mValue = $oAgency->ext_3 . "\r\n" . $oAgency->ext_35;
					}elseif($oAgency->ext_3 != '') {
						// 1 Zeilige Adresse ersetzen
						$mValue = $oAgency->ext_3;
					}
				}
				break;
			case 'agency_zip':
				if(is_object($oAgency)) {
					$mValue = $oAgency->ext_4;
				}
				break;
			case 'agency_city':
				if(is_object($oAgency)) {
					$mValue = $oAgency->ext_5;
				}
				break;
			case 'agency_country':
				if(is_object($oAgency)) {
					$aCountry = Ext_Thebing_Data::getCountryList(true, false, $this->sTemplateLanguage);
					$mValue = $aCountry[$oAgency->ext_6];
				}
				break;
			case 'agency_tax_number':
				if(is_object($oAgency)) {
					$mValue = $oAgency->ext_24;
				}
				break;
			case 'agency_person':
				if( $oMasterContact ) {
					$mValue = $oMasterContact->name;
				}
				break;
			case 'agency_user_firstname':
				if( is_object($oAgencyContact) ) {
					$mValue = $oAgencyContact->firstname;
				}elseif(is_object($oMasterContact)) {
					$mValue = $oMasterContact->firstname;
					}
				break;
			case 'agency_user_surname':
				if( is_object($oAgencyContact) ) {
					$mValue = $oAgencyContact->lastname;
				}elseif(is_object($oMasterContact)) {
					$mValue = $oMasterContact->lastname;
					}
				break;
			case 'agency_state':
				if(is_object($oAgency)){
					$mValue = $oAgency->state;
				}
				break;
			case 'agency_note':
				if(is_object($oAgency)){
					$mValue = $oAgency->comment;
				}
				break;
			case 'agency_payment_terms':
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					$aPaymentMethods = Ext_Thebing_Inquiry_Amount::getPaymentMethods($this->getLanguageObject());
					$mValue = $aPaymentMethods[$oAgency->ext_26];
				}
				break;
			case 'agency_nickname':
				$mValue = $oAgency->nickname;
				break;
			case 'agency_activation_key':

				// Ablaufzeit setzen und Aktivierungscode generieren
				$expired = new DateTime();
				$expired->modify('+2 hours');
				$sActivateCode = \Util::generateRandomString(18);

				if($this->_oAgencyContact !== null) {
                    $iContactId = $this->_oAgencyContact->id;
                } elseif($this->_oAgencyStaff !== null) {
                    $iContactId = $this->_oAgencyStaff->id;
                } else {
				    //throw new RuntimeException(L10N::t('Placeholder {agency_activation_key} is not allowed in agency list without staff loop!', 'Thebing » Placeholder'));
                    return '';
                }

				// Daten der Agentur übergeben und Objekt speichern
                $aKeys = [
                    'agency_id' => $oAgency->id,
                    'contact_id' => $iContactId
                ];
                $aData = [
                    [
                        'activation_code' => $sActivateCode,
                        'expired' => $expired->format('Y-m-d H:i:s'),
                    ]
                ];
                DB::updateJoinData('ts_agencies_activation_codes', $aKeys, $aData);

				return $sActivateCode;

				break;
			case 'agency_staffmember_salutation':
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				) {
					$iGender = null;
					if($this->_oAgencyStaff != null) {
						$iGender = $this->_oAgencyStaff->gender;
					}elseif(is_object($oMasterContact)){
						$iGender = $oMasterContact->gender;
					}
					
					if(!empty($iGender)) {
						$mValue = Ext_TC_Contact::getSalutationForFrontend($iGender, $this->getLanguageObject());
					}
					
				} else {
					$mValue = '';
				}
				break;
			case 'agency_staffmember_firstname':
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->firstname;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->firstname;
					}
				} else {
					$mValue = '';
				}
				break;
			case 'agency_staffmember_surname':
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->lastname;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->lastname;
						}
				} else {
					$mValue = '';
				}
				break;
			case 'agency_staffmember_email':
				$mValue = '';
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->email;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->email;
					}
				}
				break;
			case 'agency_staffmember_phone':
				$mValue = '';
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->phone;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->phone;
					}
				}
				break;
			case 'agency_staffmember_fax':
				$mValue = '';
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->fax;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->fax;
					}
				}
				break;
			case 'agency_staffmember_skype':
				$mValue = '';
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){
					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->skype;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->skype;
					}
				}
				break;
			case 'agency_staffmember_department':
				$mValue = '';
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){

					if($this->_oAgencyStaff != null){
						$mValue = $this->_oAgencyStaff->group;
					}elseif(is_object($oMasterContact)){
						$mValue = $oMasterContact->group;
					}
				}
				break;
			case 'agency_staffmember_responsability':
				if(
					is_object($oAgency) &&
					$oAgency->id > 0
				){

					$aFags = Ext_Thebing_Agency_Contact::getFlags($this->getLanguageObject());

					$sBack = '';
					if($this->_oAgencyStaff != null){

						if($this->_oAgencyStaff->transfer == 1){
							$sBack .= $aFags['transfer']. ' ';
						}
						if($this->_oAgencyStaff->accommodation == 1){
							$sBack .= $aFags['accommodation']. ' ';
						}
						if($this->_oAgencyStaff->reminder == 1){
							$sBack .= $aFags['reminder'];
						}
						$mValue = $sBack;
					}else{
						if(is_object($oMasterContact)){

							if($oMasterContact->transfer == 1){
								$sBack .= $aFags['transfer']. ' ';
							}
							if($oMasterContact->accommodation == 1){
								$sBack .= $aFags['accommodation'] . ' ';
							}
							if($oMasterContact->reminder == 1){
								$sBack .= $aFags['reminder'];
							}
							$mValue = $sBack;
						}
					}
				} else {
					$mValue = '';
				}
				break;

			case 'agency_account_holder':
				if(is_object($oAgency)){
					$mValue = $oAgency->ext_12;
				}
				break;
			case 'agency_bank_name':
				if(is_object($oAgency)){
					$mValue = $oAgency->ext_13;
				}
				break;
			case 'agency_bank_code':
				if(is_object($oAgency)){
					$mValue = $oAgency->ext_14;
				}
				break;
			case 'agency_account_number':
				if(is_object($oAgency)){
					$mValue = $oAgency->ext_16;
				}
				break;
			case 'agency_swift':
				if(is_object($oAgency)){
					$mValue = $oAgency->ext_15;
				}
				break;
			case 'agency_iban':
				if(is_object($oAgency)){
					$mValue = $oAgency->ext_17;
				}
				break;
			case 'agency_current_commission_category':
				if($oAgency instanceof Ext_Thebing_Agency) {
					$oCategory = $oAgency->getCurrentCommissionCategory();
					if($oCategory instanceof Ext_Thebing_Provision_Group) {
						return $oCategory->name;
					}
				}
				break;
			case 'agency_current_payment_category':
				if($oAgency instanceof Ext_Thebing_Agency) {
					$oCategory = $oAgency->getCurrentPaymentCategory();
					if($oCategory instanceof Ext_TS_Payment_Condition) {
						return $oCategory->name;
					}
				}
				break;
			case 'agency_current_cancellation_category':
				if($oAgency instanceof Ext_Thebing_Agency) {
					$oCategory = $oAgency->getCurrentCancellationCategory();
					if($oCategory instanceof Ext_Thebing_Cancellation_Group) {
						return $oCategory->name;
					}
				}
				break;
//			case 'agency_open_payments':
//				// Tabelle mit einer Übersicht der zahlungen
//				$aInquiryIds = (array)$this->aInquiryIds;
//				if(
//					empty($aInquiryIds)
//				){
//					$aInquiries		= (array)$oAgency->getInquiries();
//					$aInquiryIds	= array_keys($aInquiries);
//				}
//				$sAgencyPayment = Ext_TS_Inquiry::getAgencyOpenPayment($aInquiryIds, false, $oAgency->id);
//				return $sAgencyPayment;
//				break;
			case 'agency_note_title':
			case 'agency_comment_title':
				if($this->oAgencyCommentLoop instanceof \TsCompany\Entity\Comment) {
					return $this->oAgencyCommentLoop->title;
				}
				break;
			case 'agency_note_text':
			case 'agency_comment_text':
				if($this->oAgencyCommentLoop instanceof \TsCompany\Entity\Comment) {
					return nl2br($this->oAgencyCommentLoop->text);
				}
				break;
			case 'agency_note_subject':
			case 'agency_comment_subject':
				if($this->oAgencyCommentLoop instanceof \TsCompany\Entity\Comment) {
					$oSubject = Ext_Thebing_Marketing_Subject::getInstance($this->oAgencyCommentLoop->subject_id);
					return $oSubject->title;
				}
				break;
			case 'agency_note_type_of_contact':
			case 'agency_comment_type_of_contact':
				if($this->oAgencyCommentLoop instanceof \TsCompany\Entity\Comment) {
					$oTypeOfContact = Ext_Thebing_Marketing_Activity::getInstance($this->oAgencyCommentLoop->activity_id);
					return $oTypeOfContact->title;
				}
				break;
			case 'agency_note_date':
			case 'agency_comment_date':
				if($this->oAgencyCommentLoop instanceof \TsCompany\Entity\Comment) {
					return (new Ext_Thebing_Gui2_Format_Date())->format($this->oAgencyCommentLoop->created);
				}
				break;
			default:

				$mValue = '';
				$sFormat = 'date';

				if(is_object($oAgency)) {
					// Hier müssen jetzt ggf erst die Flex Platzhalter der Agenturmitarbeiter ersetzt werden
					// Sollte das hier geändert werden müssen, muss die Kommunikation mit einzelnen Mitarbeitern getestet werden!
					if($oAgencyContact !== null) {
						$aValue = Ext_TC_Flexibility::getPlaceholderValue($sField, $oAgencyContact->id, true, $oAgency->getLanguage());
						$mValue = $this->convertFlexPlaceholderInfo($aValue, $sFormat);
					}

					// Agenturen können auch flexible Platzhalter haben, nicht nur Agenturkontakte…
					if(empty($mValue)) {
						$aValue = Ext_TC_Flexibility::getPlaceholderValue($sField, $oAgency->id, true, $oAgency->getLanguage());
						$mValue = $this->convertFlexPlaceholderInfo($aValue,$sFormat);
					}
				}

				// Unsaubere Lösung für eine unsaubere Klasse: Versuchen, Inquiry-Placeholder zu ersetzen.
				// Diese werden eigentlich nur dann ersetzt, wenn sie in einem Loop stehen.
				// Wenn dieser Fall hier eintritt, wurde kein Loop benutzt.
				if(empty($mValue)) {

					// Kann null (Definition), false (reset) oder eine Ganzzahl (Cache) sein
					if($this->iSingleInquiryId) {
						$oInquiryPlaceholder = $this->_getInquiryPlaceholderClass($this->iSingleInquiryId);
						$mValue = $oInquiryPlaceholder->replace($aPlaceholder['code']);
					} else {
						$mValue = parent::_getReplaceValue($sField, $aPlaceholder);
					}

				} else {
					$mValue = parent::_getReplaceValue($sField, $aPlaceholder);
				}

				break;

		}

		return $mValue;

	}


	public function displayPlaceholderTable($iCount = 1, $aFilter = array(), $sType = '')
	{
		$aPlaceholders = $this->getPlaceholders();

		$aFlexPlaceholders = array();
		foreach((array)$this->_aFlexFieldLabels as $sPlaceholder=>$sLabel) {

			$aFlexPlaceholders[$sPlaceholder] = $sLabel;

}
		
		// Flex der Mitarbeiter
		$aFlexFields = Ext_TC_Flexibility::getSectionFieldData(array('agencies_users'));
		foreach((array)$aFlexFields as $aField) {
			if(!empty($aField['placeholder'])) {
				$aFlexPlaceholders[$aField['placeholder']] = $aField['title'];
			}
		}


		if(!empty($aFlexPlaceholders)) {
			$aPlaceholders[] = array(
				'section'=>L10N::t('Individuelle Felder', 'Thebing » Placeholder'),
				'placeholders'=>$aFlexPlaceholders
			);
		}

		$sHtml = self::printPlaceholderList($aPlaceholders);

		return $sHtml;
	}

}
