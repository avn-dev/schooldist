<?php


class Ext_Thebing_Gui2_Style_Until extends Ext_Gui2_View_Style_Abstract
{
	public $iCount;
	public $sDiffPart;
	public $sColor;

	protected static $_aDateCache = array();


	public function __construct($sColor = '', $sDiffPart = '', $iCount = 0) {
		$this->sColor		= $sColor;
		$this->sDiffPart	= $sDiffPart;
		$this->iCount		= $iCount;		
	}
	
	public function getStyle($mValue, &$oColumn, &$aRowData)
	{
		$sStyle		= '';
		$sColor		= $this->sColor;
		$sDiffPart	= $this->sDiffPart;
		$iCount		= (int)$this->iCount;

		// Index
		if(
			!empty($mValue) &&
			!$this->_checkDate($mValue, WDDate::DB_DATE) &&
			!empty($aRowData[$oColumn->select_column . '_original'])
		) {
			$sTemp = $aRowData[$oColumn->select_column . '_original'];
			$mValue = substr($sTemp, 0, 10);
		}
		
		if(
			!empty($sColor) &&
			!empty($sDiffPart) &&
			!empty($iCount) &&
			$mValue != '0000-00-00' &&
			$this->_checkDate($mValue, WDDate::DB_DATE)
		)
		{
			$oCurrentWdDate	= new WDDate();
			$iNow			= $oCurrentWdDate->get(WDDate::TIMESTAMP);
			$oWdDate		= new WDDate($mValue, WDDate::DB_DATE);

			$iDiff = (int)$oWdDate->getDiff(WDDate::DAY, $iNow, WDDate::TIMESTAMP);
			if($iDiff<=$iCount)
			{
				$sStyle .= 'background: '.$sColor.'; ';
			}
						
		}
		
		return $sStyle;
	}
	
	/**
	 * Datumsprüfung
	 * @param mixed $mValue
	 * @param string $sPart
	 * @return boolean
	 */
	protected function _checkDate($mValue, $sPart) {
		
		if(empty(self::$_aDateCache[$mValue])) {
			$bCheck = (bool) WDDate::isDate($mValue, $sPart);
			self::$_aDateCache[$mValue] = $bCheck;
		}
		
		return self::$_aDateCache[$mValue];
	}
	
}