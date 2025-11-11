<?php

abstract class Ext_Gui2_View_Style_Abstract implements Ext_Gui2_View_Style_Interface {

	static protected $aInstance = null;

	/**
	 * Returns the instance of an object
	 *
	 * @return object : The instance of this class
	 */
	static public function getInstance() {

		$sClass = 'Ext_Gui2_View_Style_Abstract';

		if(!isset(self::$aInstance[$sClass])) {
			try {
				if(empty($sClass)) {
					__pout(debug_backtrace(), 1);
				}
				self::$aInstance[$sClass] = new $sClass();
			} catch(Exception $e) {
				\Util::handleErrorMessage($e->getMessage());
			}
		}

		return self::$aInstance[$sClass];
	}

	public function getStyle($mValue, &$oColumn, &$aRowData){
		return ''; // background:#FFFFFF;
	}

}
