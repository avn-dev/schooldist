<?php

class Ext_TS_Enquiry_Controller extends \Ext_Gui2_Page_Controller {

	public function page() {

		$config = [
			'parent' => 'ts_enquiry',
			'childs' => [
				[
					'path' => 'ts_enquiry_combination',
					'parent_gui' => [
						'foreign_key' => 'inquiry_id',
						'reload' => true
					]
				],
			]
		];

		$page = $this->displayPage($config);
		$page->display();

		die();

	}

}