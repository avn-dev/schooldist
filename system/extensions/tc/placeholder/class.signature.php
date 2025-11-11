<?php

class Ext_TC_Placeholder_Signature extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oSignature'
	);

	protected $_aPlaceholders = [
		'signature_firstname' => [
			'label' => 'Signatur: Vorname',
			'type' => 'method',
			'source' => 'getUserInformation',
			'method_parameter' => ['firstname']
		],
		'signature_lastname' => [
			'label' => 'Signatur: Nachname',
			'type' => 'method',
			'source' => 'getUserInformation',
			'method_parameter' => ['lastname']
		],
        'signature_gender' => [
            'label' => 'Signatur: Geschlecht',
            'type' => 'field',
            'source' => 'getUser()->sex',
            'format' => Ext_TC_GUI2_Format_Gender::class,
            'format_parameter' => ['frontend']
		],
		'signature_phone' => [
			'label' => 'Signatur: Telefon',
			'type' => 'field',
			'source' => 'phone'
		],
		'signature_fax' => [
			'label' => 'Signatur: Fax',
			'type' => 'field',
			'source' => 'fax'
		],
		'signature_email' => [
			'label' => 'Signatur: E-Mail-Adresse',
			'type' => 'field',
			'source' => 'email'
		],
		'signature_skype' => [
			'label' => 'Signatur: Skype',
			'type' => 'field',
			'source' => 'skype'
		],
		'signature_title' => [
			'label' => 'Signatur: Titel',
			'type' => 'method',
			'source' => 'getTitle',
			'pass_language' => true
		],
		'signature_txt' => [
			'label' => 'Signatur: Text',
			'type' => 'method',
			'source' => 'getSignatureText'
		],
		'signature_img' => [
			'label' => 'Signatur: Bild',
			'type' => 'method',
			'source' => 'getSignatureImg'
		],
		'signature_url' => [
			'label' => 'Signatur: URL',
			'type' => 'method',
			'source' => 'getSignatureUrl'
		],
		/**
		 * TODO Legacy (Schule)
		 */
		'system_user_firstname' => [
			'label' => 'Signatur: Vorname',
			'type' => 'method',
			'source' => 'getUserInformation',
			'method_parameter' => ['firstname'],
			'hidden' => false
		],
		'system_user_surname' => [
			'label' => 'Signatur: Nachname',
			'type' => 'method',
			'source' => 'getUserInformation',
			'method_parameter' => ['lastname'],
			'hidden' => false
		],
		'system_user_phone' => [
			'label' => 'Signatur: Telefon',
			'type' => 'field',
			'source' => 'phone'
		],
		'system_user_fax' => [
			'label' => 'Signatur: Fax',
			'type' => 'field',
			'source' => 'fax'
		],
		'system_user_email' => [
			'label' => 'Signatur: E-Mail-Adresse',
			'type' => 'field',
			'source' => 'email'
		],
	];
	
}