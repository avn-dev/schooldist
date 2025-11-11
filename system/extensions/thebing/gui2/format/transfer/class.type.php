<?php

/**
 * @see \Ts\Gui2\Format\TransferMode
 */
class Ext_Thebing_Gui2_Format_Transfer_Type extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$mLanguage = new \Tc\Service\Language\Backend(System::getInterfaceLanguage());
		$mLanguage->setContext('Thebing » Transfer');

		$aOptions = [
			Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL => $mLanguage->translate('Individueller Transfer'),
			Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL => $mLanguage->translate('Anreise'),
			Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE => $mLanguage->translate('Abreise'),
		];

		return $aOptions[$mValue];

	}

}