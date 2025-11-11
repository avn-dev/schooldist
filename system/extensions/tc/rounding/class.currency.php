<?php

class Ext_TC_Rounding_Currency extends Ext_TC_Currency {

	static protected $sClassName = 'Ext_TC_Rounding_Currency';
	
	private static $aInstance = null;
	
	protected $_sPrimaryColumn = 'iso4217';
	
	/**
	 * Table Alias
	 * @var string
	 */
	protected $_sTableAlias = 'd_c';

	/**
	 * @var Ext_TC_Rounding_Setting 
	 */
	protected $_oSetting;
	
	/**
	 *
	 * @param string $sIso
	 * @return Ext_TC_Rounding_Currency 
	 */
	static public function getInstance($iDataID = 0) {
 
		$mIso = $iDataID;

		if(is_array($mIso)) {
			$sIso = $mIso['iso4217'];
		} else {
			$sIso = $mIso;
		}
		
		$sClass = self::$sClassName;

		if (!isset(self::$aInstance[$sClass][$sIso])) {
			try {
				self::$aInstance[$sClass][$sIso] = new $sClass($mIso);
			} catch (Exception $e) {
				error(print_r($e, 1));
			}
		}

		return self::$aInstance[$sClass][$sIso];
		
	}

	/**
	 * Wrapper für _getSettingObject
	 * 
	 * @return Ext_TC_Rounding_Setting
	 */
	public function getSettingObject() {
		
		return $this->_getSettingObject();
		
	}
	
	/**
	 * Holt das Objekt mit den Rounding-Settings
	 * @return Ext_TC_Rounding_Setting 
	 */
	protected function _getSettingObject() {
		
		if($this->_oSetting === null) {

			$this->_oSetting = Ext_TC_Rounding_Setting::getInstance($this->iso4217);

			// Objekt ist noch leer
			if(
				$this->_oSetting->currency_iso == '' &&
				$this->iso4217 != ''
			) {
				$this->_oSetting->saveWithPrimary($this->iso4217);
			}

		}
		
		return $this->_oSetting;
		
	}
	
	/**
	 * Abgeleitet zum setzen der Rounding-Settings
	 * 
	 * @param string $sName
	 * @param mixed $mValue 
	 */
	public function __set($sName, $mValue) {

		if(
			$sName === 'invoice_precision' ||
			$sName === 'invoice_line_item_tax_precision' ||
			$sName === 'increment'
		) {
			
			$this->_getSettingObject();
			
			$this->_oSetting->$sName = $mValue;
			
		} else {
			parent::__set($sName, $mValue);
		}

	}
	
	/**
	 * Abgeleitet zum setzen der Rounding-Settings
	 * @param string $sName
	 * @return mixed 
	 */
	public function __get($sName) {
		
		if(
			$sName === 'invoice_precision' ||
			$sName === 'invoice_line_item_tax_precision' ||
			$sName === 'increment'
		) {
			
			$this->_getSettingObject();
			
			$mValue = $this->_oSetting->$sName;
			
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}
	
	/**
	 * Manipuliert den Query
	 * @param array $aSqlParts 
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		
		$aSqlParts['select'] .= ", `tc_rs`.*";
		
		$aSqlParts['from'] .= " LEFT JOIN `tc_rounding_settings` `tc_rs` ON `iso4217` = `tc_rs`.`currency_iso`";
		
	}
	
	/**
	 * Speichert auch das Setting-Objekt
	 * @param bool $bLog 
	 */
	public function save($bLog = true) {
		
		parent::save($bLog);

		if($this->_oSetting !== null) {
			$this->_oSetting->save($bLog);
		}
		
	}

	/**
	 * Rundet einen Betrag dieser Währung mit den entsprechenden Einstellungen
	 * @param float $fAmount
	 * @return float
	 */
	public function round($fAmount, $sPrecisionType='invoice') {
		 
		$oSetting = $this->_getSettingObject();

		switch($sPrecisionType) {
			case 'invoice_line_item_tax':
				$iPrecision = $oSetting->invoice_line_item_tax_precision;
				$iIncrement = 1;
				break;
			case 'invoice':
			default:
				$iPrecision = $oSetting->invoice_precision;
				$iIncrement = $oSetting->increment;
				break;
		}

		$fAmount = round($fAmount / $iIncrement, $iPrecision) * $iIncrement;

		return (float)$fAmount;

	}
	
}