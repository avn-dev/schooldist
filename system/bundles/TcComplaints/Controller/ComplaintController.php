<?php

namespace TcComplaints\Controller;

class ComplaintController extends \Ext_Gui2_Page_Controller {

	public function pageAction() {

		$sClassName = \Ext_TC_Factory::getClassName('\TcComplaints\Gui2\Data\Complaint');
		$sYmlPath = \Ext_TC_Factory::executeStatic($sClassName, 'getComplaintYmlPath');
		$sYmlPathChild = \Ext_TC_Factory::executeStatic($sClassName, 'getComplaintHistoryYmlPath');

		$aYmlPath = [
			'parent' => $sYmlPath,
			'childs' => [
				[
					'path' => $sYmlPathChild,
					'parent_gui' => [
						'foreign_key' => 'complaint_id',
						'parent_primary_key' => 'id',
						'reload' => true
					]
				]
			]
		];


		$oPage = $this->displayPage($aYmlPath);
		$oPage->display();

		die();

	}

}