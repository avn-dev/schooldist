<?php

namespace TsSponsoring\Controller;

class SponsoringController extends \Ext_Gui2_Page_Controller {

	public function pageAction() {

		$aYmlPath = [
			'parent' => 'tsSponsoring_sponsor',
			'childs' => [
				[
					'path' => 'tsSponsoring_contact',
					'parent_gui' => [
						'foreign_key' => 'sponsor_id',
						'foreign_key_alias' => 'sponsors',
						'foreign_jointable' => 'sponsors',
						'reload' => true
					]
				],
			]
		];

		$oPage = $this->displayPage($aYmlPath);
		$oPage->display();

		die();

	}

}