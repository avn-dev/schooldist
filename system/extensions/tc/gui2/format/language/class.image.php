<?php

class Ext_TC_Gui2_Format_Language_Image extends Ext_TC_Gui2_Format {

	protected array $aLocales = [];

	public function __construct(private string $separator = ',') {
		
		$oLocaleService = new \Core\Service\LocaleService;
		$this->aLocales = $oLocaleService->getInstalledLocales();

	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(!empty($mValue)) {

			$aLanguages = explode($this->separator, $mValue);
			$aOutput = [];

			foreach ($aLanguages as $sIso) {
				if(!empty($this->aLocales[$sIso])) {
					$sLanguage = $this->aLocales[$sIso];
				} else {
					$sLanguage = $sIso;
				}

				$sFlagKey = $sIso;

				if(preg_match('/^([a-z]{2,3})_/', $sFlagKey, $aMatch) === 1) {
					$sFlagKey = strtolower($aMatch[1]);
				}

				$sFileLink = Util::getFlagIcon($sFlagKey, '');

				if(!empty($sFileLink)){
					$aOutput[] = '<img src="'.$sFileLink.'" alt="'.$sLanguage.'" title="'.$sLanguage.'" style="display: inline;" />';
				} else {
					Ext_TC_Util::reportError('Language image missing', [$sFileLink, $mValue, $sFlagKey]);
				}
			}

			return implode('&nbsp;', $aOutput);
		}

		return $mValue;
	}

	// bestimmt die ausrichtung dieser Formatierung
	public function align(&$oColumn = null){
		return 'center';
	}

}
