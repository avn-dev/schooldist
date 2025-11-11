<?php

class Ext_TC_GUI2_Format_Gender extends Ext_Gui2_View_Format_Abstract {

    public function __construct(
        private ?string $interface = null
    ) {}

    public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(empty($this->_sLanguage)) {
			$this->setLanguage(System::getInterfaceLanguage());
		}

        if(empty($this->interface)) {
            $this->interface = System::getInterface();
        }

		if($this->interface === 'frontend') {
			$oLanguageObject = new \Tc\Service\Language\Frontend($this->_sLanguage);
		} else {
			$oLanguageObject = new \Tc\Service\Language\Backend($this->_sLanguage);
		}

		$aSelection = Ext_TC_Util::getGenders(true, '', $oLanguageObject);

		$mValue = (string)$aSelection[$mValue];

		return $mValue;

	}

}
