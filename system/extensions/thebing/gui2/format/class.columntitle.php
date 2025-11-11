<?php


class Ext_Thebing_Gui2_Format_ColumnTitle extends Ext_Gui2_View_Format_ToolTip
{
	protected $_sTitleColumn;
	protected $_bAsTooltip;
	protected $_mTruncate;

	public function __construct($sTitleColumn, $bAsTooltip=false, $mTruncate=false)
	{
		$this->_sTitleColumn	= $sTitleColumn;
		$this->_bAsTooltip		= $bAsTooltip;
		$this->_mTruncate		= $mTruncate; 
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		if($this->_mTruncate && $this->sFlexType == 'list') {
			$mValue = Ext_Gui2_Util::truncateString($mValue, $this->_mTruncate);
		}

		return $mValue;

	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {
		
		$aReturn = array();
		$aReturn['content'] = (string)$aResultData[$this->_sTitleColumn];
		$aReturn['tooltip'] = (bool)$this->_bAsTooltip;

		return $aReturn;

	}

}
