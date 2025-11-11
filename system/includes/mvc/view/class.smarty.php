<?php

/**
 * @deprecated
 */
class MVC_View_Smarty extends MVC_View {

	/**
	 * @var \SmartyWrapper
	 */
	protected $oSmarty;
	
	/**
	 * @var string
	 */
	protected $sTemplate = null;
	
	/**
	 * @param string $sExtension
	 * @param string $sController
	 * @param string $sAction
	 */
	public function __construct($sExtension, $sController, $sAction) {

		parent::__construct($sExtension, $sController, $sAction);

		$this->oSmarty = new \Core\Service\Templating;
		
	}
	
	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function set($sName, $mValue) {

		// Analog zu \Core\View\Smarty
		if(
			$sName === 'translation_path' ||
			$sName === 'sTranslationPath'
		) {
			$this->oSmarty->setTranslationPath($mValue);
		}

		$this->oSmarty->assign($sName, $mValue);

	}

	public function merge($sName, array $mValue) {

		$existing = $this->oSmarty->getTemplateVars($sName);

		$mValue = array_merge((array)$existing, $mValue);

		$this->oSmarty->assign($sName, $mValue);
	}

	/**
	 * @param string $sName
	 * @return mixed
	 */
	public function get($sName) {

		return $this->oSmarty->getTemplateVars($sName);
		
	}

	/**
	 * @param string $sTemplate
	 */
	public function setTemplate($sTemplate) {
		$this->sTemplate = $sTemplate;
	}

	/**
	 * Erzeugt die Ausgabe per Smarty
	 * @todo Caching implementieren
	 */
	public function render() {

		if($this->sTemplate !== null) {
			$sTemplate = Util::getDocumentRoot().$this->sTemplate;
		} else {
			$sTemplate = $this->buildTemplateName();
		}
		
		$this->oSmarty->display($sTemplate);

		die();
	}

	/**
	 * Baut sich den default Template-Pfad zusammen
	 * 
	 * @return string
	 */
	protected function buildTemplateName() {
		
		$oBundleHelper = new Core\Helper\Bundle();		
		$sBundle = $oBundleHelper->convertBundleName($this->_sExtension);
		
		$sBundleDir = $oBundleHelper->getBundleDirectory($sBundle);
		
		$sController = \Util::convertPascalCaseToHyphenLowerCase($this->_sController);
		
		$sAction = str_replace('Action', '', $this->_sAction);
		$sAction = \Util::convertPascalCaseToHyphenLowerCase($sAction);

		$sTemplate = $sBundleDir.'/Resources/views/'.$sController.'/'.$sAction.'.tpl';
		
		return $sTemplate;
	}

}
