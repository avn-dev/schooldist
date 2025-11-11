<?php

/**
 * Erzeugt einen ToolTip, welcher auch auf die Fensterhöhe reagiert
 *
 */

class Ext_Gui2_View_Format_ToolTip extends Ext_Gui2_View_Format_Abstract
{
	protected $_sTitleColumn;
	protected $_bAsTooltip;
	protected $_mTruncate;
	protected $_bUseOriginalData;

	public function __construct($sTitleColumn, $bAsTooltip=false, $mTruncate=false, $bUseOriginalData=false)
	{
		$this->_sTitleColumn		= $sTitleColumn;
		$this->_bAsTooltip			= $bAsTooltip;
		$this->_mTruncate			= $mTruncate;
		$this->_bUseOriginalData	= $bUseOriginalData;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			is_numeric($this->_mTruncate) &&
			$this->sFlexType == 'list' // beim export soll alles exportiert werden
		) {
			$mValue = Ext_Gui2_Util::truncateString($mValue, $this->_mTruncate);
		}

		return $mValue;

	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {

		if(
			$this->_bUseOriginalData &&
			isset($aResultData[$this->_sTitleColumn.'_original']) &&
			!is_null($aResultData[$this->_sTitleColumn.'_original'])
		) {
			$mToolTip = $aResultData[$this->_sTitleColumn.'_original'];
		} else {
			$mToolTip = $aResultData[$this->_sTitleColumn] ?? null;
		}

		// Wenn kein Inhalt gefunden, dann auf Spalte zugreifen (wichtig bei Mehrsprachigkeit)
		if($mToolTip === null) {
			if(
				$this->_bUseOriginalData &&
				isset($aResultData[$oColumn->db_column.'_original']) &&
				!is_null($aResultData[$oColumn->db_column.'_original'])
			) {
				$mToolTip = $aResultData[$oColumn->db_column.'_original'];
			} else {
				$mToolTip = $aResultData[$oColumn->db_column] ?? null;
			}			
		}

		if(is_array($mToolTip)) {
			$mToolTip = implode('<br /><br />', $mToolTip);
		}
		
		$aReturn = array();
		$aReturn['content'] = (string)$mToolTip;
		$aReturn['tooltip'] = (bool)$this->_bAsTooltip;

		return $aReturn;

	}

}
