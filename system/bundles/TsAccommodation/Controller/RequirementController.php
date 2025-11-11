<?php

namespace TsAccommodation\Controller;

class RequirementController extends \Ext_Gui2_Page_Controller {

	public function pageAction() {
		
		$aYmlPath = [
			'parent' => 'TsAccommodation_requirement_list',
		];

		$this->displayPage($aYmlPath)->display();
		die();

	}

}