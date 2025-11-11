<?php

namespace Tc\Gui2\Format\Contact;

class Detail extends \Ext_Gui2_View_Format_Abstract {

	private $type;

	public function __construct(string $type) {
		$this->type = $type;
	}

	public function format($value, &$column = null, &$resultData = null) {
		
		$value = \Ext_TC_Contact_Detail::query()
			->where('contact_id', $resultData['traveller_id'])
			->where('type', $this->type)
			->pluck('value');
		
		return $value;
	}

}
