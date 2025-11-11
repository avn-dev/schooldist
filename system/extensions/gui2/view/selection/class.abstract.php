<?php

abstract class Ext_Gui2_View_Selection_Abstract implements Ext_Gui2_View_Selection_Interface {

	public $sJoinedObjectKey;
	public $iJoinedObjectId;
	public $oJoinedObject;
	public $iJoinedObjectKey;

	/**
	 * @var Ext_Gui2
	 */
	protected $_oGui;

	protected $_aConfig = array();

	/**
	 * @TODO Sinnvoller wäre es gewesen, wenn alle Daten einfach ins Objekt gesetzt werden und die Methode dann sogar per DI aufgerufen werden könnte
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = array();

		return $aOptions;

	}

	/**
	 * Gui übergeben
	 * @param Ext_Gui2 $oGui
	 */
	public function setGui(Ext_Gui2 $oGui) {
		$this->_oGui = $oGui;
	}

	/**
	 * bereitet die Select Options vor
	 * ggf wird das array umgeschrieben um manuelle selectionen zu ermöglichen
	 * @param array $aSelectOptions
	 * @return array 
	 */
	public function prepareOptionsForGui($aSelectOptions){
		$aOptions = array();
		
		foreach((array)$aSelectOptions as $mKey => $mValue) {

			$iSelected = 0;
			// Optional kann hier ein vordefinierter/vorselektierter Wert mit übergeben werden
			if(is_array($mValue)){
				$iSelected = (int)$mValue['selected'];
				$mValue = $mValue['text'];
			}

			$aOptions[] = array(
					'value' => $mKey, 
					'text' => $mValue, 
					'selected' => $iSelected
				);

		}

		return $aOptions;
	}
	
	/**
	 * get the options with an empty entry at first pos.
	 * @param array $aSelectedIds
	 * @param arrax $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array 
	 */
	public function getOptionsWithEmptyEntry($aSelectedIds, $aSaveField, &$oWDBasic){
		$aOptions = $this->getOptions($aSelectedIds, $aSaveField, $oWDBasic);
		return $this->addEmptyOption($aOptions);
	}
	
	public function addEmptyOption(array $aOptions) {
		$aFirst = array(''=>'');
		$aOptions = $aFirst + $aOptions;
		
		return $aOptions;
	}
	
	// get config
	public function __get($sName) {
		return $this->_aConfig[$sName];
	}
	
	// set config
	public function __set($sName, $mValue) {
		$this->_aConfig[$sName] = $mValue;
	}
	
	/**
	 * check if config exist
	 * @return boolean 
	 */
	public function checkConfig($sName){
		$mConfig = $this->_aConfig[$sName];
		if(!empty($mConfig)){
			return true;
		}
		return false;
	}

	public function resetJoinedObject() {
		$this->sJoinedObjectKey = null;
		$this->iJoinedObjectId = null;
		$this->oJoinedObject = null;
		$this->iJoinedObjectKey = null;
	}

}
