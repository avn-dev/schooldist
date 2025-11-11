<?php

namespace TsCompany\Handler\Communication\JobOpportunity;

/**
 * @deprecated
 */
class StudentAllocation extends \Ext_Thebing_Communication {

	protected $_sObject = \TsCompany\Entity\JobOpportunity\StudentAllocation::class;

	protected $_aDialogTabOptions = [
		'show_email' => true,
		'show_app' => false,
		'show_sms' => false,
		'show_notices' => true,
		'show_history' => true,
		'show_placeholders' => false
	];

}
