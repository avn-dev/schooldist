<?php

/**
 * Formatklasse für mehr als ein Feld bei Ext_TC_Basic::getArrayListI18N()
 *
 * @see Ext_TC_Basic::getArrayListI18N()
 */
class Ext_TC_Gui2_Format_Select_I18N extends Ext_TC_Gui2_Format { 

	private $_aConcat = '';
	
	/**
	 * Der Konstruktur dient als Übergabe eines Art Concats im Array-Format
	 * 
	 * @param array $aConcat 
	 */
	public function __construct(array $aConcat)
	{
		$this->_aConcat = $aConcat;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aReturn = array();

		foreach($mValue as $aValue) {
			$sReturn = '';

			foreach($this->_aConcat as $sConcat)
			{

				if((mb_substr($sConcat, 0, 1)) === '#') {

					$sReturn .= $aValue[mb_substr($sConcat, 1)];

				} else {
					$sReturn .= $sConcat;
				}
			}
			
			$aReturn[$aValue['id']] = $sReturn;

		}
		
		return $aReturn;
		
	}

}
?>
