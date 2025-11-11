<?php

/**
 * »Bit-Matching«-Klasse für Selections
 * 
 * Die Idee dahinter ist, es simpel zu halten und eine feste Anzahl an möglichen Optionen mit Bitflags zu steuern.
 * Dies ist PHP-intern viel schneller und der Code ist auch viel weniger, sauberer und unredundanter.
 */
class Ext_TC_Gui2_View_Selection_BitModel extends Ext_Gui2_View_Selection_Abstract {

	protected $_iBits = 0;
	protected $_iDeniedBits = 0;
	
	/**
	 * Setzt ein Bit, solange es nicht verboten wurde
	 * @param int $iBit
	 * @param bool $bOverwrite Bricht die Sperre von deny
	 */
	protected function _setBit($iBit, $bOverwrite = false)
	{
		if(
			!($iBit & $this->_iDeniedBits) ||
			$bOverwrite === true
		) {
			$this->_iBits |= $iBit;
		}
	}
	
	/**
	 * Entfernt ein Bit
	 * @param int $iBit 
	 */
	protected function _removeBit($iBit)
	{
		$this->_iBits &= ~$iBit;
	}
	
	/**
	 * Entfernt ein Bit nach dem Schema: Einmal entfernt, immer entfernt
	 * @param type $iBit 
	 */
	protected function _removeAndDenyBit($iBit)
	{
		$this->_iBits &= ~$iBit;
		$this->_iDeniedBits |= $iBit;
	}
	
	/**
	 * Zum Überschreiben!
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		$aOptions = array();
		return $aOptions;
	}
	
}
