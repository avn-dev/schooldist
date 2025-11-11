<?php

namespace Tc\Traits;

trait Placeholder {

	/**
	 * Name der Platzhalterklasse
	 *
	 * @var string
	 */
	//protected $_sPlaceholderClass = null;

	/**
	 * @var object
	 */
	protected $_oPlaceholderParent = null;
	
	/**
	 * @var object
	 */
	protected $_oPlaceholderChild = null;
	
	/**
	 * Setzt für die Platzhalterklasse die Elternentität
	 * 
	 * @param object $oParent
	 */
	public function setPlaceholderParentEntity(object $oParent) {
		$this->_oPlaceholderParent = $oParent;
	}

	/**
	 * @return object|null
	 */
	public function getPlaceholderParentEntity(): ?object {
		return $this->_oPlaceholderParent;
	}

	/**
	 * Setzt für die Platzhalterklasse das Childobjekt
	 * 
	 * @param object $oChild
	 */
	public function setPlaceholderChild(object $oChild) {
		$this->_oPlaceholderChild = $oChild;
	}
	
	/**
	 * Liefert für die Platzhalterklasse das Childobjekt
	 * 
	 * @return object|null
	 */
	public function getPlaceholderChildObject() {
		return $this->_oPlaceholderChild;
	}
	
	/**
	 * Gibt ein Kind-Objekt zurück
	 * 
	 * @param string $sLoop
	 * @param string $sSource
	 * @param int $iIndex
	 * @return object
	 * @throws \Exception
	 */
	public function getPlaceholderChild($sPlaceholder, $iIndex = 0, $bCreateEmpty = true) {
		
		if(!is_numeric($iIndex)) {
			$iIndex = 0;
		}

		$oPlaceholder = $this->getPlaceholderObject();
		$oPlaceholder->setFlexiblePlaceholder();
		$aPlaceholder = $oPlaceholder->getPlaceholder($sPlaceholder);
		
		$aChilds = array();
		$oChild = null;
		
		\Ext_TC_Placeholder_Abstract::getBasicLoopObjects($this, $aPlaceholder, $aChilds, $oChild, $bCreateEmpty);

		$aChilds = array_values($aChilds);
		
		if(isset($aChilds[$iIndex])) {
			return $aChilds[$iIndex];
		} else {
			throw new \Ext_TC_Exception('Placeholder child "'.$sPlaceholder.'" with index "'.$iIndex.'" is not defined.', 'PLACEHOLDER_CHILD_WITH_INDEX_NOT_DEFINED');
		}

	}
	
	/**
	 * Gibt ein Array mit Kind-Objekten zurück
	 * 
	 * @param string $sLoop
	 * @param string $sSource
	 * @return array
	 * @throws \Exception
	 */
	public function getPlaceholderChilds($sPlaceholder, $bCreateEmpty = true) {
		
		$aChilds = array();
		$oChild = null;
		
		$oPlaceholder = $this->getPlaceholderObject();
		$oPlaceholder->setFlexiblePlaceholder();
		$aPlaceholder = $oPlaceholder->getPlaceholder($sPlaceholder);

		\Ext_TC_Placeholder_Abstract::getBasicLoopObjects($this, $aPlaceholder, $aChilds, $oChild, $bCreateEmpty);

		$aChilds = array_values($aChilds);

		if(isset($aChilds)) {
			return $aChilds;
		} else {
			throw new \Ext_TC_Exception('Placeholder childs "'.$sPlaceholder.'" are not defined.', 'PLACEHOLDER_CHILDS_NOT_DEFINED');
		}

	}
	
	/**
	 * Gibt ein Eltern-Objekt zurück
	 * 
	 * @param string $sParent
	 * @param string $sSource
	 * @return \self
	 * @throws \Exception
	 */
	public function getPlaceholderParent($sParent, $sSource, $sClass = '') {

		$oParent = null;
		
		\Ext_TC_Placeholder_Abstract::getBasicLoopParent($this, ['parent' => $sParent, 'source' => $sSource, 'class' => $sClass], $oParent);

		if(is_object($oParent)) {
			return $oParent;
		} else {
			throw new \Exception('Placeholder parent "'.$sParent.'" / "'.$sSource.'" is not defined. ("'.get_class($this).'")');
		}

	}

	/**
	 * Prüft ob ein Platzhalterobjekt verfügbar ist und gibt es zurück
	 *
	 * @return \Ext_TC_Placeholder_Abstract
	 */
	public function getPlaceholderObject(\SmartyWrapper $oSmarty=null) {

		$sPlaceholderClass = $this->getPlaceholderClass();

		if($sPlaceholderClass !== null) {
			/* @var $oReturn \Ext_TC_Placeholder_Abstract */
			return new $sPlaceholderClass($this, $oSmarty);
		}

		return null;
	}
	
	/**
	 * Gibt den Namen der Platzhalterklasse zurück
	 *
	 * @return string
	 */
	public function getPlaceholderClass() {
		
		if(
			isset($this->_sPlaceholderClass) &&
			$this->_sPlaceholderClass !== null
		) {
			return $this->_sPlaceholderClass;
		}

		return null;
	}
	
}
