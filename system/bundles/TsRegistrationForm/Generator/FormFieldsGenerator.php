<?php

namespace TsRegistrationForm\Generator;

use Ext_Thebing_Form_Page_Block;
use Illuminate\Support\Collection;
use Tc\Service\Language;

/**
 * Felder für RegForm generieren
 */
class FormFieldsGenerator {

	/**
	 * @var Language\Backend
	 */
	private $languageBackend;

	/**
	 * @var Language\Frontend
	 */
	private $languageFrontend;

	/**
	 * Für Backend-Übersetzungen Backend-Language-Objekt setzen
	 *
	 * @param Language\Backend $language
	 */
	public function setBackendLanguage(Language\Backend $language) {
		$this->languageBackend = $language;
	}

	/**
	 * Für Frontend-Übersetzungen Frontend-Language-Objekt setzen
	 *
	 * @param Language\Frontend $language
	 */
	public function setFrontendLanguage(Language\Frontend $language) {
		$this->languageFrontend = $language;
	}

	/**
	 * Felder generieren
	 *
	 * @return Collection
	 */
	public function generate() {

		$fields = new Collection();
		$fields = $fields->merge($this->getInquiryFields());
		$fields = $fields->merge($this->getContactFields());
		$fields = $fields->merge($this->getEmergencyContactFields());
		$fields = $fields->merge($this->getMatchingFields());
		$fields = $fields->merge($this->getVisaFields());
		$fields = $fields->merge($this->getEnquiryFields());
		$fields = $fields->merge($this->getFlexFields());

		return $fields;

	}

	/**
	 * Generelle Inquiry-Felder
	 *
	 * @return array
	 */
	private function getInquiryFields() {

		return [
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Schule'),
				'frontend_label' => $this->tf('School'),
				'mapping' => ['ts_ij', 'school_id'],
				'validation' => ['integer', 'numeric']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_PLACEOFBIRTH,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Zusatz - Geburtsort'),
				'frontend_label' => $this->tf('Place of birth'),
				'mapping' => ['tc_c_d', 'place_of_birth']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_ADDITIONAL_COUNTRYOFBIRTH,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Zusatz - Geburtsland'),
				'frontend_label' => $this->tf('Country of birth'),
				'mapping' => ['tc_c_d', 'country_of_birth']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_JOB,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Zusatz - Beruf'),
				'frontend_label' => $this->tf('Profession'),
				'mapping' => ['ts_i', 'profession']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_TAX_NUMBER,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Zusatz - Steuernummer'),
				'frontend_label' => $this->tf('Tax number'),
				'mapping' => ['tc_c_d', 'tax_code']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_VAT_ID,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Zusatz - USt.-ID'),
				'frontend_label' => $this->tf('VAT ID'),
				'mapping' => ['tc_c_d', 'vat_number']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_ADDITIONAL_SOCIAL_NUMBER,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Zusatz - Sozialversicherungsnummer'),
				'frontend_label' => $this->tf('Social security number'),
				'mapping' => ['ts_i', 'social_security_number']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_PROMOTION_CODE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Promotion-Code'),
				'frontend_label' => $this->tf('Voucher ID'),
				'mapping' => ['ts_i', 'promotion']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_UPLOAD_PHOTO,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_UPLOAD,
				'backend_label' => $this->t('Foto'),
				'frontend_label' => $this->tf('Photo'),
				'mapping' => ['upload', 'static_1']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_UPLOAD_PASS,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_UPLOAD,
				'backend_label' => $this->t('Reisepass'),
				'frontend_label' => $this->tf('Passport'),
				'mapping' => ['upload', 'static_2']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_REFERER_ID,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Quelle: Wie sind Sie auf uns aufmerksam geworden?'),
				'frontend_label' => $this->tf('Source'),
				'mapping' => ['ts_i', 'referer_id'],
				'validation' => ['integer', 'numeric']
			],
//			[
//				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_TRANSFER_MODE,
//				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
//				'mapping' => ['ts_ij', 'transfer_mode'],
//				'validation' => ['in:0,1,2,3']
//			],
			[
				// Internes Feld für SPA Payment-Token
				'key' => 'payment',
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'internal' => true
			]
		];

	}

	/**
	 * Kontakt-Felder + Adressen + E-Mail
	 *
	 * @return array
	 */
	private function getContactFields() {

		$fields = [
			// tc_c
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Vorname'),
				'frontend_label' => $this->tf('Firstname'),
				'mapping' => ['tc_c', 'firstname']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Nachname'),
				'frontend_label' => $this->tf('Lastname'),
				'mapping' => ['tc_c', 'lastname']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'backend_label' => $this->t('Geburtsdatum'),
				'frontend_label' => $this->tf('Birthdate'),
				'mapping' => ['tc_c', 'birthday'],
				'validation' => ['date', 'before:today', 'after:'.Ext_Thebing_Form_Page_Block::VALIDATION_MIN_DATE]
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SEX,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Geschlecht'),
				'frontend_label' => $this->tf('Gender'),
				'mapping' => ['tc_c', 'gender'],
				'validation' => ['integer', 'numeric']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_NATIONALITY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Nationalität'),
				'frontend_label' => $this->tf('Nationality'),
				'mapping' => ['tc_c', 'nationality'],
				'validation' => ['string']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MOTHERTONGE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Muttersprache'),
				'frontend_label' => $this->tf('Mother tongue'),
				'mapping' => ['tc_c', 'language'],
				'validation' => ['string']
			],

			// tc_a contact
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_ADDRESS,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Kontakt - Adresse'),
				'frontend_label' => $this->tf('Address'),
				'mapping' => ['tc_a_c', 'address']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_ADDRESS_ADDON,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Kontakt - Adresszusatz'),
				'frontend_label' => $this->tf('Address addon'),
				'mapping' => ['tc_a_c', 'address_addon']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_ZIP,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Kontakt - PLZ'),
				'frontend_label' => $this->tf('ZIP'),
				'mapping' => ['tc_a_c', 'zip']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_CITY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Kontakt - Stadt'),
				'frontend_label' => $this->tf('City'),
				'mapping' => ['tc_a_c', 'city']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_STATE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Kontakt - Staat'),
				'frontend_label' => $this->tf('State'),
				'mapping' => ['tc_a_c', 'state']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_CONTACT_COUNTRY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Kontakt - Land'),
				'frontend_label' => $this->tf('Country'),
				'mapping' => ['tc_a_c', 'country_iso'],
				'validation' => ['string']
			],

			// tc_a billing
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_COMPANY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Firma'),
				'frontend_label' => $this->tf('Company'),
				'mapping' => ['tc_a_b', 'company']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_FIRSTNAME,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Vorname'),
				'frontend_label' => $this->tf('First name'),
				'mapping' => ['tc_bc', 'firstname']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_LASTNAME,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Nachname'),
				'frontend_label' => $this->tf('Last name'),
				'mapping' => ['tc_bc', 'lastname']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_ADDRESS,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Adresse'),
				'frontend_label' => $this->tf('Address'),
				'mapping' => ['tc_a_b', 'address']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_ZIP,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - PLZ'),
				'frontend_label' => $this->tf('ZIP'),
				'mapping' => ['tc_a_b', 'zip']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_TAX_NUMBER,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Steuernummer'),
				'frontend_label' => $this->tf('Tax number'),
				'mapping' => ['tc_bc', 'detail_tax_code']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_CITY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Stadt'),
				'frontend_label' => $this->tf('City'),
				'mapping' => ['tc_a_b', 'city']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_STATE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Bundesland'),
				'frontend_label' => $this->tf('State'),
				'mapping' => ['tc_a_b', 'state']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_INVOICE_COUNTRY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Rechnung - Land'),
				'frontend_label' => $this->tf('Country'),
				'mapping' => ['tc_a_b', 'country_iso'],
				'validation' => ['string']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_EMAIL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - E-Mail'),
				'frontend_label' => $this->tf('E-Mail'),
				'mapping' => ['tc_bc', 'email'],
				'validation' => ['email_mx']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_PHONE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Telefonnummer'),
				'frontend_label' => $this->tf('Phone number'),
				'mapping' => ['tc_bc', 'detail_phone_private']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_VAT_ID,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - USt.-ID'),
				'frontend_label' => $this->tf('VAT ID'),
				'mapping' => ['tc_bc', 'detail_vat_number']
			],

			// tc_e
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_EMAIL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'subtype' => 'email',
				'backend_label' => $this->t('Kontakt - E-Mail'),
				'frontend_label' => $this->tf('E-Mail'),
				'mapping' => ['tc_c', 'email'],
				'validation' => ['email_mx']
			],

			// tc_cd
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'subtype' => 'tel',
				'backend_label' => $this->t('Kontakt - Telefon'),
				'frontend_label' => $this->tf('Phone'),
				'mapping' => ['tc_cd', \Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE],
//				'validation' => ['phone_itu']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE_OFFICE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'subtype' => 'tel',
				'backend_label' => $this->t('Kontakt - Telefon Büro'),
				'frontend_label' => $this->tf('Phone office'),
				'mapping' => ['tc_cd', \Ext_TC_Contact_Detail::TYPE_PHONE_OFFICE],
//				'validation' => ['phone_itu']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_PHONE_MOBILE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'subtype' => 'tel',
				'backend_label' => $this->t('Kontakt - Handy'),
				'frontend_label' => $this->tf('Cellphone'),
				'mapping' => ['tc_cd', \Ext_TC_Contact_Detail::TYPE_PHONE_MOBILE],
//				'validation' => ['phone_itu']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_FAX,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'subtype' => 'tel',
				'backend_label' => $this->t('Kontakt - Fax'),
				'frontend_label' => $this->tf('Fax'),
				'mapping' => ['tc_cd', \Ext_TC_Contact_Detail::TYPE_FAX]
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_NEWSLETTER,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX,
				'backend_label' => $this->t('Automatische E-Mails - E-Mail'),
				'frontend_label' => $this->tf('Automatic e-mail'),
				'mapping' => ['tc_cd', \Ext_TS_Contact::DETAIL_NEWSLETTER]
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_NOTICE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anmerkung'),
				'frontend_label' => $this->tf('Comment'),
				'mapping' => ['tc_cd', \Ext_TS_Contact::DETAIL_COMMENT]
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_TOS,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX,
				'backend_label' => $this->t('AGB'),
				'frontend_label' => $this->tf('I agree to the terms of service.'),
				'mapping' => ['tc_cd', \Ext_TS_Contact::DETAIL_TOS]
			]
		];

		if (\TcExternalApps\Service\AppService::hasApp(\TsAccounting\Service\eInvoice\Italy\ExternalApp\XmlIt::APP_NAME)) {
			$fields[] = [
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_INVOICE_RECIPIENT_ID,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Rechnung - Empfängercode'),
				'frontend_label' => $this->tf('Recipient Code'),
				'mapping' => ['tc_bc', 'detail_recipient_code']
			];
		}

		return $fields;
	}

	/**
	 * Notfallkontakt
	 *
	 * @return array
	 */
	private function getEmergencyContactFields() {

		return [
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_NAME,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Notfall - Name'),
				'frontend_label' => $this->tf('Name'),
				'mapping' => ['tc_c_e', 'lastname']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_FIRSTNAME,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Notfall - Vorname'),
				'frontend_label' => $this->tf('Vorname'),
				'mapping' => ['tc_c_e', 'firstname']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_PHONE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Notfall - Telefon'),
				'frontend_label' => $this->tf('Phone'),
				'mapping' => ['tc_c_de', \Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE],
//				'validation' => ['phone_itu']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_EMERGENCY_EMAIL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Notfall - E-Mail'),
				'frontend_label' => $this->tf('E-mail'),
				'mapping' => ['tc_c_e', 'email'],
				'validation' => ['email_mx']
			],
		];

	}

	/**
	 * Visa-Felder
	 *
	 * @return array
	 */
	private function getVisaFields() {

		return [
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_VISA_PASS_NUMBER,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'backend_label' => $this->t('Visum - Passnummer'),
				'frontend_label' => $this->tf('Passport number'),
				'mapping' => ['ts_ijv', 'passport_number']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_FROM,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'backend_label' => $this->t('Visum - Visum gültig von'),
				'frontend_label' => $this->tf('Visa valid from'),
				'mapping' => ['ts_ijv', 'date_from'],
				'validation' => ['date']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_UNTIL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'backend_label' => $this->t('Visum - Visum gültig bis'),
				'frontend_label' => $this->tf('Visa valid until'),
				'mapping' => ['ts_ijv', 'date_until'],
				'validation' => ['date']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_PASS_FROM,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'backend_label' => $this->t('Visum - Pass Ausstellungsdatum'),
				'frontend_label' => $this->tf('Passport valid from'),
				'mapping' => ['ts_ijv', 'passport_date_of_issue'],
				'validation' => ['date']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_VISA_PASS_UNTIL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'backend_label' => $this->t('Visum - Pass Fälligkeitsdatum'),
				'frontend_label' => $this->tf('Passport valid until'),
				'mapping' => ['ts_ijv', 'passport_due_date'],
				'validation' => ['date']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_CHECKBOX_VISA_REQUIRED,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX,
				'backend_label' => $this->t('Visum - Visum wird benötigt'),
				'frontend_label' => $this->tf('Do you need a visa?'), // TODO Label
				'mapping' => ['ts_ijv', 'required'],
				'validation' => ['boolean']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_VISA_STATUS,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Visum - Status'),
				'frontend_label' => $this->tf('Visa status'),
				'mapping' => ['ts_ijv', 'status'],
				'validation' => ['integer', 'numeric']
			]
		];

	}

	private function getEnquiryFields() {

		return [
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_CLASS_CATEGORY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschte Kurskategorie'),
				'frontend_label' => $this->tf('Which type of course is interesting to you?'),
				'mapping' => ['ts_i', 'enquiry_course_category']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_CLASS_LEVEL,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschtes Kursniveau'),
				'frontend_label' => $this->tf('Which level of course is interesting to you?'),
				'mapping' => ['ts_i', 'enquiry_course_intensity']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_CATEGORY,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschte Unterkunftskategorie'),
				'frontend_label' => $this->tf('Which type of accommodation is interesting to you?'),
				'mapping' => ['ts_i', 'enquiry_accommodation_category']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_ROOM_TYPE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschte Unterkunftsraumart'),
				'frontend_label' => $this->tf('Which accommodation room type is interesting to you?'),
				'mapping' => ['ts_i', 'enquiry_accommodation_room']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_FOOD,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschte Verpflegung'),
				'frontend_label' => $this->tf('Which type of accommodation board is interesting to you?'),
				'mapping' => ['ts_i', 'enquiry_accommodation_meal']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_TYPE,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschte Transferart'),
				'frontend_label' => $this->tf('How to you want to organise your transport to school or accommodation?'),
				'mapping' => ['ts_i', 'enquiry_transfer_category']
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_LOCATIONS,
				'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'backend_label' => $this->t('Anfrage - Gewünschte Transferorte'),
				'frontend_label' => $this->tf('Which type of accommodation board is interesting to you?'),
				'mapping' => ['ts_i', 'enquiry_transfer_location']
			],
		];

	}

	/**
	 * Matching-Felder
	 *
	 * @return array
	 */
	private function getMatchingFields() {

		$alias = 'ts_i_m_d';
		$right = 'thebing_accommodation_family_matching';

		// Aus irgendeinem unbekannten Grund mussten hier wieder eigene Keys erfunden werden
		$mapping = [
			'cats' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_CATS,
			'dogs' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_DOGS,
			'pets' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_ANIMALS,
			'smoker' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_SMOKER,
			'distance_to_school' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_DISTANCE,
			'air_conditioner' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_CLIMA,
			'bath' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_OWN_BATHROOM,
			'family_age' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_AGE,
			'residential_area' => Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_MATCHING_AREA,
			'family_kids' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_CHILDS,
			'internet' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_FAMILY_INTERNET,
			'acc_smoker' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_SMOKER,
			'acc_vegetarian' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_VEGETARIAN,
			'acc_muslim_diat' => Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MATCHING_MUSLIM
		];

		// Nicht im Frontend übersetzen, sonst Absturz
		$t = function (\TsAccommodation\Entity\Matching\Criterion $field) {
			if($this->languageBackend) {
				return $field->getLabel(true);
			}
			return $field->getLabel();
		};

		$matchingFields = (new \Ext_Thebing_Matching())->getCriteria('hostfamily', true, $this->languageFrontend);
		$matchingFields = array_merge($matchingFields['soft'], $matchingFields['hard']);
		/** @var \TsAccommodation\Entity\Matching\Criterion[] $matchingFields */

		$fields = [];
		foreach ($matchingFields as $field) {
			$fields[] = [
				'key' => $mapping[$field->getField()],
				'type' => $field->getType() === 'input' ? Ext_Thebing_Form_Page_Block::TYPE_INPUT : Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'backend_label' => $this->t('Matching').': '.$t($field),
				'mapping' => [$alias, $field->getField()],
				'right' => $right,
				'validation' => $field->getType() === 'select' ? ['integer', 'numeric'] : []
			];
		}

		$fields[] = [
			'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_MATCHING_COMMENT,
			'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
			'backend_label' => $this->t('Matching - Kommentar'),
			'mapping' => [$alias, 'acc_comment'],
			'right' => 'thebing_accommodation_icon'
		];

		$fields[] = [
			'key' => Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_MATCHING_ALLERGY,
			'type' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
			'backend_label' => $this->t('Matching - Allergien'),
			'mapping' => [$alias, 'acc_allergies'],
			'right' => $right
		];

		return $fields;

	}

	/**
	 * Flex-Felder + Uploads
	 *
	 * @return array
	 */
	public function getFlexFields(): array {

		$sections = [
			'student_record_general',
			'student_record_course',
			'student_record_accommodation',
			'student_record_matching',
			'student_record_transfer',
			'student_record_insurance',
			'student_record_activities',
			'student_record_visum',
			'enquiries_enquiries',
			'student_record_sponsoring'
		];

		$typeMapping = [
			\Ext_TC_Flexibility::TYPE_TEXT => \Ext_Thebing_Form_Page_Block::TYPE_INPUT,
			\Ext_TC_Flexibility::TYPE_SELECT => \Ext_Thebing_Form_Page_Block::TYPE_SELECT,
			\Ext_TC_Flexibility::TYPE_DATE => \Ext_Thebing_Form_Page_Block::TYPE_DATE,
			\Ext_TC_Flexibility::TYPE_CHECKBOX => \Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX,
			\Ext_TC_Flexibility::TYPE_TEXTAREA => \Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
			\Ext_TC_Flexibility::TYPE_MULTISELECT => \Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT,
			\Ext_TC_Flexibility::TYPE_YESNO => \Ext_Thebing_Form_Page_Block::TYPE_YESNO,
		];

		$fields = [];
		$flexFields = \Ext_TC_Flexibility::getSectionFieldData($sections, true);

		foreach ($flexFields as $flexField) {

			if (!isset($typeMapping[$flexField->type])) {
				continue;
			}

			$field = [
				'key' => 'flex_'.$flexField->id,
				'type' => $typeMapping[$flexField->type],
				'backend_label' => $flexField->title,
				'frontend_label' => $flexField->title,
				'mapping' => ['flex', $flexField->id],
				'flex_section' => $flexField->getSection()->type,
				'flex_i18n' => (bool)$flexField->i18n,
				'usage' => $flexField->usage,
				'validation' => []
			];

			if (in_array($flexField->type, [\Ext_TC_Flexibility::TYPE_SELECT])) {
				$field['validation'] = ['integer', 'numeric'];
			}

			// TODO Könnte man erweitern, aber die Validierungen werden ohnehin selten benutzt
			switch ($flexField->validate_by) {
				case 'MAIL':
					$field['subtype'] = 'email';
					$field['validation'][] = 'email_mx';
					break;
				case 'PHONE':
				case 'PHONE_ITU':
					$field['subtype'] = 'tel';
					break;
			}

			$fields[] = $field;

		}

		// Custom Upload Fields
		$schoolIds = array_keys(\Ext_Thebing_Client::getSchoolList(true));
		$uploadFields = \Ext_Thebing_School_Customerupload::getUploadFieldsBySchoolIds($schoolIds);
		foreach ($uploadFields as $uploadField) {
			$fields[] = [
				'key' => 'flex_upload_'.$uploadField->id,
				'type' => \Ext_Thebing_Form_Page_Block::TYPE_UPLOAD,
				'backend_label' => $uploadField->name,
				'frontend_label' => $uploadField->name,
				'mapping' => ['upload', 'flex_upload_'.$uploadField->id],
				'validation' => [],
				'schools' => $uploadField->schools // Wird im Form Designer benötigt
			];
		}

		return $fields;

	}

	/**
	 * Backend-Translation
	 *
	 * @param string $sTranslation
	 * @return string
	 */
	private function t($sTranslation) {

		if($this->languageBackend) {
			return $this->languageBackend->translate($sTranslation);
		}

		return '';

	}

	/**
	 * Frontend-Translation
	 *
	 * @param string $sTranslation
	 * @return string
	 */
	private function tf($sTranslation) {

		if($this->languageFrontend) {
			return $this->languageFrontend->translate($sTranslation);
		}

		return '';

	}

}
