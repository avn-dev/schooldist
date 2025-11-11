<?php

class Ext_TC_Placeholder_Detail extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oContactDetail'
	);

	protected $_aPlaceholders = array(
		'contactdetail_description' => array(
			'label' => 'Beschreibung',
			'type' => 'method',
			'source' => 'getDescription',
			'pass_language' => true,
			'method_parameter' => array('true')
		),
		'contactdetail_value' => array(
			'label' => 'Wert',
			'type' => 'method',
			'source' => 'getFormattedValue',
		),		
		'contactdetail_type' => array(
			'label' => 'Verfügbare Typen: phone_mobile, phone_private, '
					. 'phone_office, emergency_number, skype, fax, passport_number,'
					. ' passport_valid_from, passport_valid_until, passport_date_of_issue,'
					. ' vegetarian, muslim_diet, smoker, allergies, google_maps,'
					. ' way_to_school, bank, bank_id ,depositor , phone_number,'
					. ' swift, phone, iban, accommodation_description, phone_emergency, recipient_code, vat_number ',
			'type' => 'field',
			'source' => 'type'
		)
	);
	
}
