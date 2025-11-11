<?php

/**
 * Dialog für die Anfragen an sich (obere GUI)
 *
 * Der Dialog für Journey/Leisutungen (untere GUI) wird hier dynamisch generiert:
 * @see Ext_TS_Enquiry_Combination_Gui2_Data
 */
class Ext_TS_Enquiry_Gui2_Dialog implements \Gui2\Dialog\FactoryInterface {

	/**
	 * @var Ext_Gui2
	 */
	private $gui;

	/**
	 * @var Ext_Gui2_Dialog
	 */
	private $dialog;

	public function create(Ext_Gui2 $gui): Ext_Gui2_Dialog {

		$this->gui = $gui;
		$this->dialog = $gui->createDialog($gui->t('Anfrage editieren'), $gui->t('Neue Anfrage anlegen'));
		$this->dialog->setDataObject(Ext_TS_Enquiry_Gui2_Dialog_Data::class);

		$this->createGeneralTab();
		$this->createRequestTab();
		$this->createGroupTab();

		$this->dialog->setOptionalAjaxData('mothertongues_by_nationality', Ext_Thebing_Nationality::getMotherTonguesByNationality());

		$hookData = ['dialog' => $this->dialog];
		System::wd()->executeHook('ts_enquiry_create_dialog', $hookData);

		return $this->dialog;

	}

	private function createGeneralTab() {

		$tab = $this->dialog->createTab($this->gui->t('Daten'));
		$tab->aOptions = [
			'section' => [
				'enquiries_enquiries', 
				['student_record_general', ['enquiry', 'enquiry_booking']],
				['student_record_course', ['enquiry', 'enquiry_booking']],
				['student_record_accommodation', ['enquiry', 'enquiry_booking']],
				['student_record_matching', ['enquiry', 'enquiry_booking']],
				['student_record_transfer', ['enquiry', 'enquiry_booking']],
				['student_record_visum_status', ['enquiry', 'enquiry_booking']], 
				['student_record_visum', ['enquiry', 'enquiry_booking']],
				['student_record_upload', ['enquiry', 'enquiry_booking']],
				['student_record_insurance', ['enquiry', 'enquiry_booking']],
				['student_record_activities', ['enquiry', 'enquiry_booking']],
				['student_record_sponsoring', ['enquiry', 'enquiry_booking']]
			]
		];
		
		$this->createPersonalData($tab);
		$this->createContactData($tab);
		$this->createAdditionalData($tab);
		$this->createBillingAddressData($tab);
		$this->createBookingData($tab);
		$this->createOtherData($tab);
		$this->dialog->setElement($tab);

	}

	private function createPersonalData(Ext_Gui2_Dialog_Tab $tab) {

		$bIsAllSchools = Ext_Thebing_System::isAllSchools();

		$oSchool = Ext_Thebing_Client::getFirstSchool();

		$aSchoolsForDialog = Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true);
		$aSchoolsForDialog = Ext_Thebing_Util::addEmptyItem($aSchoolsForDialog);

		$aGenders = Ext_Thebing_Util::getGenders();

		$aLangs = Ext_Thebing_Data::getLanguageSkills(true, \System::getInterfaceLanguage());

		$aNationalities = Ext_Thebing_Nationality::getNationalities(true, \System::getInterfaceLanguage(), 0);
		$aNationalities = Ext_Thebing_Util::addEmptyItem($aNationalities, Ext_Thebing_L10N::getEmptySelectLabel('nationalities'));

		System::wd()->executeHook('ts_enquiry_create_dialog_nationalities', $aNationalities);

		$aCorrespondenceLanguages = Ext_Thebing_Data::getCorrespondenceLanguages();

		$aOptions = array(
			'db_alias' => 'ts_ij',
			'db_column' => 'school_id',
			'required' => 1,
			'select_options' => $aSchoolsForDialog
		);

		if (!$bIsAllSchools) {
			$aOptions['readonly'] = 'readonly';
		}

		$tab->setElement($this->dialog->createRow($this->gui->t('Schule'), 'select', $aOptions));

		$oClient = Ext_Thebing_System::getClient();
		$aInboxList = $oClient->getInboxList(true, true);

		if (count($aInboxList) > 1) {
			$tab->setElement($this->dialog->createRow($this->gui->t('Eingang'), 'select', [
				'db_alias' => 'ts_i',
				'db_column' => 'inbox',
				'select_options' => Ext_Thebing_Util::addEmptyItem($aInboxList)
			]));
		}

		$tab->setElement($this->dialog->createRow($this->gui->t('Nachname'), 'input', array(
			'db_alias' => 'cdb1',
			'db_column' => 'lastname',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Vorname'), 'input', array(
			'db_alias' => 'cdb1',
			'db_column' => 'firstname',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Geschlecht'), 'select', array(
			'db_alias' => 'cdb1',
			'db_column' => 'gender',
			'select_options' => $aGenders,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Geburtsdatum'), 'calendar', array(
			'db_alias' => 'cdb1',
			'db_column' => 'birthday',
			'required' => 0,
			'format' => new Ext_Thebing_Gui2_Format_Date(),
			'display_age' => 1,
		)));

		$tab->setElement(Ext_TS_Inquiry_Index_Gui2_Data::createCustomerSearchDiv($this->gui));

		$tab->setElement($this->dialog->createRow($this->gui->t('Nationalität'), 'select', array(
			'db_alias' => 'cdb1',
			'db_column' => 'nationality',
			'required' => 0,
			'select_options' => $aNationalities,
			'events' => [
				[
					'event' => 'change',
					'function' => 'autodiscoverMotherTongue'
				]
			]
		)));


		$tab->setElement($this->dialog->createRow($this->gui->t('Muttersprache'), 'select', array(
			'db_alias' => 'cdb1',
			'db_column' => 'language',
			'required' => 0,
			'select_options' => $aLangs,
			'selection' => new Ext_TS_Gui2_Selection_Enquiry_Mothertongue(),
			'dependency' => array(
				array(
					'db_alias' => 'cdb1',
					'db_column' => 'nationality'
				)
			)
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Korrespondenzsprache'), 'select', array(
			'db_alias' => 'cdb1',
			'db_column' => 'corresponding_language',
			'required' => 1,
			'select_options' => $aCorrespondenceLanguages,
			'default_value' => $oSchool->getLanguage(),
			'dependency' => array(
				array(
					'db_alias' => 'cdb1',
					'db_column' => 'language'
				),
				array(
					'db_alias' => 'ts_ij',
					'db_column' => 'school_id'
				)
			)
		)));

		if (Ext_Thebing_Access::hasRight('thebing_students_contact_group')) {
			$tab->setElement($this->dialog->createRow($this->gui->t('Gruppenanfrage').'?', 'checkbox', array(
//				'db_alias' => 'cdb1',
				'db_column' => 'is_group',
				'events' => array(
					array(
						'event' => 'change',
						'function' => 'toggleGroupTab'
					)
				),
				'skip_value_handling' => true
			)));
		}
	}

	private function createContactData(Ext_Gui2_Dialog_Tab $tab) {

		$aCountries = Ext_Thebing_Data::getCountryList(true, true);

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Kontaktinformationen'));
		$tab->setElement($iH3);

		$tab->setElement($this->dialog->createRow($this->gui->t('Adresse'), 'input', array(
			'db_alias' => 'tc_a_c',
			'db_column' => 'address',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Adresszusatz'), 'input', array(
			'db_alias' => 'tc_a_c',
			'db_column' => 'address_addon',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('PLZ'), 'input', array(
			'db_alias' => 'tc_a_c',
			'db_column' => 'zip',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Stadt'), 'input', array(
			'db_alias' => 'tc_a_c',
			'db_column' => 'city',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Bundesland'), 'input', array(
			'db_alias' => 'tc_a_c',
			'db_column' => 'state',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Land'), 'select', array(
			'db_alias' => 'tc_a_c',
			'db_column' => 'country_iso',
			'required' => 0,
			'select_options' => $aCountries,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Telefon'), 'input', array(
			'db_alias' => 'tc_c_d',
			'db_column' => 'phone_private',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Telefon Büro'), 'input', array(
			'db_alias' => 'tc_c_d',
			'db_column' => 'phone_office',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Handy'), 'input', array(
			'db_alias' => 'tc_c_d',
			'db_column' => 'phone_mobile',
			'required' => 0,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Fax'), 'input', array(
			'db_alias' => 'tc_c_d',
			'db_column' => 'fax',
			'required' => 0,
		)));

		// Manueller wiederholbarer Bereich: E-Mails
		\Ext_Thebing_Inquiry_Gui2_Html::setContactEmailContainer($this->gui, $this->dialog, $tab);

	}

	private function createAdditionalData(Ext_Gui2_Dialog_Tab $tab) {

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Zusatzinformationen'));
		$tab->setElement($iH3);

		$tab->setElement($this->dialog->createRow($this->gui->t('Beruf'), 'input', array(
			'db_alias' => 'ts_i',
			'db_column' => 'profession',
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Sozialversicherungsnummer'), 'input', array(
			'db_alias' => 'ts_i',
			'db_column' => 'social_security_number',
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Automatische E-Mails'), 'checkbox', array(
			'db_alias' => 'tc_c_d',
			'db_column' => Ext_TS_Contact::DETAIL_NEWSLETTER,
			'required' => 0,
			'default_value' => 1,
		)));

	}

	private function createBillingAddressData(Ext_Gui2_Dialog_Tab $tab) {

		$aCountries = Ext_Thebing_Data::getCountryList(true, true);

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Rechnungsadresse'));
		$tab->setElement($iH3);

		$tab->setElement($this->dialog->createRow($this->gui->t('Firma'), 'input', array(
			'db_alias' => 'tc_a_b',
			'db_column' => 'company',
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Rechungsadresse'), 'input', array(
			'db_alias' => 'tc_a_b',
			'db_column' => 'address'
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('PLZ'), 'input', array(
			'db_alias' => 'tc_a_b',
			'db_column' => 'zip'
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Stadt'), 'input', array(
			'db_alias' => 'tc_a_b',
			'db_column' => 'city'
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Land'), 'select', array(
			'db_alias' => 'tc_a_b',
			'db_column' => 'country_iso',
			'select_options' => $aCountries,
		)));

	}

	private function createBookingData(Ext_Gui2_Dialog_Tab $tab) {

		$aPaymentMethods = Ext_Thebing_Inquiry_Amount::getPaymentMethods();

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Buchungsdaten'));
		$tab->setElement($iH3);

		$tab->setElement($this->dialog->createRow($this->gui->t('Agentur'), 'select', array(
			'db_alias' => 'ts_i',
			'db_column' => 'agency_id',
			'required' => 0,
			'selection' => new Ext_TS_Inquiry_Gui2_Selection_Agency(),
			'events' => array(
				array(
					'event' => 'change',
					'function' => 'reloadAgencyDependingFields'
				)
			)
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Agenturmitarbeiter'), 'select', array(
			'db_alias' => 'ts_i',
			'db_column' => 'agency_contact_id',
			'required' => 0,
			'dependency' => array(
				array(
					'db_alias' => 'ts_i',
					'db_column' => 'agency_id'
				)
			),
			'selection' => new Ext_Thebing_Gui2_Selection_Agency_Comments(),
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Zahlungsmethode'), 'select', array(
			'db_alias' => 'ts_i',
			'db_column' => 'payment_method',
			'select_options' => $aPaymentMethods,
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Zahlungsart Kommentar'), 'textarea', array(
			'db_alias' => 'ts_i',
			'db_column' => 'payment_method_comment',
		)));


		$tab->setElement($this->dialog->createRow($this->gui->t('Währung'), 'select', array(
			'db_alias' => 'ts_i',
			'db_column' => 'currency_id',
			'required' => 1,
			'dependency' => array(
				array(
					'db_alias' => 'ts_ij',
					'db_column' => 'school_id'
				)
			),
			'selection' => new Ext_Thebing_Gui2_Selection_School_Currency(),
		)));

		$oAccess = Access::getInstance();

		if ($oAccess->hasRight('thebing_invoice_sales_person')) {
			$tab->setElement($this->dialog->createRow($this->gui->t('Vertriebsmitarbeiter'), 'select', [
				'db_alias' => 'ts_i',
				'db_column' => 'sales_person_id',
				'required' => false,
				'selection' => new Ext_Thebing_Gui2_Selection_User_SalesPerson(),
				'dependency' => [
					[
						'db_alias' => 'cdb1',
						'db_column' => 'nationality',
					],
					[
						'db_alias' => 'cdb1',
						'db_column' => 'agency_id',
					],
				]
			]));
		}

	}

	private function createOtherData(Ext_Gui2_Dialog_Tab $tab) {

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Sonstiges'));
		$tab->setElement($iH3);

		$tab->setElement($this->dialog->createRow($this->gui->t('Promotion-Code'), 'input', array(
			'db_alias' => 'ts_i',
			'db_column' => 'promotion',
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Kommentar'), 'textarea', array(
			'db_alias' => 'tc_c_d',
			'db_column' => 'comment',
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Status d. Schülers'), 'select', array(
			'db_alias' => 'ts_i',
			'db_column' => 'status_id',
			'dependency' => array(
				array(
					'db_alias' => 'ts_ij',
					'db_column' => 'school_id'
				)
			),
			'selection' => new Ext_Thebing_Gui2_Selection_School_Status(),
		)));

		$tab->setElement($this->dialog->createRow($this->gui->t('Wie sind Sie auf uns aufmerksam geworden?'), 'select', array(
			'db_alias' => 'ts_i',
			'db_column' => 'referer_id',
			'required' => 0,
			//'select_options'	=> $aReferer,
			'select_options' => [], // Wird im JS gefüllt
			'dependency' => array(
				array(
					'db_alias' => 'ts_ij',
					'db_column' => 'school_id'
				)
			),
			'selection' => new Ext_Thebing_Gui2_Selection_School_Referer(),
		)));

//		$tab->setElement($this->dialog->createRow($this->gui->t('Mit Buchung verknüpfen'), 'autocomplete', array(
//			'db_column' => 'autocomplete_inquiry_id',
//			'autocomplete' => new Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry(),
//			'skip_value_handling' => true
//		)));

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Nachhaken'));
		$tab->setElement($iH3);

		$tab->setElement($this->dialog->createRow($this->gui->t('Datum'), 'calendar', array(
			'db_alias' => 'ts_i',
			'db_column' => 'follow_up',
			'format' => new Ext_Thebing_Gui2_Format_Date('convert_null'),
		)));

	}

	private function createRequestTab() {

		$oTab = $this->dialog->createTab($this->gui->t('Anfrage'));

		$oTab->aOptions = [
			'access' => 'thebing_students_contact_enquiry'
		];

		$oH3 = $this->dialog->create('h4');
		$oH3->setElement($this->gui->t('Kurs'));
		$oTab->setElement($oH3);

		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Kurskategorien'), 'textarea', ['db_column' => 'enquiry_course_category', 'db_alias' => 'ts_i']));
		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Kurslevel'), 'textarea', ['db_column' => 'enquiry_course_intensity', 'db_alias' => 'ts_i']));

		$oH3 = $this->dialog->create('h4');
		$oH3->setElement($this->gui->t('Unterkunft'));
		$oTab->setElement($oH3);

		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Unterkunftskategorien'), 'textarea', ['db_column' => 'enquiry_accommodation_category', 'db_alias' => 'ts_i']));
		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Unterkunftsraumart'), 'textarea', ['db_column' => 'enquiry_accommodation_room', 'db_alias' => 'ts_i']));
		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Verpflegung'), 'textarea', ['db_column' => 'enquiry_accommodation_meal', 'db_alias' => 'ts_i']));

		$oH3 = $this->dialog->create('h4');
		$oH3->setElement($this->gui->t('Transfer'));
		$oTab->setElement($oH3);

		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Transferart'), 'textarea', ['db_column' => 'enquiry_transfer_category', 'db_alias' => 'ts_i']));
		$oTab->setElement($this->dialog->createRow($this->gui->t('Gewünschte Transferorte'), 'textarea', ['db_column' => 'enquiry_transfer_location', 'db_alias' => 'ts_i']));

		$this->dialog->setElement($oTab);

	}

	private function createGroupTab() {

		$aYesNo = Ext_Thebing_Util::getYesNoArray();

		$oTab = $this->dialog->createTab($this->gui->t('Gruppeninformationen'));

		$oTab->aOptions = array(
			'section' => 'groups_enquiries_bookings', // ID der Gruppe! Ext_TS_Enquiry_Gui2::getFlexEditDataHTML()
			'access' => 'thebing_students_contact_group',
		);

		$oJoinContainer = $this->dialog->createJoinedObjectContainer('group', ['min' => 1, 'max' => 1, 'no_confirm' => 1]);

		$oJoinContainer->setElement($oJoinContainer->createRow($this->gui->t('Name'), 'input', [
			'db_column' => 'name',
			'db_alias' => 'group',
			'required' => true,
		]));

		$iMaxLength = 10;
		$oJoinContainer->setElement($oJoinContainer->createRow(
			sprintf($this->gui->t('Kürzel (max. %1$d Zeichen)'), $iMaxLength),
			'input',
			[
				'db_column' => 'short',
				'db_alias' => 'group',
				'class' => 'w100',
				'required' => true,
				'max_length' => $iMaxLength,
			]
		));

		/****************************** Einstellungen Gruppenmitglieder *******************************/

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Einstellungen - Gruppenmitglieder'));
		$oJoinContainer->setElement($iH3);

		$oJoinContainer->setElement($oJoinContainer->createRow($this->gui->t('Geschlossener Unterricht'), 'select', [
			'db_column' => 'course_closed',
			'db_alias' => 'group',
			'select_options' => $aYesNo,
		]));

		$iH3 = $this->dialog->create('h4');
		$iH3->setElement($this->gui->t('Schüler'));
		$oJoinContainer->setElement($iH3);

		$oTab->setElement($oJoinContainer);

		$oJoinContainerContact = $this->dialog->createJoinedObjectContainer(Ext_TS_Enquiry_Group::JOIN_CONTACTS, [
			'min' => 1,
			'max' => 100,
			'no_confirm' => 0,
			'count_field' => true,
			'save_handler' => new Ext_TS_Enquiry_Gui2_Dialog_GroupContactSaveHandler(),
			'remove_label' => 'Gruppenmitglied entfernen',
			'add_label' => 'Gruppenmitglied hinzufügen'
		]);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Nachname'),
				'input',
				[
					'db_alias' => 'group',
					'db_column' => 'lastname',
					//'required' => true,
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Vorname'),
				'input',
				[
					'db_alias' => 'group',
					'db_column' => 'firstname',
					//'required' => true,
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Geburtsdatum'),
				'calendar',
				[
					'db_alias' => 'group',
					'db_column' => 'birthday',
					'format' => new Ext_Thebing_Gui2_Format_Date(),
				]
			)
		);

		$aGender = Ext_Thebing_Util::getGenders();

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Geschlecht'),
				'select',
				[
					'db_alias' => 'group',
					'db_column' => 'gender',
					'class' => 'w100',
					'select_options' => $aGender,
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Guide?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_guide',
					'class' => 'fire_event',
					'events' => [
						[
							'event' => 'change',
							'function' => 'toggleGuideCheckbox',
						],
					],
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Alles kostenfrei?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_free_all',
					'row_style' => 'display: none',
					'class' => 'fire_event',
					'events' => [
						[
							'event' => 'change',
							'function' => 'toggleFreeCheckbox',
						],
					],
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Kurs frei?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_free_course',
					'row_style' => 'display: none',
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Unterkunft frei?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_free_accommodation',
					'row_style' => 'display: none',
				]
			)
		);

		$oJoinContainer->setElement($oJoinContainerContact);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Kursbezogene Zusatzkosten frei?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_free_course_fee',
					'row_style' => 'display: none',
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Unterkunftsbezogene Zusatzkosten frei?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_free_accommodation_fee',
					'row_style' => 'display: none',
				]
			)
		);

		$oJoinContainerContact->setElement(
			$oJoinContainerContact->createRow(
				$this->gui->t('Transfer frei?'),
				'checkbox',
				[
					'db_alias' => 'group',
					'db_column' => 'detail_free_transfer',
					'row_style' => 'display: none',
				]
			)
		);

		$this->dialog->setElement($oTab);

	}

}