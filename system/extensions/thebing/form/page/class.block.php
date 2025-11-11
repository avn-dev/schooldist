<?php

/**
 * @property integer $id
 * @property integer $active
 * @property integer $creator_id
 * @property integer $user_id
 * @property integer $page_id ID der zugehörigen Seite (siehe Ext_Thebing_Form_Page)
 * @property int|string $block_id Art des Blocks (siehe Ext_Thebing_Form_Page_Block::TYPE_* Konstanten)
 * @property integer $required Pflichtfeld Ja/Nein (1/0)
 * @property integer $parent_id ID des Eltern-Blocks oder 0 wenn es keinen Eltern-Block gibt
 *                              (dann gehört der Block direkt zur Seite)
 * @property integer $parent_area Bereich-Nummer des Eltern-Blocks (nur wenn der Eltern-Block Bereiche hat) oder 0
 * @property integer $position
 * @property string $css_class CSS-Klasse die diesem Block zugeordnet werden soll (kann leer sein)
 * @property string $set_type Unterart des Blocks (nicht bei allen Blöcken)
 *                            (siehe Ext_Thebing_Form_Page_Block::SUBTYPE_* Konstanten)
 * @property integer $set_number_of_cols Anzahl der Spalten (siehe Ext_Thebing_Form_Page_Block::hasAreas())
 * @property integer[] $set_numbers Breite der Spalten (siehe Ext_Thebing_Form_Page_Block::getAreaWidths())
 */
class Ext_Thebing_Form_Page_Block extends Ext_Thebing_Basic {

	/**
	 * Unbekannte Block-Art
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @var integer
	 */
	const TYPE_UNDEFINED = 0;

	/**
	 * Mehrspaltiger Bereich
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @var integer
	 */
	const TYPE_COLUMNS = -1;

	/**
	 * H2 - Überschrift 2
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isHeadlineBlock()
	 * @var integer
	 */
	const TYPE_HEADLINE2 = -2;

	/**
	 * H3 - Überschrift 3
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isHeadlineBlock()
	 * @var integer
	 */
	const TYPE_HEADLINE3 = -3;

	/**
	 * Statischer Text
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isTextBlock()
	 * @var integer
	 */
	const TYPE_STATIC_TEXT = -4;

	/**
	 * Download
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_DOWNLOAD = -5;

	/**
	 * Input/Einfaches Eingabefeld
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_INPUT = -6;

	/**
	 * Select/Dropdown
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_SELECT = -7;

	/**
	 * Datum
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_DATE = -8;

	/**
	 * Checkbox
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_CHECKBOX = -9;

	/**
	 * Upload
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_UPLOAD = -10;

	/**
	 * Textarea/Mehrzeiliges Eingabefeld
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_TEXTAREA = -11;


	/**
	 * MultiSelect
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_MULTISELECT = -12;

	/**
	 * Ja/Nein
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @var integer
	 */
	const TYPE_YESNO = -13;

	/**
	 * Navigation: Schritte
	 */
	const TYPE_NAV_STEPS = -14;

	/**
	 * Navigation: Buttons
	 */
	const TYPE_NAV_BUTTONS = -15;

	/**
	 * Mitteilungen (automatisches Element)
	 */
	const TYPE_NOTIFICATIONS = -16;

	/**
	 * Trennlinie <hr>
	 */
	const TYPE_HORIZONTAL_RULE = -17;

	/**
	 * Honeypot: Statisches HTML, aber Input Values müssen gesetzt werden
	 */
	const TYPE_HONEYPOT = -18;

	const TYPES_INPUTS = [
		self::TYPE_INPUT,
		self::TYPE_SELECT,
		self::TYPE_DATE,
		self::TYPE_CHECKBOX,
		self::TYPE_TEXTAREA,
		self::TYPE_MULTISELECT,
		self::TYPE_YESNO,
	];

	/**
	 * Fester Block > Kurse
	 *
	 * Der Unterschied zwischen < 0 und > 0 ist historisch bedingt aufgrund von AS-Classics.
	 * Hiermit sollte im DESIGNER unterschieden werden, ob dieser Block-Typ mehr als einmal vorkommen kann.
	 * Diese Logik war aber bereits mit dem Preisblock (5) nicht mehr zu gebrauchen, da dieser mehrfach vorkommen darf.
	 * Hier passiert auch nichts automatisch, denn die IDs sind eh wieder im JS hardcoded.
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isFixedBlock()
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @var integer
	 */
	const TYPE_COURSES = 1;

	/**
	 * Fester Block > Unterkunft
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isFixedBlock()
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @var integer
	 */
	const TYPE_ACCOMMODATIONS = 2;

	/**
	 * Fester Block > Transfer
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isFixedBlock()
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @var integer
	 */
	const TYPE_TRANSFERS = 3;

	/**
	 * Fester Block > Versicherung
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isFixedBlock()
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @var integer
	 */
	const TYPE_INSURANCES = 4;

	/**
	 * Fester Block > Preise
	 *
	 * @see Ext_Thebing_Form_Page_Block::$block_id
	 * @see Ext_Thebing_Form_Page_Block::isFixedBlock()
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @var integer
	 */
	const TYPE_PRICES = 5;

	/**
	 * Fester Block > Zusätzliche Gebühren
	 *
	 * @var int
	 */
	const TYPE_FEES = 6;

	/**
	 * Fester Block > Bezahlung
	 *
	 * @var int
	 */
	const TYPE_PAYMENT = 7;

	/**
	 * Aktivitäten
	 */
	const TYPE_ACTIVITY = 8;

	/**
	 * Alle Service-Typen, da in die positiven IDs auch andere Elemente gemischt wurden
	 */
	const TYPES_SERVICES = [
		self::TYPE_COURSES,
		self::TYPE_ACCOMMODATIONS,
		self::TYPE_TRANSFERS,
		self::TYPE_INSURANCES,
		self::TYPE_FEES,
		self::TYPE_ACTIVITY
	];

	/**
	 * Spezieller Block > Buttons zum Duplizieren von Blöcken
	 *
	 * @var string
	 */
	const SUBTYPE_SPECIAL_DUPLICATOR_CONTROLS = 'duplicator_controls';

	/**
	 * Einfaches Eingabefeld > Vorname
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_FIRSTNAME = 'firstname';

	/**
	 * Einfaches Eingabefeld > Nachname
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_LASTNAME = 'lastname';

	/**
	 * Einfaches Eingabefeld > Kontakt: Adresse
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_ADDRESS = 'contact_address';

	/**
	 * Einfaches Eingabefeld > Kontakt: Adresszusatz
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_ADDRESS_ADDON = 'contact_address_add';

	/**
	 * Einfaches Eingabefeld > Kontakt: PLZ
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_ZIP = 'contact_zip';

	/**
	 * Einfaches Eingabefeld > Kontakt: Stadt
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_CITY = 'contact_city';

	/**
	 * Einfaches Eingabefeld > Kontakt: Bundesland
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_STATE = 'contact_state';

	/**
	 * Einfaches Eingabefeld > Kontakt: Telefon
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_PHONE = 'contact_phone';

	/**
	 * Einfaches Eingabefeld > Kontakt: Telefon Büro
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_PHONE_OFFICE = 'contact_phone_office';

	/**
	 * Einfaches Eingabefeld > Kontakt: Handy
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_PHONE_MOBILE = 'contact_mobile';

	/**
	 * Einfaches Eingabefeld > Kontakt: Fax
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_FAX = 'contact_fax';

	/**
	 * Einfaches Eingabefeld > Kontakt: E-Mail
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_CONTACT_EMAIL = 'contact_email';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Firma
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_COMPANY = 'invoice_company';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Vorname
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_FIRSTNAME = 'invoice_firstname';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Nachname
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_LASTNAME = 'invoice_lastname';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Adresse
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_ADDRESS = 'invoice_address';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: PLZ
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_ZIP = 'invoice_zip';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Steuernummer
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_TAX_NUMBER = 'invoice_tax_number';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Stadt
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_CITY = 'invoice_city';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Bundesland
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_STATE = 'invoice_state';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: E-Mail
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_EMAIL = 'invoice_email';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Telefonnummer
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_PHONE = 'invoice_phone';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: USt.-ID
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_VAT_ID = 'invoice_vat_id';

	/**
	 * Einfaches Eingabefeld > Rechnungsadresse: Empfängercode
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_INVOICE_RECIPIENT_ID = 'invoice_recipient_id';

	/**
	 * Einfaches Eingabefeld > Zusatz: Geburtsort
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_ADDITIONAL_PLACEOFBIRTH = 'add_place_of_birth';

	/**
	 * Select/Dropdown > Zusatz: Geburtsland
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_SELECT_ADDITIONAL_COUNTRYOFBIRTH = 'add_country_of_birth';

	/**
	 * Einfaches Eingabefeld > Zusatz: Beruf
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_ADDITIONAL_JOB = 'add_job';

	/**
	 * Einfaches Eingabefeld > Zusatz: Steuernummer
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_ADDITIONAL_TAX_NUMBER = 'add_tax_number';

	/**
	 * Einfaches Eingabefeld > Zusatz: USt.-ID
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_ADDITIONAL_VAT_ID = 'add_vat_id';

	/**
	 * Einfaches Eingabefeld > Zusatz: Sozialversicherungsnummer
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_ADDITIONAL_SOCIAL_NUMBER = 'add_social_number';

	/**
	 * Einfaches Eingabefeld > Notfallkontakt: Name
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_EMERGENCY_NAME = 'emergency_name';

	/**
	 * Einfaches Eingabefeld > Notfallkontakt: Vorname
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_EMERGENCY_FIRSTNAME = 'emergency_firstname';

	/**
	 * Einfaches Eingabefeld > Notfallkontakt: Telefon
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_EMERGENCY_PHONE = 'emergency_phone';

	/**
	 * Einfaches Eingabefeld > Notfallkontakt: E-Mail
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_EMERGENCY_EMAIL = 'emergency_email';

	/**
	 * Einfaches Eingabefeld > Zuweisung: Schüler möchte in Wohngebiet wohnen
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_MATCHING_AREA = 'matching_area';

	/**
	 * Einfaches Eingabefeld > Visum: Passnummer
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_VISA_PASS_NUMBER = 'visa_pass_number';

	/**
	 * Einfaches Eingabefeld > Promotion Code
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_INPUT
	 * @var string
	 */
	const SUBTYPE_INPUT_PROMOTION_CODE = 'promotion_code';

	/**
	 * Datum > Geburtsdatum
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_DATE
	 * @var string
	 */
	const SUBTYPE_DATE_BIRTHDATE = 'birthdate';

	/**
	 * Datum > Visum: Visum gültig von
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_DATE
	 * @var string
	 */
	const SUBTYPE_DATE_VISA_FROM = 'visa_from';

	/**
	 * Datum > Visum: Visum gültig bis
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_DATE
	 * @var string
	 */
	const SUBTYPE_DATE_VISA_UNTIL = 'visa_until';

	/**
	 * Datum > Visum: Pass gültig ab
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_DATE
	 * @var string
	 */
	const SUBTYPE_DATE_VISA_PASS_FROM = 'visa_pass_from';

	/**
	 * Datum > Visum: Pass gültig bis
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_DATE
	 * @var string
	 */
	const SUBTYPE_DATE_VISA_PASS_UNTIL = 'visa_pass_until';

	/**
	 * Select/Dropdown > Schule
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_SCHOOL = 'school';

	/**
	 * Select/Dropdown > Geschlecht
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_SEX = 'sex';

	/**
	 * Select/Dropdown > Nationalität
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_NATIONALITY = 'nationality';

	/**
	 * Select/Dropdown > Muttersprache
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MOTHERTONGE = 'mothertonge';

	/**
	 * Select/Dropdown > Kontakt: Land
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_CONTACT_COUNTRY = 'contact_country';

	/**
	 * Select/Dropdown > Rechnungsadresse: Land
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_INVOICE_COUNTRY = 'invoice_country';

	/**
	 * Select/Dropdown > Quelle: "aufmerksam auf Schule"
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_REFERER_ID = 'hear_about_us';

	/**
	 * Select/Dropdown > Zuweisung: Vegetarisches Essen
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_VEGETARIAN = 'matching_vegetarian';

	/**
	 * Select/Dropdown > Zuweisung: Muslime Diät
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_MUSLIM = 'matching_muslim';

	/**
	 * Select/Dropdown > Zuweisung: Schüler raucht
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_SMOKER = 'matching_smoker';

	/**
	 * Select/Dropdown > Zuweisung: Familie kann Katzen haben
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_CATS = 'matching_family_cats';

	/**
	 * Select/Dropdown > Zuweisung: Familie kann Hunden haben
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_DOGS = 'matching_family_dogs';

	/**
	 * Select/Dropdown > Zuweisung: Familie kann andere Tiere haben
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_ANIMALS = 'matching_family_animals';

	/**
	 * Select/Dropdown > Zuweisung: Raucherfamilie
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_SMOKER = 'matching_family_smoker';

	/**
	 * Select/Dropdown > Zuweisung: Entfernung zur Schule
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_DISTANCE = 'matching_distance';

	/**
	 * Select/Dropdown > Zuweisung: Klimaanlage gewünscht
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_CLIMA = 'matching_clima';

	/**
	 * Select/Dropdown > Zuweisung: Eigenes Bad wird benötigt
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_OWN_BATHROOM = 'matching_own_bathroom';

	/**
	 * Select/Dropdown > Zuweisung: Alter der Familie
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_AGE = 'matching_family_age';

	/**
	 * Select/Dropdown > Zuweisung: Familie kann Kinder haben
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_CHILDS = 'matching_family_childs';

	/**
	 * Select/Dropdown > Zuweisung: Internet gewünscht
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_SELECT
	 * @var string
	 */
	const SUBTYPE_SELECT_MATCHING_FAMILY_INTERNET = 'matching_family_internet';

	/**
	 * V3: Transfermodus vom Journey
	 */
	const SUBTYPE_SELECT_TRANSFER_MODE = 'transfer_mode';

	/**
	 * Datum > Visum: Status
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_DATE
	 * @var string
	 */
	const SUBTYPE_SELECT_VISA_STATUS = 'visa_status';

	/**
	 * Checkbox > Newsletter
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX
	 * @var string
	 */
	const SUBTYPE_CHECKBOX_NEWSLETTER = Ext_TS_Contact::DETAIL_NEWSLETTER;

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX
	 * @var string
	 */
	const SUBTYPE_CHECKBOX_TOS = Ext_TS_Contact::DETAIL_TOS;

	/**
	 * Checkbox > Visum: Schüler mit Visum
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX
	 * @var string
	 */
	const SUBTYPE_CHECKBOX_VISA_REQUIRED = 'visa_required';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Kommentarfeld
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_NOTICE = Ext_TS_Contact::DETAIL_COMMENT;

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Zuweisung: Kommentar
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_MATCHING_COMMENT = 'matching_comment';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Zuweisung: Allergien
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_MATCHING_ALLERGY = 'matching_allergy';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschte Kurskategorie
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_CLASS_CATEGORY = 'enquiry_class_category';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschtes Kursniveau
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_CLASS_LEVEL = 'enquiry_class_level';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschte Unterkunftskategorie
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_CATEGORY = 'enquiry_accommodation_category';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschte Unterkunftsraumart
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_ROOM_TYPE = 'enquiry_accommodation_room_type';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschte Verpflegung
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_FOOD = 'enquiry_food';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschte Transferart
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_TYPE = 'enquiry_transfer_type';

	/**
	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Gewünschte Transferorte
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
	 * @var string
	 */
	const SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_LOCATIONS = 'enquiry_transfer_locations';

//	/**
//	 * Textarea/Mehrzeiliges Eingabefeld > Anfrage: Kommentar zum Schüler
//	 *
//	 * @see Ext_Thebing_Form_Page_Block::$set_type
//	 * @see Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
//	 * @var string
//	 */
//	const SUBTYPE_TEXTAREA_ENQUIRY_STUDENT_COMMENT = 'enquiry_student_comment';

	/**
	 * Upload > Schülerfoto
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_UPLOAD
	 * @var string
	 */
	const SUBTYPE_UPLOAD_PHOTO = 'file_photo';

	/**
	 * Upload > Reisepass
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @see Ext_Thebing_Form_Page_Block::TYPE_UPLOAD
	 * @var string
	 */
	const SUBTYPE_UPLOAD_PASS = 'file_passport';

	/**
	 * Erlaubte Dateierweiterungen für Uploads
	 */
	const VALIDATION_TYPE_UPLOAD_ALLOWED_EXTENSIONS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'pdf',
		'msg'
	];

	/**
	 * Erlaubte MIME-Typen für Uploads
	 */
	const VALIDATION_TYPE_UPLOAD_ALLOWED_MIME_TYPES = [
		'image/jpeg',
		'image/png',
		'image/gif',
		'application/pdf'
	];

	/**
	 * Relevante Felder für Adresse, z.B. für Zahlungen
	 */
	const SUBTYPES_ADDRESS = [
		self::SUBTYPE_INPUT_FIRSTNAME,
		self::SUBTYPE_INPUT_LASTNAME,
		self::SUBTYPE_INPUT_CONTACT_ADDRESS,
		self::SUBTYPE_INPUT_CONTACT_ZIP,
		self::SUBTYPE_INPUT_CONTACT_CITY,
		self::SUBTYPE_INPUT_CONTACT_STATE,
		self::SUBTYPE_INPUT_CONTACT_EMAIL,
		self::SUBTYPE_INPUT_INVOICE_FIRSTNAME,
		self::SUBTYPE_INPUT_INVOICE_LASTNAME,
		self::SUBTYPE_INPUT_INVOICE_ADDRESS,
		self::SUBTYPE_INPUT_INVOICE_ZIP,
		self::SUBTYPE_INPUT_INVOICE_TAX_NUMBER,
		self::SUBTYPE_INPUT_INVOICE_STATE,
		self::SUBTYPE_INPUT_INVOICE_CITY,
		self::SUBTYPE_INPUT_INVOICE_EMAIL,
		self::SUBTYPE_INPUT_INVOICE_PHONE,
		self::SUBTYPE_INPUT_INVOICE_VAT_ID,
		self::SUBTYPE_INPUT_INVOICE_RECIPIENT_ID,
		self::SUBTYPE_SELECT_CONTACT_COUNTRY,
		self::SUBTYPE_SELECT_INVOICE_COUNTRY
	];

	/**
	 * Min Value for validation of type date (XXXX-XX-XX)
	 *
	 * @var string
	 */
	const VALIDATION_MIN_DATE = '1900-01-01';

	/**
	 * Cache für virtuelle Kind-Blöcke.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getVirtualChildBlocksCourses()
	 * @see Ext_Thebing_Form_Page_Block::getVirtualChildBlocksAccommodations()
	 * @see Ext_Thebing_Form_Page_Block::getVirtualChildBlocksTransfers()
	 * @see Ext_Thebing_Form_Page_Block::getVirtualChildBlocksInsurances()
	 * @see Ext_Thebing_Form_Page_Block::getVirtualChildBlocksPrices()
	 * @var Ext_Thebing_Form_Page_Block[]
	 */
	private $aVirtualChildBlockCache = null;

	protected $_sTable = 'kolumbus_forms_pages_blocks';

	protected $_sTableAlias = 'kfpb';

	protected $_aFormat = array(
		'page_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		),
		'block_id' => array(
			'required' => true
		)
	);

	protected $_aJoinedObjects = array(
		'page' => array(
			'class' => 'Ext_Thebing_Form_Page',
			'key' => 'page_id',
			'type' => 'parent',
			'check_active' => true
		),
		'child_blocks' => array(
			'class' => 'Ext_Thebing_Form_Page_Block',
			'key' => 'parent_id',
			'type' => 'child',
			'check_active' => true,
			'orderby' => 'position',
			'orderby_type' => 'ASC',
			'cloneable' => true
		)
	);

	protected $_aSettings;

	protected $_aTranslations;

	protected $aJsonSettings = [
		'numbers',
		'dependency_services',
		'provider'
	];

	public function __get($sName) {

		Ext_Gui2_Index_Registry::set($this);

		if($sName == 'translations') {
			return $this->_aTranslations;
		}

		$aTemp = explode('_', $sName, 2);

		switch($aTemp[0]) {

			case 'set':
				$aSettings = $this->getSettings();
				return $aSettings[$aTemp[1]];

			case 'translation':
				$sKey = \Illuminate\Support\Str::beforeLast($aTemp[1], '_');
				$sLanguage = \Illuminate\Support\Str::afterLast($aTemp[1], '_');
				return \Illuminate\Support\Arr::get($this->_aTranslations, $sKey.'.'.$sLanguage, '');

			// TODO Warum man hierfür keinen Präfix verwendet hat, wie z.B. für set, wird auf ewig ein Geheimnis bleiben
			case 'title':
			case 'text':
			case 'error':
			case 'infotext':
			case 'add':
			case 'remove':
			case 'start':
			case 'end':
			case 'duration':
			case 'unit':
			case 'roomtype':
			case 'meal':
			case 'extra':
			case 'extraWeek':
			case 'week':
			case 'description':
			case 'level':
			case 'serviceChanged':
			case 'serviceRemoved':
			case 'priceTitle':
			case 'priceCurrency':
			case 'priceCourse':
			case 'priceAccommodation':
			case 'priceInsurance':
			case 'priceTransfer':
			case 'priceCostsGeneral':
			case 'pricePrice':
			case 'priceSum':
			case 'priceTotal':
			case 'priceWeek':
			case 'priceUnit':
			case 'priceExtra':
			case 'priceSpecial':
				$mValue = '';
				if(isset($this->_aTranslations[$aTemp[0]][$aTemp[1]])) {
					$mValue = $this->_aTranslations[$aTemp[0]][$aTemp[1]];
				}
				return $mValue;

			default:
				if(mb_substr($aTemp[0], 0, 9) == 'infotext-') {
					$mValue = '';
					if(isset($this->_aTranslations[$aTemp[0]][$aTemp[1]])) {
						$mValue = $this->_aTranslations[$aTemp[0]][$aTemp[1]];
					}
					return $mValue;
				}

		}

		return parent::__get($sName);

	}

	public function __set($sName, $mValue) {

		$aTemp = explode('_', $sName, 2);

		switch($aTemp[0]) {

			case 'set':
				if(is_array($mValue)) {
					// json_decode() erfolgt nur für bestimmte Werte in Ext_Thebing_Form_Page_Block::getSettings()
					$mValue = json_encode($mValue);
				}
				$this->_aSettings[$aTemp[1]] = $mValue;
				return;

			case 'translation':
				$sKey = \Illuminate\Support\Str::beforeLast($aTemp[1], '_');
				$sLanguage = \Illuminate\Support\Str::afterLast($aTemp[1], '_');
				$this->_aTranslations[$sKey][$sLanguage] = $mValue;
				return;

			// TODO Warum man hierfür keinen Präfix verwendet hat, wie z.B. für set, wird auf ewig ein Geheimnis bleiben
			case 'title':
			case 'text':
			case 'error':
			case 'infotext':
			case 'add':
			case 'remove':
			case 'start':
			case 'end':
			case 'duration':
			case 'unit':
			case 'roomtype':
			case 'meal':
			case 'extra':
			case 'extraWeek':
			case 'week':
			case 'description':
			case 'level':
			case 'serviceChanged':
			case 'serviceRemoved':
			case 'priceTitle':
			case 'priceCurrency':
			case 'priceCourse':
			case 'priceAccommodation':
			case 'priceInsurance':
			case 'priceTransfer':
			case 'priceCostsGeneral':
			case 'pricePrice':
			case 'priceSum':
			case 'priceTotal':
			case 'priceWeek':
			case 'priceUnit':
			case 'priceExtra':
			case 'priceSpecial':
				$this->_aTranslations[$aTemp[0]][$aTemp[1]] = $mValue;
				return;

			default:
				if(mb_substr($aTemp[0], 0, 9) == 'infotext-') {
					$this->_aTranslations[$aTemp[0]][$aTemp[1]] = $mValue;
					return;
				}

		}

		parent::__set($sName, $mValue);

	}

	/**
	 * Alle Blocke (global) auf inaktiv setzten deren Eltern-Block bereits inaktiv ist.
	 */
	public static function clearTable() {

		$sSelectSQL = "
			SELECT
				`kfpb_1`.`id`
			FROM
				`kolumbus_forms_pages_blocks` AS `kfpb_1`
			INNER JOIN
				`kolumbus_forms_pages_blocks` AS `kfpb_2`
			ON
				`kfpb_1`.`parent_id` = `kfpb_2`.`id`
			WHERE
				`kfpb_1`.`active` = 1 AND
				`kfpb_2`.`active` = 0
		";
		$aTemp = DB::getQueryCol($sSelectSQL);

		while(!empty($aTemp)) {
			$sSQL = "
				UPDATE
					`kolumbus_forms_pages_blocks`
				SET
					`active` = 0
				WHERE
					`id` IN(:aIDs)
			";
			$aSQL = array('aIDs' => $aTemp);
			DB::executePreparedQuery($sSQL, $aSQL);
			$aTemp = DB::getQueryCol($sSelectSQL);
		}

	}

	/**
	 * Gibt alle Block-Einstellungen zurück.
	 *
	 * @return mixed[]
	 */
	public function getSettings() {

		$aSettings = (array)$this->_aSettings;

		foreach ($this->aJsonSettings as $sKey) {
			if (!empty($aSettings[$sKey])) {
				$aSettings[$sKey] = json_decode($aSettings[$sKey], true);
			}
		}

		return $aSettings;

	}

	/**
	 * @param string $sKey
	 * @return string |null
	 */
	public function getSetting($sKey) {

		$aSettings = $this->getSettings();
		if(isset($aSettings[$sKey])) {
			return $aSettings[$sKey];
		}

		return null;

	}

	/**
	 * Gibt die nächste Positionsnummer für die angegebene Seite, das Formular und den Bereich zurück.
	 *
	 * Wenn ein Wert nicht angegeben ist, wird der entsprechende Wert des Blocks verwendet.
	 *
	 * @param integer $iPageID
	 * @param integer $iParentID
	 * @param integer $iArea
	 * @return integer
	 */
	public function getNextPosition($iPageID = null, $iParentID = null, $iArea = null) {

		if(is_null($iPageID)) {
			$iPageID = $this->page_id;
		}
		if(is_null($iParentID)) {
			$iParentID = $this->parent_id;
		}
		if(is_null($iArea)) {
			$iArea = $this->parent_area;
		}

		$sSQL = "
			SELECT
				MAX(`position`)
			FROM
				`kolumbus_forms_pages_blocks`
			WHERE
				`active` = 1 AND
				`page_id` = :iPageID AND
				`parent_id` = :iParentID AND
				`parent_area` = :iArea
		";
		$aSQL = array(
			'iPageID' => $iPageID,
			'iParentID' => $iParentID,
			'iArea' => $iArea
		);
		$iPosition = (int)DB::getQueryOne($sSQL, $aSQL);
		$iPosition++;

		return $iPosition;

	}

	/**
	 * {@inheritdoc}
	 *
	 * @param boolean $bUpdateAdditionals
	 */
	public function save($bLog = true, $bUpdateAdditionals = true) {

		$aTranslations = $this->_aTranslations;
		$aSettings = $this->_aSettings;

		// TODO Das ist nicht gut, da das hier immer annimmt, dass die Elemente in korrekter Reihenfolge gespeichert werden
		if(
			$this->id <= 0 &&
			$this->position == 1 // Ganz wichtig fürs Klonen, da ansonsten überall 1 drin steht! #11803
		) {
			$this->position = $this->getNextPosition();
		}

		// page_id korrigieren, wird beim Kopieren des Formulars nicht richtig gesetzt und die WDBasic
		// hat keine Option an der richtigen Stelle in den Kopierprozess einzugreifen :(
		if($this->isChildBlock()) {
			$this->page_id = $this->getParentBlock()->page_id;
		}

		if($this->set_type === self::SUBTYPE_SELECT_SCHOOL) {
			$this->required = 1;
		}

		// Bei Service-Blöcken required rauswerfen, wenn eine Abhängigkeit auf dem Feld ist
		// Damit das funktioniert, müsste erst einmal der Validator auf beiden Seiten angepasst werden
//		if (
//			$this->isFixedBlock() &&
//			!empty($this->getSetting('dependency_type')) &&
//			$this->block_id != self::TYPE_FEES
//		) {
//			$this->required = 0;
//		}

		// required bedeutet required, daher macht die Pflichtauswahl keinen Sinn
		if (
			$aSettings['require_selection'] && (
				$this->block_id == self::TYPE_TRANSFERS ||
				$this->block_id == self::TYPE_INSURANCES
			)
		) {
			$this->required = 0;
		}

		parent::save($bLog);

		if($bUpdateAdditionals) {

			$this->saveTranslations($aTranslations);
			$this->saveBlockSettings($aSettings);

			if($this->block_id == self::TYPE_COLUMNS) {

				$aAreas = array();
				$aNumbers = json_decode($aSettings['numbers']);

				foreach(array_keys((array)$aNumbers) as $iArea) {
					$aAreas[] = $iArea;
				}

				$sSQL = "
					UPDATE
						`kolumbus_forms_pages_blocks`
					SET
						`active` = 0
					WHERE
						`parent_id` = :iParentID AND
						`parent_area` NOT IN(:aAreas)
				";
				$aSQL = array(
					'iParentID' => $this->id,
					'aAreas' => $aAreas
				);
				DB::executePreparedQuery($sSQL, $aSQL);

			}

		}

		self::clearTable();

		$this->_aTranslations = $aTranslations;
		$this->_aSettings = $aSettings;

		return $this;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _loadData($iDataID) {

		parent::_loadData($iDataID);

		if($iDataID <= 0) {
			return;
		}

		$sSQL = "
			SELECT
				`language`,
				`field`,
				`content`
			FROM
				`kolumbus_forms_translations`
			WHERE
				`active` = 1 AND
				`item` = 'block' AND
				`item_id` = :iBlockID
		";
		$aSQL = array(
			'iBlockID' => $this->id
		);
		$aTranslations = DB::getQueryRows($sSQL, $aSQL);

		foreach((array)$aTranslations as $iKey => $aData) {
			$this->_aTranslations[$aData['field']][$aData['language']] = $aData['content'];
		}

		$sSQL = "
			SELECT
				*
			FROM
				`kolumbus_forms_pages_blocks_settings`
			WHERE
				`block_id` = :iBlockID
		";
		$aSQL = array(
			'iBlockID' => $this->id
		);
		$aSettings = DB::getQueryRows($sSQL, $aSQL);

		foreach((array)$aSettings as $iKey => $aData) {
			$this->_aSettings[$aData['setting']] = $aData['value'];
		}

	}

	/**
	 * Gibt die Seite zurück zu der dieser Block gehört.
	 *
	 * @return Ext_Thebing_Form_Page
	 */
	public function getPage() {
		return $this->getJoinedObject('page');
	}

	/**
	 * Gibt true zurück wenn der Block einen Eltern-Block hat, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getParentBlock()
	 * @return boolean
	 */
	public function isChildBlock() {
		return ($this->getParentBlock() !== null);
	}

	/**
	 * Gibt den Eltern-Block dieses Blocks zurück oder null wenn es keinen
	 * Eltern-Block gibt (dann gehört der Block direkt zur Seite).
	 *
	 * @see Ext_Thebing_Form_Page_Block::isChildBlock()
	 * @return null|Ext_Thebing_Form_Page_Block
	 */
	public function getParentBlock() {

		if($this->parent_id < 1) {
			return null;
		}

		// darf nicht über self:: aufgerufen werden da sonst ggf. Instanzen von erbenden (virtuellen) Blöcken
		// erstellt werden, es sollen aber immer Instanzen von Ext_Thebing_Form_Page_Block sein
		$oParentBlock = Ext_Thebing_Form_Page_Block::getInstance($this->parent_id);

		if($oParentBlock->id < 1) {
			return null;
		}

		return $oParentBlock;

	}

	/**
	 * Gibt den ersten nicht virtuellen Eltern-Block dieses Blocks zurück oder null wenn es keinen
	 * Eltern-Block gibt (dann gehört der Block direkt zur Seite).
	 *
	 * @see Ext_Thebing_Form_Page_Block::isChildBlock()
	 * @return null|Ext_Thebing_Form_Page_Block
	 */
	public function getNonVirtualParentBlock() {

		for($oParent = $this->getParentBlock(); $oParent !== null; $oParent = $oParent->getParentBlock()) {

			if(!$oParent->isVirtualBlock()) {

				return $oParent;

			}

		}

		return null;

	}

	/**
	 * Gibt true zurück wenn der Block Kind-Blöcke hat, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @param boolean $bCheckCache
	 * @return boolean
	 */
	public function hasChildBlocks($bCheckCache = false) {
		return count($this->getChildBlocks($bCheckCache)) > 0;
	}

	/**
	 * Gibt die Liste mit allen aktiven Blöcken zurück die direkte Kinder dieses Blocks sind.
	 *
	 * Wenn dieser Block ein fester Block ist werden virtuelle Kind-Blöcke zurück gegeben um die nötigen
	 * Eingabefelder generieren zu können.
	 *
	 * @see Ext_Thebing_Form_Page_Block::hasChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::getFilteredChildBlocks()
	 * @param boolean $bCheckCache
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getChildBlocks($bCheckCache = false) {

		$aChildBlocks = array();

		if($this->isFixedBlock()) {

			// Diese Liste muss mit der Liste in Ext_Thebing_Form_Page_Block::isFixedBlock() übereinstimmen
			switch($this->block_id) {
				case self::TYPE_COURSES:
					$aChildBlocks = $this->getVirtualChildBlocksCourses();
					break;
				case self::TYPE_ACCOMMODATIONS:
					$aChildBlocks = $this->getVirtualChildBlocksAccommodations();
					break;
				case self::TYPE_TRANSFERS:
					$aChildBlocks = $this->getVirtualChildBlocksTransfers();
					break;
				case self::TYPE_INSURANCES:
					$aChildBlocks = $this->getVirtualChildBlocksInsurances();
					break;
				case self::TYPE_PRICES:
					$aChildBlocks = $this->getVirtualChildBlocksPrices();
					break;
				case self::TYPE_FEES:
					$aChildBlocks = $this->getVirtualChildBlocksCostsGeneral();
					break;
			}

		} elseif(!$this->isVirtualBlock()) {

			$aChildBlocks = $this->getJoinedObjectChilds('child_blocks', $bCheckCache);

		}

		return $aChildBlocks;

	}

	/**
	 * @return self[]
	 */
	public function getChildBlocksRecursively() {

		/** @var self[] $aChilds */
		$aChilds = $this->getJoinedObjectChilds('child_blocks', true);
		foreach ($aChilds as $oChild) {
			$aChilds = array_merge($aChilds, $oChild->getChildBlocksRecursively());
		}

		return $aChilds;

	}

	/**
	 * Gibt die Liste mit allen aktiven Blöcken zurück die direkte Kinder dieses Blocks sind und in den
	 * angegebenen Bereich gehören.
	 *
	 * Bereiche werden von 0 aufsteigend nummeriert, von links nach rechts.
	 *
	 * @see Ext_Thebing_Form_Page_Block::hasChildBlocks()
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getAreaWidths()
	 * @param integer $iAreaNumber
	 * @param boolean $bCheckCache
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getChildBlocksForArea($iAreaNumber, $bCheckCache = false) {

		$aChildBlocks = $this->getChildBlocks($bCheckCache);

		$aChildBlocks = array_filter(
			$aChildBlocks,
			function(Ext_Thebing_Form_Page_Block $oBlock) use($iAreaNumber) {
				return $oBlock->parent_area == $iAreaNumber;
			}
		);

		return $aChildBlocks;

	}

	/**
	 * Generiert virtuelle Kind-Blöcke für den Kurse-Block.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	private function getVirtualChildBlocksCourses() {

		if(is_array($this->aVirtualChildBlockCache)) {
			return $this->aVirtualChildBlockCache;
		}

		$this->aVirtualChildBlockCache = array();

		$oDuplicateableBlock = new Ext_Thebing_Form_Page_Block_Virtual_Container();
		$oDuplicateableBlock->sAdditionalBlockDataAttributes = 'data-duplicateable="area"';
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oDuplicateableBlock);

		$aDuplicateableChildBlocks = array();

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype($oDuplicateableBlock);
		$oBlock->required = $this->required;
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Courses_Level($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Courses_Units($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Courses_Duration($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Duplicatorcontrols($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oDuplicateableBlock->aChildBlocks = $aDuplicateableChildBlocks;

		return $this->aVirtualChildBlockCache;

	}

	/**
	 * Generiert virtuelle Kind-Blöcke für den Unterkunft-Block.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	private function getVirtualChildBlocksAccommodations() {

		if(is_array($this->aVirtualChildBlockCache)) {
			return $this->aVirtualChildBlockCache;
		}

		$this->aVirtualChildBlockCache = array();

		$oDuplicateableBlock = new Ext_Thebing_Form_Page_Block_Virtual_Container();
		$oDuplicateableBlock->sAdditionalBlockDataAttributes = 'data-duplicateable="area"';
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oDuplicateableBlock);

		$aDuplicateableChildBlocks = array();

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Accommodationtype($oDuplicateableBlock);
		$oBlock->required = $this->required;
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Roomtype($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Meals($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Duration($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Enddate($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Duplicatorcontrols($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oDuplicateableBlock->aChildBlocks = $aDuplicateableChildBlocks;

		return $this->aVirtualChildBlockCache;

	}

	/**
	 * Generiert virtuelle Kind-Blöcke für den Transfer-Block.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	private function getVirtualChildBlocksTransfers() {

		if(is_array($this->aVirtualChildBlockCache)) {
			return $this->aVirtualChildBlockCache;
		}

		$this->aVirtualChildBlockCache = array();

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Transfertype();
		$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Headline();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferfrom();
		//$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Transferto();
		//$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Airline();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Flightnumber();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Date();
		//$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Time();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Notice();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Headline();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferfrom();
		//$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Transferto();
		//$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Airline();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Flightnumber();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Date();
		//$oBlock->required = $this->required;
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Time();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Notice();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		return $this->aVirtualChildBlockCache;

	}

	/**
	 * Generiert virtuelle Kind-Blöcke für den Versicherung-Block.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	private function getVirtualChildBlocksInsurances() {

		if(is_array($this->aVirtualChildBlockCache)) {
			return $this->aVirtualChildBlockCache;
		}

		$this->aVirtualChildBlockCache = array();

		$oDuplicateableBlock = new Ext_Thebing_Form_Page_Block_Virtual_Container();
		$oDuplicateableBlock->sAdditionalBlockDataAttributes = 'data-duplicateable="area"';
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oDuplicateableBlock);

		$aDuplicateableChildBlocks = array();

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Insurances_Insurancetype($oDuplicateableBlock);
		$oBlock->required = $this->required;
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Insurances_Startdate($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Insurances_Duration($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Insurances_Enddate($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Duplicatorcontrols($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oDuplicateableBlock->aChildBlocks = $aDuplicateableChildBlocks;

		return $this->aVirtualChildBlockCache;

	}

	/**
	 * Generiert virtuelle Kind-Blöcke für den Preise-Block.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	private function getVirtualChildBlocksPrices() {

		if(is_array($this->aVirtualChildBlockCache)) {
			return $this->aVirtualChildBlockCache;
		}

		$this->aVirtualChildBlockCache = array();

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Prices_Currency();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Prices_Display();
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oBlock);

		return $this->aVirtualChildBlockCache;

	}

	/**
	 * Generiert virtuelle Kind-Blöcke für den Block der generellen Gebühren
	 *
	 * @return array|Ext_Thebing_Form_Page_Block[]
	 */
	private function getVirtualChildBlocksCostsGeneral() {

		if(is_array($this->aVirtualChildBlockCache)) {
			return $this->aVirtualChildBlockCache;
		}

		$this->aVirtualChildBlockCache = array();

		$oDuplicateableBlock = new Ext_Thebing_Form_Page_Block_Virtual_Container();
		$oDuplicateableBlock->sAdditionalBlockDataAttributes = 'data-duplicateable="area"';
		$this->addVirtualChildBlock($this->aVirtualChildBlockCache, $oDuplicateableBlock);

		$aDuplicateableChildBlocks = array();

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Costs_Cost($oDuplicateableBlock);
		$oBlock->required = $this->required;
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oBlock = new Ext_Thebing_Form_Page_Block_Virtual_Duplicatorcontrols($oDuplicateableBlock);
		$this->addVirtualChildBlock($aDuplicateableChildBlocks, $oBlock);

		$oDuplicateableBlock->aChildBlocks = $aDuplicateableChildBlocks;

		return $this->aVirtualChildBlockCache;

	}

	/**
	 * Fügt einen virtuellen Kind-Block zur angegebenen Liste hinzu.
	 *
	 * Der Block wird an die angegebene Liste angehängt und die Position, Eltern-Block, usw. gesetzt.
	 *
	 * @param Ext_Thebing_Form_Page_Block[] $aChildBlocks
	 * @param Ext_Thebing_Form_Page_Block $oBlock
	 */
	private function addVirtualChildBlock(array &$aChildBlocks, Ext_Thebing_Form_Page_Block $oBlock) {

		$oBlock->page_id = $this->page_id;
		$oBlock->parent_id = $this->id;
		//$oBlock->required = $this->required;
		$oBlock->position = count($aChildBlocks);

		$aChildBlocks[] = $oBlock;

	}

	/**
	 * Gibt die angegebene Übersetzung zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Sollte ein Wert nicht existieren oder nicht gesetzt sein wird ein leerer String zurück gegeben.
	 *
	 * Die Übersetzungen sind in "kolumbus_forms_translations" gespeichert, "item" = "block".
	 *
	 * @param string $sKey
	 * @param \Tc\Service\Language\Frontend|string $mLanguage
	 * @return string
	 */
	public function getTranslation($sKey, $mLanguage = null) {

		// V2
		if(empty($mLanguage)) {
			$mLanguage = $this->getDynamicLanguage($mLanguage);
		}

		if($mLanguage instanceof \Tc\Service\Language\Frontend) {
			$oLanguage = $mLanguage;
			$sLanguage = $mLanguage->getLanguage();
		} else {
			$oLanguage = new \Tc\Service\Language\Frontend($mLanguage);
			$oLanguage->setContext(\TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT);
			$sLanguage = $mLanguage;
		}

		if(empty($this->_aTranslations[$sKey][$sLanguage])) {
			return $this->getDefaultTranslation($sKey, $oLanguage);
		}

		return (string)$this->_aTranslations[$sKey][$sLanguage];

	}

	/**
	 * Standard-Frontend-Übersetzung für Feld
	 *
	 * @param string $sKey
	 * @param \Tc\Service\Language\Frontend $oLanguage
	 * @return mixed|string
	 */
	private function getDefaultTranslation($sKey, \Tc\Service\Language\Frontend $oLanguage) {

		switch($this->block_id) {
			case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
			case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
			case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
			case Ext_Thebing_Form_Page_Block::TYPE_DATE:
			case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
			case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
			case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
			case Ext_Thebing_Form_Page_Block::TYPE_YESNO:

				switch($sKey) {
					case 'title':
						$oFormFieldsGenerator = new \TsRegistrationForm\Generator\FormFieldsGenerator();
						$oFormFieldsGenerator->setFrontendLanguage($oLanguage);
						$aFields = $oFormFieldsGenerator->generate();

						$aField = $aFields->firstWhere('key', $this->set_type);
						if(isset($aField['frontend_label'])) {
							return $aField['frontend_label'];
						}
						return '';
					case 'error':
						$oForm = $this->getPage()->getForm();
						return $oForm->getTranslation('errorrequired', $oLanguage);
					default:
						return '';
				}

			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
			case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:
			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
			case Ext_Thebing_Form_Page_Block::TYPE_PRICES:
			case Ext_Thebing_Form_Page_Block::TYPE_FEES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY:

				$aTranslations = $this->getTranslationConfig(Ext_Thebing_Form_Gui2::getLanguageObject(), $oLanguage);
				if(isset($aTranslations[$sKey])) {
					return $aTranslations[$sKey][1];
				}

				return '';
			default:
				return '';

		}

	}

	/**
	 * Übersetzung mit Singular/Plural
	 *
	 * @link https://laravel.com/docs/5.0/localization#pluralization
	 *
	 * @param string $sKey
	 * @param int $iCount
	 * @param \Tc\Service\Language\Frontend|string $mLanguage
	 * @return mixed
	 */
	public function getTranslationChoice($sKey, $iCount, $mLanguage) {

		$oSelector = new \Illuminate\Translation\MessageSelector();
		$sTranslation = $this->getTranslation($sKey, $mLanguage);

		$sLanguage = $mLanguage;
		if ($mLanguage instanceof \Tc\Service\Language\Frontend) {
			$sLanguage = $mLanguage->getLanguage();
		}

		return $oSelector->choose($sTranslation, $iCount, $sLanguage);

	}

	public function checkIfTranslationIsFieldLabel(string $key): bool {

		return match ((int)$this->block_id) {
			Ext_Thebing_Form_Page_Block::TYPE_COURSES => in_array($key, ['title', 'language', 'level', 'start', 'duration', 'units', 'program']),
			Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS => in_array($key, ['start', 'end']),
//			Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS => in_array($key, ['origin', 'destination', 'type', 'date']),
			Ext_Thebing_Form_Page_Block::TYPE_INSURANCES => in_array($key, ['start', 'end', 'duration']),
			Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY => in_array($key, ['start', 'duration', 'units']),
			default => false,
		};

	}

	/**
	 * Gibt den Titel des Blocks in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block keinen Titel haben kann wird ein leerer String zurück gegeben.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getTitle($sLanguage = null) {
		return $this->getTranslation('title', $sLanguage);
	}

	/**
	 * Gibt den Text des Blocks in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block keinen Text hat wird ein leerer String zurück gegeben.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getBlockText($sLanguage = null) {
		return $this->getTranslation('text', $sLanguage);
	}

	/**
	 * Gibt die Fehlermeldung die angezeigt werden soll wenn das Eingabefeld in diesem Block falsch ausgefüllt wurde
	 * in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block kein Eingabefeld hat wird ein leerer String zurück gegeben.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getErrorMessage($sLanguage = null) {
		return $this->getTranslation('error', $sLanguage);
	}

	/**
	 * Gibt die Infomeldung die zusätzlich zum Eingabefeld dieses Blocks angezeigt werden soll
	 * in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block kein Eingabefeld hat wird ein leerer String zurück gegeben.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getInfoMessage($sLanguage = null) {
		return $this->getTranslation('infotext', $sLanguage);
	}

	/**
	 * Gibt die Unterart des Blocks zurück.
	 *
	 * Wenn der Block keine Unterart hat wird ein leerer String zurück gegeben.
	 *
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 * @return string
	 */
	public function getSubtype() {
		return (string)$this->set_type;
	}

	/**
	 * @return bool
	 */
	public function isRequired() {
		return (bool)$this->required;
	}

	/**
	 * Gibt true zurück wenn der Block mehrere Bereiche haben kann, ansonsten false.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getAreaWidths()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @see Ext_Thebing_Form_Page_Block::getAreaType()
	 * @return boolean
	 */
	public function hasAreas() {

		if(
			$this->block_id == self::TYPE_COLUMNS ||
			$this->isFixedBlock()
		) {
			return true;
		}
		return false;

	}

	/**
	 * Gibt ein Array mit den Breiten der einzelnen Bereiche zurück.
	 *
	 * Die Werte sind Prozentangaben, Anordnung von links nach rechts.
	 *
	 * Die Array-Keys werden von 0 aufsteigend vergeben und können direkt als Parameter für
	 * Ext_Thebing_Form_Page_Block::getChildBlocksForArea() verwendet werden.
	 *
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocksForArea()
	 * @return integer[]
	 */
	public function getAreaWidths() {

		// Feste Blöcke generieren virtuelle Kind-Blöcke für die nötigen Felder und gelten
		// deswegen als Blöcke mit mehreren Bereichen, haben aber effektiv nur einen 100% breiten
		// Bereich in dem alle Kind-Blöcke angezeigt werden.
		if($this->isFixedBlock()) {
			return array(0 => 100);
		}

		// Die Liste wird zur Sicherheit neu nummeriert damit die Keys wirklich bei 0
		// beginnen und durchgängig nummeriert sind (damit über die Keys auch die Kind-Blöcke
		// korrekt abgefragt werden können).
		$aTmpAreaWidths = $this->set_numbers;
		$iAreaNumber = 0;
		$aAreaWidths = array();
		foreach($aTmpAreaWidths as $iAreaWidth) {
			$aAreaWidths[$iAreaNumber] = (int)$iAreaWidth;
			$iAreaNumber++;
		}

		return $aAreaWidths;

	}

	/**
	 * Gibt die Art des Mehrbereichts-Blocks zurück (Standard, Kurse, Unterkünfte, usw.).
	 *
	 * Wenn der Block nicht mehrere Bereiche hat wird ein leerer String zurück gegeben.
	 *
	 * @see Ext_Thebing_Form_Page_Block::hasAreas()
	 * @return string
	 */
	public function getAreaType() {

		if(!$this->hasAreas()) {
			return '';
		}

		switch($this->block_id) {
			case self::TYPE_COURSES:
				return 'courses';
			case self::TYPE_ACCOMMODATIONS:
				return 'accommodations';
			case self::TYPE_TRANSFERS:
				return 'transfers';
			case self::TYPE_INSURANCES:
				return 'insurances';
			case self::TYPE_PRICES:
				return 'prices';
			case self::TYPE_FEES:
				return 'costs-general';
		}	

		return 'default';

	}

	/**
	 * Gibt true zurück wenn es sich um einen Überschrift-Block handelt, ansonsten false.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getHeadlineLevel()
	 * @return boolean
	 */
	public function isHeadlineBlock() {

		// Wenn der Block ein Überschrift-Level hat ist es ein Überschrift-Block, durch diese
		// Abfrage muss die Liste mit Block-Arten die ein Überschrift-Block sind nicht
		// redundant gepflegt werden.
		return $this->getHeadlineLevel() > 0;

	}

	/**
	 * Gibt das Überschrift-Level zurück oder 0 wenn der Block kein Überschrift-Block ist.
	 *
	 * @see Ext_Thebing_Form_Page_Block::isHeadlineBlock()
	 * @return integer
	 */
	public function getHeadlineLevel() {

		switch($this->block_id) {
			case self::TYPE_HEADLINE2:
				return 2;
			case self::TYPE_HEADLINE3:
				return 3;
		}

		return 0;

	}

	/**
	 * Gibt true zurück wenn es sich um einen Text-Block handelt, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isTextBlock() {
		return $this->block_id == self::TYPE_STATIC_TEXT;
	}

	/**
	 * Gibt true zurück wenn es sich um einen Eingabe-Block handelt, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isInputBlock() {

		$aInputBlockIds = array(
			self::TYPE_DOWNLOAD,
			self::TYPE_INPUT,
			self::TYPE_SELECT,
			self::TYPE_DATE,
			self::TYPE_CHECKBOX,
			self::TYPE_UPLOAD,
			self::TYPE_TEXTAREA,
			self::TYPE_MULTISELECT,
			self::TYPE_YESNO
		);

		return in_array($this->block_id, $aInputBlockIds);

	}

	/**
	 * Gibt den Namen, der für das Eingabefeld im HTML verwendet werden soll, zurück.
	 *
	 * Wenn der Block kein Eingabefeld darstellt wird ein leerer String zurück gegeben.
	 *
	 * Ein Download-Block ist zwar technisch gesehen kein Eingabefeld, in diesem Fall ist der Name der Key über
	 * den die Datei angefragt werden kann.
	 *
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @return string
	 */
	public function getInputBlockName() {

		if(!$this->isInputBlock()) {
			return '';
		}

		$sName = '';

		// Sonderfall: virtuelle Blöcke als Kinder von festen Blöcken
		if($this->isVirtualBlock()) {
			$oParentBlock = $this->getNonVirtualParentBlock();
			if($oParentBlock !== null) {
				$sName = $oParentBlock->getInputDataIdentifier();
			}
		}

		// Standardmäßig einfach die Block-ID nehmen
		if(strlen($sName) < 1) {
			$sName = 'block_'.$this->id;
		}

		// Wenn der Block eine Unterart hat diese auch noch an den Namen anhängen
		$sSubtype = $this->getSubtype();
		if(strlen($sSubtype) > 0) {
			$sName .= '_'.$sSubtype;
		}

		return $sName;

	}

	/**
	 * @return bool
	 */
	public function isServiceBlock(): bool {

		return in_array($this->block_id, [
			self::TYPE_COURSES,
			self::TYPE_ACCOMMODATIONS,
			self::TYPE_TRANSFERS,
			self::TYPE_INSURANCES,
			self::TYPE_FEES,
			self::TYPE_ACTIVITY,
		]);

	}

	/**
	 * Gibt true zurück wenn es sich um einen festen Block handelt, ansonsten false.
	 *
	 * @deprecated
	 * @return boolean
	 */
	public function isFixedBlock() {

		// Diese Liste muss mit der Liste in Ext_Thebing_Form_Page_Block::getChildBlocks() übereinstimmen
		$aFixedBlockIds = array(
			self::TYPE_COURSES,
			self::TYPE_ACCOMMODATIONS,
			self::TYPE_TRANSFERS,
			self::TYPE_INSURANCES,
			self::TYPE_PRICES,
			self::TYPE_FEES,
			self::TYPE_PAYMENT,
			self::TYPE_ACTIVITY,
		);

		return in_array($this->block_id, $aFixedBlockIds);

	}

	/**
	 * Gibt die Liste der gültigen Select-Optionen in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block kein Select-Feld ist wird ein leeres Array zurück gegeben. Diese Methode funktioniert
	 * nur für Eingabe-Blöcke, nicht für feste Blöcke.
	 *
	 * Array-Keys sind die internen Werte, Values die dazugehörige lesbare Darstellung. Das Array kann also
	 * direkt zum Generieren eines HTML-Select-Feldes o.ä. verwendet werden.
	 *
	 * @see Ext_Thebing_Form_Page_Block::isInputBlock()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param \Tc\Service\Language\Frontend|string $mLanguage
	 * @param boolean $bAddEmpty
	 * @param string $sEmptyText
	 * @param string $sEmptyValue
	 * @return string[]
	 */
	public function getSelectOptions($mSchool, $mLanguage = null, $bAddEmpty = true, $sEmptyText = '', $sEmptyValue = '0') {

		// V2
		if(empty($mLanguage)) {
			$mLanguage = $this->getDynamicLanguage($mLanguage);
		}

		if($mLanguage instanceof \Tc\Service\Language\Frontend) {
			$oLanguage = $mLanguage;
			$sLanguage = $mLanguage->getLanguage();
		} else {
			$oLanguage = new \Tc\Service\Language\Frontend($mLanguage);
			$oLanguage->setContext(\TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT);
			$sLanguage = $mLanguage;
		}

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$bAddEmpty = (bool)$bAddEmpty;
		$sEmptyText = (string)$sEmptyText;
		$sEmptyValue = (string)$sEmptyValue;
		$aOptions = array();

		if(
			// alles was kein Select-Feld ist hat auch keine Optionen
			$this->block_id != self::TYPE_SELECT &&
			$this->block_id != self::TYPE_MULTISELECT &&
			$this->block_id != self::TYPE_YESNO ||
			// virtuelle Blöcke können Select-Felder sein, sollen aber auf jeden Fall selber definieren
			// was für Optionen verfügbar sind (Kind-Klassen)
			$this->isVirtualBlock()
		) {
			return $aOptions;
		}

		// V3
		if($this->block_id == self::TYPE_YESNO) {
			$aOptions = [
				'yes' => $oLanguage->translate('Yes'),
				'no' => $oLanguage->translate('No')
			];
		}

		// Geschlecht
		if($this->set_type == self::SUBTYPE_SELECT_SEX) {
			$aOptions = Ext_TC_Util::getGenders(false, '', $oLanguage);
		}

		// Visa-Status
		if($this->set_type == self::SUBTYPE_SELECT_VISA_STATUS) {
			$aOptions = Ext_Thebing_Visum::getVisumStatusList($oSchool->id);
		}

		// Kontakt: Land
		// Rechnungsadresse: Land
		// Zusatz: Geburtsland
		elseif(
			$this->set_type == self::SUBTYPE_SELECT_CONTACT_COUNTRY ||
			$this->set_type == self::SUBTYPE_SELECT_INVOICE_COUNTRY ||
			$this->set_type == self::SUBTYPE_SELECT_ADDITIONAL_COUNTRYOFBIRTH
		) {
//			$oLocaleService = new Core\Service\LocaleService();
//			$aOptions = $oLocaleService->getCountries($sLanguage);
			$aOptions = \Ext_Thebing_Country_Search::getCountriesForFrontend($sLanguage);
		}

		// Muttersprache
		elseif($this->set_type == self::SUBTYPE_SELECT_MOTHERTONGE) {
			$aOptions = Ext_TC_Language::getSelectOptions($sLanguage);
		}

		// Schule
		elseif($this->set_type == self::SUBTYPE_SELECT_SCHOOL) {
			$aSchools = \Ext_Thebing_Client::getSchoolList(true);
			$aFormSchools = $this->getPage()->getForm()->schools;
			$aOptions = array_intersect_key($aSchools, array_flip($aFormSchools));
		}

		// Nationalität
		elseif($this->set_type == self::SUBTYPE_SELECT_NATIONALITY) {
			// Ext_TC_Nationality macht quasi das gleiche, nur ohne die WDBasic. Hier wird die Version der
			// Schule verwendet weil sie vorhanden ist, keine Ahnung ob es einen Unterschied machen würde.
			$aOptions = Ext_Thebing_Nationality::getNationalities(true, $sLanguage, false);
		}

		// Quelle: "aufmerksam auf Schule"
		elseif($this->set_type == self::SUBTYPE_SELECT_REFERER_ID) {
			$aOptions = \Ext_TS_Referrer::getReferrers(true, $oSchool->id, $sLanguage);
		}

		// Matching-Felder: ja/nein
		elseif(
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_SMOKER ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_VEGETARIAN ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_MUSLIM ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_CATS ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_DOGS ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_ANIMALS ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_SMOKER ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_CLIMA ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_OWN_BATHROOM ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_CHILDS ||
			$this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_INTERNET
		) {
			$aOptions = Ext_Thebing_Util::getMatchingYesNoArray($sLanguage);
			unset($aOptions[0]);
		}

		// Matching: Entfernung zur Familie
		elseif($this->set_type == self::SUBTYPE_SELECT_MATCHING_DISTANCE) {
			$aOptions = Ext_Thebing_Data::getDistance($oLanguage);
			unset($aOptions[0]);
		}

		// Matching: Alter der Familie
		elseif($this->set_type == self::SUBTYPE_SELECT_MATCHING_FAMILY_AGE) {
			$aOptions = Ext_Thebing_Data::getFamilyAge($oLanguage);
			unset($aOptions[0]);
		}

		// Flexibles Feld
		elseif(
			$this->isFlexFieldBlock() &&
			$this->block_id != self::TYPE_YESNO
		) {
			$iFlexId = $this->getFlexFieldId();
			$aOptions = Ext_TC_Flexibility::getOptions($iFlexId, $sLanguage);
		}

		if($bAddEmpty) {
			$aOptions = Ext_TC_Util::addEmptyItem($aOptions, $sEmptyText, $sEmptyValue);
		}

		return $aOptions;

	}

	/**
	 * Gibt true zurück wenn es sich um einen virtuellen Block handelt, ansonsten false.
	 *
	 * @return bool
	 */
	public function isVirtualBlock() {

		if(
			$this->id < 1 &&
			$this->isChildBlock() && (
				$this->getParentBlock()->isFixedBlock() ||
				$this->getParentBlock()->isVirtualBlock()
			)
		) {
			return true;
		}

		return false;

	}

	/**
	 * @deprecated V2
	 *
	 * Gibt einen gültigen Sprach-Code zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	protected function getDynamicLanguage($sLanguage) {

		$sLanguage = (string)$sLanguage;

		if(strlen($sLanguage) < 1) {
			$oPage = $this->getPage();
			$oForm = $oPage->getForm();
			$sLanguage = $oForm->default_language;
		}

		return $sLanguage;

	}

	/**
	 * Gibt true zurück wenn es sich um einen Spezial-Block handelt, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isSpecialBlock() {

		// aktuell hier immer false, muss wenn dann in Kind-Klassen aktiviert werden
		return false;

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getBlockDataAttributesArray()
	 * @uses Ext_Thebing_Form_Page_Block::getAdditionalBlockDataAttributes();
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getBlockDataAttributes($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oForm = $this->getPage()->getForm();

		$sCacheGroup = 'FormDataCache_'.$oForm->id.'_'.$oSchool->id.'_'.$sLanguage;
		$sCacheKey = $sCacheGroup.'_'.$this->getInputDataIdentifier().'_block';
		$sReturn = '';
		if($oForm->useCache()) {
			if($oForm->oCachingHelper instanceof Ext_TC_Frontend_Combination_Helper_Caching) {
				$sReturn = $oForm->oCachingHelper->getFromCache($sCacheKey);
			} else {
				$sReturn = WDCache::get($sCacheKey);
			}
			if($sReturn !== null) {
				return $sReturn;
			}
			$sReturn = '';
		}

		$aAttributes = $this->getBlockDataAttributesArray($mSchool, $sLanguage);
		if(count($aAttributes) > 0) {
			$sAttributes = htmlentities(json_encode($aAttributes));
			$sReturn .= ' data-dynamic-config="'.$sAttributes.'" ';
		}

		$sReturn .= ' '.$this->getAdditionalBlockDataAttributes($mSchool, $sLanguage).' ';
		$sReturn .= ' data-validateable="block" ';

		$sReturn = trim($sReturn);

		if(strlen($sReturn) > 0) {
			$sReturn = ' '.$sReturn.' ';
			if($oForm->oCachingHelper instanceof Ext_TC_Frontend_Combination_Helper_Caching) {
				// Wenn der Caching-Helper gesetzt ist den Rückgabewert speichern - keine Abfrage auf
				// $oForm->useCache() da diese Methode nur angibt ob aus dem Cache geladen werden soll
				$oForm->oCachingHelper->writeToCache($sCacheKey, $sReturn);
			} else {
				// Ansonsten WDCache benutzen
				$iCacheExpiration = (60 * 60 * 36);
				WDCache::set($sCacheKey, $iCacheExpiration, $sReturn, false, $sCacheGroup);
			}
		}

		return $sReturn;

	}

	/**
	 * Gibt die Daten-Attribute als Array zurück.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getBlockDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function getBlockDataAttributesArray($mSchool, $sLanguage = null) {

		$aAttributes = array();

		switch($this->block_id) {
			case self::TYPE_COURSES:

				// Nachfolgende Kurse deaktivieren
				$aDisableOptions = [];
				if($this->getSetting('limit_following_'.$mSchool->getId())) {

					$oCombination = $this->getPage()->getForm()->oCombination;
					$aCourses = $oCombination->getServiceHelper()->getCourses();
					$aFirstCourses = $aFollowingCourses = [];
					foreach($aCourses as $oCourseDto) {
						if(
							$oCourseDto->bFirstCourse &&
							$oCourseDto->bFollowingCourse
						) {
							// Als erster Kurs und Nachfolgekurs verfügbar, daher überspringen
							continue;
						}

						if($oCourseDto->bFirstCourse) {
							$aFirstCourses[] = $oCourseDto->oCourse->id;
						}
						if($oCourseDto->bFollowingCourse) {
							$aFollowingCourses[] = $oCourseDto->oCourse->id;
						}
					}

					$sCourseTypeField = null;
					$oVirtualCourseContainer = reset($this->getVirtualChildBlocksCourses());
					foreach($oVirtualCourseContainer->getChildBlocks(true) as $oBlock) {
						if($oBlock instanceof Ext_Thebing_Form_Page_Block_Virtual_Courses_Coursetype) {
							$sCourseTypeField = $oBlock->getInputDataIdentifier();
						}
					}

					// Alle Kurse im ersten Container deaktivieren, die nicht dort verfügbar sind
					$aDisableOptions[] = [
						'input' => $sCourseTypeField,
						'values' => $aFollowingCourses,
						'offset' => 'exact',
						'offset_value' => 0
					];

					// Alle Kurse in Container > 0 deaktivieren, die nicht als Nachfolgekurs verfügbar sind
					$aDisableOptions[] = [
						'input' => $sCourseTypeField,
						'values' => $aFirstCourses,
						'offset' => 'from',
						'offset_value' => 1
					];

				}

				$aAttributes[] = array(
					'type' => 'DuplicateableContainer',
					'data' => [
						'disable_options' => $aDisableOptions
					]
				);

				// Schulferien
				$aAttributes[] = [
					'type' => 'AjaxContainerChange',
					'data' => [
						'task' => 'prices',
						'type' => 'courses'
					]
				];

				break;
			case self::TYPE_ACCOMMODATIONS:
			case self::TYPE_INSURANCES:
			case self::TYPE_FEES:
				$aAttributes[] = array(
					'type' => 'DuplicateableContainer',
					'data' => []
				);
				break;
		}

		return $aAttributes;

	}

	/**
	 * Gibt die zusätzlichen Daten-Attribute als String zurück.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getBlockDataAttributes()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getAdditionalBlockDataAttributes($mSchool, $sLanguage = null) {

		return '';

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getTitleDataAttributesArray()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getTitleDataAttributes($mSchool, $sLanguage = null) {

		$sReturn = '';

		$aAttributes = $this->getTitleDataAttributesArray($mSchool, $sLanguage);
		if(count($aAttributes) > 0) {
			$sAttributes = htmlentities(json_encode($aAttributes));
			$sReturn .= ' data-dynamic-config="'.$sAttributes.'" ';
		}

		$sReturn = trim($sReturn);

		if(strlen($sReturn) > 0) {
			$sReturn = ' '.$sReturn.' ';
		}

		return $sReturn;

	}

	/**
	 * Gibt die Daten-Attribute als Array zurück.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function getTitleDataAttributesArray($mSchool, $sLanguage = null) {

		$aAttributes = array();
		return $aAttributes;

	}

	/**
	 * Checkbox: Label (Titel) als Platzhalter (HTML5 placeholder) anzeigen
	 *
	 * @return bool
	 */
	public function isShowingLabelAsPlaceholder() {

		if($this->isVirtualBlock()) {
			$oParentBlock = $this->getNonVirtualParentBlock();
			if($oParentBlock !== null) {
				return !!$oParentBlock->getSetting('label_as_placeholder');
			}
		}

		return !!$this->getSetting('label_as_placeholder');

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page_Block::getInputDataAttributesArray()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getInputDataAttributes($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oForm = $this->getPage()->getForm();

		$sCacheGroup = 'FormDataCache_'.$oForm->id.'_'.$oSchool->id.'_'.$sLanguage;
		$sCacheKey = $sCacheGroup.'_'.$this->getInputDataIdentifier().'_input';
		$sReturn = '';
		if($oForm->useCache()) {
			if($oForm->oCachingHelper instanceof Ext_TC_Frontend_Combination_Helper_Caching) {
				$sReturn = $oForm->oCachingHelper->getFromCache($sCacheKey);
			} else {
				$sReturn = WDCache::get($sCacheKey);
			}
			if($sReturn !== null) {
				return $sReturn;
			}
			$sReturn = '';
		}

		$aAttributes = $this->getInputDataAttributesArray($mSchool, $sLanguage);

		$aHookData = ['attributes' => &$aAttributes, 'block' => $this];
		\System::wd()->executeHook('ts_frontend_form_input_data_attributes_array', $aHookData);

		if(count($aAttributes) > 0) {
			$sAttributes = htmlentities(json_encode($aAttributes));
			$sReturn .= ' data-dynamic-config="'.$sAttributes.'" ';
		}

		$sIdentifier = $this->getInputDataIdentifier();
		if(strlen($sIdentifier) > 0) {
			$sIdentifier = htmlentities($sIdentifier);
			$sReturn .= ' data-dynamic-identifier="'.$sIdentifier.'" ';
		}

		if(
			$this->isShowingLabelAsPlaceholder() &&
			$this->block_id == self::TYPE_INPUT ||
			$this->block_id == self::TYPE_TEXTAREA ||
			$this->block_id == self::TYPE_DATE
		) {
			$sTitle = $this->getTitle($sLanguage);
			if($this->required) {
				$sTitle .= ' *';
			}
			$sReturn .= ' placeholder="'.$sTitle.'"';
		}

		if(
			Ext_Thebing_Util::isDevSystem() ||
			Util::isDebugIP()
		) {
			$sReturn .= ' data-debug-set-type="'.$this->set_type.'" ';
		}

		$sReturn = trim($sReturn);

		if(strlen($sReturn) > 0) {
			$sReturn = ' '.$sReturn.' ';
			if($oForm->oCachingHelper instanceof Ext_TC_Frontend_Combination_Helper_Caching) {
				// Wenn der Caching-Helper gesetzt ist den Rückgabewert speichern - keine Abfrage auf
				// $oForm->useCache() da diese Methode nur angibt ob aus dem Cache geladen werden soll
				$oForm->oCachingHelper->writeToCache($sCacheKey, $sReturn);
			} else {
				// Ansonsten WDCache benutzen
				$iCacheExpiration = (60 * 60 * 36);
				WDCache::set($sCacheKey, $iCacheExpiration, $sReturn, false, $sCacheGroup);
			}
		}

		return $sReturn;

	}

	/**
	 * Gibt die Daten-Attribute als Array zurück.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return array
	 */
	public function getInputDataAttributesArray($mSchool, $sLanguage = null) {

		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$aAttributes = array();

		// Wenn es kein Eingabe-Block ist direkt abbrechen
		if(!$this->isInputBlock()) {
			return $aAttributes;
		}

		// Select-Optionen
		if(
			$this->block_id == self::TYPE_SELECT ||
			$this->block_id == self::TYPE_MULTISELECT
		) {
			$sEmptyText = '';
			if($this->isShowingLabelAsPlaceholder()) {
				$sEmptyText = $this->getTitle($sLanguage);
			}

			$aOptions = $this->getSelectOptions($mSchool, $sLanguage, $this->block_id == self::TYPE_SELECT, $sEmptyText);
			$aOptions = $this->convertSelectOptions($aOptions);
			if(count($aOptions) > 0) {
				$aAttributes[] = array(
					'type' => 'StaticSelectOptions',
					'data' => array(
						'select_options' => $aOptions
					)
				);
			}
		}

		$this->setDefaultInputDataAttributesValidators($aAttributes, $mSchool, $sLanguage);

		return $aAttributes;

	}

	/**
	 * Setzt die Default-Validatoren (für das JavaScript)
	 *
	 * @param array $aAttributes
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 */
	protected function setDefaultInputDataAttributesValidators(&$aAttributes, $mSchool, $sLanguage = null) {

		$sLanguage = $this->getDynamicLanguage($sLanguage);

		// Text-, Textarea-, Datum- und Upload-Pflichtfelder dürfen nicht leer sein
		if(
			(
				$this->required == 1
			) && (
				$this->block_id == self::TYPE_INPUT ||
				$this->block_id == self::TYPE_TEXTAREA ||
				$this->block_id == self::TYPE_DATE ||
				$this->block_id == self::TYPE_UPLOAD
			)
		) {
			$aAttributes[] = array(
				'type' => 'ValidateInput',
				'data' => array(
					'message' => $this->getTranslation('error', $sLanguage),
					'algorithm' => 'NotEmpty'
				)
			);
		}

		// E-Mail-Adressen auf korrektes Format prüfen
		if(
			$this->block_id == self::TYPE_INPUT &&
			$this->set_type == self::SUBTYPE_INPUT_CONTACT_EMAIL
		) {
			$aAttributes[] = array(
				'type' => 'ValidateInput',
				'data' => array(
					'message' => $this->getTranslation('error', $sLanguage),
					'algorithm' => 'EmailOrEmpty'
				)
			);
		}

		// Select-Pflichtfelder dürfen keine leere Option oder "0" ausgewählt haben
		if(
			$this->required == 1 &&
			$this->block_id == self::TYPE_SELECT
		) {
			$aAttributes[] = array(
				'type' => 'ValidateInput',
				'data' => array(
					'message' => $this->getTranslation('error', $sLanguage),
					'algorithm' => 'SelectOptionsBlacklist',
					'blacklist' => [null, '', '0']
				)
			);
		}

		// Checkbox-Pflichtfelder müssen ausgewählt/angehakt sein
		if(
			$this->required == 1 &&
			$this->block_id == self::TYPE_CHECKBOX
		) {
			$aAttributes[] = array(
				'type' => 'ValidateInput',
				'data' => array(
					'message' => $this->getTranslation('error', $sLanguage),
					'algorithm' => 'CheckboxChecked'
				)
			);
		}

		if(
			$this->required == 1 &&
			$this->block_id == self::TYPE_YESNO
		) {
			$aAttributes[] = array(
				'type' => 'ValidateInput',
				'data' => array(
					'message' => $this->getTranslation('error', $sLanguage),
					'algorithm' => 'RadioChecked'
				)
			);
		}

		// Bei Uploads die Dateierweiterung prüfen
		if($this->block_id == self::TYPE_UPLOAD) {
			$aAttributes[] = [
				'type' => 'ValidateInput',
				'data' => [
					'message' => $this->getPage()->getForm()->getFileExtensionError(),
					'algorithm' => 'FileExtensionOrEmpty',
					'file_extensions' => self::VALIDATION_TYPE_UPLOAD_ALLOWED_EXTENSIONS
				]
			];
		}

	}

	/**
	 * Gibt den Identifier des Eingabe-Elements zurück.
	 *
	 * @return string
	 */
	public function getInputDataIdentifier() {

		if($this->id > 0) {
			return 'block_'.$this->id;
		}

		return '';

	}

	/**
	 * Konvertiert die Liste mit Select-Optionen in ein JS-geeignetes Format.
	 *
	 * Eingabeformat wie in der Software üblich:
	 *
	 * array(
	 *     <value1> => <text1>,
	 *     <value2> => <text2>,
	 *     ...
	 * )
	 *
	 * Ausgabeformat für JS ohne spezielle Array-Keys (damit es ein JS-Array und kein JS-Objekt wird):
	 *
	 * array(
	 *     array( <value1>, <text1> ),
	 *     array( <value2>, <text2> ),
	 *     ...
	 * )
	 *
	 * @param mixed[] $aOptions
	 * @return mixed[]
	 */
	public function convertSelectOptions(array $aOptions) {

		$aOptions = array_map(
			function($sValue, $sText) {
				return array($sValue, $sText);
			},
			array_keys($aOptions),
			$aOptions
		);
		$aOptions = array_values($aOptions);

		return $aOptions;

	}

	/**
	 * Gibt eine Liste mit allen Kind-Blöcken dieses Blocks zurück.
	 *
	 * Die Liste wird durch die angegebene Callback-Funktion gefiltert, wenn die Funktion true zurück gibt wird
	 * der Block in die zurückgegebene Liste aufgenommen, ansonsten nicht.
	 *
	 * Es werden rekursiv auch alle Kind-Blöcke von Blöcken durchlaufen.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @param Closure $oCallbackFilter
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getFilteredChildBlocks(Closure $oCallbackFilter) {

		$aBlocks = array();

		$oCallbackWalkBlocks = function($aCurrentBlocks) use (&$oCallbackWalkBlocks, &$aBlocks, &$oCallbackFilter) {
			foreach($aCurrentBlocks as $oBlock) {
				if($oCallbackFilter($oBlock)) {
					$aBlocks[] = $oBlock;
				}
				$aChildBlocks = $oBlock->getChildBlocks();
				$oCallbackWalkBlocks($aChildBlocks);
			}
		};

		$aChildBlocks = $this->getChildBlocks();
		$oCallbackWalkBlocks($aChildBlocks);

		return $aBlocks;

	}

	/**
	 * Gibt true zurück wenn der Block für die angegebene Schul-/Sprach-Kombination verfügbar ist, ansonsten false.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return boolean
	 */
	public function isAvailable($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);

		// Sonderfall: Download-Block
		if($this->block_id == self::TYPE_DOWNLOAD) {
			$aSettings = $this->getSettings();
			if(!isset($aSettings['file_'.$sLanguage])) {
				return false;
			}
			$iFileId = $aSettings['file_'.$sLanguage];
			$oFile = Ext_Thebing_Upload_File::getInstance($iFileId);
			if($oFile->id < 1) {
				return false;
			}
			$aFileSchoolIds = $oFile->objects;
			if(!in_array($oSchool->id, $aFileSchoolIds)) {
				return false;
			}
			return true;
		}

		// Standardmäßig ist ein Block immer verfügbar
		return true;

	}

	/**
	 * Validiert die Formulareingaben für diesen Block.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Format des Rückgabe-Arrays:
	 *
	 * array(
	 *     'block_errors' => array(
	 *         <string (block-identifier)> => array(
	 *             'value' => <string (value)>
	 *             'message' => <string (nachricht)>
	 *         )
	 *     ),
	 *     'form_errors' => array(
	 *         <string (nachricht)>
	 *     )
	 * )
	 *
	 * @param MVC_Request $oRequest
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function validateFormInput(MVC_Request $oRequest, $mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);

		$sInputName = $this->getInputBlockName();
		if(strlen($sInputName) < 1) {
			return array();
		}

		$sValue = (string)$this->getFormInputValue($oRequest);

		// Select prüfen: Ob vorhandenen Wert übermittelt und ob Pflichtfeld
		if($this->block_id == self::TYPE_SELECT) {
			$aValidValues = $this->getSelectOptions($mSchool, $sLanguage);
			if(array_key_exists($sValue, $aValidValues)) {
				return array();
			}
			if(
				$this->required != 1 &&
				$sValue === '0'
			) {
				return array();
			}
			return array(
				'block_errors' => array(
					$sInputName => array(
						'value' => $sValue,
						'message' => $this->getErrorMessage($sLanguage),
						'algorithm' => 'SelectOptionsBlacklist'
					)
				)
			);
		}

		// E-Mail überprüfen
		if(
			$this->block_id == self::TYPE_INPUT &&
			(
				$this->set_type == self::SUBTYPE_INPUT_CONTACT_EMAIL ||
				$this->set_type == self::SUBTYPE_INPUT_EMERGENCY_EMAIL
			)
		) {
			if(
				strlen($sValue) > 0 &&
				!Util::checkEmailMx($sValue)
			) {
				return array(
					'block_errors' => array(
						$sInputName => array(
							'value' => $sValue,
							'message' => $this->getErrorMessage($sLanguage),
							'algorithm' => 'InputBlacklist'
						)
					)
				);
			}
		}

		// Datum überprüfen
		if(
			$this->block_id == self::TYPE_DATE &&
			strlen($sValue) > 0
		) {
			$oFormat = new Ext_Thebing_Gui2_Format_Date('frontend_date_format', $oSchool->id);
			$mDate = $oFormat->convert($sValue);
			if(!\Core\Helper\DateTime::isDate($mDate, 'Y-m-d')) {
				return array(
					'block_errors' => array(
						$sInputName => array(
							'value' => $sValue,
							'message' => $this->getErrorMessage($sLanguage),
							'algorithm' => 'InputBlacklist'
						)
					)
				);
			}
			// Geburtsdatum muss in der Vergangenheit liegen (wie weit genau ist eigentlich egal, hauptsache nicht
			// in der Zukunft sonst gibt es beim Speichern einen Fehler)
			if($this->set_type == self::SUBTYPE_DATE_BIRTHDATE) {
				$dDate = \Core\Helper\DateTime::createFromFormat('Y-m-d', $mDate);
				$dMinimumRequiredDate = new \Core\Helper\DateTime('yesterday');
				if($dDate >= $dMinimumRequiredDate) {
					return array(
						'block_errors' => array(
							$sInputName => array(
								'value' => $sValue,
								'message' => $this->getErrorMessage($sLanguage),
								'algorithm' => 'InputBlacklist'
							)
						)
					);
				}
			}
		}

		// Pflichtfelder
		if(
			(
				$this->block_id == self::TYPE_INPUT ||
				$this->block_id == self::TYPE_TEXTAREA ||
				$this->block_id == self::TYPE_DATE
			) &&
			$this->required == 1 &&
			strlen($sValue) < 1
		) {
			return array(
				'block_errors' => array(
					$sInputName => array(
						'value' => $sValue,
						'message' => $this->getErrorMessage($sLanguage),
						'algorithm' => 'InputBlacklist'
					)
				)
			);
		}

		// Upload validieren
		if($this->block_id == self::TYPE_UPLOAD) {
			$mError = $this->validateFormInputUpload($oRequest, $sLanguage);
			if($mError !== true) {
				return [
					'block_errors' => [
						$sInputName => [
							// Value muss gesetzt werden. sonst wird Feld nicht markiert
							'value' => $mError[0],
							'message' => $mError[1],
							'algorithm' => null
						]
					]
				];
			}
		}

		return [];

	}

	/**
	 * Upload validieren
	 *
	 * @param MVC_Request $oRequest
	 * @param string $sLanguage
	 * @return bool|array
	 */
	protected function validateFormInputUpload(MVC_Request $oRequest, $sLanguage) {

		$sInputName = $this->getInputBlockName();
		$aFiles = $oRequest->getFilesData();

		$oLog = function($sError) use($sInputName, $aFiles) {
			$this->getPage()->getForm()->oCombination->log('Upload failed with error "'.$sError.'"!', [$sInputName, $aFiles]);
		};

		// Keine Datei hochgeladen
		if(
			!isset($aFiles[$sInputName]) ||
			!is_file($aFiles[$sInputName]['tmp_name'])
		) {
			if($this->required == 1) {
				return ['', $this->getErrorMessage($sLanguage)];
			}

			return true;
		}

		// Upload-Fehler
		if($aFiles[$sInputName]['error'] !== UPLOAD_ERR_OK) {
			if(
				$aFiles[$sInputName]['error'] === UPLOAD_ERR_INI_SIZE ||
				$aFiles[$sInputName]['error'] === UPLOAD_ERR_FORM_SIZE
			) {
				$oLog('FILESIZE');
				return [$aFiles[$sInputName]['name'], $this->getPage()->getForm()->getTranslation('extensionsize')];
			} else {
				$oLog('INTERNAL');
				return [$aFiles[$sInputName]['name'], $this->getPage()->getForm()->getTranslation('errorinternal')];
			}
		}

		// Dateierweiterung prüfen (.php und .htaccess sollten bspw. nicht hochgeladen werden)
		$sExtension = pathinfo($aFiles[$sInputName]['name'], PATHINFO_EXTENSION);
		if(!in_array($sExtension, self::VALIDATION_TYPE_UPLOAD_ALLOWED_EXTENSIONS)) {
			$oLog('EXTENSION');
			return [$aFiles[$sInputName]['name'], $this->getPage()->getForm()->getFileExtensionError()];
		}

		// MIME-Typ prüfen (eine Datei mit Dateierweiterung kann alles enthalten)
		$oFileInfo = new \finfo();
		$sMimeType = $oFileInfo->file($aFiles[$sInputName]['tmp_name'], FILEINFO_MIME);
		$sMimeType  = explode(';', $sMimeType)[0]; // charset entfernen (steht manchmal mit drin)
		if(!in_array($sMimeType, self::VALIDATION_TYPE_UPLOAD_ALLOWED_MIME_TYPES)) {
			$oLog('MIME_TYPE');
			return [$aFiles[$sInputName]['name'], $this->getPage()->getForm()->getFileExtensionError()];
		}

		// Bei Bildern zusätzlich prüfen, ob es sich wirklich um ein Bild handelt
		if(
			strpos($sMimeType, 'image/') !== false &&
			!is_array(@getimagesize($aFiles[$sInputName]['tmp_name']))
		) {
			$oLog('IMAGE_TYPE_BUT_NO_IMAGE');
			return [$aFiles[$sInputName]['name'], $this->getPage()->getForm()->getFileExtensionError()];
		}

		return true;

	}

	/**
	 * Prüfen, ob ein Eingabewert für den Block übermittelt wurde
	 *
	 * @param MVC_Request $oRequest
	 * @return bool
	 */
	public function hasFormInputValue(MVC_Request $oRequest) {

		$sInputName = $this->getInputBlockName();

		if(
			!$this->isInputBlock() ||
			strlen($sInputName) < 1
		) {
			return false;
		}

		if(!$oRequest->exists($sInputName)) {
			return false;
		}

		return true;

	}

	/**
	 * Gibt den aktuellen Eingabe-Wert des Blocks zurück.
	 *
	 * Zur Verwendung während Submit/Ajax-Requests.
	 * Anmerkung: Methode ist abgeleitet und kümmert sich dort um [] im Key.
	 *
	 * @param MVC_Request $oRequest
	 * @return mixed
	 */
	public function getFormInputValue(MVC_Request $oRequest) {

		$sInputName = $this->getInputBlockName();

		if(!$this->hasFormInputValue($oRequest)) {
			return null;
		}

		// HTML-Tags entfernen (z.B. XSS)
		return filter_var($oRequest->input($sInputName), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	}

	/**
	 * Gibt true zurück wenn die Eingaben für diesen Block validiert werden können/müssen, ansonsten false.
	 *
	 * @return boolean
	 */
	public function canValidate() {

		if(
			$this->block_id == self::TYPE_CHECKBOX ||
			$this->block_id == self::TYPE_SELECT ||
			$this->block_id == self::TYPE_INPUT ||
			$this->block_id == self::TYPE_TEXTAREA ||
			$this->block_id == self::TYPE_DATE ||
			$this->block_id == self::TYPE_UPLOAD ||
			$this->block_id == self::TYPE_COURSES ||
			$this->block_id == self::TYPE_ACCOMMODATIONS ||
			$this->block_id == self::TYPE_TRANSFERS ||
			$this->block_id == self::TYPE_INSURANCES
		) {

			return true;

		}

		return false;

	}

	/**
	 * Gibt true zurück wenn der Block ein Flex-Feld ist, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isFlexFieldBlock() {
		return strpos($this->set_type, 'flex_') === 0;
	}

	/**
	 * Gibt true zurück wenn der Block ein Flex-Upload-Feld ist, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isFlexFieldUploadBlock() {

		if(
			$this->block_id == self::TYPE_UPLOAD &&
			strpos($this->set_type, 'flex_upload_') === 0
		) {
			return true;
		}

		return false;

	}

	/**
	 * Gibt die ID des Flex-Felds zurück, wenn der Block kein Flex-Feld ist wird 0 zurück gegeben.
	 *
	 * @see Ext_Thebing_Form_Page_Block::isFlexFieldBlock()
	 * @return integer
	 */
	public function getFlexFieldId() {

		if(!$this->isFlexFieldBlock()) {
			return 0;
		}

		return (int)substr($this->set_type, 5);

	}

	/**
	 * Gibt den Text für den Hinzufügen-Button eines duplizierbaren Bereichs zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block nicht zu einem duplizierbaren Bereich gehört wird ein leerer String zurück gegeben.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getDuplicateAddButtonText($mSchool, $sLanguage = null) {

		// aktuell hier immer ein leerer String, muss wenn dann in Kind-Klassen aktiviert werden
		return '';

	}

	/**
	 * Gibt den Text für den Entfernen-Button eines duplizierbaren Bereichs zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * Wenn der Block nicht zu einem duplizierbaren Bereich gehört wird ein leerer String zurück gegeben.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getDuplicateRemoveButtonText($mSchool, $sLanguage = null) {

		// aktuell hier immer ein leerer String, muss wenn dann in Kind-Klassen aktiviert werden
		return '';

	}

	/**
	 * {@inheritdoc}
	 */
	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {

		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		$oClone->_aTranslations = $this->_aTranslations;
		$oClone->_aSettings = $this->_aSettings;

		$oClone->saveTranslations($oClone->_aTranslations);
		$oClone->saveBlockSettings($oClone->_aSettings);

		return $oClone;

	}

	/**
	 * Übersetzungen speichern.
	 *
	 * @param mixed[] $aTranslations
	 */
	protected function saveTranslations(&$aTranslations) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		$sSQL = "
			DELETE FROM
				`kolumbus_forms_translations`
			WHERE
				`item` = 'block' AND
				`item_id` = :iBlockID
		";
		$aSQL = array(
			'iBlockID' => $this->id
		);
		DB::executePreparedQuery($sSQL, $aSQL);

		foreach((array)$aTranslations as $sField => $aLanguages) {
			foreach((array)$aLanguages as $sLanguage => $sContent) {

				if ($this->block_id == self::TYPE_STATIC_TEXT) {
					$oPurifier = new \Core\Service\HtmlPurifier(\Core\Service\HtmlPurifier::SET_FRONTEND);
					$sContent = $oPurifier->purify($sContent);
					// Da damals das Konzept von JoinedObjects scheinbar unbekannt war, muss das irgendwie zurück geschrieben werden für den Dialog
					$aTranslations[$sField][$sLanguage] = $sContent;
				}

				$oTranslation = new Ext_Thebing_Form_Translation();
				$oTranslation->item = 'block';
				$oTranslation->item_id = $this->id;
				$oTranslation->language = $sLanguage;
				$oTranslation->field = $sField;
				$oTranslation->content = $sContent;
				$oTranslation->save();

			}
		}

	}

	/**
	 * Block-Einstellungen speichern.
	 *
	 * @param mixed[] $aSettings
	 */
	protected function saveBlockSettings($aSettings) {

		foreach((array)$aSettings as $sKey => $mValue) {
			$sSQL = "
				REPLACE INTO
					`kolumbus_forms_pages_blocks_settings`
				SET
					`block_id` = :iBlockID,
					`setting` = :sSetting,
					`value` = :sValue
			";
			$aSQL = array(
				'iBlockID' => $this->id,
				'sSetting' => $sKey,
				'sValue' => $mValue
			);
			DB::executePreparedQuery($sSQL, $aSQL);
		}

	}

	/**
	 * Alle Keys löschen, die nicht mehr übermittelt wurden
	 */
	public function clearSettings(array $keys) {

		$keys = array_map(fn(string $key) => str_starts_with($key, 'set_') ? substr($key, 4) : $key, $keys);

		foreach ($this->getSettings() as $key => $value) {
			if (!in_array($key, $keys)) {
				unset($this->_aSettings[$key]);
				DB::executePreparedQuery("
					DELETE FROM 
					 kolumbus_forms_pages_blocks_settings 
					WHERE
					    block_id = :block_id AND
					    setting = :setting
				", [
					'block_id' => $this->id,
					'setting' => $key
				]);
			}
		}

	}

	/**
	 * Neuer Ansatz für Form-Translations, mit Defaults
	 *
	 * @param \Tc\Service\Language\Backend $oBackendLanguage
	 * @param \Tc\Service\Language\Frontend $oFrontedLanguage
	 * @return array
	 */
	public function getTranslationConfig(\Tc\Service\Language\Backend $oBackendLanguage = null, \Tc\Service\Language\Frontend $oFrontedLanguage = null) {

		$oForm = $this->getPage()->getForm();

		$t = function($sTranslation) use ($oBackendLanguage) {
			if($oBackendLanguage === null) {
				return '';
			}
			return $oBackendLanguage->translate($sTranslation);
		};

		$tf = function($sTranslation) use ($oFrontedLanguage) {
			if($oFrontedLanguage === null) {
				return '';
			}

			return $oFrontedLanguage->translate($sTranslation);
		};

		switch($this->block_id) {

			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:

				$aTranslations = array(
					'title' => [$t('%s: Bezeichnung'), $tf('Course')],
					'titlePerCourse' => [$t('%s: Bezeichnung pro Kurs'), $tf('Course {number}'), true],
					'error' => [$t('%s: Fehlermeldung'), $tf('Please select a valid course.')],
					'add' => [$t('%s: Hinzufügen'), $tf('Add another course')],
					'remove' => [$t('%s: Entfernen'), $tf('Remove')],
					'start' => [$t('%s: Startdatum'), $tf('Start date')],
					'end' => [$t('%s: Enddatum'), ''],
					'duration' => [$t('%s: Dauer'), ucfirst($tf('Duration'))],
					'units_per_week' => [$t('%s: Einheiten pro Woche'), $tf('Number of units (per week)'), true],
					'units_total' => [$t('%s: Einheiten absolut'), $tf('Number of units (total)'), true],
					'week' => [$t('%s: Woche'), ''],
					'level' => [$t('%s: Niveau'), $tf('Level')],
					'serviceChanged' => [$t('%s: Leistung automatisch verändert (Ferien)'), $tf('Services has been adjusted due to holidays.')],
					'errorTooManyUnits' => [$t('%s: Fehlermeldung: Zu viele Einheiten'), $tf('Too many lessons were entered.'), true],
					'additional_services' => [$t('%s: Zusatzleistungen'), $tf('Additional services'), true],
					'category' => [$t('%s: Kategorie'), $tf('Category'), true],
					'language' => [$t('%s: Sprache'), $tf('Language'), true],
					'program' => [$t('%s: Programm'), $tf('Program'), true],
					'service_removed_age' => [$t('%s: Leistung automatisch entfernt aufgrund von Alter'), $tf('The course has been removed due to age requirement.'), true],
					'no_results' => [$t('%s: Keine Ergebnisse gefunden'), $tf('No courses found.'), true],
				);

				if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
					unset($aTranslations['end']);
					unset($aTranslations['week']);
				} else {
					unset($aTranslations['titlePerCourse']);
				}

				return $aTranslations;

			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:

				$aTranslations = array(
					'title' => [$t('%s: Bezeichnung'), $tf('Accommodation')], // Wird nur im Backend für Abhängigkeiten benutzt
					'error' => [$t('%s: Fehlermeldung'), $tf('Please select an accommodation.')],
					'add' => [$t('%s: Hinzufügen'), $tf('Add another accommodation')],
					'remove' => [$t('%s: Entfernen'), $tf('Remove')],
					'start' => [$t('%s: Startdatum'), $tf('Check-in')],
					'end' => [$t('%s: Enddatum'), $tf('Check-out')],
					'duration' => [$t('%s: Dauer'), ''],
					'roomtype' =>[$t('%s: Raumart'), ''],
					'meal' => [$t('%s: Verpflegung'), ''],
					'extra' => [$t('%s: Extranächte'), $tf('Additional night|Additional nights')],
					'extraWeek' => [$t('%s: Extrawoche'), $tf('Extra week|Extra weeks')],
					'serviceChanged' => [$t('%s: Leistung automatisch verändert'), $tf('Accommodation dates have been adjusted.')],
					'serviceRemoved' => [$t('%s: Leistung automatisch entfernt'), $tf('Accommodation has been removed.')],
					'additional_services' => [$t('%s: Zusatzleistungen'), $tf('Additional services'), true],
				);

				if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
//					unset($aTranslations['title']);
					unset($aTranslations['add']);
					unset($aTranslations['duration']);
					unset($aTranslations['roomtype']);
					unset($aTranslations['meal']);
				} else {
					unset($aTranslations['serviceChanged']);
				}

				return $aTranslations;

			case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:

				$aTranslations = array(
					'title' => [$t('%s: Bezeichnung'), $tf('Transfer'), true],
					'no_transfer' => [$t('Nicht gewünscht'), $tf('Not requested'), true],
					'arrival' => [$t('Anreise'), $tf('Arrival'), true],
					'departure' => [$t('Abreise'), $tf('Departure'), true],
					'arrival_departure' => [$t('Anreise und Abreise'), $tf('Arrival and return transfer'), true],
					'error' => [$t('%s: Fehlermeldung'), $tf('Please select a transfer.')],
					'origin' => [$t('%s: Anreiseort'), $tf('Pick-up'), true],
					'destination' => [$t('%s: Ankunftsort'), $tf('Drop-off'), true],
					'airline' => [$t('%s: Fluglinie'), $tf('Airline'), true],
					'flight_number' => [$t('%s: Flugnummer'), $tf('Flight number'), true],
					'date' => [$t('%s: Datum'), $tf('Date'), true],
					'time' => [$t('%s: Anreiseuhrzeit'), $tf('Time'), true],
					'comment' => [$t('%s: Kommentar'), $tf('Note'), true],
					'type' => [$t('%s: Typ'), $tf('Type'), true],
					'description' => [$t('%s: Beschreibung'), '']
				);

				return $aTranslations;

			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:

				$aTranslations = array(
					'title' => [$t('%s: Bezeichnung'), $tf('Insurance')],
					'error' => [$t('%s: Fehlermeldung'), $tf('Please select an insurance.')],
					'add' => [$t('%s: Hinzufügen'), $tf('Add')],
					'remove' => [$t('%s: Entfernen'), $tf('Remove')],
					'start' => [$t('%s: Startdatum'), $tf('Start date')],
					'end' => [$t('%s: Enddatum'), $tf('End date')],
					'duration' => [$t('%s: Dauer'), $tf('Duration')],
					'serviceChanged' => [$t('%s: Leistung automatisch verändert'), $tf('Insurance "{name}" has been adjusted.')],
					'serviceRemoved' => [$t('%s: Leistung automatisch entfernt'), $tf('Insurance "{name}" has been removed.')],
				);

				return $aTranslations;

			case Ext_Thebing_Form_Page_Block::TYPE_PRICES:

				$aTranslations = [
					'priceTitle' => [$t('%s: Titel (Zusammenfassung)').' (V3)', $tf('Summary')],
					'priceCurrency' => [$t('%s: Währung'), $tf('Currency')],
					'priceCourse' => [$t('%s: Kurse'), $tf('Courses')],
					'priceAccommodation' => [$t('%s: Unterkünfte'), $tf('Accommodations')],
					'priceTransfer' => [$t('%s: Transfer'), $tf('Transfer')],
					'priceInsurance' => [$t('%s: Versicherung'), $tf('Insurance')],
					'priceCostsGeneral' => [$t('%s: Zusätzliche Gebühren'), $tf('Additional fees')],
					'pricePrice' => [$t('%s: Betrag'), $tf('Amount')],
					'priceTotal' => [$t('%s: Gesamt'), $tf('Total')],
					'priceWeek' => [$t('%s: Woche(n)'), $tf('week|weeks')],
					'priceUnit' => [$t('%s: Einheit(en)'), $tf('unit|units')],
					'deposit' => [$t('%s: Anzahlung'), $tf('Deposit'), true],
					'depositDescription' => [$t('%s: Anzahlung sofort zu leisten'), $tf('The deposit needs to be paid immediately.'), true],
					'pay_full_amount' => [$t('%s: Vollen Betrag zahlen'), $tf('Pay full amount instead'), true],
				];

				if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
					$aTranslations['priceExtra'] = [$t('%s: Extras'), $tf('Extras')];
					$aTranslations['priceSpecial'] = [$t('%s: Rabatt'), $tf('Discount')];
					unset($aTranslations['priceCurrency']);
					unset($aTranslations['priceTransfer']);
				}

				return $aTranslations;

			case Ext_Thebing_Form_Page_Block::TYPE_FEES:

				$aTranslations = array(
					'title' => [$t('%s: Bezeichnung'), $tf('Additional fees')],
					'error' => [$t('%s: Fehlermeldung'), $tf('Please select at least one item.')], // TODO Auf choose_one migrieren?
					'add' => [$t('%s: Hinzufügen'), $tf('Add')],
					'remove' => [$t('%s: Entfernen'), $tf('Remove')]
				);

				if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
					unset($aTranslations['add']);
					unset($aTranslations['remove']);
				}

				return $aTranslations;

			case Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY:

				$aTranslations = array(
//					'title' => [$t('%s: Bezeichnung'), $tf('Activity')],
					'error' => [$t('%s: Fehlermeldung'), $tf('Please select a valid activity.')],
					'start' => [$t('%s: Startdatum'), $tf('Start date')],
//					'end' => [$t('%s: Enddatum'), $tf('End date')],
					'duration' => [$t('%s: Dauer'), $tf('Duration')],
					'units' => [$t('%s: Einheiten'), $tf('Number of units'), true],
					'service_changed' => [$t('%s: Leistung automatisch verändert'), $tf('Activity "{name}" has been adjusted.'), true],
					'service_removed' => [$t('%s: Leistung automatisch entfernt'), $tf('Activity "{name}" has been removed.'), true],
					'error_too_many_units' => [$t('%s: Fehlermeldung: Zu viele Einheiten'), $tf('Too many units were chosen.'), true],
				);

				return $aTranslations;

			default:
				return [];

		}

	}

	/**
	 * @param Ext_Thebing_Form_Page $oPage
	 */
	public function moveToPage(Ext_Thebing_Form_Page $oPage) {

		$aChilds = $this->getChildBlocksRecursively();
		array_unshift($aChilds, $this);

		foreach ($aChilds as $oChild) {

			$oParent = $oChild->getParentBlock();
			if (!in_array($oParent, $aChilds)) {
				$oChild->parent_id = 0;
				$oChild->parent_area = 0;
			}

			$oChild->page_id = $oPage->id;
			$oChild->save();
//			$oChild->setJoinedObject('page', $oPage);
		}

		// TODO Funktioniert natürlich nicht, auch nicht mit bidrectional
//		$oPage->save();

	}

	public function getServiceBlockType(): string {

		switch ($this->block_id) {
			case self::TYPE_COURSES:
				return 'courses';
			case self::TYPE_ACCOMMODATIONS:
				return 'accommodations';
			case self::TYPE_TRANSFERS:
				return 'transfers';
			case self::TYPE_INSURANCES:
				return 'insurances';
			case self::TYPE_FEES:
				return 'fees';
			case self::TYPE_ACTIVITY:
				return 'activities';
			default:
				throw new \RuntimeException('Invalid service block: '.$this->block_id);
		}

	}

	public function getServiceBlockKey(): string {
		return $this->getServiceBlockType().'_'.$this->id;
	}

	public function getServiceBlockFields(): array {

		switch ($this->block_id) {
			case self::TYPE_COURSES:
				$journeyCourse = new \Ext_TS_Inquiry_Journey_Course();
				return array_keys($journeyCourse->getRegistrationFormData());
			case self::TYPE_ACCOMMODATIONS:
				$journeyAccommodation = new \Ext_TS_Inquiry_Journey_Accommodation();
				return array_keys($journeyAccommodation->getRegistrationFormData());
			case self::TYPE_TRANSFERS:
				$journeyTransfer = new \Ext_TS_Inquiry_Journey_Transfer();
				return array_keys($journeyTransfer->getRegistrationFormData());
			case self::TYPE_INSURANCES:
				$journeyInsurance = new \Ext_TS_Inquiry_Journey_Insurance();
				return array_keys($journeyInsurance->getRegistrationFormData());
			case self::TYPE_FEES:
				return ['fee'];
			case self::TYPE_ACTIVITY:
				$journeyActivity = new \Ext_TS_Inquiry_Journey_Activity();
				return array_keys($journeyActivity->getRegistrationFormData());
			default:
				throw new \RuntimeException('Invalid service block: '.$this->block_id);
		}

	}

	/**
	 * @return Ext_Thebing_Tuition_Course[]|\TsAccommodation\Dto\AccommodationCombination[]|Ext_Thebing_School_Additionalcost[]
	 */
	public function getServiceBlockServices(): array {

		$aServices = [];

		switch ($this->block_id) {
			case self::TYPE_COURSES:
				foreach ($this->getSettings() as $sSetting => $sValue) {
					if ($sValue && preg_match('/course_(\d+)/', $sSetting, $aMatches)) {
						$aServices[] = Ext_Thebing_Tuition_Course::getInstance($aMatches[1]);
					}
				}
				break;
			case self::TYPE_ACCOMMODATIONS:
				foreach ($this->getSettings() as $sSetting => $sValue) {
					if ($sValue && preg_match('/accommodation_(\d+)_(\d+)_(\d+)/', $sSetting, $aMatches)) {
						$oCategory = Ext_Thebing_Accommodation_Category::getInstance($aMatches[1]);
						$oRoomType = Ext_Thebing_Accommodation_Roomtype::getInstance($aMatches[2]);
						$oBoard = Ext_Thebing_Accommodation_Meal::getInstance($aMatches[3]);
						$aServices[] = new \TsAccommodation\Dto\AccommodationCombination($oCategory, $oRoomType, $oBoard);
					}
				}
				usort($aServices, function (\TsAccommodation\Dto\AccommodationCombination $oDto1, \TsAccommodation\Dto\AccommodationCombination $oDto2) {
					return $oDto1->category->position > $oDto2->category->position;
				});
				break;
			case self::TYPE_TRANSFERS:
				$cLabel = function (string $sKey) {
					[$sType, $iId] = explode('_', $sKey, 2);
					switch ($sType) {
						case 'school':
							return L10N::t('Schule', 'Thebing » Transfer');
						case 'accommodation':
							return L10N::t('Unterkunft', 'Thebing » Transfer');
						case 'location':
						default:
							return Ext_TS_Transfer_Location::getInstance($iId)->short;
					}
				};
				foreach ($this->getSettings() as $sSetting => $sValue) {
					if ($sValue && preg_match('/transfer_\w+_(\w+_\d+)_to_(\w+_\d+)/', $sSetting, $aMatches)) {
						$aServices[] = sprintf('%s ↔ %s', $cLabel($aMatches[1]), $cLabel($aMatches[2]));
					}
				}
				break;
			case self::TYPE_FEES;
				foreach ($this->getSettings() as $sSetting => $sValue) {
					if ($sValue && preg_match('/cost_\d+_(\d+)/', $sSetting, $aMatches)) {
						$aServices[] = Ext_Thebing_School_Additionalcost::getInstance($aMatches[1]);
					}
				}
				break;
			default:
				throw new \RuntimeException('Invalid service block: '.$this->block_id);
		}

		return $aServices;

	}

}
