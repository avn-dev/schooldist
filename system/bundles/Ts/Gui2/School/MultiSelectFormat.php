<?php

namespace Ts\Gui2\School;

class MultiSelectFormat extends \Ext_Thebing_Gui2_Format_Multiselect {

	public function __construct() {
		$aSelectOptions = \Ext_Thebing_Client::getSchoolList(true);
		parent::__construct($aSelectOptions);
	}

}
