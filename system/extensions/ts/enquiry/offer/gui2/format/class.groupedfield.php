<?php

class Ext_TS_Enquiry_Offer_Gui2_Format_Groupedfield extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var null
	 */
	protected $sFormatClass;

	/**
	 * @var string
	 */
	protected $sValue;

	/**
	 * @var string
	 */
	protected $sField;

	/**
	 * @var null|string
	 */
	protected $sRowSeperator = '{||}';

	/**
	 * @var null|string
	 */
	protected $sFieldSeperator = '{|}';

	/**
	 * @param string $sField
	 * @param null $sFormatClass
	 * @param null $sRowSeperator
	 * @param null $sFieldSeperator
	 */
	public function __construct($sField, $sFormatClass=null, $sRowSeperator=null, $sFieldSeperator=null) {

		$this->sFormatClass = $sFormatClass;
		$this->sField = $sField;
		if($sRowSeperator !== null) {
			$this->sRowSeperator = $sRowSeperator;
		}
		if($sFieldSeperator !== null) {
			$this->sFieldSeperator = $sFieldSeperator;
		}

	}

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$this->sValue = $mValue;

		$aData = $this->getArray($mValue);

		if($this->sFormatClass !== null) {
			
			$oFormat = new $this->sFormatClass();

			foreach($aData as &$sValue) {
				$sValue = $oFormat->format($sValue, $oColumn, $aResultData);
			}

		}
		
		$aData = array_unique($aData);
		
		$sReturn = implode('<br>', $aData);
		
		return $sReturn;
	}

	/**
	 * @param null $oColumn
	 * @return mixed
	 */
	public function align(&$oColumn = null) {
		
		if($this->sFormatClass !== null) {
			
			$oFormat = new $this->sFormatClass();

			$sReturn = $oFormat->align($oColumn);

			return $sReturn;
		}
		
	}

	/**
	 * @param string $sValue
	 * @return array
	 */
	protected function getArray($sValue) {

		$aItems = explode($this->sRowSeperator, $sValue);

		if(!empty($aItems)) {
			
			$aData = array();
			$aReturn = array();
			foreach($aItems as $iItem=>$sItem) {
				$aItem = explode($this->sFieldSeperator, $sItem);
				reset($aItem);
				$aData[$iItem] = array();
				if(is_numeric($this->sField)) {
					$aData[$iItem] = $aItem;
				} else {
					while($sKey = current($aItem)) {
						$sValue = next($aItem);
						$aData[$iItem][$sKey] = $sValue;
						next($aItem);
					}
				}

				$aReturn[$iItem] = $aData[$iItem][$this->sField];
			}

			return $aReturn;
		}
		
	}
	
}
