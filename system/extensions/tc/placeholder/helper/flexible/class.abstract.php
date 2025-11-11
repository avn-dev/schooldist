<?php

abstract class Ext_TC_Placeholder_Helper_Flexible_Abstract {

	/**
	 * Platzhalter Cache
	 * @var array 
	 */
	protected $_aPlaceholderCache = array();
	
	/**
	 * aktuelles Smarty-Objekt
	 * @var SmartyWrapper 
	 */
	protected $_oSmarty = null;
	
	/**
	 * Objekte, auf die sich die Platzhalter beziehen sollen
	 * @var array 
	 */
	protected $_aObjects = array();

	/**
	 * gibt an, ob die Platzhalterdaten in das Smartyobjekt geschrieben werden sollen
	 * @var boolean 
	 */
	public $bAssignData = true;
	
	/**
	 * Konstruktor
	 * @param SmartyWrapper $oSmarty
	 */
	public function __construct($oSmarty) {
		$this->_oSmarty = $oSmarty;
	}
	
	/**
	 * Objekte auf die sich die Platzhalter beziehen sollen 
	 * @param array $aObjects
	 */
	public function setObjects($aObjects){
		$this->_aObjects = $aObjects;
	}
	
	/**
	 * Variable in Smarty zuweisen, sofern noch nicht vorhanden
	 * 
	 * @param string $sName
	 * @param mixed $mVariable 
	 */
	protected function _assignVariable($sName, &$mVariable) {
		if(
			$this->_oSmarty != null &&
			!isset($this->_oSmarty->tpl_vars[$sName])
		) {
			$this->_oSmarty->assign($sName, $mVariable);
		}
	}
	
	/**
	 * bearbeitet den verwendeten Platzhalter
	 * @param string $sPlaceholder
	 * @return string
	 */
	protected function _preparePlaceholder($sPlaceholder) {	

		$sTrim = trim($sPlaceholder);

		$sPlaceholder = str_replace(array('{', '}'), '', $sTrim);		
		$sFinalPlaceholder = str_replace(' ', '_', $sPlaceholder);
		
		return $sFinalPlaceholder;
	}

	/**
	 * liefert die Platzhalter 
	 */
	abstract public function getPlaceholder();
		
}
