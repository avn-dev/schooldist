<?php

/**
 * Formatklasse für Adressen in der History
 */
class Ext_TC_Communication_Gui2_Format_Addresses extends Ext_Gui2_View_Format_Abstract {

	protected $_sType;
	protected $_bLong;
	protected $_bShowType;
	
	public function __construct($sType, $bLong=true, $bShowType=false) {
		$this->_sType = $sType;
		$this->_bLong = (bool)$bLong;
		$this->_bShowType = (bool)$bShowType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$oMessage = Ext_TC_Factory::getInstance('Ext_TC_Communication_Message', (int)$aResultData['id']);
		$sReturn = $oMessage->getFormattedContacts($this->_sType, $this->_bLong, $this->_bShowType);
		return \Util::getEscapedString($sReturn, 'htmlall');
		
	}
	
	/**
	 * Tooltip mit kompletten Adressen
	 * 
	 * @param string $oColumn
	 * @param string $aResultData
	 * @return boolean 
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$mValue = $aResultData[$oColumn->db_column] ?? null;

		$oMessage = Ext_TC_Factory::getInstance('Ext_TC_Communication_Message', (int)$mValue);
		$sReturn = $oMessage->getFormattedContacts($this->_sType, true);

		$aReturn = array();
		$aReturn['content'] = (string)\Util::getEscapedString($sReturn, 'htmlall');
		$aReturn['tooltip'] = true;

		return $aReturn;

	}

}
