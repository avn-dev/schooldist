<?php

class Ext_Gui2_Page_Controller extends MVC_Abstract_Controller {

	/**
	 * View Class
	 * @var string
	 */
	protected $_sViewClass = '\Ext_Gui2_Page_View';
	
	/**
	 * Default Zugriffsrecht 
	 * @var string
	 */
	protected $_sAccessRight = 'control';
	
	/**
	 * Erzeugt eine GUI
	 * 
	 * @param string $sName 
	 */
	public function __call($sName, $aArguments) {

		$this->createGui($sName);
		
	}
	
	public function createGui($sName, $sSet='') {

		//$sName = strtolower($sName);

		$oFactory = new Ext_Gui2_Factory($sName, true);

		$oGui = $oFactory->createGui($sSet);

		$oGui->include_jscolor = true;

		$this->set('gui', $oGui);		

	}

	/**
	 * Zeigt eine oder eine doppelte Page an.
	 * Diese Page oder Pages können eine oder mehrere Guis enthalten.
	 * @param array $aYmlPath
	 * @return Ext_Gui2_Page
	 */
	public function displayPage($aYmlPath) {

		$oPage = new \Ext_Gui2_Page();

		$sSet = $aYmlPath['set'] ?? '';

		$oGenerator = new Ext_Gui2_Factory($aYmlPath['parent'], true);
		$oGui = $oGenerator->createGui($sSet);

		$oPage->setGui($oGui);

		if(!empty($aYmlPath['childs'])) {
			foreach($aYmlPath['childs'] as $iKey => $aChildYmlPath) {

				$sChildSet = $aChildYmlPath['set'] ?? '';

				$oGeneratorChild = new Ext_Gui2_Factory($aChildYmlPath['path'], true);
				$oChildGui = $oGeneratorChild->createGui($sChildSet, $oGui);

				$aChildYmlPath['parent_gui']['hash'] = $oGui->hash;

				$oPage->setGui($oChildGui, $aChildYmlPath['parent_gui']);

			}
		}

		return $oPage;
	}
	
}
