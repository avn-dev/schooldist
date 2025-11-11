<?php

use TsSponsoring\Entity\InquiryGuarantee;

class Ext_TS_Inquiry_Placeholder extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = array(
		'variable_name' => 'inquiry'
	);

	public function getPlaceholders()
	{
		$isBooking = ($this->_oWDBasic->type & \Ext_TS_Inquiry::TYPE_BOOKING) ? true : false;

		return [
			'traveller_loop' => [
				'label' => 'Reisende',
				'type' => 'loop',
				'loop' => 'join_table',
				'source' => 'travellers',
				'variable_name' => 'aTravellers'
			],
			'billing_contact_object' => [
				'label' => 'Rechnungskontakt',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getBooker',
				'class' => Ext_TS_Inquiry_Contact_Booker::class,
				'variable_name' => 'inquiryBooker'
			],
			'course_loop' => [
				'label' => 'Kurse',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getCourses',
				'variable_name' => 'aCourses',
				'class' => Ext_TS_Inquiry_Journey_Course::class,
				'exclude_placeholders' => ['inquiry']
			],
//			'courselanguages_loop' => array(
//				'label' => 'Kurssprachen',
//				'type' => 'loop',
//				'loop' => 'method',
//				'source' => 'getCourseLanguages',
//				'variable_name' => 'courseLanguages',
//				'class' => Ext_Thebing_Tuition_LevelGroup::class,
//				'exclude_placeholders' => ['inquiry']
//			),
			'activity_loop' => [
				'label' => 'Aktivitäten',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getActivities',
				'variable_name' => 'activities',
				'class' => Ext_TS_Inquiry_Journey_Activity::class,
				'exclude_placeholders' => ['inquiry'],
				'invisible' => !$isBooking
			],
			'accommodation_loop' => [
				'label' => 'Unterkünfte',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getAccommodations',
				'variable_name' => 'aAccommodations',
				'class' => Ext_TS_Inquiry_Journey_Accommodation::class,
				'exclude_placeholders' => ['inquiry']
			],
			'accommodation_provider_loop' => [
				'label' => 'Unterkunftsanbieter',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getAccommodationProvider',
				'variable_name' => 'aInquiryAccommodationProviders',
				'class' => Ext_Thebing_Accommodation::class,
				'invisible' => !$isBooking
			],
			'examination_loop' => [
				'label' => 'Prüfungen',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getExaminations',
				'class' => 'Ext_Thebing_Examination',
				'variable_name' => 'aExaminations',
				'invisible' => !$isBooking
			],
			'holiday_loop' => [
				'label' => 'Ferien',
				'type' => 'loop',
				'method' => 'joined_object',
				'source' => 'holidays',
				'variable_name' => 'aHolidays',
				'invisible' => !$isBooking
			],
			'financial_guarantee_loop' => [
				'label' => 'Finanzgarantien',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getSponsoringGuarantees',
				'class' => InquiryGuarantee::class,
				//	'exclude_placeholders' => ['inquiry']
				'invisible' => !$isBooking
			],
			'placement_test_loop' => [
				'label' => 'Einstufungstests',
				'type' => 'loop',
				'method' => 'joined_object',
				'source' => 'placementtests',
				'exclude_placeholders' => ['inquiry'],
				'variable_name' => 'placementtests',
				'invisible' => !$isBooking
			],
			'invoice_loop' => [
				'label' => 'Rechnungen',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getInvoices',
				'exclude_placeholders' => ['inquiry'],
				'variable_name' => 'invoices',
				'class' => Ext_Thebing_Inquiry_Document::class,
				'invisible' => !$isBooking
			],
			'agency_object' => [
				'label' => 'Agentur',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getAgency',
				'class' => \Ext_Thebing_Agency::class,
				'variable_name' => 'oInquiryAgency'
			],
			'sponsor_object' => [
				'label' => 'Sponsor',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getSponsor',
				'class' => \TsSponsoring\Entity\Sponsor::class,
				'variable_name' => 'oInquirySponsor',
				'invisible' => !$isBooking
			],
			'group_object' => [
				'label' => 'Gruppe',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getGroup',
				'class' => \Ext_Thebing_Inquiry_Group::class,
				'variable_name' => 'oInquirySponsor',
				'invisible' => !$isBooking
			],
			'group_member_loop' => [
				'label' => 'Gruppenmitglieder',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getGroupMembers',
				'class' => \Ext_TS_Inquiry::class,
				'exclude_placeholders' => ['group_member_loop'],
				'invisible' => !$isBooking
			],
			'date_first_course_start' => [
				'label' => 'Erster Kursstart',
				'type' => 'method',
				'source' => 'getFirstCourseStart',
				'format' => 'Ext_Thebing_Gui2_Format_Date'
			],
			'date_last_course_end' => [
				'label' => 'Letztes Kursende',
				'type' => 'method',
				'source' => 'getLastCourseEnd',
				'format' => 'Ext_Thebing_Gui2_Format_Date'
			],
			'date_last_course_start' => [
				'label' => 'Start vom letzten Kurs',
				'type' => 'method',
				'source' => 'getLatestCourseStart',
				'format' => 'Ext_Thebing_Gui2_Format_Date'
			],
			'last_course_category' => [
				'label' => 'Kategorie vom letzten Kurs',
				'type' => 'method',
				'source' => 'getLatestCourseCategory'
			],
			'last_course_weeks' => [
				'label' => 'Wochen vom letzten Kurs',
				'type' => 'method',
				'source' => 'getLatestCourseWeeks'
			],
			'date_first_service_start'=>[
				'label' => 'Leistungsbeginn',
				'type' => 'method',
				'source' => 'getServiceFrom',
				'format' => 'Ext_Thebing_Gui2_Format_Date'
			],
			'date_last_service_end'=>[
				'label' => 'Leistungsende',
				'type' => 'method',
				'source' => 'getServiceUntil',
				'format' => 'Ext_Thebing_Gui2_Format_Date'
			],
			'last_level' => [
				'label' => 'Letztes Niveau',
				'type' => 'method',
				'source' => 'getLastLevel',
				'method_parameter' => [
					'name'
				],
				'invisible' => !$isBooking
			],
			'referrer' => [
				'label' => 'Wie sind Sie auf uns aufmerksam geworden?',
				'type' => 'method',
				'source' => 'getReferrer()->getName',
				'pass_language' => true
			],
			'booking_number' => [
				'label' => 'Buchungsnummer',
				'type' => 'field',
				'source' => 'number'
			],
			'promotion_code' => [
				'label' => 'Promotion-Code',
				'type' => 'field',
				'source' => 'promotion'
			],
			// TODO Hier fehlt _loop
			'other_contacts' => [
				'label' => 'Weitere Kontakte',
				'type' => 'loop',
				'method' => 'joined_object',
				'source' => 'other_contacts',
				'variable_name' => 'aOtherContacts',
				'invisible' => !$isBooking
			],
			'form_process_key' => [
				'label' => 'Key für Formularprozess erzeugen (Änderung von Daten oder Angebotsbestätigung)',
				'type' => 'class',
				'source' => TsFrontend\Helper\FormProcessPlaceholder::class,
				'only_final_output' => true
			],
//			'payment_instruction' => [
//				'label' => 'Zahlungsanweisung',
//				'type' => 'method',
//				'source' => 'getPaymentInstruction',
//				'invisible' => true
//			],
			'matching_data' => [
				'label' => 'Matching-Informationen',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getMatchingData',
				'invisible' => !$isBooking
			],
			'school' => [
				'label' => 'Schule',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getSchool',
				'class' => \Ext_Thebing_School::class,
				'variable_name' => 'oInquirySchool'
			],
			'arrival_object' => [
				'label' => 'Anreise',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getArrivalTransfer',
				'class' => \Ext_TS_Inquiry_Journey_Transfer::class,
				'variable_name' => 'inquiryArrival',
				'exclude_placeholders' => ['inquiry']
			],
			'departure_object' => [
				'label' => 'Abreise',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getDepartureTransfer',
				'class' => \Ext_TS_Inquiry_Journey_Transfer::class,
				'variable_name' => 'inquiryDeparture',
				'exclude_placeholders' => ['inquiry']
			],
			'unique_key' => [
				'label' => 'Einzigartiger Schlüssel',
				'type' => 'method',
				'source' => 'uniqueKey',
				'variable_name' => 'uniqueKey'
			],
			'sponsor_id' => [
				'label' => 'Sponsoring-ID',
				'type' => 'method',
				'source' => 'getSponsoringID',
				'invisible' => !$isBooking
			],
		];
	}

	protected function _getDynamicPlaceholders() {

		$placeholders = [];

		// Wird noch nicht benutzt, kommt noch, siehe #21752
//		foreach (Ext_TC_Marketing_Feedback_Questionary::query()->get() as $questionary) {
//			$placeholders['link_feedback_'.$questionary->id] = [
//				'label' => 'Link des Feedback Formulars "'.$questionary->name.'"',
//				'type' => 'method',
//				'source' => 'getFeedbackLink',
//				'method_parameter' => $questionary->id,
//				'variable_name' => 'feedbackLink'
//			];
//		}

		if (\TcExternalApps\Service\AppService::hasApp(Ts\Handler\VisaLetterVerification\ExternalApp::APP_NAME)) {
			$placeholders['visa_qr_code'] = [
					'label' => 'Visa QR-Code (Nur PDF)',
					'type' => 'method',
					'source' => 'getVisaQrCode',
					'variable_name' => 'visaQrCode'
				];
		}

		return $placeholders;
	}

}
