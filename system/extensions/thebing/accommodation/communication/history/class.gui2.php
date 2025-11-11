<?php


class Ext_Thebing_Accommodation_Communication_History_Gui2 extends Ext_Thebing_Gui2_Data {


	public function getTranslations($sL10NDescription){

		$aData = parent::getTranslations($sL10NDescription);

		$aData['accommodation_cancelation'] = L10N::t('Unterkunftszuweisungen werden durch das Absagen gelöscht.', $sL10NDescription);

		return $aData;
	}
	

}
