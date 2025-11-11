<?php

/**
 * @property string[] $frontend_languages
 * @property $password_strength
 * @property $frontend_store_ips
 * @property $ts_statistic_tuition_rooms
 * @property $ts_statistic_tuition_times
 */
class Ext_TS_Config extends Ext_TC_Config {

	protected $_aFormat = [
		'frontend_languages' => ['json' => true],
		'backend_languages' => ['json' => true],
		'password_strength' => [],
		'frontend_store_ips' => [],
		'auto_format_phone_numbers' => [],
		'lastname_capital_letters_inquiry' => [],
		'format_additional_contactdata' => [],
		'lastname_capital_letters_accommodation' => [],
		'ts_statistic_tuition_times' => ['json' => true],
		'ts_statistic_tuition_rooms' => ['json' => true],
		'zendesk_id' => ['validate' => 'INT'],
		'tc_flex_fields_per_section_limit' => ['validate' => 'REGEX', 'validate_value' => '[1-9]$|[1-3][0-9]'], // 1-30
		'tc_flex_fields_per_section_visible_limit' => ['validate' => 'REGEX', 'validate_value' => '[1-9]$|1[0-5]'], // 1-15
		'ts_max_attached_additional_docments' => ['validate' => 'REGEX', 'validate_value' => '[1-9]$|[1-3][0-9]'], // 1-30
		'ts_auto_document_payment_clearing' => [],
		'ts_payments_without_invoice' => [],
		'hubspot_inquiry_customfields' => ['json' => true],
		'hubspot_course_customfields' => ['json' => true],
		'hubspot_agency_customfields' => ['json' => true],
		'hubspot_inquiry_fields' => ['json' => true],
		\TsHubspot\Service\InquiryCourses::HUBSPOT_FIELD_PROPERTIES_KEY => ['json' => true],
		\TsHubspot\Service\InquiryAccommodations::HUBSPOT_FIELD_PROPERTIES_KEY => ['json' => true],
		\TsHubspot\Service\InquiryInsurances::HUBSPOT_FIELD_PROPERTIES_KEY => ['json' => true],
		\TsHubspot\Service\InquiryTransfers::HUBSPOT_FIELD_PROPERTIES_KEY => ['json' => true],
		\TsHubspot\Service\InquiryPayments::HUBSPOT_FIELD_PROPERTIES_KEY => ['json' => true],
		'hubspot_agency_fields' => ['json' => true],
		'hubspot_activity_mapping' => ['json' => true],
		'hubspot_course_additional_services' => ['json' => true],
		'hubspot_accommodation_additional_services' => ['json' => true],
		\TsAccommodationLogin\Handler\ExternalApp::KEY_COLUMNS => ['json' => true],
		\TsAccommodationLogin\Handler\ExternalApp::KEY_DEACTIVATED_PAGES => ['json' => true],
		'hubspot_agency_ids_not_found_in_hubspot' => ['json' => true],
		'hubspot_agencies' => ['json' => true],
		'admin.communication.message.default_layout' => ['validate' => 'INT'],
	];

	public function getInternalSettings(): array {

		$settings = parent::getInternalSettings();

		$settings[] = [
			'key' => 'ts_max_attached_additional_docments',
			'label' => 'Max attached additional documents per service',
			'type' => 'input',
			'form_text' => 'Default: '.Ext_Thebing_Document::MAX_ATTACHED_ADDITIONAL_DOCUMENTS
		];

		$settings[] = [
			'key' => 'school_tuition_max_units',
			'label' => 'Course booking - Max lessons',
			'type' => 'input',
			'form_text' => 'Default: 500'
		];

		$settings[] = [
			'key' => 'ts_registration_form_tracking_key',
			'label' => 'Frontend - Tracking key',
			'type' => 'input',
			'form_text' => 'Default: null'
		];

		return $settings;

	}
	
}