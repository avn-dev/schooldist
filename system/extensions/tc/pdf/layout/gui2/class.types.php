<?php

class Ext_TC_Pdf_Layout_Gui2_Types extends Ext_Gui2_View_Selection_Abstract {

	protected $_aTypes = array();
	
	public function __construct($aTypes) {
		$this->_aTypes = $aTypes;
	}
	
	/**
	 * Gibt die Typen zurück
	 * Prüft, ob main_text schon verwendet wurde und entfernt es eventuell
	 * @param type $aSelectedIds
	 * @param type $aSaveField
	 * @param type $oWDBasic
	 * @return type 
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = $this->_aTypes;
		
		$sSql = "	
					SELECT 
						`id` 
					FROM
						`tc_pdf_layouts_elements`
					WHERE
						`layout_id` = :layout_id AND
						`id` != :element_id AND
						`element_type` = 'main_text' AND
						`active` = 1
					LIMIT 1";
		$aSql = array(
			'layout_id'=>(int)$oWDBasic->layout_id,
			'element_id'=>(int)$oWDBasic->id
		);
		$aResult = DB::getQueryRow($sSql, $aSql);

		// Wenn main_text schon vorkommt
		if(!empty($aResult)) {
			unset($aOptions['main_text']);
		}

		return $aOptions;

	}

}
