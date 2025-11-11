<?php

namespace TsStudentSso\Events;

use LightSaml\ClaimTypes;
use Illuminate\Queue\SerializesModels;
use LightSaml\Model\Assertion\Attribute;

class Assertion extends \CodeGreenCreative\SamlIdp\Events\Assertion {
	
	public function __construct(\LightSaml\Model\Assertion\AttributeStatement &$attribute_statement, $guard = null) {
		
		$access = \Access_Frontend::getInstance();
		$studentLogin = \Ext_TS_Inquiry_Contact_Login::getInstance($access->id);
		$contact = $studentLogin->getContact();
		
        $this->attribute_statement = &$attribute_statement;
        $this->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::NAME_ID, $contact->id))
            ->addAttribute(new Attribute(ClaimTypes::EMAIL_ADDRESS, $contact->email))
            ->addAttribute(new Attribute(ClaimTypes::COMMON_NAME, $contact->getName()));
    }
	
}
