<?php

class Ext_TS_Inquiry_Index_Gui2_Data extends Ext_Thebing_Inquiry_Gui2 {

	use \Tc\Traits\Gui2\Import;

	protected $createInvoiceItems = [];
	protected $cancelBookingItems = [];

	/**
	 * {@inheritdoc}
	 */
	public function __construct(&$oGui) {
		global $_VARS;

		parent::__construct($oGui);

		$iInbox = (int)($_VARS['inbox_id'] ?? 0);
		$oGui->setOption('inbox_id', $iInbox);

		// Recht der GUI manipulieren
		if($iInbox > 0) {
			$oGui->access .= '_'.$iInbox;
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function switchAjaxRequest($_VARS) {

		switch($_VARS['action'] ?? null) {
			case 'revokeChangedStatus':
				// Der Verändert-Status wird hier entfernt
				foreach($_VARS['id'] as $iSelectedId) {
					DB::updateData('kolumbus_inquiries_documents_versions_items_changes', array('active' => 0), '`inquiry_id` = '.$iSelectedId);
					Ext_Gui2_Index_Stack::add('ts_inquiry', $iSelectedId, 0);
					//Ext_Gui2_Index_Stack::executeCache();
				}
				echo json_encode([
					'action' => 'showSuccessAndReloadTable',
					'success' => 1,
					'message' => L10N::t('Die Veränderung wurde rückgängig gemacht!', self::TRANSLATION_PATH),
				]);
				break;
			default:
				parent::switchAjaxRequest($_VARS);
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public static function getIndexWhere() {

	}
	
	public static function getSudentlistWhere() {
		
		$aClientSettings	= array();
		$iSetting			= (int) Ext_Thebing_System::getConfig('show_customer_without_invoice');

		// 0 = Schüler mit Proforma anzeigen
		// 1 = Schüler ohne Proforma / Rechnung anzeigen
		// 2 = Schüler mit Rechnung anzeigen
		if($iSetting === 0) {
			$aClientSettings = array('has_proforma_or_invoice_data' => '1');
		}elseif($iSetting === 1) {
			// do nothing
		} else if($iSetting === 2) {
			$aClientSettings = array('has_invoice_data' => '1');
		}

		$aClientSettings['invoice_status'] = 'not_cancelled';
		$aSchoolWhere = self::getListWhere();
		
		$aReturn = array_merge($aClientSettings, $aSchoolWhere);
		
		return $aReturn;
	}
	
	public static function getAgencyPaymentWhere() {
		$aReturn = self::getSudentlistWhere();
		unset($aReturn['invoice_status']);
		return $aReturn;
	}	
	
	public static function getStudentVisumlistWhere() {
		$aReturn = static::getSudentlistWhere();
		$aReturn['has_visum_original'] = 1;
		
		return $aReturn;
	}

	public static function getStudentSponsoringlistWhere() {
		$aReturn = static::getSudentlistWhere();
		$aReturn['sponsored'] = true;
		return $aReturn;
	}

	
	public static function getStudentCheckedinListWhere() {

		$oCheckinQuery = new \Elastica\Query\Exists('checkin_original');
		
		$oCheckoutQuery = new \Elastica\Query\BoolQuery();
		$oCheckoutQuery->addMustNot(new \Elastica\Query\Exists('checkout_original'));
		
		$aReturn = static::getSudentlistWhere();
		$aReturn['checkin'] = $oCheckinQuery;
		$aReturn['checkout'] = $oCheckoutQuery;

		return $aReturn;
	}
	
	public static function getIndexOrderby(Ext_Gui2 $oGui=null){

		if($oGui->set === 'inquiry') {
			return array('booking_created_filter' => 'DESC');
		}
		
		return array('created_original' => 'DESC');
	}
	
	public static function getListOrderby(Ext_Gui2 $oGui=null) {
		return array('created' => 'DESC');
	}
	
	public static function getListWhere(Ext_Gui2 $oGui=null) {
		$aWhere	= array();

		$bIsAllSchools = Ext_Thebing_System::isAllSchools();
		if(!$bIsAllSchools){
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId = (int)$oSchool->id;
			$aWhere['school_id'] = (string)$iSchoolId;
		} else {
			
			$oAccess = Access::getInstance();

			if(
				!$oAccess instanceof Access_Backend ||
				$oAccess->checkValidAccess() !== true
			) {

				$aSchools = Ext_Thebing_Client::getSchoolList(true);
				
			} else {
				
				$oClient				= Ext_Thebing_System::getClient();
				$aSchools				= $oClient->getSchoolListByAccess(true, false, true);
				
			}
			
			$aSchoolIds				= array_keys($aSchools); 
			$aWhere['school_id']	= array('IN', $aSchoolIds);
			
		}

		if($oGui instanceof Ext_Gui2) {
			/*
			 * Die ID vorher in eine Variable holen da es sonst einen Fehler gibt (nicht mehr ab PHP 5.5)
			 *
			 * !empty($oGui->getOption('inbox_id')) => Fatal error: Can't use method return value in write context
			 *
			 * http://stackoverflow.com/questions/1075534/cant-use-method-return-value-in-write-context
			 */
			$mInboxId = $oGui->getOption('inbox_id');
			if(!empty($mInboxId)) {
				$oInbox = Ext_Thebing_Client_Inbox::getInstance($mInboxId);
				$aWhere['inbox'] = $oInbox->short;
			}
		}

		$aWhere['contact_id'] = '_exists_:contact_id';

		$aWhere['type'] = 'booking';

		return $aWhere;
	}
	
	public static function getInvoiceProformaStatusOptions(){
		$aStatis	= array(
            'xNullx'	=>	L10N::t('Alle Dokumente'),
            'invoice'	=>	L10N::t('nur Rechnungen'),
            'proforma'	=>	L10N::t('nur Proforma')
		);
		return $aStatis;
	}
	
	public static function getReferrerOptions() {
		if(Ext_Thebing_System::isAllSchools()) {
			return \Ext_TS_Referrer::getReferrers(true);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			return \Ext_TS_Referrer::getReferrers(true, $oSchool->id);
		}
	}

	public static function getCustomerStatusOptions() {
		if(Ext_Thebing_System::isAllSchools()) {
			return \Ext_Thebing_Marketing_Studentstatus::getList(true);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			return \Ext_Thebing_Marketing_Studentstatus::getList(true, $oSchool->id);
		}
	}
	
	public static function getSpecialOptions(){
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		if($oSchool){
			$aOptions = $oSchool->getSpecials(true);
		} else {
			$oTemp = new Ext_Thebing_School_Special();
			$aOptions = $oTemp->getArrayList(true);
		}
		return $aOptions;
	}

	public static function getCreatorOptions(Ext_Gui2 $oGui = null): array {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$oDummy = null;
		$oFormat = new Ext_Gui2_View_Format_Name();

		$sWhere = "";

		if(
			$oSchool instanceof Ext_Thebing_School && 
			$oSchool->exist()
		) {
			$sWhere = " AND `ts_ij`.`school_id` = :school_id ";
		}

		if ($oGui) {
			$sWhere = " AND `ts_i`.`type` & :type ";
		}

		$sSql = "
			SELECT
				`su`.`id`,
				`su`.`firstname`,
				`su`.`lastname`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    `ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`active` = 1 INNER JOIN
				`system_user` `su` ON
					`su`.`id` = `ts_i`.`creator_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`creator_id` != 0 AND (
				    `su`.`active` = 1 OR
				    `su`.`lastlogin` > :last_login
				)
				{$sWhere}
			GROUP BY
				`su`.`id`
		";

		$aUsers = collect(DB::getQueryRowsAssoc($sSql, [
			'type' => $oGui?->set === 'enquiry' ? Ext_TS_Inquiry::TYPE_ENQUIRY : Ext_TS_Inquiry::TYPE_BOOKING,
			'school_id' => $oSchool ? $oSchool->id : null,
			'last_login' => \Carbon\Carbon::now()->subYears(3)->format('Y-m-d')
		]));

		// Eigenen User immer hinzufügen, da die Filter nicht nachgeladen werden
		$oUser = System::getCurrentUser();
		if ($oUser && !$aUsers->has($oUser->id)) {
			$aUsers->put($oUser->id, ['firstname' => $oUser->firstname, 'lastname' => $oUser->lastname]);
		}

		$aUsers = $aUsers->map(function (array $aUser) use ($oFormat, $oDummy) {
			return $oFormat->format(null, $oDummy, $aUser);
		});

		$aUsers = $aUsers->sort();

		$aUsers->put(-1, L10N::t('Formular', self::TRANSLATION_PATH));

		System::wd()->executeHook('ts_inquiry_get_creator_options', $aUsers);

		return $aUsers->toArray();

	}

	/**
	 * YML-Column: invoice_status
	 * @see Ext_TS_Inquiry::getInvoiceStatus()
	 * @param Ext_Thebing_Gui2 $oGui
	 * @return array
	 */
	public static function getInvoiceStatusOptions(Ext_Thebing_Gui2 $oGui) {

		$oGuiData = $oGui->getDataObject();

		if ($oGuiData instanceof Ext_TS_Enquiry_Gui2) {
			return [
				'offer_created' => L10N::t('Angebot erstellt', self::TRANSLATION_PATH),
				'offer_not_created' =>  L10N::t('Angebot nicht erstellt', self::TRANSLATION_PATH)
			];
		}

		$aOptions = [
			'invoice_not_created' => L10N::t('Dokument nicht erstellt', self::TRANSLATION_PATH),
			'invoice_created' => L10N::t('Dokument erstellt', self::TRANSLATION_PATH),
			'invoice_outdated' => L10N::t('Dokument nicht aktuell', self::TRANSLATION_PATH),
			'invoice_uptodate' => L10N::t('Dokument aktuell', self::TRANSLATION_PATH),
			'not_cancelled' => L10N::t('Rechnung nicht storniert', self::TRANSLATION_PATH),
			'cancelled' => L10N::t('Rechnung storniert', self::TRANSLATION_PATH)
		];

		if ($oGuiData instanceof Ext_Thebing_Inquiry_Gui2_Group) {
			unset($aOptions['invoice_outdated']);
			unset($aOptions['invoice_uptodate']);
		}

		return $aOptions;

	}
	
	public static function getBookingTypeOptions(){
		$aOptions = array(
			0 => L10N::t('Direktbuchungen'),
			1 => L10N::t('Agenturbuchungen')
		);
		return $aOptions;
	}
	
	public static function getPaymentTypeOptions(){
		$aOptions = array(
			'open' => L10N::t('offene Zahlungen'),
			'due' => L10N::t('fällige Zahlungen'),
			'prepayed' => L10N::t('Teilzahlung eingegangen'),
			'payed' => L10N::t('komplett bezahlt'),
			'open_and_overpayed' => L10N::t('Offene Zahlungen inkl. Überzahlungen')
		);
		return $aOptions;
	}
	
	public static function getVisumOptions(){
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aOptions =(array) $oSchool->getVisumList();
		
//		$aOptions[-1] = L10N::t('Kein Visum');

		ksort($aOptions);
		
		return $aOptions;
	}
	
	public static function getAccommodationCategories() {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		if(!$oSchool){
			$oSchool	= Ext_Thebing_Client::getFirstSchool();
		}
		$aCateories = $oSchool->getAccommodationCategoriesList(true);
		
		return $aCateories;
	}
	
	public static function getCourseCategories() {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if($oSchool->exist()) {

			$aCourseCategories = $oSchool->getCourseCategoriesList('select');

			return $aCourseCategories;
		}

		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aCourseCategories = $oClient->getCourseCategories();

		return $aCourseCategories;
	}

    public static function getCurrencyOptions(){
        $oSchool = Ext_Thebing_School::getSchoolFromSession();
        $aCurrencies = $oSchool->getSchoolCurrencyList();
        return $aCurrencies;
    }

	public static function getCourselanguageFilterOptions(\Ext_Gui2 $gui2)
	{
		if (Ext_Thebing_System::isAllSchools()) {
			$schools = \Ext_Thebing_Client::getStaticSchoolListByAccess(false, false, true);
		} else {
			$schools = [Ext_Thebing_School::getSchoolFromSession()];
		}

		return \Ext_Thebing_Tuition_LevelGroup::getSelectOptions($gui2, null, $schools);
	}

	/**
	 * @see \Ext_Thebing_Inquiry_Gui2::getEditDialogHTML()
	 * @see Ext_Thebing_Inquiry_Gui2_Html
	 *
	 * @param string $sView
	 * @param Ext_Thebing_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog($sView, $oGui){

		Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription = $oGui->gui_description;

		$oDialog = $oGui->createDialog();
		$oDialog->width = 1300;
		
		if(Access::getInstance() === null) {
			return $oDialog;
		}
		
		if(Access::getInstance()->hasRight(['core_customise_dialogue', 'edit'])) {
			#$oDialog->setOption('settings', true);
		}
		
		$oClient						= Ext_Thebing_System::getClient();

		// daten
		$oSchool						= Ext_Thebing_Client::getFirstSchool();
		$sSchoolFileDir					= $oSchool->getSchoolFileDir(false);

		if(!$oSchool){
			__pout('no School found');
			die();
		}
		$sInterfaceLanguage	= Ext_TC_System::getInterfaceLanguage();
		$oCurrency 						= new Ext_Thebing_Currency_Util($oSchool->id);
		$aVisumStatus 					= $oSchool->getVisumList($oGui->gui_description);
		$aTransfer						= Ext_Thebing_Data::getTransferList();
		
		$aReferer 						= $oSchool->getRefererList();
		$aReferer						= Ext_Thebing_Util::addEmptyItem($aReferer);
		$aCustomerStatus				= $oSchool->getCustomerStatusList();
		$aCustomerStatus = Ext_Thebing_Util::addEmptyItem($aCustomerStatus);
		$aCurrencys						= $oCurrency->getCurrencyList(2);
		$aPaymentMethods				= Ext_Thebing_Inquiry_Amount::getPaymentMethods();
		$aPartialInvoicePaymentConditions = Ext_TS_Payment_Condition::getSelectOptionsForPartialInvoice();

		$aCorrespondenceLanguages		= Ext_Thebing_Data::getCorrespondenceLanguages(true, $sInterfaceLanguage);
		$aLangs							= Ext_Thebing_Data::getLanguageSkills(true, $sInterfaceLanguage);
		$aNationalities					= Ext_Thebing_Nationality::getNationalities(true, $sInterfaceLanguage, 0);
		$aNationalities					= Ext_Thebing_Util::addEmptyItem($aNationalities, Ext_Thebing_L10N::getEmptySelectLabel('nationalities'));

		// In der "all schools" Ansicht soll im Label keine Datumsformatierung mehr stehen.
		$sFormat = '';
		if(!Ext_Thebing_System::isAllSchools()) {
			$sFormat						= Ext_Thebing_Format::getFormat();
			$sFormat						= str_replace(array('%d', '%m', '%Y'), array('DD','MM','YYYY'), $sFormat);
		}

		$aGender						= Ext_Thebing_Util::getGenders(true, $oGui->t('kein Geschlecht'));
		$aGroups						= $oSchool->getAllGroups(true); 
		$aGroups						= Ext_Thebing_Util::addEmptyItem($aGroups);
		$aSchoolList					= $oClient->getSchoolListByAccess(true, false, true);
		$aCountries						= Ext_Thebing_Data::getCountryList(true, true);
		

		// DIalog
		$oDialog->save_as_new_button = false;
		$oDialog->save_bar_options = false;

		$oTabPersonalData				= $oDialog->createTab($oGui->t('Persönliche Daten'));
		$oTabCourses					= $oDialog->createTab($oGui->t('Kurse'));
		$oTabAccommodations				= $oDialog->createTab($oGui->t('Unterkünfte'));
		$oTabMatching					= $oDialog->createTab($oGui->t('Matching Details'));
		$oTabTransfer					= $oDialog->createTab($oGui->t('Transfer'));
		$oTabVisum						= $oDialog->createTab($oGui->t('Visum'));
		$oTabUpload						= $oDialog->createTab($oGui->t('Upload'));
		$oTabTuition					= $oDialog->createTab($oGui->t('Unter. & Anwes.'));
		$oTabHolidays					= $oDialog->createTab($oGui->t('Ferien'));
		$oTabInsurances					= $oDialog->createTab($oGui->t('Versicherungen'));
		$oTabActivities					= $oDialog->createTab($oGui->t('Aktivitäten'));
		$oTabSponsoring = $oDialog->createTab($oGui->t('Sponsoring'));
		$oTabLogs = $oDialog->createTab($oGui->t('Protokoll'));

		$oDivContainer = new Ext_Gui2_Html_Div;
		$oDivContainer->class = 'student_personal_data_container flex flex-row gap-x-2 mb-2';
		
		$oDivContainerLeft = new Ext_Gui2_Html_Div;
		$oDivContainerLeft->class = 'student_personal_data_fields ';
		$oDivContainer->setElement($oDivContainerLeft);
		
		$oDivContainerSearch = new Ext_Gui2_Html_Div;
		$oDivContainerSearch->class = 'student_personal_data_search ';
		$oDivContainer->setElement($oDivContainerSearch);
		
		$oDivContainerRight = new Ext_Gui2_Html_Div;
		$oDivContainerRight->class = 'student_personal_data_photo ';
		
		// Photo
		
		$oPhotoBox = new Ext_Gui2_Html_Div();
		$oPhotoBox->class = "box box-default with-border";
		
//		$oBoxHeader = new Ext_Gui2_Html_Div();
//		$oBoxHeader->class = 'box-header';
//			
//		$oBoxHeader->setElement('<h3 class="box-title">'.$oGui->t('Photo').'</h3>');
//		$oPhotoBox->setElement($oBoxHeader);

		$oPhotoBox->setElement('<div class="box-separator"></div>');

		$oPhotoBoxBody = new Ext_Gui2_Html_Div();
		$oPhotoBoxBody->class = 'box-body';
		$oPhotoBox->setElement($oPhotoBoxBody);
		
		$oPhotoContainer = $oDialog->create('div');
		$oPhotoContainer->class = 'photo';
		
		$oPhotoBoxBody->setElement($oPhotoContainer);
		
		$oPhotoButton = $oDialog->create('button');
		$oPhotoButton->class = 'btn btn-default btn-block';
		$oPhotoButton->setElement($oGui->t('Photo hochladen'));
		
		$oPhotoBoxBody->setElement($oPhotoButton);

		$oDivContainerRight->setElement($oPhotoBox);
		
		$oDivContainer->setElement($oDivContainerRight);
		
		// Persönliche Daten
		
		$oDialog->bBigLabels = true;
		
		
		$oPersonaldetailsBox = new Ext_Gui2_Html_Div();
		$oPersonaldetailsBox->class = "box box-default with-border";
		
//		$oBoxHeader = new Ext_Gui2_Html_Div();
//		$oBoxHeader->class = 'box-header';
//			
//		$oBoxHeader->setElement('<h3 class="box-title">'.$oGui->t('Persönliche Daten').'</h3>');
//		$oPersonaldetailsBox->setElement($oBoxHeader);

		$oPersonaldetailsBox->setElement('<div class="box-separator"></div>');

		$oPersonaldetailsBoxBody = new Ext_Gui2_Html_Div();
		$oPersonaldetailsBoxBody->class = 'box-body';

		$oPersonaldetailsBox->setElement($oPersonaldetailsBoxBody);
		
		$oDivContainerLeft->setElement($oPersonaldetailsBox);
		
		$oDiv = $oDialog->createRow(
			$oGui->t('Schule'),						
			'select',	
			[
				'db_column' => 'school_id', 
				'db_alias' => 'ts_ij',
				'select_options' => Ext_Thebing_Util::addEmptyItem($aSchoolList), 
				'readonly' => 1, 
				'required' => true,
				'class'=> 'school_select'
			]
		);

		$oPersonaldetailsBoxBody->setElement($oDiv);

		$oDiv = $oDialog->createRow( $oGui->t('Gruppe'), 'select', array('db_column' => 'group_id', 'db_alias' => 'ki', 'select_options' => $aGroups, 'readonly' => 1, 'default_value' => '0'));
		$oPersonaldetailsBoxBody->setElement($oDiv);
		$oDiv = $oDialog->createRow( $oGui->t('Nachname'), 'input', array('db_column' => 'lastname', 'db_alias' => 'cdb1','required' => 1));
		$oPersonaldetailsBoxBody->setElement($oDiv);
		$oDiv = $oDialog->createRow( $oGui->t('Vorname'), 'input', array('db_column' => 'firstname', 'db_alias' => 'cdb1', 'required' => 1));
		$oPersonaldetailsBoxBody->setElement($oDiv);
		$oDiv = $oDialog->createRow( $oGui->t('Geschlecht'), 'select', array('db_column' => 'gender', 'db_alias' => 'cdb1', 'select_options' => $aGender, 'required' => 1));
		$oPersonaldetailsBoxBody->setElement($oDiv);

		$sTitle = $oGui->t('Geburtsdatum');
		if(Ext_Thebing_System::isAllSchools()) {
			$sTitle = $oGui->t('Geburtsdatum');
		}

		$oDiv = $oDialog->createRow(
			$sTitle,
			'calendar',	
			array(
				'db_column' => 'birthday', 
				'db_alias' => 'cdb1', 
				'required' => 1, 
				'format'=>new Ext_Thebing_Gui2_Format_Date(), 
				'display_age' => true,
				'placeholder' => $sFormat
			)
		);
		$oPersonaldetailsBoxBody->setElement($oDiv);

		$oDiv = self::createCustomerSearchDiv($oGui);
		$oDiv->style = 'margin-left: 240px;';
		$oPersonaldetailsBoxBody->setElement($oDiv);

		$oDiv = $oDialog->createRow( 
			$oGui->t('Nationalität'),					
			'select',	
			[
				'db_column' => 'nationality', 
				'db_alias' => 'cdb1', 
				'select_options' => $aNationalities, 
				'required' => 1,
				'events' => [
					[
						'event' => 'change',
						'function' => 'autodiscoverMotherTongue'
					]
				]
			]
		);

		$oPersonaldetailsBoxBody->setElement($oDiv);
		$oDiv = $oDialog->createRow( $oGui->t('Muttersprache'), 'select', array('db_column' => 'language', 'db_alias' => 'cdb1', 'select_options' => $aLangs, 'required' => 1));
		$oPersonaldetailsBoxBody->setElement($oDiv);
		$oDiv = $oDialog->createRow( $oGui->t('Korrespondenzsprache'), 'select', array('db_column' => 'corresponding_language', 'db_alias' => 'cdb1', 'select_options' => $aCorrespondenceLanguages, 'default_value' => $oSchool->getLanguage(), 'required' => 1));
		$oPersonaldetailsBoxBody->setElement($oDiv);

		
		$contactSearchBox = new Ext_Gui2_Html_Div();
		$contactSearchBox->class = 'box box-default with-border';

		$contactSearchBox->setElement('<div class="box-separator"></div>');

		$contactSearchBoxBody = new Ext_Gui2_Html_Div();
		$contactSearchBoxBody->class = 'box-body';

		$contactSearchBoxBody->setElement(self::getDialogContactSearch($oDialog));
		
		$contactSearchBox->setElement($contactSearchBoxBody);
		
		$oDivContainerSearch->setElement($contactSearchBox);
		
		$oTabPersonalData->setElement($oDivContainer);
		
		// Kontaktinformationen

		$oRow = $oDialog->create('div');
		$oRow->class = 'student_contact_data_container flex flex-row gap-x-2 mb-2';
		$oColumnAddress = $oDialog->create('div');
		$oColumnAddress->class = 'student_contact_data_address';
		$oBoxAddress = new Ext_Gui2_Dialog_Box();		
		
		$oColumnContact = $oDialog->create('div');
		$oColumnContact->class = 'student_contact_data_contact';
		$oBoxContact = new Ext_Gui2_Dialog_Box();

		$oDiv							= $oDialog->createRow( $oGui->t('Adresse'),						'input',	array('db_column' => 'address', 'db_alias' => 'tc_a_c'));
		$oBoxAddress->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('Adresszusatz'),					'input',	array('db_column' => 'address_addon', 'db_alias' => 'tc_a_c'));
		$oBoxAddress->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('PLZ'),							'input',	array('db_column' => 'zip', 'db_alias' => 'tc_a_c'));
		$oBoxAddress->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('Stadt'),							'input',	array('db_column' => 'city', 'db_alias' => 'tc_a_c'));
		$oBoxAddress->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('Bundesland'),					'input',	array('db_column' => 'state', 'db_alias' => 'tc_a_c'));
		$oBoxAddress->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('Land'),							'select',	array('db_column' => 'country_iso', 'db_alias' => 'tc_a_c', 'select_options' => $aCountries));
		$oBoxAddress->setElement($oDiv);
		
		$oDiv							= $oDialog->createRow( $oGui->t('Telefon'),						'input',	array('db_column' => 'phone_private', 'db_alias' => 'tc_c_d'));
		$oBoxContact->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('Telefon Büro'),					'input',	array('db_column' => 'phone_office', 'db_alias' => 'tc_c_d'));
		$oBoxContact->setElement($oDiv);
		$oDiv							= $oDialog->createRow( $oGui->t('Handy'),							'input',	array('db_column' => 'phone_mobile', 'db_alias' => 'tc_c_d'));
		$oBoxContact->setElement($oDiv);
		
//		$oDiv							= $oDialog->createRow( $oGui->t('E-Mail'),						'input',	array('db_column' => 'email', 'db_alias' => 'cdb1'));
//		$oTabPersonalData				->setElement($oDiv);

		// Manueller wiederholbarer Bereich: E-Mails
		\Ext_Thebing_Inquiry_Gui2_Html::setContactEmailContainer($oGui, $oDialog, $oBoxContact);
		
		$oBoxContact->setElement($oDialog->createRow( $oGui->t('Automatische E-Mails'), 'checkbox', array('db_column' => Ext_TS_Contact::DETAIL_NEWSLETTER, 'db_alias' => 'tc_c_d', 'default_value' => '1')));

		$oColumnAddress->setElement($oBoxAddress);
		$oColumnContact->setElement($oBoxContact);
		
		$oRow->setElement($oColumnAddress);
		$oRow->setElement($oColumnContact);
		
		$oTabPersonalData->setElement($oRow);
		
		$oDialog->bBigLabels = false;

		if(\TsHubspot\Handler\ExternalApp::isActive()) {
			$oBox = $oDialog->createExpandableBox($oGui->t('Hubspot Kontaktsuche'));
			$oBox->activateAutoExpand();
			$oBox->class = 'hubspot-contact-search';

			$oDivContainerSearch = new Ext_Gui2_Html_Div;
			$oDivContainerSearch->class = 'student_personal_data_search ';
			$contactSearchBox = new Ext_Gui2_Html_Div();
			$contactSearchBox->class = 'box box-default with-border';

			$contactSearchBoxBody = new Ext_Gui2_Html_Div();
			$contactSearchBoxBody->class = 'box-body';

			$contactSearchBoxBody->setElement(self::getDialogContactSearch($oDialog, 'hubspot', true));

			$contactSearchBox->setElement($contactSearchBoxBody);

			$oDivContainerSearch->setElement($contactSearchBox);

			$oBox->setElement($oDivContainerSearch);

			$div = new Ext_Gui2_Html_Div();

			$oButton = new \Ext_Gui2_Html_Button();
			$oButton->class = 'btn btn-sm btn-default search-in-hubspot';
			$oButton->setElement($oGui->t('Suche in Hubspot'));

			$div->setElement($oButton);
			$div->setElement('&nbsp');

			$oButton = new \Ext_Gui2_Html_Button();
			$oButton->class = 'btn btn-sm btn-default search-from-fidelo-to-hubspot';
			$oButton->setElement($oGui->t('Suche von Fidelo nach Hubspot'));

			$div->setElement($oButton);

			$oBox->setElement($div);

			$oTabPersonalData->setElement($oBox);
		}
		
		$oBox = $oDialog->createExpandableBox($oGui->t('Zusatzinformationen'));
		$oBox->activateAutoExpand();
		$oBox->setElement($oDialog->createRow( $oGui->t('Geburtsort'), 'input', array('db_column' => 'place_of_birth', 'db_alias' => 'tc_c_d')));
		$oBox->setElement($oDialog->createRow( $oGui->t('Geburtsland'), 'select', array('db_column' => 'country_of_birth', 'db_alias' => 'tc_c_d', 'select_options' => $aCountries)));
		$oBox->setElement($oDialog->createRow( $oGui->t('Beruf'), 'input', array('db_column' => 'profession', 'db_alias' => 'ki')));
		$oBox->setElement($oDialog->createRow( $oGui->t('Sozialversicherungsnummer'), 'input', array('db_column' => 'social_security_number', 'db_alias' => 'ki')));
		$oDiv = $oDialog->createRow( $oGui->t('Fax'), 'input', array('db_column' => 'fax', 'db_alias' => 'tc_c_d'));
		$oBox->setElement($oDiv);
		$oBox->setElement($oDialog->createRow( $oGui->t('Steuernummer'), 'input', array('db_column' => 'tax_code', 'db_alias' => 'tc_c_d')));
		$oBox->setElement($oDialog->createRow( $oGui->t('USt.-ID'), 'input', array('db_column' => 'vat_number', 'db_alias' => 'tc_c_d')));
		
		$oTabPersonalData->setElement($oBox);
		
		// TODO: Ist bei Inquiry nicht mehr automatisch befüllt, aber bei Enquiry schon?

		$oBox = $oDialog->createExpandableBox($oGui->t('Rechnungsadresse'));
		$oBox->activateAutoExpand();
		
		$invoiceAddressBox = new Ext_Gui2_Html_Div;
		$invoiceAddressBox->class = 'grid grid-cols-3 gap-6';
		$invoiceAddressBoxLeft = new Ext_Gui2_Html_Div;
		$invoiceAddressBoxLeft->class = '';
		$invoiceAddressBox->setElement($invoiceAddressBoxLeft);
		$invoiceAddressBoxMiddle = new Ext_Gui2_Html_Div;
		$invoiceAddressBoxMiddle->class = '';
		$invoiceAddressBox->setElement($invoiceAddressBoxMiddle);
		$invoiceAddressBoxRight = new Ext_Gui2_Html_Div;
		$invoiceAddressBoxRight->class = '';
		$invoiceAddressBox->setElement($invoiceAddressBoxRight);
		
		$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('Firma'), 'input',	array('db_column' => 'company', 'db_alias' => 'tc_a_b')));
		$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('Vorname'), 'input',	array('db_column' => 'firstname', 'db_alias' => 'tc_bc')));
		$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('Nachname'), 'input',	array('db_column' => 'lastname', 'db_alias' => 'tc_bc')));
		$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('Adresse'), 'input',	array('db_column' => 'address', 'db_alias' => 'tc_a_b')));
		$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('PLZ'), 'input',	array('db_column' => 'zip', 'db_alias' => 'tc_a_b')));
		$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('Steuernummer'), 'input',	array('db_column' => 'detail_tax_code', 'db_alias' => 'tc_bc')));
		if (\TcExternalApps\Service\AppService::hasApp(\TsAccounting\Service\eInvoice\Italy\ExternalApp\XmlIt::APP_NAME)) {
			$invoiceAddressBoxLeft->setElement($oDialog->createRow( $oGui->t('Empfängercode'), 'input',	array('db_column' => 'detail_recipient_code', 'db_alias' => 'tc_bc')));
		}
		$invoiceAddressBoxMiddle->setElement($oDialog->createRow( $oGui->t('Stadt'), 'input',	array('db_column' => 'city', 'db_alias' => 'tc_a_b')));
        $invoiceAddressBoxMiddle->setElement($oDialog->createRow( $oGui->t('Bundesland'), 'input',	array('db_column' => 'state', 'db_alias' => 'tc_a_b')));
		$invoiceAddressBoxMiddle->setElement($oDialog->createRow( $oGui->t('Land'), 'select',	array('db_column' => 'country_iso', 'db_alias' => 'tc_a_b', 'select_options' => $aCountries)));
		$invoiceAddressBoxMiddle->setElement($oDialog->createRow( $oGui->t('E-Mail'), 'input',	array('db_column' => 'email', 'db_alias' => 'tc_bc')));
		$invoiceAddressBoxMiddle->setElement($oDialog->createRow( $oGui->t('Telefonnummer'), 'input',	array('db_column' => 'detail_phone_private', 'db_alias' => 'tc_bc')));
		$invoiceAddressBoxMiddle->setElement($oDialog->createRow( $oGui->t('USt.-ID'), 'input',	array('db_column' => 'detail_vat_number', 'db_alias' => 'tc_bc')));
		$invoiceAddressBoxRight->setElement(self::getDialogContactSearch($oDialog, 'booker'));
		
		$oBox->setElement($invoiceAddressBox);
		
		$oTabPersonalData->setElement($oBox);
		
		$oBox = $oDialog->createExpandableBox($oGui->t('Weitere Kontaktdaten'));
		$oBox->activateAutoExpand();
		
		$joinedObjectContainerContacts = $oDialog->createJoinedObjectContainer('other_contacts', 
			[
				'min' => 0,
				'add_label' => 'Kontakt hinzufügen', 
				'remove_label' => 'Kontakt löschen',
				'no_box_border' => true
			]
		);

		$joinedObjectContainerContacts->setElement($joinedObjectContainerContacts->createMultiRow(null, array(
			'db_alias' => 'tc_c_e',
			'items' => array(
				array(
					'db_column' => 'type',
					'input' => 'select',
					'select_options' => self::getOtherContactsTypes($oGui),
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'required' => true,
					'placeholder' => $oGui->t('Beziehung')
				),
				array(
					'db_column' => 'firstname',
					'input' => 'input',
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'placeholder' => $oGui->t('Vorname')
				),
				array(
					'db_column' => 'lastname',
					'input' => 'input',
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'placeholder' => $oGui->t('Nachname')
				),
				array(
					'db_column' => 'detail_phone_private',
					'input' => 'input',
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'placeholder' => $oGui->t('Telefon')
				),
				array(
					'db_column' => 'email',
					'input' => 'input',
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'placeholder' => $oGui->t('E-Mail')
				)
			)
		)));
		
//		$joinedObjectContainerContacts->setElement($oDialog->createRow( $oGui->t('Beziehung'), 'select', array('db_column' => 'type', 'db_alias' => 'tc_c_e', 'select_options'=>self::getOtherContactsTypes($oGui))));
//		$joinedObjectContainerContacts->setElement($oDialog->createRow( $oGui->t('Vorname'), 'input', array('db_column' => 'firstname', 'db_alias' => 'tc_c_e')));
//		$joinedObjectContainerContacts->setElement($oDialog->createRow( $oGui->t('Nachname'), 'input', array('db_column' => 'lastname', 'db_alias' => 'tc_c_e')));
//		$joinedObjectContainerContacts->setElement($oDialog->createRow( $oGui->t('Telefon'), 'input', array('db_column' => 'phone_private', 'db_alias' => 'tc_c_de')));
//		$joinedObjectContainerContacts->setElement($oDialog->createRow( $oGui->t('E-Mail'), 'input', array('db_column' => 'email', 'db_alias' => 'tc_c_e')));
		
		$oBox->setElement($joinedObjectContainerContacts);
		$oTabPersonalData->setElement($oBox);

		$oH3					= $oDialog->create('h4');
		$oH3					->setElement($oGui->t('Buchungsdaten'));
		$oTabPersonalData		->setElement($oH3);

		$oHint = $oDialog->createNotification($oGui->t('Sonderangebot'), '',								'info', array('row_id' => 'general_special_info', 'row_style' => 'display: none;')); 
		$oTabPersonalData->setElement($oHint->generateHTML());

		// Da alles im JS miteinander zusammenhängt: nur ausblenden
		$sAgencyRowStyle = '';
		if(!Ext_Thebing_Access::hasRight('thebing_invoice_agency_data')) {
			$sAgencyRowStyle = 'display: none;';
		}

		$oDiv = $oDialog->createRow(
			$oGui->t('Agentur'),
			'select',
			array(
				'db_column' => 'agency_id',
				'db_alias' => 'ki',
				'selection' => new Ext_TS_Inquiry_Gui2_Selection_Agency(),
				'events' => array(
					array(
						'event' => 'change',
						'function'  => 'reloadAgencyDependingFields'
					)
				),
				'row_style' => $sAgencyRowStyle
			)
		);

		$oTabPersonalData->setElement($oDiv);
		$oDiv = $oDialog->createRow($oGui->t('Agenturmitarbeiter'), 'select', array(
			'db_column' => 'agency_contact_id',
			'db_alias' => 'ki',
			'dependency' => [['db_alias'=>'ki', 'db_column' => 'agency_id']],
			'selection' => new Ext_Thebing_Gui2_Selection_Agency_Comments(),
			'row_style' => $sAgencyRowStyle
		));

		$oTabPersonalData->setElement($oDiv);

		$oTabPersonalData->setElement($oDialog->createRow($oGui->t('Unteragentur'), 'select', [
			'db_alias' => 'ki',
			'db_column' => 'subagency_id',
			'selection' => new \Ts\Gui2\Selection\Inquiry\SubAgencies(),
			'dependency' => [['db_alias'=>'ki', 'db_column' => 'agency_id']],
			'dependency_visibility' => [
				'db_alias' => 'ki',
				'db_column' => 'agency_id',
				'on_values' => ['!0']
			]
		]));

		$oDiv					= $oDialog->createRow( $oGui->t('Zahlungsmethode'),						'select',	array('db_column' => 'payment_method', 'db_alias' => 'ki', 'select_options' => $aPaymentMethods));
		$oTabPersonalData		->setElement($oDiv);

		$sPartialInvoiceRowStyle = '';
		if(!Ext_Thebing_Access::hasRight('ts_invoice_partial_invoicing')) {
			$sPartialInvoiceRowStyle = 'display: none;';
		}

		$oTabPersonalData->setElement($oDialog->createRow($oGui->t('Teilzahlung'), 'select', array(
			'db_alias' => 'ki',
			'db_column' => 'partial_invoices_terms',
			'select_options' => Ext_Thebing_Util::addEmptyItem($aPartialInvoicePaymentConditions),
			'row_style' => $sPartialInvoiceRowStyle
		)));
		
		$oDiv = $oDialog->createRow( $oGui->t('Zahlungsart Kommentar'),					'textarea',	array('db_column' => 'payment_method_comment', 'db_alias' => 'ki', 'row_style' => $sAgencyRowStyle));
		$oTabPersonalData->setElement($oDiv);

		$oDiv					= $oDialog->createRow( $oGui->t('Währung'),								'select',	array('db_column' => 'currency_id', 'db_alias' => 'ki', 'select_options' => $aCurrencys, 'required' => 1));
		$oTabPersonalData		->setElement($oDiv);

		$oDiv = $oDialog->create('div');
		$oDiv->id = 'agency_info_container';
		$oDiv->class = 'GUIDialogRow';
		$oDiv->style = 'display: none;';
		$oTabPersonalData->setElement($oDiv);

		$oAccess = Access::getInstance();

		if ($oAccess->hasRight('thebing_invoice_sales_person')) {
			$oDiv = $oDialog->createRow($oGui->t('Vertriebsmitarbeiter'), 'select', [
				'db_column' => 'sales_person_id', // Ist die `system_user` id
				'db_alias' => 'ki',
				'selection' => new Ext_Thebing_Gui2_Selection_User_SalesPerson(),
				'dependency' => [
					[
						'db_alias' => 'cdb1',
						'db_column' => 'nationality',
					],
					[
						'db_alias' => 'ki',
						'db_column' => 'agency_id',
					],
				]
			]);
			$oTabPersonalData->setElement($oDiv);
		}

		$oH3					= $oDialog->create('h4');
		$oH3					->setElement($oGui->t('Sonstiges'));
		$oTabPersonalData		->setElement($oH3);

		$oDiv					= $oDialog->createRow( $oGui->t('Promotion-Code'),						'input',	array('db_column' => 'promotion', 'db_alias' => 'ki'));
		$oTabPersonalData		->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Kommentar'),								'textarea',	array('db_column' => 'comment', 'db_alias' => 'tc_c_d'));
		$oTabPersonalData		->setElement($oDiv);

		$oDiv					= $oDialog->createRow( $oGui->t('Status d. Schülers'),					'select',	array('db_column' => 'status_id', 'db_alias' => 'ki', 'select_options' => $aCustomerStatus));
		$oTabPersonalData		->setElement($oDiv);

		$oDiv					= $oDialog->createRow( $oGui->t('Wie sind Sie auf uns aufmerksam geworden?'),		'select',	array('db_column' => 'referer_id', 'db_alias' => 'ki', 'select_options' => $aReferer));
		$oTabPersonalData		->setElement($oDiv);


		// Matching
		$oDiv					= $oDialog->createRow( $oGui->t('Kommentar'),							'textarea',	array('db_column' => 'acc_comment', 'db_alias' => 'ts_i_m_d'));
		$oTabMatching			->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Kommentar 2'),						'textarea',	array('db_column' => 'acc_comment2', 'db_alias' => 'ts_i_m_d'));
		$oTabMatching			->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Beleg ID'),							'input',	array('db_column' => 'voucher_id', 'db_alias' => 'ki'));
		$oTabMatching			->setElement($oDiv);

		$oDiv					= $oDialog->createRow( $oGui->t('Allergien'), 'textarea',	array('db_column' => 'acc_allergies', 'db_alias' => 'ts_i_m_d'));
		$oTabMatching			->setElement($oDiv);
		
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Diese Eigenschaften treffen auf den Schüler zu'));
		$oTabMatching->setElement($oH3);

		$oMatching = new Ext_Thebing_Matching();
		$aCriteria = $oMatching->getCriteria();
		
		foreach($aCriteria['hard'] as $sKey=>$oCriterion) {
			$oDiv = $oDialog->createRow($oCriterion->getLabel(true), $oCriterion->getType(), array('db_column' => $oCriterion->getField(), 'db_alias' => 'ts_i_m_d', 'select_options' => $oCriterion->getOptions()));
			$oTabMatching->setElement($oDiv);
		}
		
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Auf die Unterkunft können folgende Eigenschaften zutreffen'));
        $oH3->id = "matching_soft";
		$oTabMatching->setElement($oH3);

		foreach($aCriteria['soft'] as $sKey=>$oCriterion) {
			$oDiv = $oDialog->createRow($oCriterion->getLabel(true), $oCriterion->getType(), array('db_column' => $oCriterion->getField(), 'db_alias' => 'ts_i_m_d', 'select_options' => $oCriterion->getOptions()));
			$oTabMatching->setElement($oDiv);
		}
		
		$oH3					= $oDialog->create('h4');
		$oH3					->setElement($oGui->t('Zusammenreisende Schüler'));
		$oTabMatching			->setElement($oH3);

		$oDiv0					= new Ext_Gui2_Html_Div();
		$oDiv1					= new Ext_Gui2_Html_Label();
		$oDiv1->class			= 'GUIDialogRowLabelDiv control-label col-sm-3';
		$oDiv1					->setElement($oGui->t('Suche'));
		$oDiv2					= new Ext_Gui2_Html_Div();
		$oDiv2->class			= 'GUIDialogRowInputDiv col-sm-9';
		$oInput					= new Ext_Gui2_Html_Input();
		$oInput->class			= 'txt form-control input-sm';
		$oInput->id				= 'saveid[acc_share_with_id]';
		$oInput->name			= 'saveid[acc_share_with_id]';
		$oDiv2					->setElement($oInput);
		$oDiv0					->setElement($oDiv1);
		$oDiv0					->setElement($oDiv2);
		$oDiv0->class			= 'GUIDialogRow form-group form-group-sm';
		$oTabMatching			->setElement($oDiv0);

		$oDiv0					= new Ext_Gui2_Html_Div();
		$oDiv1					= new Ext_Gui2_Html_Label();
		$oDiv1->class			= 'GUIDialogRowLabelDiv control-label col-sm-3';
		$oDiv1					->setElement($oGui->t('Passende Kunden'));
		$oDiv2					= new Ext_Gui2_Html_Div();
		$oDiv2->class			= 'GUIDialogRowInputDiv col-sm-9 pt-2';
		$oDiv2->id				= 'saveid[room_sharing_search_results_list]';
		$oDiv0					->setElement($oDiv1);
		$oDiv0					->setElement($oDiv2);
		$oDiv0->class			= 'GUIDialogRow form-group form-group-sm';
		$oDiv0->style			= 'display: none;';
		$oTabMatching			->setElement($oDiv0);

		$oDiv0					= new Ext_Gui2_Html_Div();
		$oDiv1					= new Ext_Gui2_Html_Label();
		$oDiv1->class			= 'GUIDialogRowLabelDiv control-label col-sm-3';
		$oDiv1					->setElement($oGui->t('Ausgewählte Kunden'));
		$oDiv2					= new Ext_Gui2_Html_Div();
		$oDiv2->class			= 'GUIDialogRowInputDiv col-sm-9 pt-2';
		$oDiv2->id				= 'saveid[room_sharing_list_list]';
		$oDiv3					= new Ext_Gui2_Html_Div();
		$oDiv3->class			= 'divCleaner';
		$oDiv0					->setElement($oDiv1);
		$oDiv0					->setElement($oDiv2);
		$oDiv0					->setElement($oDiv3);
		$oDiv0->class			= 'GUIDialogRow form-group form-group-sm';
		$oDiv0->style			= 'display: none;';
		$oTabMatching			->setElement($oDiv0);

		// Transfer
		$oDiv					= $oDialog->createRow( $oGui->t('Transfer'),							'select',	array('db_column' => 'transfer_mode', 'db_alias' => 'ts_ij', 'select_options' => $aTransfer));
		$oTabTransfer			->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Kommentar'),							'textarea',	array('db_column' => 'transfer_comment', 'db_alias' => 'ts_ij'));
		$oTabTransfer			->setElement($oDiv);

		// Visum
		$oDiv					= $oDialog->createRow( $oGui->t('Visum wird benötigt'),				'checkbox',	array('db_column' => 'required', 'db_alias' => 'ts_ijv'));
		$oTabVisum				->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Sevis ID'),							'input',	array('db_column' => 'servis_id', 'db_alias' => 'ts_ijv'));
		$oTabVisum				->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Mail tracking number'),				'input',	array('db_column' => 'tracking_number', 'db_alias' => 'ts_ijv'));
		$oTabVisum				->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Passnummer'),						'input',	array('db_column' => 'passport_number', 'db_alias' => 'ts_ijv'));
		$oTabVisum				->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Visum gültig von'),					'calendar',	array('db_column' => 'date_from', 'db_alias' => 'ts_ijv', 'format' => new Ext_Thebing_Gui2_Format_Date()));
		$oTabVisum				->setElement($oDiv);
		$oDiv					= $oDialog->createRow( $oGui->t('Visum gültig bis'),					'calendar',	array('db_column' => 'date_until', 'db_alias' => 'ts_ijv', 'format' => new Ext_Thebing_Gui2_Format_Date()));
		$oTabVisum				->setElement($oDiv);	
		
		$oDiv					= $oDialog->createRow($oGui->t('Pass Ausstellungsdatum'),			'calendar',	array('db_column' => 'passport_date_of_issue', 'db_alias' => 'ts_ijv'));
		$oTabVisum				->setElement($oDiv);
		$oDiv					= $oDialog->createRow($oGui->t('Pass Fälligkeitsdatum'),				'calendar',	array('db_column' => 'passport_due_date', 'db_alias' => 'ts_ijv'));
		$oTabVisum				->setElement($oDiv);
		
		$oDiv					= $oDialog->createRow( $oGui->t('Status'),							'select',	array('db_column' => 'status', 'db_alias' => 'ts_ijv', 'select_options' => $aVisumStatus));
		$oTabVisum				->setElement($oDiv);

		// Uploads
		// Info Div - Falls Kunde noch nicht gespeichert wurde
		$oDivInfo = $oDialog->createNotification($oGui->t('Achtung'), $oGui->t('Bitte speichern Sie zuerst die Buchung.'), 'hint', array('row_class' => 'upload_save_info', 'row_style' => 'display: none;'));
		$oTabUpload->setElement($oDivInfo);

		 // Closure zum Generieren von Upload + Checkbox, da alles eigentlich gleich ist, aber man daraus 2x statisch + dynamisch machen musste
		$oGenerateUploadCheckboxRow = function(Ext_Gui2_Dialog_Upload $oUpload) use($oGui, $oDialog, $oTabUpload) {

			// Aus dem Key irgendwie Typ und Typ-ID ermitteln
			if(strpos($oUpload->db_column, 'studentupload_') !== false) {
				$iId = str_replace('studentupload_', '', $oUpload->db_column);
				$sKey = '[flex]['.$iId.'';
			} elseif(strpos($oUpload->db_column, 'upload') !== false) {
				$iId = str_replace('upload', '', $oUpload->db_column);
				$sKey = '[static]['.$iId.'';
			} else {
				throw new InvalidArgumentException('Unknown upload '.$oUpload->db_column);
			}

			$aItems = [
				[
					'input' => 'upload',
					'upload' => $oUpload,
					'grid_cols' => 10,
					'info_icon' => false
				]
			];

			if(Ext_Thebing_Access::hasRight('thebing_release_documents_sl')) {
				$aItems[] = [
					'input' => 'checkbox',
					'type' => 'checkbox',
					'value' => '1',
					'title' => $oGui->t('Freigegeben für Schüler-App'),
					'db_column' => 'studentupload_released_sl]'.$sKey,
					'grid_cols' => 2
				];
			}
			return $oDialog->createMultirow($oUpload->sTitle, ['grid' => true, 'items' => $aItems]);
		};

		$oUpload = new Ext_Gui2_Dialog_Upload(
			$oGui,
			$oGui->t('Foto'),
			$oDialog,
			'upload1',
			'upload',
			$sSchoolFileDir.'/studentcards/',
			false,
			array('class'=>'studentrecord_save_field', 'style'=>'margin:0px;')
		);
		$oUpload->bNoCache = true; // Eigentlich überflüssig, da getEditDialogData komplett überschrieben

		$oTabUpload->setElement($oGenerateUploadCheckboxRow($oUpload));
		//$oDialog->bSmallLabels = true;
		$oUpload = new Ext_Gui2_Dialog_Upload(
			$oGui,
			$oGui->t('Reisepass'),
			$oDialog,
			'upload2',
			'upload',
			$sSchoolFileDir.'/passport/',
			false,
			array('class'=>'studentrecord_save_field', 'style'=>'margin:0px;')
		);
		$oUpload->bNoCache = true;  // Eigentlich überflüssig, da getEditDialogData komplett überschrieben

		$oTabUpload->setElement($oGenerateUploadCheckboxRow($oUpload));

		if(Ext_Thebing_System::isAllSchools()) {
			$aFlexUploadsSchoolIds = array_keys($oClient->getSchoolListByAccess(true, false, true));
		} else {
			$aFlexUploadsSchoolIds = [Ext_Thebing_School::getSchoolFromSession()->id];
		}

		$aUploadFields = Ext_Thebing_School_Customerupload::getUploadFieldsBySchoolIds($aFlexUploadsSchoolIds);

		$oMatrix = new \Ts\Gui2\Data\CustomerUpload\AccessMatrix();
		$aUploadIdsWithAccess = array_keys($oMatrix->getListByUserRight());

		foreach($aUploadFields as $oUploadField) {

			if (!in_array($oUploadField->id, $aUploadIdsWithAccess)) {
				continue;
			}

			// Pfad wird nachträglich verändert
			$oUpload = new Ext_Gui2_Dialog_Upload(
				$oGui,
				$oUploadField->name,
				$oDialog,
				'studentupload_'.$oUploadField->id,
				'upload',
				'/storage/',
				false,
				['class'=>'studentrecord_save_field', 'style'=>'margin:0px;']
			);
			$oUpload->bNoCache = true;  // Eigentlich überflüssig, da getEditDialogData komplett überschrieben

			$oDiv = new Ext_Gui2_Html_Div();
			$oDiv->class = 'FlexUploadContainerSchool';
			$oDiv->setDataAttribute('schools', \Util::convertHtmlEntities(json_encode($oUploadField->schools)));
			$oDiv->setElement($oGenerateUploadCheckboxRow($oUpload));
			$oTabUpload->setElement($oDiv);

		}

		self::getSponsoringTab($oDialog, $oGui, $oTabSponsoring);

		$oFactory = new \Ext_Gui2_Factory('core_logs');
		$oGuiLogs = $oFactory->createGui(null, $oGui);
		$oGuiLogs->setTableData('where', array('sl.elementname'=>'Ext_TS_Inquiry'));
		$oGuiLogs->foreign_key = 'element_id';
		
		$oTabLogs->setElement($oGuiLogs);
		
		## Start Optionen für Tabs setzen
		// section => Section der Flex2
		$oTabPersonalData->aOptions = array(
			'class' => 'tab_data',
			'access' => '',
			'task' => 'personal_data',
			'section' => [['student_record_general', ['booking', 'enquiry_booking']]]
		);
		$oTabCourses->aOptions = array(
			'class' => 'tab_courses',
			'access' => 'thebing_tuition_icon',
			'task' => 'course_data',
			'section' => [['student_record_course', ['booking', 'enquiry_booking']]]
		);
		$oTabAccommodations->aOptions = array(
			'class' => 'tab_accommodations',
			'access' => 'thebing_accommodation_icon',
			'task' => 'accommodation_data',
			'section' => [['student_record_accommodation', ['booking', 'enquiry_booking']]]
		);
		$oTabMatching->aOptions = array(
			'class' => 'tab_matching',
			'access' => 'thebing_accommodation_icon',
			'task' => 'matching_data',
			'section' => [['student_record_matching', ['booking', 'enquiry_booking']]]
		);
		$oTabTransfer->aOptions = array(
			'class' => 'tab_transfer',
			'access' => 'thebing_pickup_icon',
			'task' => 'transfer_data',
			'section' => [['student_record_transfer', ['booking', 'enquiry_booking']]]
		);
		$oTabVisum->aOptions = array(
			'class' => 'tab_visum',
			'access' => 'thebing_invoice_visa_tab',
			'task' => 'visum_data',
			'class' => 'student_record_visum',
			'section' => [['student_record_visum_status', ['booking', 'enquiry_booking']], ['student_record_visum', ['booking', 'enquiry_booking']]]
		);
		$oTabUpload->aOptions = array(
			'class' => 'tab_upload',
			'access' => '',
			'task' => 'upload_data',
			'class' => 'student_record_upload',
			'section' => [['student_record_upload', ['booking', 'enquiry_booking']]]
		);
		$oTabTuition->aOptions = array(
			'class' => 'tab_tuition',
			'access' => '',
			'task' => 'tuition_data',
			'section' => ''
		);
		$oTabHolidays->aOptions = array(
			'class' => 'tab_holidays',
			'access' => '',
			'task' => 'holiday_data',
			'section' => ''
		);
		$oTabInsurances->aOptions = array(
			'class' => 'tab_insurances',
			'access' => 'thebing_insurance_icon',
			'task' => 'insurance_data',
			'section' => [['student_record_insurance', ['booking', 'enquiry_booking']]]
		);
		$oTabActivities->aOptions = array(
			'class' => 'tab_activities',
			'access' => '',
			'task' => 'activity_data',
			'section' => [['student_record_activities', ['booking', 'enquiry_booking']]]
		);
		$oTabSponsoring->aOptions = [
			'class' => 'tab_sponsoring',
			'access' => 'thebing_invoice_sponsoring_tab',
			'task' => 'sponsoring_data',
			'section' => [['student_record_sponsoring', ['booking', 'enquiry_booking']]]
		];
		$oTabLogs->aOptions = [
			'class' => 'tab_logs',
			'access' => ['thebing_invoice_edit_student', 'logs'],
			'task' => 'logs'
		];

		if($sView == 'inbox') {

			// Darf nicht gesetzt werden wenn irgendwelche Felder disabled sind!
			$oTabPersonalData->setElement($oDialog->createSaveField('hidden', [
				'name' => 'inquiry_save_handler',
				'value' => 1
			]));

			$oDialog->setElement($oTabPersonalData);
			$oDialog->setElement($oTabCourses);
			$oDialog->setElement($oTabAccommodations);
			$oDialog->setElement($oTabMatching);
			$oDialog->setElement($oTabTransfer);
			$oDialog->setElement($oTabVisum);
			$oDialog->setElement($oTabUpload);
			$oDialog->setElement($oTabTuition);
			$oDialog->setElement($oTabHolidays);
			$oDialog->setElement($oTabInsurances);
			$oDialog->setElement($oTabActivities);
			$oDialog->setElement($oTabSponsoring);
			$oDialog->setElement($oTabLogs);

		} else if(
			$sView == 'accommodation_communication' ||
			$sView == 'employment_student_allocations'
		){
			
			$oTabPersonalData	->setModus('readonly');
			$oTabCourses		->setModus('readonly');
			$oTabAccommodations	->setModus('readonly');
			$oTabMatching		->setModus('readonly');
			$oTabTransfer		->setModus('readonly');
			$oTabVisum			->setModus('readonly');
			$oTabUpload			->setModus('readonly');
			$oTabTuition		->setModus('readonly');
			$oTabHolidays		->setModus('readonly');
			$oTabInsurances		->setModus('readonly'); 

			$oDialog->setElement($oTabPersonalData);
			$oDialog->setElement($oTabCourses);
			$oDialog->setElement($oTabAccommodations);
			$oDialog->setElement($oTabMatching);
			$oDialog->setElement($oTabTransfer);
			$oDialog->setElement($oTabVisum);
			$oDialog->setElement($oTabUpload);
			$oDialog->setElement($oTabTuition);
			$oDialog->setElement($oTabHolidays);
			$oDialog->setElement($oTabInsurances);

			$oDialog->bReadOnly = 1;
			$oDialog->save_button = false;

		} else {

			$oTabPersonalData	->setModus('readonly');
			$oTabCourses		->setModus('readonly');
			$oTabAccommodations	->setModus('readonly');
			$oTabMatching		->setModus('readonly');
			if(
				$sView == 'client_payment' ||
				$sView == 'agency_payment' ||
				$sView == 'visum_list' ||
				$sView == 'students_sponsoring'
			){
				if($sView!='visum_list'){
					$oDialog->save_button = false;
				}

				$oTabTransfer		->setModus('readonly');
			}

			//$oTabTransfer		->setModus('readonly');
			if($sView != 'visum_list'){
				$oTabVisum			->setModus('readonly');
			}

			$oTabUpload			->setModus('readonly');
			$oTabTuition		->setModus('readonly');
			$oTabHolidays		->setModus('readonly');
			$oTabInsurances		->setModus('readonly');

			$oDialog->setElement($oTabPersonalData);
			$oDialog->setElement($oTabCourses);
			$oDialog->setElement($oTabAccommodations);
			$oDialog->setElement($oTabMatching);
			$oDialog->setElement($oTabTransfer);
			$oDialog->setElement($oTabVisum);
			$oDialog->setElement($oTabUpload);
			$oDialog->setElement($oTabTuition);
			$oDialog->setElement($oTabHolidays);
			$oDialog->setElement($oTabInsurances);
			//$oDialog->save_button = false;

			if ($sView === 'students_sponsoring') {
				$oDialog->setElement($oTabSponsoring);
			}

			# Tabs hinzufügen #19738
			if ($sView === 'students') {
				$oDialog->setElement($oTabActivities);
				$oDialog->setElement($oTabSponsoring);
			}

			// Datenlust vermeiden #17989
			$oDialog->bReadOnly = true;
			$oDialog->save_button = false;

		}
		
		return $oDialog;
	}
	
	static protected function getDialogContactSearch(Ext_Gui2_Dialog &$oDialog, string $type='traveller', $hubspot = false) {

		$oGui = $oDialog->getDataObject()->getGui();

		if (!$hubspot) {
			$inputClass = 'form-control customernumber_search';
			$searchObjectType = 'Kunden';
			$title = \L10N::t('Suche nach Vorname, Nachname, Kundennummer, Firma, E-Mail-Adresse und Geburtsdatum', self::TRANSLATION_PATH);
		} else {
			$inputClass = 'form-control hubspotcustomer_search';
			$searchObjectType = 'Kontakte';
			$title = \L10N::t('Suche nach Vorname, Nachname, Firma, E-Mail-Adresse', self::TRANSLATION_PATH);
		}
		
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = "customer_identification_results_field py-2";
		$oDiv->setDataAttribute('type', $type);
		
		$oInputDiv = new \Ext_Gui2_Html_Div();
		
		$oInputGroupDiv = new \Ext_Gui2_Html_Div();
		$oInputGroupDiv->class = 'input-group';
		$oInputGroupDiv->style = 'width:100%;';
		
		$oInputInfo = new \Ext_Gui2_Html_Span();
		$oInputInfo->class = 'input-group-addon prototypejs-is-dead';
		$oInputInfo->setDataAttribute('toggle', 'tooltip');
		$oInputInfo->setDataAttribute('placement', 'bottom');
		$oInputInfo->setDataAttribute('html', true);
		$oInputInfo->setDataAttribute('original-title', $oGui->t($title)."<br><br>".$oGui->t('Zum Übernehmen klicken Sie bitte den Eintrag an'));
		$oInputInfo->setElement('<i class="fas fa-info"></i>');

		$oInput = new \Ext_Gui2_Html_Input();
		$oInput->class = $inputClass;
		$oInput->placeholder = $oGui->t('Kontaktsuche');

		// Bei Hubspot erstmal das Suchfeld und danach erst das "I", sonst andersrum.
		if (!$hubspot) {
			$oInputGroupDiv->setElement($oInputInfo);
			$oInputGroupDiv->setElement($oInput);
		} else {
			$oInputGroupDiv->setElement($oInput);
			$oInputGroupDiv->setElement($oInputInfo);
		}

		$oInputAddon = new \Ext_Gui2_Html_Span();
		$oInputAddon->class = 'input-group-addon loading';
		$oInputAddon->setElement('<i class="fas fa-spinner fa-pulse"></i>');
		$oInputAddon->style = 'display:none';
		$oInputGroupDiv->setElement($oInputAddon);
		
		$oInputDiv->setElement($oInputGroupDiv);
				
		$oDiv->setElement($oInputDiv);
		
		// Results anzeigen
		$oDivResults = new Ext_Gui2_Html_Div();
		$oDivResults->class = 'customer_identification_results border border-gray-50 rounded p-1 mt-1';
		$oDivResults->style = 'display: none;';
		
		// Header

		$oLabel = new Ext_Gui2_Html_Label();
		$oLabel->class = 'text-xs';
		//$oLabel->style = 'display: block;';
		$oLabel->setElement(L10N::t('Folgende '.$searchObjectType.' wurden gefunden:').'&nbsp;');
		$oDivResults->setElement($oLabel);

		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'customer_box_result text-xs';
		$oSpan->setElement('0');
		$oDivResults->setElement($oSpan);

		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'text-xs';
		$oSpan->setElement('&nbsp;'.L10N::t('Treffer'));
		$oDivResults->setElement($oSpan);

		// Result
		
		$oScrollDiv = new Ext_Gui2_Html_Div();
		$oScrollDiv->class = 'customer_identification_entries';
		$oDivResults->setElement($oScrollDiv);
		
		// - - - - - - - - - - - - - - -
		
		$oDiv->setElement($oDivResults);
		
		return $oDiv;
	}
	
	static public function getOtherContactsTypes(Ext_Gui2 $oGui=null) {
		
		if($oGui) {
			$types = [
				'' => '-- '.$oGui->t('Beziehung').' --',
				'emergency' => $oGui->t('Notfallkontakt'),
				'parent' => $oGui->t('Eltern'),
				'other' => $oGui->t('Sonstige'),
			];
		} else {
			$types = [
				'emergency',
				'parent',
				'other'
			];
		}
		
		return $types;
	}
	
	public static function getDiffPart() {
		return WDDate::DAY;
	}
	
	public static function getVisumDue() {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		if(!$oSchool) {
			$oSchool = Ext_Thebing_Client::getFirstSchool();
		}
		
		$iVisumDue = (int)$oSchool->visum_due;
		
		return $iVisumDue;
	}
	
	public static function getPassportDue() {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		if(!$oSchool) {
			$oSchool = Ext_Thebing_Client::getFirstSchool();
		}
		
		$iPassportDue = (int)$oSchool->passport_due;
		
		return $iPassportDue;
	}

	/**
	 * @param string $sType
	 * @return \Elastica\Query\BoolQuery
	 */
	public static function getPaymentTypeFilterOptionQuery($sType) {

		switch($sType) {
			case 'open_and_overpayed':
				
				// Nicht komplett bezahlt, egal ob fällig oder nicht
				$oBool = new \Elastica\Query\BoolQuery();

				$oQuery = new \Elastica\Query\Term();
				$oQuery->setTerm('amount_open_original', 0);
				$oBool->addMustNot($oQuery);

				return $oBool;
			case 'open':

				// Nicht komplett bezahlt, egal ob fällig oder nicht
				$oBool = new \Elastica\Query\BoolQuery();

				$oQuery = new \Elastica\Query\Range('amount_open_original', ['gt' => 0]);
				$oBool->addMust($oQuery);

				return $oBool;
			case 'due':

				$oBool = self::getPaymentDueQuery(['lte' => date('Y-m-d')]);

				return $oBool;
			case 'prepayed':

				// Nicht komplett bezahlt, ein Betrag wurde aber bereits bezahlt
				$oBool = new \Elastica\Query\BoolQuery();

				$oQuery = new \Elastica\Query\Range('amount_open_original', ['gt' => 0]);
				$oBool->addMust($oQuery);

				$oQuery = new \Elastica\Query\Range('amount_payed_original', ['gt' => 0]);
				$oBool->addMust($oQuery);

				return $oBool;
			case 'payed':

				// Komplett bezahlt und Betrag vorhanden
				$oBool = new \Elastica\Query\BoolQuery();

				$oQuery = new \Elastica\Query\Range('amount_total_original', ['gt' => 0]);
				$oBool->addMust($oQuery);

				$oQuery = new \Elastica\Query\Range('amount_open_original', ['lte' => 0]);
				$oBool->addMust($oQuery);

				return $oBool;
			default:
				throw new InvalidArgumentException('Invalid payment filter type '.$sType);
		}

	}

	/**
	 * Elasticsearch-Query für fällige Zahlungen (Buchungen und Dokumente)
	 *
	 * @param array $aCriteria
	 * @return \Elastica\Query\BoolQuery
	 */
	public static function getPaymentDueQuery(array $aCriteria) {

		$oBool = new \Elastica\Query\BoolQuery();

		$oQuery = new \Elastica\Query\Range('amount_total_original', ['gt' => 0]);
		$oBool->addMust($oQuery);

		$oQuery = new \Elastica\Query\Range('amount_open_original', ['gt' => 0]);
		$oBool->addMust($oQuery);

		$oQuery = new \Elastica\Query\Range('paymentterms_next_amount_original', ['gt' => 0]);
		$oBool->addMust($oQuery);

		$oQuery = new \Elastica\Query\Range('paymentterms_next_date_original', $aCriteria);
		$oBool->addMust($oQuery);

		return $oBool;

	}

	/**
	 * Gibt ein Array mit Ja/Nein zurück
	 *
	 * @deprecated Ext_Thebing_Util::getYesNoArray() nutzen
	 * @return array
	 */
	public static function getYesNo() {
		$aReturn = Ext_Thebing_Util::getYesNoArray();
		return $aReturn;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Html_Div
	 */
	public static function createCustomerSearchDiv($oGui) {

		$oDivLabel = new Ext_Gui2_Html_Div();
		$oDivLabel->class = 'GUIDialogRowLabelDiv';
		$oDivLabel->setElement($oGui->t('Kunden mit gleichen Daten (anklicken zum Kopieren persönlicher Daten)'));

		$oDivResults = new Ext_Gui2_Html_Div();
		$oDivResults->class = 'GUIDialogRowInputDiv';
		$oDivResults->id = 'saveid[customer_results_list]';

		$oDivCleaner = new Ext_Gui2_Html_Div();
		$oDivCleaner->class = 'divCleaner';

		$oMainDiv = new Ext_Gui2_Html_Div();
		$oMainDiv->class = 'GUIDialogRow';
		$oMainDiv->setElement($oDivLabel);
		$oMainDiv->setElement($oDivResults);
		$oMainDiv->setElement($oDivCleaner);

		return $oMainDiv;

	}

	/**
	 * @inheritdoc
	 */
	public function getEditDialogData($aSelectedIds, $aSaveData = [], $sAdditional = false) {

		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

		if(!$this->oWDBasic) {
			$this->getWDBasicObject($aSelectedIds);
		}

		$oAccess = Access::getInstance();

		if($oAccess->hasRight('thebing_invoice_sales_person')) {
			// Inquiry direkt benutzen, da $this->oWDBasic von update_select_options verändert wird und null immer 0 sein wird
			$oInquiry = Ext_TS_Inquiry::getInstance(reset($aSelectedIds));

			// Der Wert 0 bedeutet dass nicht mehr gesucht werden soll (Wert wurde schon gespeichert).
			if($oInquiry->id === 0) {
				foreach($aData as &$aSaveFields) {
					if(
						$aSaveFields['db_alias'] === 'ki' &&
						$aSaveFields['db_column'] === 'sales_person_id'
					) {
						$aSaveFields['value'] = $this->oWDBasic->allocateSalesperson(true);
						break;
					}
				}
			}
		}

		return $aData;
	}

	public static function getSponsoringTab(\Ext_Gui2_Dialog $oDialog, \Ext_Thebing_Gui2 $oGui, Ext_Gui2_Dialog_Tab $oTabSponsoring) {

//		$oSponsoringRepository = TsSponsoring\Entity\Sponsor::getRepository();
//		$aSponsorsForSelect = $oSponsoringRepository->getSponsorsForSelect();

		$oDivInfo = $oDialog->createNotification($oGui->t('Achtung'), $oGui->t('Uploads können erst nach dem Speichern hinzugefügt werden.'), 'hint', array('row_class' => 'upload_save_info', 'row_style' => 'display: none;'));
		$oTabSponsoring->setElement($oDivInfo);

		$oDiv = $oDialog->createRow(
			$oGui->t('Gesponserter Schüler'),
			'checkbox',
			[
				'db_column' => 'sponsored',
				'db_alias' => 'ki',
			]
		);
		$oTabSponsoring->setElement($oDiv);

		$oDiv = $oDialog->createRow(
			$oGui->t('Büro'),
			'select',
			[
				'db_column' => 'sponsor_id',
				'db_alias' => 'ki',
//				'select_options' => Ext_Thebing_Util::addEmptyItem($aSponsorsForSelect, '-- '.$oGui->t('Sponsor').' --'),
				'selection' => new Ext_TS_Inquiry_Gui2_Selection_Sponsor(),
//				'child_visibility' => array(
//					[
//						'db_column' => 'sponsor_contact_id',
//						'db_alias' => 'ki',
//						'on_values' => array_keys($aSponsorsForSelect)
//					]
//				),
			]
		);
		$oTabSponsoring->setElement($oDiv);

		$oDiv = $oDialog->createRow(
			$oGui->t('Mitarbeiter'),
			'select',
			[
				'db_column' => 'sponsor_contact_id',
				'db_alias' => 'ki',
				'selection' => new TsSponsoring\Gui2\Selection\SponsorContacts(),
				'dependency' => [
					[
						'db_alias' => 'ki',
						'db_column' => 'sponsor_id'
					],
				],

			]
		);
		$oTabSponsoring->setElement($oDiv);

		$oDiv = $oDialog->createRow(
			$oGui->t('ID'),
			'input',
			[
				'db_column' => 'sponsor_id_number',
				'db_alias' => 'tc_c_d'
			]
		);
		$oTabSponsoring->setElement($oDiv);

	}

	protected function requestCanvasTransfer($aData) {
		
		$aSelectedIds = $aData['id'];
		
		$aInquiries = \Ext_TS_Inquiry::getRepository()
				->findBy([ 'id' => $aSelectedIds ]);
		
		$oCanvas = new \TsCanvas\Api();
		$aErrors = $aStudents = [];
		
		foreach ($aInquiries as $oInquiry) {
			/* @var $oInquiry \Ext_TS_Inquiry */			
			$oCustomer = $oInquiry->getTraveller();		
			
			if($oCanvas->hasUser($oCustomer)) {
				$aResponse = $oCanvas->updateUser($oCustomer);
			} else {
				$aResponse = $oCanvas->createUser($oCustomer);				
			}
			
			if($aResponse === false) {
				$sError = $this->_oGui->t('Der Schüler "%s" konnte nicht übertragen werden');
				$aErrors[] = sprintf($sError, $oCustomer->getName());
			} else {
				$aStudents[] = $oCustomer->getName();
			}
			
		}

		$aTransfer = [];
		if(empty($aErrors)) {
			
			$sSuccess = $this->_oGui->t('Folgende Schüler wurden erfolgreich übertragen:').'<br><br>';			
			$sSuccess .= implode('<br/>', $aStudents);
			
			$aTransfer['action'] = 'showSuccess';
			$aTransfer['success'] = 1;
			$aTransfer['message'] = $sSuccess;
			$aTransfer['success_title'] = $this->_oGui->t('Erfolgreich übertragen');
		} else {
			$aTransfer['action'] = 'showError';
			$aTransfer['error'] = $aErrors;
		}
		
		return $aTransfer;
	}

	/**
	 * Funktion wird für GUI-Action verwendet
	 * Bestätigt Buchungen manuell
	 * @param $aData
	 */
	public function confirmBooking($aData) {

		DB::begin(__METHOD__);

		/** @var \Ext_TS_Inquiry $oInquiry */
		$oInquiry = $this->getWDBasicObject($aData['id']);
		$aInquiries = $oInquiry->hasGroup() ? $oInquiry->getGroup()->getMembers() : [$oInquiry];

		foreach ($aInquiries as $oInquiry) {
			$oInquiry->confirm();
			$oInquiry->save();
		}

		DB::commit(__METHOD__);

		$aTransfer = [];
		$aTransfer['action'] = 'loadTable';
		$aTransfer['error'] = [];

		return $aTransfer;
	}
		
	static public function getCheckinFilterOptionQuery($bValue, $sField) {
		
		$oBool = new \Elastica\Query\BoolQuery();
		$oQuery = new \Elastica\Query\Exists($sField);

		if($bValue) {
			
			$oBool->addMust($oQuery);
				
		} else {
			
			$oBool->addMustNot($oQuery);

		}
		
		return $oBool;
	}
	
	protected function getInquiries(array $aIds) {

		$aInquiries = [];
		foreach($aIds as $iInquiryId) {
			$aInquiries[]= call_user_func(array($this->_oGui->class_wdbasic, 'getInstance'), $iInquiryId);
		}

		return $aInquiries;
	}


	public function requestConfirmArrival($aVars) {
		
		$aInquiries = $this->getInquiries($aVars['id']);
		
		array_walk($aInquiries, function(Ext_TS_Inquiry $oInquiry) {
			$oInquiry->checkin = time();
			$oInquiry->checkout = null;
			$oInquiry->save();
			
			\Log::add(Ext_TS_Inquiry::LOG_CHECKIN, $oInquiry->id, Ext_TS_Inquiry::class);
			
			\System::wd()->executeHook('ts_inquiry_confirm_arrival', $oInquiry);

			\Ts\Events\Inquiry\CheckIn::dispatch($oInquiry);
		});

		$aTransfer = array();
		$aTransfer['error'] = array();
		$aTransfer['action'] = 'loadTable';
		
		return $aTransfer;
	}
	
	public function requestConfirmArrivalUndo($aVars) {
		
		$aInquiries = $this->getInquiries($aVars['id']);
		
		array_walk($aInquiries, function(Ext_TS_Inquiry $oInquiry) {
			$oInquiry->checkin = null;
			$oInquiry->save();
			
			\Log::add(Ext_TS_Inquiry::LOG_CHECKIN_UNDO, $oInquiry->id, Ext_TS_Inquiry::class);
			
		});
		
		$aTransfer = array();
		$aTransfer['error'] = array();
		$aTransfer['action'] = 'loadTable';
		
		return $aTransfer;
	}
	
	public function requestConfirmDeparture($aVars) {
		
		$aErrors = $aTransfer = [];
		
		$aInquiries = $this->getInquiries($aVars['id']);
		
		$oPersister = WDBasic_Persister::getInstance();

		if(empty($aVars['save']['confirm'])) {
			foreach($aInquiries as $oInquiry) {

				\System::wd()->executeHook('ts_inquiry_confirm_departure_check', $oInquiry, $aTransfer);

				if(empty($aTransfer)) {

					$fOpenAmount = $oInquiry->getOpenPaymentAmount();

					if($fOpenAmount > 0) {

						$aErrors[] = sprintf($this->t('Der Schüler "%s" hat noch einen ausstehenden Betrag in Höhe von %s!'), $oInquiry->getTraveller()->getCustomerNumber(), \Ext_Thebing_Format::Number($fOpenAmount, 1));

					}

				}

			}
		}
		
		// Dialog
		$oDialog = $this->_oGui->createDialog($this->t('Achtung'), $this->t('Achtung'));
		$oDialog->width = 600;
		$oDialog->height = 300;

		$oDiv = $oDialog->create('div');
		$oDiv->setElement(implode('<br>', $aErrors));

		$oDialog->setElement($oDiv);

		$oDialog->setElement($oDialog->createSaveField('hidden', [
			'db_column' => 'confirm',
			'value' => 1
		]));

		$aTransfer['data'] = $oDialog->generateAjaxData($aVars['id'], $this->_oGui->hash);
		
		if(empty($aErrors)) {

			foreach($aInquiries as $oInquiry) {

				if($oInquiry->checkout === null) {
					$oInquiry->checkout = time();
					$oInquiry->save();

					\Log::add(Ext_TS_Inquiry::LOG_CHECKOUT, $oInquiry->id, Ext_TS_Inquiry::class);

					\System::wd()->executeHook('ts_inquiry_confirm_departure', $oInquiry, $aTransfer);

					\Ts\Events\Inquiry\CheckOut::dispatch($oInquiry);
				}
					
			}
			
			$aTransfer['error'] = array();
			$aTransfer['action'] = 'closeDialogAndReloadTable';
			
		} else {

			$aTransfer['action'] = 'openDialog';
			$aTransfer['data']['bSaveButton'] = false;
			$aTransfer['data']['task'] = 'request';
			$aTransfer['data']['action'] = 'confirmDeparture';
			$aTransfer['data']['buttons'] = [
				[
					'label' => $this->t('Abbrechen'),
					'task' => 'closeDialog',
					'action' => 'generate-example',
					'default' => true
				],
				[
					'label' => $this->t('Trotzdem auschecken'),
					'task' => 'saveDialog',
					'action' => 'confirmDeparture'
				]
			];

			return $aTransfer;
		}
		
		return $aTransfer;
	}
	
	public function requestConfirmDepartureUndo($aVars) {
		
		$aInquiries = $this->getInquiries($aVars['id']);
		
		array_walk($aInquiries, function(Ext_TS_Inquiry $oInquiry) {
			$oInquiry->checkout = null;
			$oInquiry->save();
			
			\Log::add(Ext_TS_Inquiry::LOG_CHECKOUT_UNDO, $oInquiry->id, Ext_TS_Inquiry::class);
			
		});

		$aTransfer = array();
		$aTransfer['error'] = array();
		$aTransfer['action'] = 'loadTable';
		
		return $aTransfer;
	}

	/**
	 * @see requestCreateAdditionalServiceDocuments
	 */
	public function requestDocumentOverview() {

		/** @var Ext_TS_Inquiry $oInquiry */
		$oInquiry = $this->_getWDBasicObject($this->request->input('id.0'));
		$sBundleDir = (new Core\Helper\Bundle())->getBundleDirectory('Ts');
		$aItems = $oInquiry->buildDocumentItems();
		
		$invoiceItemService = new Ts\Service\Invoice\Items($oInquiry->getSchool());
		$companyItems = $invoiceItemService->splitItemsByCompany($oInquiry, $aItems);
		
		$companies = \Ext_Thebing_System::getAccountingCompanies(true);
		
		$oService = new Ext_TS_Document_AdditionalServiceDocuments($oInquiry);

		$oTemplateEngine = new Core\Service\Templating();
		$oTemplateEngine->setTranslationPath(Ext_Thebing_Document::$sL10NDescription);
		$oTemplateEngine->assign('oInquiry', $oInquiry);
		$oTemplateEngine->assign('companies', $companies);
		$oTemplateEngine->assign('companyItems', $companyItems);
		$oTemplateEngine->assign('aAdditionalDocumentsData', $oService->buildOptions($aItems));

		$oDialog = new \Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->sDialogIDTag = 'DOCUMENT_OVERVIEW_';

		$oDiv = $oDialog->create('DIV');
		$oDiv->setElement($oTemplateEngine->fetch($sBundleDir.'/Resources/views/documents/document_overview.tpl'));
		$oDialog->setElement($oDiv);

		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($this->request->input('action'), $this->request->input('id'), $this->request->input('additional'));
		$aTransfer['data']['js'] = file_get_contents($sBundleDir.'/Resources/js/document_overview.js');
		$aTransfer['data']['title'] = sprintf($this->t('Dokumentenübersicht "%s"'), $oInquiry->getTraveller()->getName());
		$aTransfer['action'] = 'openDialog';

		return $aTransfer;

	}

	/**
	 * @see requestDocumentOverview
	 */
	public function requestCreateAdditionalServiceDocuments() {

		/** @var Ext_TS_Inquiry $oInquiry */
		$oInquiry = $this->_getWDBasicObject($this->request->input('inquiry_id'));
		$oService = new Ext_TS_Document_AdditionalServiceDocuments($oInquiry);

		$aTemplateIds = $this->request->input('template_id', []);
		if (!empty($aTemplateIds)) {
			$oService->prepareBackgroundTasks($this->request->input('template_id', []));
			return ['success' => true];
		}

		return ['success' => false];

	}
	
	public function requestInvoiceOverview($aVars) {

		/** @var Ext_TS_Inquiry $inquiry */
		$inquiry = $this->_getWDBasicObject($aVars['id'][0]);
		$aItems = $inquiry->buildDocumentItems();
		
		$invoiceItemService = new Ts\Service\Invoice\Items($inquiry->getSchool());
		$companyItems = $invoiceItemService->splitItemsByCompany($inquiry, $aItems);
		
		$companies = \Ext_Thebing_System::getAccountingCompanies(true);
		
		$sBundleDir = (new Core\Helper\Bundle())->getBundleDirectory('Ts');

		$oDialog = new \Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		
		if($inquiry->isConfirmed()) {
			$oDialog->aButtons = [
				[
					'label' => $this->t('Buchung stornieren'), 
					'task' => 'request',
					'action' => 'cancelBooking',
					'request_data' => '',
					'default' => true
				],
				[
					'label' => $this->t('Rechnung erstellen'), 
					'task' => 'request',
					'action' => 'createInvoice',
					'request_data' => ''
				]
			];
		}
		
		$oDialog->sDialogIDTag = 'INVOICE_OVERVIEW_';

		$aPartialInvoices = Ts\Entity\Inquiry\PartialInvoice::getRepository()->findBy(['inquiry_id'=>$inquiry->id]);
		
		$aInvoices = $inquiry->getDocuments('invoice_with_creditnote_and_manual_creditnote');

		$oTemplateEngine = new Core\Service\Templating();
		$oTemplateEngine->setTranslationPath(Ext_Thebing_Document::$sL10NDescription);
		$oTemplateEngine->assign('oInquiry', $inquiry);
		$oTemplateEngine->assign('companies', $companies);
		$oTemplateEngine->assign('companyItems', $companyItems);
		$oTemplateEngine->assign('aInvoices', $aInvoices);
		$oTemplateEngine->assign('aPartialInvoices', $aPartialInvoices);

		$oDiv = $oDialog->create('DIV');
		$oDiv->setElement($oTemplateEngine->fetch($sBundleDir.'/Resources/views/invoices/overview.tpl'));
		
		$oDialog->setElement($oDiv);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);
		$aTransfer['data']['js'] = file_get_contents($sBundleDir.'/Resources/js/invoice_overview_partial.js');
		$aTransfer['data']['title'] = sprintf($this->t('Rechnungsübersicht "%s"'), $inquiry->getTraveller()->getName());
		$aTransfer['data']['buttons'] = $oDialog->aButtons;
		
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		
		return $aTransfer;
	}

	public function requestCancelBooking($aVars) {
		
		/** @var \Ext_TS_Inquiry $inquiry */
		$inquiry = $this->_getWDBasicObject($aVars['id'][0]);
		
		$school = $inquiry->getSchool();
		$language = $inquiry->getLanguage();

		// Dummy-Dokument nur zum Ermitteln der Items
		$document = $inquiry->newDocument('proforma_brutto');

		/** @var \Ext_Thebing_Inquiry_Document_Version $version */
		$version = $document->getJoinedObjectChild('versions'); 
		$version->setInquiry($inquiry);
		$version->tax = $school->tax;
		$version->template_language = $language;
		$version->sLanguage = $language;

		$generatedItems = $version->buildStornoItems();
		
		$generatedItems = $version->sortPositions((array)$generatedItems);

		foreach($generatedItems as &$generatedItem) {
			if(empty($generatedItem['item_key'])) {
				$generatedItem['item_key'] = \Util::generateRandomString(16);
			}
		}
		
		$templateType = 'document_invoice_storno';
		$view = 'gross';
		
		// Template-Auswahl
		if(
			$inquiry->hasAgency() &&
			$inquiry->hasNettoPaymentMethod()
		) {
			$view = 'net';
		}
		
		$inbox = $inquiry->getInbox();
		
		$templates = \Ext_Thebing_Pdf_Template_Search::s($templateType, false, $inquiry->getSchool()->id, $inbox?->id, true);
		
		$invoiceItemService = new Ts\Service\Invoice\Items($inquiry->getSchool());
		$companyItems = $invoiceItemService->splitItemsByCompany($inquiry, $generatedItems);
		
		$this->cancelBookingItems = $companyItems;
		
		$companies = \Ext_Thebing_System::getAccountingCompanies(true);
		
		$oDialog = new \Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->aButtons = [
			[
				'label' => $this->t('Abbrechen'), 
				'task' => 'request',
				'action' => 'invoiceOverview',
				'request_data' => '',
				'default' => true
			],
			[
				'label' => $this->t('Buchung stornieren'), 
				'task' => 'saveDialog',
				'request_data' => '',
			]
		];
		$oDialog->sDialogIDTag = 'INVOICE_OVERVIEW_';

		$sBundleDir = (new Core\Helper\Bundle())->getBundleDirectory('Ts');

		$oTemplateEngine = new Core\Service\Templating();
		$oTemplateEngine->setTranslationPath(Ext_Thebing_Document::$sL10NDescription);
		$oTemplateEngine->assign('oInquiry', $inquiry);
		$oTemplateEngine->assign('companies', $companies);
		$oTemplateEngine->assign('companyItems', $companyItems);
		$oTemplateEngine->assign('templates', $templates);
		$oTemplateEngine->assign('view', $view);
		$oTemplateEngine->assign('notification', $oDialog->createNotification(
			$this->_oGui->t('Möchten Sie die Buchung wirklich stornieren?'),
			$this->_oGui->t('Bitte beachten Sie, dass eine Stornierung nicht mehr rückgängig gemacht werden kann! Falls es sich um eine Gruppe handelt, dann wird die komplette Gruppe unwiderruflich storniert.'),
			'hint'
		)->generateHTML());

		$oDiv = $oDialog->create('DIV');
		$oDiv->setElement($oTemplateEngine->fetch($sBundleDir.'/Resources/views/invoices/cancel_booking.tpl'));
		
		$oDialog->setElement($oDiv);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);
		$aTransfer['data']['js'] = file_get_contents($sBundleDir.'/Resources/js/create_invoice.js');
		$aTransfer['data']['title'] = sprintf($this->t('Buchung "%s" stornieren'), $inquiry->getTraveller()->getName());
		$aTransfer['data']['buttons'] = $oDialog->aButtons;
		
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		$aTransfer['data']['task'] = 'saveDialog';
		$aTransfer['data']['action'] = 'cancel_booking';
				
		return $aTransfer;
	}
		
	public function requestCreateInvoice($aVars) {
		
		/** @var \Ext_TS_Inquiry $inquiry */
		$inquiry = $this->_getWDBasicObject($aVars['id'][0]);
		
		$school = $inquiry->getSchool();
		$language = $inquiry->getLanguage();

		// Dummy-Dokument nur zum Ermitteln der Items
		$document = $inquiry->newDocument('proforma_brutto');

		/** @var \Ext_Thebing_Inquiry_Document_Version $version */
		$version = $document->getJoinedObjectChild('versions'); 
		$version->setInquiry($inquiry);
		$version->tax = $school->tax;
		$version->template_language = $language;
		$version->sLanguage = $language;

		$generatedItems = $version->buildItems();

		// Wenn es nicht die erste Rechnung ist, dann DIFF anzeigen		
		if($inquiry->has_invoice) {
			
			$diffService = new \Ts\Service\Invoice\Diff($inquiry);
			$diffService->loadItemsFromInvoices();
		
			$generatedItems = $diffService->getDiff($generatedItems);

		}
		
		// Template-Auswahl
		if(
			$inquiry->hasAgency() &&
			$inquiry->hasNettoPaymentMethod()
		) {
			$templateType = 'document_invoice_agency';
			$view = 'net';
		} else {
			$templateType = 'document_invoice_customer';
			$view = 'gross';
		}
		
		$inbox = $inquiry->getInbox();
		
		$templates = \Ext_Thebing_Pdf_Template_Search::s($templateType, false, $inquiry->getSchool()->id, $inbox?->id, true);
		
		$invoiceItemService = new Ts\Service\Invoice\Items($inquiry->getSchool());
		$companyItems = $invoiceItemService->splitItemsByCompany($inquiry, $generatedItems);
		
		$this->createInvoiceItems = $companyItems;
		
		$companies = \Ext_Thebing_System::getAccountingCompanies(true);
		
		$oDialog = new \Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->aButtons = [
			[
				'label' => $this->t('Abbrechen'), 
				'task' => 'request',
				'action' => 'invoiceOverview',
				'request_data' => '',
				'default' => true
			],
			[
				'label' => $this->t('Rechnung speichern'), 
				'task' => 'saveDialog',
				'request_data' => '',
			]
		];
		$oDialog->sDialogIDTag = 'INVOICE_OVERVIEW_';

		$sBundleDir = (new Core\Helper\Bundle())->getBundleDirectory('Ts');

		$oTemplateEngine = new Core\Service\Templating();
		$oTemplateEngine->setTranslationPath(Ext_Thebing_Document::$sL10NDescription);
		$oTemplateEngine->assign('oInquiry', $inquiry);
		$oTemplateEngine->assign('companies', $companies);
		$oTemplateEngine->assign('companyItems', $companyItems);
		$oTemplateEngine->assign('templates', $templates);
		$oTemplateEngine->assign('view', $view);
		
		$oDiv = $oDialog->create('DIV');
		$oDiv->setElement($oTemplateEngine->fetch($sBundleDir.'/Resources/views/invoices/create.tpl'));
		
		$oDialog->setElement($oDiv);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);
		$aTransfer['data']['js'] = file_get_contents($sBundleDir.'/Resources/js/create_invoice.js');
		$aTransfer['data']['title'] = sprintf($this->t('Rechnung für "%s" erstellen'), $inquiry->getTraveller()->getName());
		$aTransfer['data']['buttons'] = $oDialog->aButtons;
		
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		$aTransfer['data']['task'] = 'saveDialog';
		$aTransfer['data']['action'] = 'create_invoice';
				
		return $aTransfer;
	}
	
	private function addPartialInvoicesTable(&$transferData, Ext_TS_Inquiry $inquiry) {

		$partialInvoices = Ts\Entity\Inquiry\PartialInvoice::getRepository()->findBy(['inquiry_id'=>$inquiry->id]);

		$invoices = $inquiry->getDocuments('invoice_with_creditnote_and_manual_creditnote');
		
		$oTemplateEngine = new Core\Service\Templating();
		$oTemplateEngine->setTranslationPath(Ext_Thebing_Document::$sL10NDescription);
		$oTemplateEngine->assign('oInquiry', $inquiry);
		$oTemplateEngine->assign('aPartialInvoices', $partialInvoices);
		$oTemplateEngine->assign('aInvoices', $invoices);

		$oBundleHelper = new Core\Helper\Bundle();

		$sBundleDir = $oBundleHelper->getBundleDirectory('Ts');

		$transferData['js'] = file_get_contents($sBundleDir.'/Resources/js/invoice_overview_partial.js');
		$transferData['html'] = $oTemplateEngine->fetch($sBundleDir.'/Resources/views/invoices/partial_invoices.tpl');

	}

	public function requestMarkGenerated($aVars) {
	
		$aTransfer = [
			'action' => 'reloadPartialInvoiceTable',
			'data' => []
		];

		$partialInvoice = \Ts\Entity\Inquiry\PartialInvoice::getInstance((int)$aVars['partial_invoice_id']);
		
		if($partialInvoice->exist()) {

			$inquiry = $partialInvoice->getJoinedObject('inquiry');

			if(!empty($aVars['undo'])) {
				$partialInvoice->converted = null;
				\Log::add(Ext_TS_Inquiry::LOG_PARTIALINVOICES_UNMARK, $inquiry->id, Ext_TS_Inquiry::class);
			} else {
				$partialInvoice->converted = time();
				\Log::add(Ext_TS_Inquiry::LOG_PARTIALINVOICES_MARK, $inquiry->id, Ext_TS_Inquiry::class);
			}
			$partialInvoice->save();
		
			$this->addPartialInvoicesTable($aTransfer['data'], $inquiry);
			
		}
		
		echo json_encode($aTransfer);
		die();
	}
		
	public function requestPartialInvoicesRefresh($aVars) {
	
		$transfer = [
			'action' => 'reloadPartialInvoiceTable',
			'data' => []
		];

		/** @var Ext_TS_Inquiry $inquiry */
		$inquiry = $this->getWDBasicObject($aVars['id']);

		$inquiry->generatePartialInvoices();
		
		\Log::add(Ext_TS_Inquiry::LOG_PARTIALINVOICES_REFRESH, $inquiry->id, Ext_TS_Inquiry::class);
		
		$this->addPartialInvoicesTable($transfer['data'], $inquiry);
		
		echo json_encode($transfer);
		die();
	}
	
	public function requestPartialInvoicesSaveDeposit($aVars) {
	
		/** @var Ext_TS_Inquiry $inquiry */
		$inquiry = $this->getWDBasicObject($aVars['id']);

		$errors = [];
		
		$date = Ext_Thebing_Format::ConvertDate($aVars['date'], null, 3);
		
		if(!WDDate::isDate($date->format('Y-m-d'), WDDate::DB_DATE)) {
			$errors[] = $this->t('Das Datum ist nicht gültig.');
		}

		$amount = (float)$aVars['amount'];

		$partialInvoices = Ts\Entity\Inquiry\PartialInvoice::getRepository()->findBy(['inquiry_id'=>$inquiry->id]);

		$newIsDeposit = true;
		
		// Folgende Teilrechnung nach Datum ermitteln
		foreach($partialInvoices as $partialInvoice) {
			
			$partialInvoiceDate = new \Carbon\Carbon($partialInvoice->date);
			
			if($partialInvoiceDate > $date) {
				$nextPartialInvoices = $partialInvoice;
				break;
			}
			
			$newIsDeposit = false;
			
		}

		if(!$nextPartialInvoices instanceof \Ts\Entity\Inquiry\PartialInvoice) {
			$errors[] = $this->t('Das Datum muss früher sein als das der letzten Teilrechnung.');
		}
		
		if($amount > $nextPartialInvoices->amount) {
			$errors[] = $this->t('Der Betrag muss kleiner sein, als der Betrag der ersten Teilrechnung.');
		}
			
		if(!empty($errors)) {
			
			array_unshift($errors, $this->t('Es ist ein Fehler aufgetreten'));
			
			$transfer = [
				'action' => 'showError',
				'error' => $errors
			];
			
		} else {

			// Eintrag anlegen
			$new = new Ts\Entity\Inquiry\PartialInvoice;
			$new->inquiry_id = $inquiry->id;
			$new->payment_condition_id = $inquiry->partial_invoices_terms;
			$new->from = $date->format('Y-m-d');
			$new->until = $date->format('Y-m-d');
			$new->date = $date->format('Y-m-d');
			$new->amount = $amount;
			$new->additional = json_encode(['manual_entry'=>true]);
			if($newIsDeposit) {
				$new->type = 'deposit';
			} else {
				$new->type = 'interim';
			}
			$new->save();
		
			$nextAdditional = $nextPartialInvoices->getAdditional();
			if(!isset($nextAdditional['amount_manipulated'])) {
				$nextAdditional['amount_manipulated'] = [];
			}
			$nextAdditional['amount_manipulated'][] = ['previous'=>$nextPartialInvoices->amount, 'by'=> $new->id];
			$nextPartialInvoices->additional = json_encode($nextAdditional);
			$nextPartialInvoices->amount -= $amount;
			$nextPartialInvoices->save();
			
			\Log::add(Ext_TS_Inquiry::LOG_PARTIALINVOICES_ADDED, $inquiry->id, Ext_TS_Inquiry::class);
			
			$transfer = [
				'action' => 'reloadPartialInvoiceTable',
				'data' => []
			];

			$this->addPartialInvoicesTable($transfer['data'], $inquiry);
			
		}
		
		echo json_encode($transfer);
		die();
	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @return int[][]
	 */
	public static function getCourseDialogData(Ext_Thebing_School $oSchool) {

		$aData = array_map(function(Ext_Thebing_Tuition_Course $oCourse) {

			$aPrograms = $oCourse->getPrograms()
				->map(fn (\TsTuition\Entity\Course\Program $oProgram) => ['value' => $oProgram->getId(), 'text' => $oProgram->getName()])
				->values()
				->toArray();

			$courseLessons = $oCourse->getLessons();

			return [
				'id' => (int)$oCourse->id,
				'name' => $oCourse->getName(),
				'category_id' => (int)$oCourse->category_id,
				'per_unit' => (int)$oCourse->per_unit,
				'lessons' => array_map(fn ($number) => ['value' => $number, 'text' => $number], (array)$courseLessons?->getLessons()),
				'lessons_unit' => $courseLessons?->getUnit()->value,
				'lessons_fix' => $courseLessons?->isFix(),
				'programs' => $aPrograms,
				'course_languages' => $oCourse->course_languages
			];
		}, $oSchool->getCourses());

		array_unshift($aData, [
			'id' => 0,
			'name' => L10N::t('Kein Kurs', \Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription),
			'category_id' => 0,
			'per_unit' => 0,
			'lessons' => [],
			'lessons_unit' => null,
			'lessons_fix' => false,
			'programs' => [],
			'course_languages' => []
		]);

		return $aData;

	}

	public static function getServiceOptionsForIndex() {

		return [
			\Ext_TS_Inquiry::SERVICE_DEACTIVATED_PARTLY => \L10N::t('Teilweise deaktiviert'),
			\Ext_TS_Inquiry::SERVICE_DEACTIVATED_COMPLETELY => \L10N::t('Komplett deaktiviert'),
			\Ext_TS_Inquiry::SERVICE_ACTIVE => \L10N::t('Alle aktiv'),
			\Ext_TS_Inquiry::NO_SERVICE_AVAILABLE => \L10N::t('Keine vorhanden')
		];
	}
	
	protected function saveCreateInvoice($aSelectedIds, $aData) {
		
		$inquiry = $this->getWDBasicObject($aSelectedIds);

		$documentType = 'brutto';
		$view = 'gross';
		if(
			$inquiry->hasAgency() &&
			$inquiry->hasNettoPaymentMethod()
		) {
			$documentType = 'netto';
			$view = 'net';
		}
		
		$invoicesData = $this->_oGui->getRequest()->input('invoice');

		// Input aus Dialog in zwischengespeicherte Items übernehmen
		foreach($this->createInvoiceItems as $companyId=>&$items) {
			foreach($items as $itemKey=>&$item) {
				
				if(empty($invoicesData[$companyId]['items'][$itemKey]['active'])) {
					unset($this->createInvoiceItems[$companyId][$itemKey]);
					continue;
				}
				
				if(isset($invoicesData[$companyId]['items'][$itemKey]['amount'])) {
					
					if($view === 'net') {
						$item['amount'] = \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount']);
						$item['amount_provision'] =  \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount_provision']);
						$item['amount_net'] = \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount_net']);
					} else {
						$item['amount'] = $item['amount_net'] = \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount']);
					}
					
				}
				
			}
		}
		
		// Rechnung pro Firma erstellen
		foreach($this->createInvoiceItems as $companyId=>&$items) {
			
			if(empty($items)) {
				continue;
			}
			
			$template = \Ext_Thebing_Pdf_Template::getInstance((int)$invoicesData[$companyId]['template_id']);
			$contact = $inquiry->getCustomer();

			$paymentCondition = $inquiry->getPaymentCondition();
			
			$service = new \Ts\Helper\Document($inquiry, $inquiry->getSchool(), $template, $contact->corresponding_language);
			
			$service->create($documentType, $companyId);
			$service->setAddress($view);
			$service->setItems($items);
			$service->setPaymentConditions($paymentCondition);
			$service->setUser(\Access_Backend::getInstance()->getUser());

			$service->save(true);
		
		}
				
		$transfer = $this->requestInvoiceOverview($this->_oGui->getRequest()->getAll());
		$transfer['action'] = 'saveDialogCallback';
		$transfer['error'] = array();
		$transfer['success_message'] = $this->t('Die Rechnung wurde gespeichert.');

		return $transfer;
	}

	protected function saveCancelBooking($aSelectedIds, $aData) {
		
		$inquiry = $this->getWDBasicObject($aSelectedIds);

		$documentType = 'storno';
		$view = 'gross';
		if(
			$inquiry->hasAgency() &&
			$inquiry->hasNettoPaymentMethod()
		) {
			$view = 'net';
		}
		
		$invoicesData = $this->_oGui->getRequest()->input('invoice');

		// Input aus Dialog in zwischengespeicherte Items übernehmen
		foreach($this->cancelBookingItems as $companyId=>&$items) {
			foreach($items as $itemKey=>&$item) {
				
				if(empty($invoicesData[$companyId]['items'][$itemKey]['active'])) {
					unset($this->cancelBookingItems[$companyId][$itemKey]);
					continue;
				}
				
				if(isset($invoicesData[$companyId]['items'][$itemKey]['amount'])) {
					
					if($view === 'net') {
						$item['amount'] = \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount']);
						$item['amount_provision'] =  \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount_provision']);
						$item['amount_net'] = \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount_net']);
					} else {
						$item['amount'] = $item['amount_net'] = \Ext_Thebing_Format::convertFloat($invoicesData[$companyId]['items'][$itemKey]['amount']);
					}
					
				}
				
			}
		}

		// Rechnung pro Firma erstellen
		foreach($this->cancelBookingItems as $companyId=>&$items) {
			
			if(empty($items)) {
				continue;
			}
			
			$template = \Ext_Thebing_Pdf_Template::getInstance((int)$invoicesData[$companyId]['template_id']);
			$contact = $inquiry->getCustomer();

			$paymentCondition = $inquiry->getPaymentCondition();
			
			$service = new \Ts\Helper\Document($inquiry, $inquiry->getSchool(), $template, $contact->corresponding_language);
			
			$service->create($documentType, $companyId);
			$service->setAddress($view);
			$service->setItems($items);
			$service->setPaymentConditions($paymentCondition);
			$service->setUser(\Access_Backend::getInstance()->getUser());

			$service->save(true);
		
		}
				
		$cancellationAmount = $inquiry->getAmount(false, true);
		$inquiry->confirmCancellation($cancellationAmount);
		
		$transfer = $this->requestInvoiceOverview($this->_oGui->getRequest()->getAll());
		$transfer['action'] = 'saveDialogCallback';
		$transfer['error'] = array();
		$transfer['success_message'] = $this->t('Die Buchung wurde storniert.');

		return $transfer;
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		
		if($sAction === 'create_invoice') {
			return $this->saveCreateInvoice($aSelectedIds, $aData);
		} elseif($sAction === 'cancel_booking') {
			return $this->saveCancelBooking($aSelectedIds, $aData);
		}
		
		return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
	}

	protected function getImportDialogId() {
		return 'INQUIRY_IMPORT_';
	}

	protected function getImportService(): \Tc\Service\Import\AbstractImport {
		return new \Ts\Service\Import\Inquiry();
	}
}
