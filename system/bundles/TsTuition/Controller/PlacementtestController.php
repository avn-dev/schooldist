<?php

namespace TsTuition\Controller;

class PlacementtestController extends \Ext_Gui2_Page_Controller
{

	protected $_sInterface = 'backend';

	public function placementtests() {

		$config = [
			'parent' => 'TsTuition_placementtests',
			'childs' => [
				[
					'path' => 'TsTuition_placementtest_questions',
					'parent_gui' => [
						'foreign_key' => ['placementtest_id'],
					]
				],
			]
		];

		$page = $this->displayPage($config);
		$page->display();die();
	}

}
