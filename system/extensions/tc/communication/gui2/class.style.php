<?php

/**
 * Kommunikation Tab Verlauf Row Style
 */
class Ext_TC_Communication_Gui2_Style extends Ext_Gui2_View_Style_Abstract {

	public function __construct (
		private ?string $type = null
	) {}

	public function getStyle($mValue, &$oColumn, &$aRowData) {
		
		$oMessage = Ext_TC_Factory::getInstance('Ext_TC_Communication_Message', $aRowData['id']);
		$sReturn = '';
		
		if($oMessage->id > 0) {
			
			$iCategoryId = $oMessage->category_id;
			$oCategory = Ext_TC_Communication_Category::getInstance($iCategoryId);
			
			if($oCategory->id > 0) {
				$sReturn .= 'background-color: '.$oCategory->code.'; ';
			} else if ($this->type !== 'spool' && !$oMessage->isSent()) {
				$sReturn .= 'color: '.\Ext_TC_Util::getColor('inactive');
			}

			if($oMessage->isUnseen()) {
				$sReturn .= 'font-weight: bold; ';
			}
			
		}

		return $sReturn;

	}

}
