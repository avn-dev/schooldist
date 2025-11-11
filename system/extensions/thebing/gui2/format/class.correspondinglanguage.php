<?php

class Ext_Thebing_Gui2_Format_CorrespondingLanguage extends Ext_Gui2_View_Format_Abstract {

	protected $locales = [];

	public function __construct()
	{
		$oLocaleService = new Core\Service\LocaleService;
		$this->locales = $oLocaleService->getInstalledLocales($this->_sLanguage);
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		return $this->locales[$mValue] ?? $mValue;
	}

}
