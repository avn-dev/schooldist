<?php

namespace Ts\Gui2\Format;

/**
 * @see \Ext_Thebing_Gui2_Format_Transfer_Type
 */
class TransferMode extends \Ext_Gui2_View_Format_Abstract {

	public function format($value, &$column = null, &$resultData = null){

		$language = new \Tc\Service\Language\Backend(\System::getInterfaceLanguage());
		$language->setContext('Thebing » Transfer');

		$options = [
			\Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE => $language->translate('nicht gewünscht'),
			\Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL => $language->translate('Anreise'),
			\Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE => $language->translate('Abreise'),
			\Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH => $language->translate('An- und Abreise'),
		];

		return $options[$value];

	}

}
