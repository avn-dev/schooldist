<?php

namespace TcComplaints\Controller;

class CategoryController extends \Ext_Gui2_Page_Controller {

	/**
	 * Setzt die Kategorieliste mit der Unterkategorieliste zusammen
	 */
	public function pageAction() {

		$sClassName = \Ext_TC_Factory::getClassName('\TcComplaints\Gui2\Data\Category');
		$sYmlPath = \Ext_TC_Factory::executeStatic($sClassName, 'getCategoryYMLPath');
		$sYmlPathChild = \Ext_TC_Factory::executeStatic($sClassName, 'getSubCategoryYMLPath');

		$aYmlPath = [
			'parent' => $sYmlPath,
			'childs' => [
				[
					'path' => $sYmlPathChild,
					'parent_gui' => [
						'foreign_key' => 'category_id',
						'parent_primary_key' => 'id',
						'reload' => true
					]
				]
			]
		];

		$this->displayPage($aYmlPath)->display();
		die();

	}

}