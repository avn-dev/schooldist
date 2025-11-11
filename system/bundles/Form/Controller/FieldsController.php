<?php

namespace Form\Controller;

class FieldsController extends \Ext_Gui2_Page_Controller {

	protected $_sAccessRight = 'modules_admin';

	public function page($iFormId) {

		\Form\Gui2\Data\Pages::$iFormId = $iFormId;
		\Form\Gui2\Data\Fields::$iFormId = $iFormId;
		
		$aYmlPath = [
			'parent' => 'form_pages',
			'childs' => [
				[
					'path' => 'form_fields',
					'parent_gui' => [
						'foreign_key' => 'page_id',
						'parent_primary_key' => 'id',
						'reload' => true
					]
				]
			]
		];

		$oPage = $this->displayPage($aYmlPath);
		$aElements = $oPage->getElements();
		
		/* @var $oGui \Ext_Gui2 */
		$oGui = reset($aElements);

		$oGui->setOption('form_id', $iFormId);

		$oPage->display();

		die();

	}
	
}
