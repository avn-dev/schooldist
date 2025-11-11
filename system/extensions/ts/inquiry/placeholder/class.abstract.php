<?php


abstract class Ext_TS_Inquiry_Placeholder_Abstract extends Ext_Thebing_Placeholder
{
	protected $_oAgencyStaff = null;

	/**
	 * Diese Platzhalter hier werden NUR für die ANFRAGEN verwendet!
	 * Die Inquiry-Placeholder-Klasse überschreibt die Platzhalter alle (in einer anderen Methode).
	 */
	public function buildPlaceholderTableData()
	{
		parent::buildPlaceholderTableData();
		
		$this->_aPlaceholderTableData['customer'] = array(
			'header'	=> $this->_t('Kunden Daten'),
			'data'		=> array(
				'firstname'						=> array(
					'tag'	=> 'firstname',
					'label'	=> $this->_t('Vorname'),
				),
				'surname'						=> array(
					'tag'	=> 'surname',
					'label'	=> $this->_t('Nachname'),
				),
				'age'							=> array(
					'tag'	=> 'age',
					'label'	=> $this->_t('Alter'),
				),
				'salutation'					=> array(
					'tag'	=> 'salutation',
					'label'	=> $this->_t('Anrede'),
				),
				'birthdate'						=> array(
					'tag'	=> 'birthdate',
					'label'	=> $this->_t('Geburtsdatum'),
				),
				'gender'						=> array(
					'tag'	=> 'gender',
					'label'	=> $this->_t('Geschlecht'),
				),
				'nationality'					=> array(
					'tag'	=> 'nationality',
					'label'	=> $this->_t('Nationalität'),
				),
				'mothertongue'					=> array(
					'tag'	=> 'mothertongue',
					'label'	=> $this->_t('Muttersprache'),
				),
				'address'						=> array(
					'tag'	=> 'address',
					'label'	=> $this->_t('Addresse'),
				),
				'address_addon'					=> array(
					'tag'	=> 'address_addon',
					'label'	=> $this->_t('Adresszusatz'),
				),	
				'zip'							=> array(
					'tag'	=> 'zip',
					'label'	=> $this->_t('PLZ'),
				),	
				'city'							=> array(
					'tag'	=> 'city',
					'label'	=> $this->_t('Stadt'),
				),	
				'state'							=> array(
					'tag'	=> 'state',
					'label'	=> $this->_t('Bundesland'),
				),	
				'country'						=> array(
					'tag'	=> 'country',
					'label'	=> $this->_t('Land'),
				),			
				'phone_home'					=> array(
					'tag'	=> 'phone_home',
					'label'	=> $this->_t('Telefon'),
				),		
				'phone_mobile'					=> array(
					'tag'	=> 'phone_mobile',
					'label'	=> $this->_t('Handy'),
				),
				'phone_office'					=> array(
					'tag'	=> 'phone_office',
					'label'	=> $this->_t('Telefon Büro'),
				),
				'fax'					=> array(
					'tag'	=> 'fax',
					'label'	=> $this->_t('Fax'),
				),
				'email'					=> array(
					'tag'	=> 'email',
					'label'	=> $this->_t('E-Mail'),
				),
				'sales_person' => [
					'tag' => 'sales_person',
					'label' => $this->_t('Vertriebsmitarbeiter'),
				],
				'social_security_number' => array(
					'tag'	=> 'social_security_number',
					'label'	=> $this->_t('Solzialversicherungsnummer'),
				),
				'arrival_comment' => array(
					'tag'	=> 'arrival_comment',
					'label'	=> $this->_t('Anreise'),
				),
				'departure_comment' => array(
					'tag'	=> 'departure_comment',
					'label'	=> $this->_t('Abreise'),
				),
				'other' => array(
					'tag'	=> 'other',
					'label'	=> $this->_t('Kunde: Sonstiges'),
				),
				'profession' => array(
					'tag'	=> 'profession',
					'label'	=> $this->_t('Beruf'),
				),
				'company' => array(
					'tag'	=> 'company',
					'label'	=> $this->_t('Firma'),
				),
				'billing_address' => array(
					'tag'	=> 'billing_address',
					'label'	=> $this->_t('Rechnungsdaten: Addresse'),
				),
				'billing_zip' => array(
					'tag'	=> 'billing_zip',
					'label'	=> $this->_t('Rechnungsdaten: Zip'),
				),
				'billing_city' => array(
					'tag'	=> 'billing_city',
					'label'	=> $this->_t('Rechnungsdaten: Stadt'),
				),
				'billing_country' => array(
					'tag'	=> 'billing_country',
					'label'	=> $this->_t('Rechnungsdaten: Land'),
				),
				'customer_state' => array(
					'tag'	=> 'customer_state',
					'label'	=> $this->_t('Status d. Schülers'),
				),
				/*
				'emergency_contact_person' => array(
					'tag'	=> 'emergency_contact_person',
					'label'	=> $this->_t('Notfallkontakt Person'),
				)*/
			)
		);
		
		$this->_aPlaceholderTableData['agency'] = array(
			'header'	=> $this->_t('Agentur Daten'),
			'data'		=> array(
				'name'						=> array(
					'tag'	=> 'agency',
					'label'	=> $this->_t('Agentur: Name'),
				),
				'number'				=> array(
					'tag'	=> 'agency_number',
					'label'	=> $this->_t('Agentur: Nummer'),
				),
				'abbreviation'			=> array(
					'tag'	=> 'agency_abbreviation',
					'label'	=> $this->_t('Agentur: Abkürzung'),
				),
				'address'				=> array(
					'tag'	=> 'agency_address',
					'label'	=> $this->_t('Agentur: Addresse'),
				),
				'zip'					=> array(
					'tag'	=> 'agency_zip',
					'label'	=> $this->_t('Agentur: PLZ'),
				),
				'city'					=> array(
					'tag'	=> 'agency_city',
					'label'	=> $this->_t('Agentur: Stadt'),
				),
				'country'				=> array(
					'tag'	=> 'agency_country',
					'label'	=> $this->_t('Agentur: Land'),
				),
				'groups'						=> array(
					'tag'	=> 'agency_groups',
					'label'	=> $this->_t('Agentur: Gruppe'),
				),
				'category'						=> array(
					'tag'	=> 'agency_category',
					'label'	=> $this->_t('Agentur: Kategorie'),
				),
				'tax_number'				=> array(
					'tag'	=> 'agency_tax_number',
					'label'	=> $this->_t('Agentur: Steuernummer'),
				),
				'state'					=> array(
					'tag'	=> 'agency_state',
					'label'	=> $this->_t('Agentur: Staat'),
				),
				'note'					=> array(
					'tag'	=> 'agency_note',
					'label'	=> $this->_t('Agentur: Kommentar'),
				),
				'staffmember_salutation'	=> array(
					'tag'	=> 'agency_staffmember_salutation',
					'label'	=> $this->_t('Agenturansprechpartner Anrede'),
				),	
				'staffmember_firstname'	=> array(
					'tag'	=> 'agency_staffmember_firstname',
					'label'	=> $this->_t('Agenturansprechpartner Vorname'),
				),	
				'staffmember_surname'	=> array(
					'tag'	=> 'agency_staffmember_surname',
					'label'	=> $this->_t('Agenturansprechpartner Name'),
				),	
				'staffmember_email'		=> array(
					'tag'	=> 'agency_staffmember_email',
					'label'	=> $this->_t('Agenturansprechpartner E-Mail'),
				),	
				'staffmember_phone'		=> array(
					'tag'	=> 'agency_staffmember_phone',
					'label'	=> $this->_t('Agenturansprechpartner Telefon'),
				),			
				'staffmember_fax'		=> array(
					'tag'	=> 'agency_staffmember_fax',
					'label'	=> $this->_t('Agenturansprechpartner Fax'),
				),		
				'staffmember_skype'		=> array(
					'tag'	=> 'agency_staffmember_skype',
					'label'	=> $this->_t('Agenturansprechpartner Skype'),
				),
				'staffmember_department'	=> array(
					'tag'	=> 'agency_staffmember_department',
					'label'	=> $this->_t('Agenturansprechpartner Department'),
				),
				'staffmember_responsability'	=> array(
					'tag'	=> 'agency_staffmember_responsability',
					'label'	=> $this->_t('Agenturansprechpartner Zuständichkeit'),
				),
				'loop_staffmembers'				=> array(
					'tag'	=> 'start_loop_agency_staffmembers}.....{end_loop_agency_staffmembers',
					'label'	=> $this->_t('Durchläuft alle Agenturansprechpartner'),
				),
			)
		);
		
		$this->_aPlaceholderTableData['pdf'] = array(
			'header'	=> $this->_t('PDF Platzhalter'),
			'data'		=> array(
				'document_number'		=> array(
					'tag'	=> 'pdf_document_number',
					'label'	=> $this->_t('Buchungsnummer für PDFs'),
				),
				'today'					=> array(
					'tag'	=> 'pdf_today',
					'label'	=> $this->_t('Aktuelles Datum für PDFs'),
				),
				'amount'				=> array(
					'tag'	=> 'pdf_amount',
					'label'	=> $this->_t('Bruttosumme für PDFs'),
				),
				'amount_net'			=> array(
					'tag'	=> 'pdf_amount_net',
					'label'	=> $this->_t('Nettosumme für PDFs'),
				),
				'amount_initalcost'		=> array(
					'tag'	=> 'pdf_amount_initalcost',
					'label'	=> $this->_t('Vorortkosten für PDFs'),
				),
				'amount_provison'		=> array(
					'tag'	=> 'pdf_amount_provison',
					'label'	=> $this->_t('Provision für PDFs'),
				),
				'amount_incl_vat'		=> array(
					'tag'	=> 'pdf_amount_incl_vat',
					'label'	=> $this->_t('Bruttosumme (inkl. Steuern) für PDFs'),
				),
				'amount_net_incl_vat'	=> array(
					'tag'	=> 'pdf_amount_net_incl_vat',
					'label'	=> $this->_t('Nettosumme (inkl. Steuern) für PDFs'),
				),
				'amount_initalcost_incl_vat'	=> array(
					'tag'	=> 'pdf_amount_initalcost_incl_vat',
					'label'	=> $this->_t('Vorortkosten (inkl. Steuern) für PDFs'),
				),
				'amount_provison_incl_vat'		=> array(
					'tag'	=> 'pdf_amount_provison_incl_vat',
					'label'	=> $this->_t('Provision (inkl. Steuern) für PDFs'),
				),
				'amount_course_incl_vat'		=> array(
					'tag'	=> 'pdf_amount_course_incl_vat',
					'label'	=> $this->_t('Brutto Kurssumme (inkl. Steuern) für PDFs'),
				),
				'amount_course_net_incl_vat'		=> array(
					'tag'	=> 'pdf_amount_course_net_incl_vat',
					'label'	=> $this->_t('Netto Kurssumme (inkl. Steuern) für PDFs'),
				),
				'amount_accommodation_incl_vat'	=> array(
					'tag'	=> 'pdf_amount_accommodation_incl_vat',
					'label'	=> $this->_t('Brutto Unterkunftssumme (inkl. Steuern) für PDFs'),
				),
				'amount_accommodation_net_incl_vat'	=> array(
					'tag'	=> 'pdf_amount_accommodation_net_incl_vat',
					'label'	=> $this->_t('Netto Unterkunftssumme (inkl. Steuern) für PDFs'),
				),
				'amount_transfer_incl_vat'		=> array(
					'tag'	=> 'pdf_amount_transfer_incl_vat',
					'label'	=> $this->_t('Brutto Transfersumme (inkl. Steuern) für PDFs'),
				),
				'amount_transfer_net_incl_vat'		=> array(
					'tag'	=> 'pdf_amount_transfer_net_incl_vat',
					'label'	=> $this->_t('Netto Transfersumme (inkl. Steuern) für PDFs'),
				),
				'amount_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_excl_vat',
					'label'	=> $this->_t('Bruttosumme (excl. Steuern) für PDFs'),
				),
				'amount_net_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_net_excl_vat',
					'label'	=> $this->_t('Nettosumme (excl. Steuern) für PDFs'),
				),
				'amount_initalcost_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_initalcost_excl_vat',
					'label'	=> $this->_t('Vorortkosten (excl. Steuern) für PDFs'),
				),
				'amount_provison_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_provison_excl_vat',
					'label'	=> $this->_t('Provision (excl. Steuern) für PDFs'),
				),
				'amount_course_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_course_excl_vat',
					'label'	=> $this->_t('Brutto Kurssumme (excl. Steuern) für PDFs'),
				),
				'amount_course_net_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_course_net_excl_vat',
					'label'	=> $this->_t('Netto Kurssumme (excl. Steuern) für PDFs'),
				),
				'amount_accommodation_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_accommodation_excl_vat',
					'label'	=> $this->_t('Brutto Unterkunftssumme (excl. Steuern) für PDFs'),
				),
				'amount_accommodation_net_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_accommodation_net_excl_vat',
					'label'	=> $this->_t('Netto Unterkunftssumme (excl. Steuern) für PDFs'),
				),
				'amount_transfer_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_transfer_excl_vat',
					'label'	=> $this->_t('Brutto Transfersumme (excl. Steuern) für PDFs'),
				),
				'amount_transfer_net_excl_vat'		=> array(
					'tag'	=> 'pdf_amount_transfer_net_excl_vat',
					'label'	=> $this->_t('Netto Transfersumme (excl. Steuern) für PDFs'),
				),
				//'amount_reminder'		=> array(
				//	'tag'	=> 'pdf_amount_reminder',
				//	'label'	=> $this->_t('Restsumme für PDFs'),
				//),
				'amount_credit'		=> array(
					'tag'	=> 'pdf_amount_credit',
					'label'	=> $this->_t('Guthaben für PDFs'),
				),
				'amount_finalpay'		=> array(
					'tag'	=> 'pdf_amount_finalpay',
					'label'	=> $this->_t('Restzahlungsbetrag für PDFs'),
				),
				'amount_prepay'		=> array(
					'tag'	=> 'pdf_amount_prepay',
					'label'	=> $this->_t('Anzahlungssumme für PDFs'),
				),
				'amount_prepay'		=> array(
					'tag'	=> 'pdf_amount_prepay',
					'label'	=> $this->_t('Anzahlungssumme für PDFs'),
				),
				'date_prepay'		=> array(
					'tag'	=> 'pdf_date_prepay',
					'label'	=> $this->_t('Anzahlungssumme für PDFs'),
				),
				'date_finalpay'		=> array(
					'tag'	=> 'pdf_date_finalpay',
					'label'	=> $this->_t('Restzahlungsdatum für PDFs'),
				),
			),
		);
		
		$this->_aPlaceholderTableData['group'] = array(
			'header'	=> $this->_t('Gruppen Daten'),
			'data'		=> array(
				'name_short'		=> array(
					'tag'	=> 'group_name',
					'label'	=> $this->_t('Gruppe: Namen (kurz)'),
				),
				'group_number'		=> array(
					'tag'	=> 'group_number',
					'label'	=> $this->_t('Gruppe: Nummer'),
				),
				'members'		=> array(
					'tag'	=> 'group_customers',
					'label'	=> $this->_t('Gruppe: Mitglieder'),
				),
				'count_member'		=> array(
					'tag'	=> 'group_count_member',
					'label'	=> $this->_t('Gruppe: Anzahl Mitglieder'),
				),
				'count_leader'		=> array(
					'tag'	=> 'group_count_leader',
					'label'	=> $this->_t('Gruppe: Anzahl Gruppenleiter'),
				),
				'count_member_excl_leader' => array(
					'tag'	=> 'group_count_member_excl_leader',
					'label'	=> $this->_t('Gruppe: Anzahl Mitglieder ohne Gruppenleiter'),
				),
//				'loop_group_members'		=> array(
//					'tag'	=> 'start_loop_group_members}.....{end_loop_group_members',
//					'label'	=> $this->_t('Durchläuft alle Kunden der Gruppe, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt'),
//				),
			),
		);
		
		$this->_aPlaceholderTableData['course'] = array(
			'header'	=> $this->_t('Kurse'),
			'data'		=> array(
				'loop_courses'		=> array(
					'tag'	=> 'start_loop_courses}.....{end_loop_courses',
					'label'	=> $this->_t('Durchläuft alle gebuchten Kurse, Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt'),
				),
				'name'		=> array(
					'tag'	=> 'course',
					'label'	=> $this->_t('Kurs: Name'),
				),
				'weeks'		=> array(
					'tag'	=> 'course_weeks',
					'label'	=> $this->_t('Kurs: Wochenanzahl'),
				),
				'category_name'		=> array(
					'tag'	=> 'course_category',
					'label'	=> $this->_t('Kurs: Kategorie'),
				),
				'max_students'		=> array(
					'tag'	=> 'course_max_students',
					'label'	=> $this->_t('Kurs: Maximale Schüleranzahl'),
				),
				'start_date'		=> array(
					'tag'	=> 'date_course_start',
					'label'	=> $this->_t('Kurs: Start'),
				),
				'end_date'		=> array(
					'tag'	=> 'date_course_end',
					'label'	=> $this->_t('Kurs: Ende'),
				),
				'lessons_per_week'		=> array(
					'tag'	=> 'lessons_per_week',
					'label'	=> $this->_t('Lektionen pro Woche'),
				),
				'lessons_amount'		=> array(
					'tag'	=> 'lessons_amount',
					'label'	=> $this->_t('Gesamtanzahl der Lektionen'),
				),
				'lessons_amount_total'		=> array(
					'tag'	=> 'lessons_amount_total',
					'label'	=> $this->_t('Gesamtanzahl der Lektionen aller Kurse'),
				),
				'first_start'		=> array(
					'tag'	=> 'date_first_course_start',
					'label'	=> $this->_t('Startdatum des ersten Kurses'),
				),
				'last_end'		=> array(
					'tag'	=> 'date_last_course_end',
					'label'	=> $this->_t('Enddatum des letzten Kurses'),
				),
				'total_course_weeks_absolute' => array(
					'tag'	=> 'total_course_weeks_absolute',
					'label'	=> $this->_t('Gesamtzahl der gebuchten Kurswochen'),
				),
				'normal_level'		=> array(
					'tag'	=> 'normal_level',
					'label'	=> $this->_t('Kurs: Level'),
				)
			)
		);
		
		$this->_aPlaceholderTableData['accommodation'] = array(
			'header'	=> $this->_t('Unterkunft'),
			'data'		=> array(
				'loop_accommodations'		=> array(
					'tag'	=> 'start_loop_accommodations} ..... {end_loop_accommodations',
					'label'	=> $this->_t('Durchläuft alle gebuchten Unterkunfte. Der Komplette Text sowie die Platzhalter die dazwischen stehen werden jeweils wiederholt'),
				),
				'weeks'		=> array(
					'tag'	=> 'accommodation_weeks',
					'label'	=> $this->_t('Unterkunft: Wochen'),
				),
				'end_date'		=> array(
					'tag'	=> 'date_accommodation_end',
					'label'	=> $this->_t('Unterkunft: Ende'),
				),
				'start_date'		=> array(
					'tag'	=> 'date_accommodation_start',
					'label'	=> $this->_t('Unterkunft: Start'),
				),
				'roomtype'		=> array(
					'tag'	=> 'roomtype',
					'label'	=> $this->_t('Unterkunft: Raumtype'),
				),
				'roomtype_full'		=> array(
					'tag'	=> 'roomtype_full',
					'label'	=> $this->_t('Unterkunft: Raumtype Ganzername'),
				),
				'accommodation_meal'		=> array(
					'tag'	=> 'accommodation_meal',
					'label'	=> $this->_t('Unterkunft: Verpflegung'),
				),
				'accommodation_meal_full'		=> array(
					'tag'	=> 'accommodation_meal_full',
					'label'	=> $this->_t('Unterkunft: Verpflegung (lang)'),
				),
				'accommodation_category_name'		=> array(
					'tag'	=> 'accommodation_category',
					'label'	=> $this->_t('Unterkunft: Kategorie'),
				),
			)
		);
		
		$this->_aPlaceholderTableData['transfer'] = array(
			'header'	=> $this->_t('Transfer An-/Abreise'),
			'data'		=> array(
				'type'		=> array(
					'tag'	=> 'booked_transfer',
					'label'	=> $this->_t('Transfer: Art'),
				),
				'comment'		=> array(
					'tag'	=> 'transfer_comment',
					'label'	=> $this->_t('Transfer: Kommentar'),
				),
				'arrival_airline'		=> array(
					'tag'	=> 'arrival_airline',
					'label'	=> $this->_t('Transfer-Ankunft: Fluggesellschaft'),
				),
				'departurel_airline'		=> array(
					'tag'	=> 'departurel_airline',
					'label'	=> $this->_t('Transfer-Abflug: Fluggesellschaft'),
				),
				'arrival_date'		=> array(
					'tag'	=> 'arrival_date',
					'label'	=> $this->_t('Transfer-Ankunft: Datum'),
				),
				'departure_date'		=> array(
					'tag'	=> 'departure_date',
					'label'	=> $this->_t('Transfer-Abflug: Datum'),
				),
				'arrival_time'		=> array(
					'tag'	=> 'arrival_time',
					'label'	=> $this->_t('Transfer-Ankunft: Uhrzeit'),
				),
				'arrival_pickup_time'		=> array(
					'tag'	=> 'arrival_pickup_time',
					'label'	=> $this->_t('Transfer-Anreise: Uhrzeit für die Abholung'),
				),
				'departure_time'		=> array(
					'tag'	=> 'departure_time',
					'label'	=> $this->_t('Transfer-Abflug: Uhrzeit'),
				),
				'departure_pickup_time'		=> array(
					'tag'	=> 'departure_pickup_time',
					'label'	=> $this->_t('Transfer-Abflug: Uhrzeit für die Abholung'),
				),
				'arrival_flightnumber'		=> array(
					'tag'	=> 'arrival_flightnumber',
					'label'	=> $this->_t('Transfer-Ankunft: Flugnummer'),
				),
				'departure_flightnumber'		=> array(
					'tag'	=> 'departure_flightnumber',
					'label'	=> $this->_t('Transfer-Abflug: Flugnummer'),
				),
				'arrival_pick_up'		=> array(
					'tag'	=> 'arrival_pick_up',
					'label'	=> $this->_t('Transfer-Ankunft: Aufnahme'),
				),
				'arrival_drop_off'		=> array(
					'tag'	=> 'arrival_pick_up',
					'label'	=> $this->_t('Transfer-Ankunft: Abgabe'),
				),
				'departure_pick_up'		=> array(
					'tag'	=> 'departure_pick_up',
					'label'	=> $this->_t('Transfer-Abflug: Aufnahme'),
				),
				'departure_drop_off'		=> array(
					'tag'	=> 'departure_drop_off',
					'label'	=> $this->_t('Transfer-Abflug: Abgabe'),
				),
			)
		);
		
		$this->_aPlaceholderTableData['additional_transfer'] = array(
			'header'	=> $this->_t('Individueller Transfer'),
			'data'		=> array(
				'loop_additional_transfer'		=> array(
					'tag'	=> 'start_loop_individual_transfer}.....{end_loop_individual_transfer',
					'label'	=> $this->_t('Durchläuft alle gebuchten Versicherungen, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt'),
				),
				'date'		=> array(
					'tag'	=> 'individual_transfer_date',
					'label'	=> $this->_t('Transfer-Individuell: Datum'),
				),
				'time'		=> array(
					'tag'	=> 'individual_transfer_time',
					'label'	=> $this->_t('Transfer-Individuell: Zeit'),
				),
				'pick_up_location'		=> array(
					'tag'	=> 'individual_transfer_pick_up_location',
					'label'	=> $this->_t('Transfer-Individuell: Abreise'),
				),
				'drop_off_location'		=> array(
					'tag'	=> 'individual_transfer_drop_off_location',
					'label'	=> $this->_t('Transfer-Individuell: Ankunft'),
				),
				'comment'		=> array(
					'tag'	=> 'individual_transfer_comment',
					'label'	=> $this->_t('Transfer-Individuell: Kommentar'),
				),
			)
		);
		
		$this->_aPlaceholderTableData['agency_bank'] = array(
			'header'	=> $this->_t('Agentur Bankinformationen'),
			'data'		=> array(
				'account_holder'		=> array(
					'tag'	=> 'agency_account_holder',
					'label'	=> $this->_t('Kontoinhaber'),
				),
				'bank_name'		=> array(
					'tag'	=> 'agency_bank_name',
					'label'	=> $this->_t('Name der Bank'),
				),
				'bank_code'		=> array(
					'tag'	=> 'agency_bank_code',
					'label'	=> $this->_t('BLZ'),
				),
				'account_number'		=> array(
					'tag'	=> 'agency_account_number',
					'label'	=> $this->_t('Kontonummer'),
				),
				'swift'		=> array(
					'tag'	=> 'agency_swift',
					'label'	=> $this->_t('SWIFT'),
				),
				'iban'		=> array(
					'tag'	=> 'agency_iban',
					'label'	=> $this->_t('IBAN'),
				),
			)
		);
		
		$this->_aPlaceholderTableData['insurance'] = array(
			'header'	=> $this->_t('Versicherungen'),
			'data'		=> array(
				'loop_insurances'		=> array(
					'tag'	=> 'start_loop_insurances}.....{end_loop_insurances',
					'label'	=> $this->_t('Durchläuft alle gebuchten Versicherungen, der komplette Text sowie die Platzhalter, die dazwischen stehen, werden jeweils wiederholt'),
				),
				'name'		=> array(
					'tag'	=> 'insurance',
					'label'	=> $this->_t('Versicherung: Name'),
				),
				'provider'		=> array(
					'tag'	=> 'insurance_provider',
					'label'	=> $this->_t('Versicherung: Anbieter'),
				),
				'start_date'		=> array(
					'tag'	=> 'date_insurance_start',
					'label'	=> $this->_t('Versicherung: Start'),
				),
				'end_date'		=> array(
					'tag'	=> 'date_insurance_end',
					'label'	=> $this->_t('Versicherung: Ende'),
				),
				'price'		=> array(
					'tag'	=> 'insurance_price',
					'label'	=> $this->_t('Versicherung: Preis'),
				),
			)
		);
		
		$this->_aPlaceholderTableData['numbers'] = array(
			'header'	=> $this->_t('Nummern'),
			'data'		=> array(
				'document_number'		=> array(
					'tag'	=> 'document_number',
					'label'	=> $this->_t('Buchungsnummer für E-Mails'),
				),
				'customer_number'		=> array(
					'tag'	=> 'customernumber',
					'label'	=> $this->_t('Kundennummer'),
				),
			),
		);
        
        $this->_aPlaceholderTableData['document'] = array(
			'header'	=> $this->_t('Dokumente'),
			'data'		=> array(
				'document_firstname'		=> array(
					'tag'	=> 'document_firstname',
					'label'	=> $this->_t('Vorname'),
				),
				'document_surname'		=> array(
					'tag'	=> 'document_surname',
					'label'	=> $this->_t('Nachname'),
				),
				'document_salutation'		=> array(
					'tag'	=> 'document_salutation',
					'label'	=> $this->_t('Anrede'),
				),
				'document_address'		=> array(
					'tag'	=> 'document_address',
					'label'	=> $this->_t('Adresse'),
				),
				'document_address_addon'		=> array(
					'tag'	=> 'document_address_addon',
					'label'	=> $this->_t('Adresszusatz'),
				),
				'document_zip'		=> array(
					'tag'	=> 'document_zip',
					'label'	=> $this->_t('PLZ'),
				),
				'document_city'		=> array(
					'tag'	=> 'document_city',
					'label'	=> $this->_t('Stadt'),
				),
				'document_state'		=> array(
					'tag'	=> 'document_state',
					'label'	=> $this->_t('Bundesland'),
				),
				'document_country'		=> array(
					'tag'	=> 'document_country',
					'label'	=> $this->_t('Land'),
				),
				'document_company'		=> array(
					'tag'	=> 'document_company',
					'label'	=> $this->_t('Firma'),
				),
				'document_address_type'		=> array(
					'tag'	=> 'document_address_type',
					'label'	=> $this->_t('Adressat-Typ'),
				),
			),
		);

		$this->_aPlaceholderTableData['enquiry'] = [
			'header' => $this->_t('Anfrage Daten'),
			'data' => [
				'enquiry_comment_course_category' => [
					'tag' => 'enquiry_comment_course_category',
					'label' => $this->_t('Gewünschte Kurskategorien'),
				],
				'enquiry_comment_course_intensity' => [
					'tag' => 'enquiry_comment_course_intensity',
					'label' => $this->_t('Gewünschte Kurslevel'),
				],
				'enquiry_comment_accommodation_category' => [
					'tag' => 'enquiry_comment_accommodation_category',
					'label' => $this->_t('Gewünschte Unterkunftskategorien'),
				],
				'enquiry_comment_accommodation_room' => [
					'tag' => 'enquiry_comment_accommodation_room',
					'label' => $this->_t('Gewünschte Unterkunftsraumart'),
				],
				'enquiry_comment_accommodation_meal' => [
					'tag' => 'enquiry_comment_accommodation_meal',
					'label' => $this->_t('Gewünschte Verpflegung'),
				],
				'enquiry_comment_transfer_category' => [
					'tag' => 'enquiry_comment_transfer_category',
					'label' => $this->_t('Gewünschte Transferart'),
				],
				'enquiry_comment_transfer_location' => [
					'tag' => 'enquiry_comment_transfer_location',
					'label' => $this->_t('Gewünschte Transferorte'),
				],
			],
		];

	}

	/**
	 * interne Übersetzung mit dem Pfad Thebing » Placeholder
	 * @param string $sText
	 * @return string 
	 */
	protected function _t($sText)
	{
		$sTranslation = L10N::t($sText, 'Thebing » Placeholder');
		
		return $sTranslation;
	}

	public function getPlaceholderCustomerTag($sKey)
	{ 
		return $this->getPlaceholderTag('customer', $sKey);
	}
	
	public function getPlaceholderCustomerTitle($sKey)
	{
		return $this->getPlaceholderTitle('customer', $sKey);
	}
	
	public function getPlaceholderAgencyTag($sKey)
	{
		return $this->getPlaceholderTag('agency', $sKey);
	}
	
	public function getPlaceholderAgencyTitle($sKey)
	{
		return $this->getPlaceholderTitle('agency', $sKey);
	}
	
	public function searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder=array()) {
		global $_VARS;

		$mValue					= null;
		$sFormat				= false;
		$sDisplayLanguage		= $this->getLanguage();
		$oInquiry				= $this->getMainObject();
		/* @var $oCustomer Ext_TS_Inquiry_Contact_Abstract */
		$oCustomer				= $this->getCustomer();
		$oAgency				= $this->getAgency();
		$oAgencyMasterContact	= $this->getAgencyMasterContact();
		$sPlaceholder			= $sField;

		//Modifiersprache
		if($this->_getModifierLanguage($aPlaceholder, $this->_aLanguages)) {
			$sDisplayLanguage = $this->_getModifierLanguage($aPlaceholder, $this->_aLanguages);
		}

		$oLanguage = new Tc\Service\Language\Frontend($sDisplayLanguage);
		
		switch($sPlaceholder)
		{
			case 'firstname':
				$mValue = $oCustomer->firstname; 
				break;
			case 'surname':
			case 'lastname':
				$mValue = $oCustomer->lastname; 
				break;
			case 'age':
				$mValue = $oCustomer->getAge(); 
				break;
			case 'salutation': 
				$mValue = Ext_TS_Contact::getSalutationForFrontend($oCustomer->gender, $oLanguage);
				break;
			case 'birthdate':
				if(WDDate::isDate($oCustomer->birthday, WDDate::DB_DATE)){
					$oDate		= new WDDate($oCustomer->birthday, WDDate::DB_DATE);
					$mValue		= $oDate->get(WDDate::TIMESTAMP);
					$sFormat	= 'date';
				} else {
					$mValue = '';
				}
				break;
			case 'gender':
				$aGenders = \Ext_TC_Util::getGenders(bEmptyItem: false, mLanguage: $oLanguage, bLowerCase: true);
				$mValue = $aGenders[$oCustomer->gender] ?? 'unknown';
				break;
			case 'nationality':
				$aNationality = Ext_Thebing_Nationality::getNationalities(true,  $sDisplayLanguage, false);
				$mValue = (string)$aNationality[$oCustomer->nationality];
				break;
			case 'mothertongue':
				$aLangs	= Ext_Thebing_Data::getLanguageSkills(true,$sDisplayLanguage);
				$mValue = (string)$aLangs[$oCustomer->language];
				break;			
			case 'address': 
			case 'address_addon': 
			case 'zip': 
			case 'city':
			case 'state': 
				$oAddress = $oCustomer->getAddress('contact');
				$mValue = $oAddress->$sPlaceholder;
				break;
			case 'company':
			case 'billing_address':
			case 'billing_zip': 
			case 'billing_city':
				$oAddress = $oInquiry->getBooker()?->getAddress('billing');
				$sKey = str_replace('billing_', '', $sPlaceholder);
				$mValue = $oAddress?->$sKey;
				break;
			case 'billing_country':
				$oAddress = $oInquiry->getBooker()?->getAddress('billing');
				$aCountry = Ext_Thebing_Data::getCountryList(true,false,$sDisplayLanguage);
				$mValue = $aCountry[$oAddress?->country_iso] ?? '';
				break;
			case 'country': 
				$oAddress = $oCustomer->getAddress('contact');
				$aCountry = Ext_Thebing_Data::getCountryList(true,false,$sDisplayLanguage);
				$mValue = $aCountry[$oAddress->country_iso] ?? '';
				break;
			case 'phone_home': 
			case 'phone_mobile': 
			case 'phone_office': 
			case 'fax': 
				
				if($sPlaceholder == 'phone_home'){
					$sPlaceholder = 'phone_private';
				}

				$mValue = $oCustomer->getDetail($sPlaceholder);
				break;
			case 'email': 
				$mValue = $oCustomer->getEmail();
				break;
			//AGENTUR
			case 'agency':
			case 'agency_number':
			case 'agency_user_firstname':
			case 'agency_abbreviation': 
			case 'agency_address': 
			case 'agency_zip': 
			case 'agency_city': 
			case 'agency_country': 
			case 'agency_tax_number': 
			case 'agency_person': 
			case 'agency_state':
			case 'agency_note':
			case 'agency_payment_terms':
			case 'agency_account_holder':
			case 'agency_bank_name':
			case 'agency_bank_code':
			case 'agency_account_number':
			case 'agency_swift':
			case 'agency_iban':
			case 'agency_groups':
			case 'agency_category':
				if(is_object($oAgency) && $oAgency instanceof Ext_Thebing_Agency){
					$oAgencyPlaceholder = new Ext_Thebing_Agency_Placeholder($oAgency->id, 'agency');
					$oAgencyPlaceholder->sTemplateLanguage = $this->getLanguage();
					$mValue = $oAgencyPlaceholder->_getReplaceValue($sPlaceholder, $aPlaceholder);
				}
				$mValue = (string)$mValue;
				break;
			// Staffmember
			case 'agency_staffmember_salutation':
			case 'agency_staffmember_firstname':
			case 'agency_staffmember_surname':
			case 'agency_staffmember_email':
			case 'agency_staffmember_phone':
			case 'agency_staffmember_fax':
			case 'agency_staffmember_skype':
			case 'agency_staffmember_department':
			case 'agency_staffmember_responsability': 
				if(
					is_object($oAgency) && 
					$oAgency instanceof Ext_Thebing_Agency
				){
					$oAgencyPlaceholder = new Ext_Thebing_Agency_Placeholder($oAgency->id, 'agency');
					$oAgencyPlaceholder->sTemplateLanguage = $this->getLanguage();
					$oAgencyPlaceholder->_oAgencyStaff = $this->_oAgencyStaff;
					$oAgencyPlaceholder->_oAgencyMasterContact		= $oAgencyMasterContact;
					$mValue = $oAgencyPlaceholder->_getReplaceValue($sPlaceholder, $aPlaceholder);
				}
				$mValue = (string)$mValue;
				break;
			case 'profession':
			case 'social_security_number':
				$mValue = $oInquiry->$sPlaceholder;
				break;
			case 'other': 
				return $oCustomer->getDetail('comment');
				break;
			case 'document_number':
			case 'main_document_number':
			//case 'document_date':
			case 'document_type':

				/*
				if(
					$this->_oDocument instanceof Ext_Thebing_Inquiry_Document &&
					$this->_oDocument->id > 0
				){
					$oDocument = $this->_oDocument;
					return $oDocument->document_number;
				} else {
					$oDocumentNumber = new Ext_Thebing_Inquiry_Document_Number($oInquiry);
					return $oDocumentNumber->createForShow();
				}
				else
				{
					$oLastDocument = $oInquiry->getLastDocument('invoice_with_creditnote');
					
					if($oLastDocument)
					{
						return $oLastDocument->document_number;
					}
					else
					{
						return '{document_number}';
					}
					
					return '{document_number}';
					
				}*/

				// $_VARS ist zwar nicht schön, aber bei den Platzhaltern gibt es keine andere Möglichkeit
				// $_VARS['save']['invoice_select'] ist bei allen Zusatzdokumenten vorhanden (ausgeblendet), steht aber standardmäßig auf der letzten Rechnung
				if(!empty($_VARS['save']['invoice_select'])) {
					$oLastDocument = Ext_Thebing_Inquiry_Document::getInstance($_VARS['save']['invoice_select']);
				} else {
					$oLastDocument = $oInquiry->getLastDocument('invoice_with_creditnote');
				}

				if($oLastDocument) {
					switch ($sPlaceholder) {
						case 'document_type':
							$sReturn = $oLastDocument->type;
							break;
						case 'document_number':
							$sReturn = $oLastDocument->document_number;
							break;
						case 'main_document_number':
							$oParentDocument = $oLastDocument->getParentDocument();
							if($oParentDocument) {
								$sReturn = $oParentDocument->document_number;
							}
							break;
						case 'document_date':
							$oSchool = $oInquiry->getSchool();
							$oVersion = $oLastDocument->getLastVersion();
							$dDate = new DateTime($oVersion->date);
							$sReturn = strftime($oSchool->date_format_long, $dDate->getTimestamp());
							break;
					}
					return $sReturn;
				} else {
					return '';
				}
				break;
			case 'customernumber':
				$mValue = $oCustomer->getCustomerNumber();
				break;
			case 'customer_state':
				$sState = $oInquiry->getState();
				return $sState;
			// TODO Redundant in \Ext_TS_Inquiry_Placeholder_Abstract::searchPlaceholderValue()
			case 'document_address_type':
			case 'document_addressee':
			case 'document_firstname':
			case 'document_surname':
			case 'document_lastname':
			case 'document_salutation':
			case 'document_address':
			case 'document_address_addon':
			case 'document_zip':
			case 'document_city':
			case 'document_state':
			case 'document_country':
			case 'document_company':
				$aAddressData = $this->_aAdditionalData['document_address'];
				if(!empty($aAddressData)){
                    $mValue = $this->_helperReplaceDocumentPlaceholders($sField, $aAddressData[0], $oInquiry);
                }
				break;
			case 'group_name':
				$oGroup = $oInquiry->getGroup();
				if($oGroup){
					return $oGroup->name;
				}
				break;
			case 'group_number':
				$oGroup = $oInquiry->getGroup();
				if($oGroup){
					return $oGroup->number;
				}
				break;
			case 'group_customers':
				$oGroup = $oInquiry->getGroup();
				if($oGroup){
					$aGoupInquirys = $oGroup->getMembers();
					$sGroupCustomers = '';
					$t = 1;
					foreach($aGoupInquirys as $oGroupInquiry){

						if($oGroupInquiry instanceof Ext_TS_Inquiry) {
							$oGroupCustomer = $oGroupInquiry->getCustomer();
						} else {
							//Bei Anfragen ist der Mitglied dirent das Contact Objekt
							$oGroupCustomer = $oGroupInquiry;
						}

						$sGroupCustomers .= $oGroupCustomer->lastname." ".$oGroupCustomer->firstname;
						if($t < count($aGoupInquirys)){
							$sGroupCustomers .= ", ";
						}
						$t++;
					}
					return $sGroupCustomers;
				}
				break;
			case 'group_count_member':
				$oGroup = $oInquiry->getGroup();
				if($oGroup){
					return $oGroup->countAllMembers();
				}
				break;
			case 'group_count_leader':
				$oGroup = $oInquiry->getGroup();
				if($oGroup){
					return $oGroup->countGuides();
				}
				break;
			case 'group_count_member_excl_leader':
				$oGroup = $oInquiry->getGroup();
				if($oGroup){
					return $oGroup->countAllMembers() - $oGroup->countGuides();
				}
				break;
				
				
				
				
				
			default:
				$mValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;
		}
		
		if(!empty($sFormat))
		{
			$mReturn = array(
				'value'		=> $mValue,
				'format'	=> $sFormat,
				'language'	=> $sDisplayLanguage
			);
		}
		else
		{
			$mReturn = $mValue;
		}
		
		return $mReturn;
	}

	/**
	 * Ersetzt die speziellen Dokumente-Platzhalter, welche sich auf die Adresse beziehen
	 *
	 * @param $sField
	 * @param $aAddressData
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @return string
	 * @throws Exception
	 */
	protected function _helperReplaceDocumentPlaceholders($sField, $aAddressData, Ext_TS_Inquiry_Abstract $oInquiry)
	{
		$sReturn = '';

		// Wenn keine Adresse-Informationen übergeben wurden, kann hier auch nichts ersetzt werden
		if(!empty($aAddressData)) {

			$aData = (new Ext_Thebing_Document_Address($oInquiry))
					->getAddressData($aAddressData, $this->getLanguageObject());

			$sReturn = $aData[$sField];
		}

		return $sReturn;

	}
	
	/**
	 * @todo Das ist ein bisschen krass, dass das bei fast jedem Platzhalter einzeln passiert
	 * 
	 * @return string
	 */
	public function getLanguage()
	{	
		$sLang = parent::getLanguage();

		if(empty($sLang)){
			
			$oCustomer				= $this->getCustomer();
			
			//Kundenobject holen
			if(is_object($oCustomer) && $oCustomer instanceof Ext_TS_Inquiry_Contact_Abstract)
			{
				//Kundensprache
				$sLang = $oCustomer->getLanguage();	
			} 
		}
		
		return $sLang;
	}
	
	protected function _helperReplaceVars($sText, $iOptionalId = 0)
	{
		// Standardschleifen die überall verfügbar sind

		$sText = parent::_helperReplaceVars($sText, $iOptionalId);

		$sText = preg_replace_callback('@\{start_loop_agency_staffmembers\}(.*?)\{end_loop_agency_staffmembers\}@ims',array( $this, "_helperReplaceAgencyStaffLoop"),$sText);

		$sText = $this->_helperReplaceVars2($sText, $iOptionalId);

		return $sText;
	
	}
	
	protected function _helperReplaceAgencyStaffLoop($aText)
	{
		$this->addMonitoringEntry('start_loop_agency_staffmembers');

		$sText = "";

		$oAgency	= $this->getAgency();

		$aContacts	= array();

		if($oAgency)
		{
			$aContacts = $oAgency->getContacts(false, true);
		}

		foreach((array)$aContacts as $oContact)
		{
			$this->_oAgencyStaff = $oContact;
			$sText .= $this->_helperReplaceVars($aText[1]);
		}

		// wieder reseten damit nicht schleifen platzhalter normal ersetzt werden
		$this->_oAgencyStaff = null;

		return $sText;

	}
	
	public function replace($sText = '', $iPlaceholderLib = 1, $iOptionalId = 0)
	{
		$this->_iPlaceholderLib = $iPlaceholderLib;
		return $this->_helperReplaceVars($sText,$iOptionalId);
	}
	
	/**
	 * Prüft ob der Platzhalter eine Modifier Sprache benötigt
	 * @param type $aPlaceholder
	 * @param type $sLanguage
	 * @return type 
	 */
	protected function _getModifierLanguage($aPlaceholder, $aLanguage){
		
		$mDisplayLanguage = false;
		
		if(
			($aPlaceholder['modifier'] ?? null) == 'language' &&
			array_key_exists($aPlaceholder['parameter'], $aLanguage)
		){
			$mDisplayLanguage = $aPlaceholder['parameter'];
		}
		
		return $mDisplayLanguage;
	}

	abstract public function getCustomer();

	abstract public function getAgency();

	abstract public function getAgencyMasterContact();
	
	/**
	 * Haupt Inquiry/Enquiry Objekt
	 * 
	 * @return Ext_TS_Inquiry_Abstract
	 */
	abstract public function getMainObject();
}
