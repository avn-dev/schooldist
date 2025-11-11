<?php

/**
 * @see \Ts\Service\Import\Enquiry::processItem()
 */
class Ext_TS_Enquiry_Gui2_Icon_Visible extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if (
			$oElement->action === 'import' &&
			empty(Core\Handler\SessionHandler::getInstance()->get('sid'))
		) {
			return false;
		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);

	}

}
