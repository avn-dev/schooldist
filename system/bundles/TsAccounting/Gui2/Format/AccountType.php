<?php

namespace TsAccounting\Gui2\Format;

class AccountType extends \Ext_Gui2_View_Format_Abstract {

	static public function getOptions() {
		
		$aAccountTypes = [
			'agency' => \L10N::t('Agentur'), 
			'group' => \L10N::t('Gruppe'),
			'contact' => \L10N::t('Kontakt'), 
			'sponsor' => \L10N::t('Sponsor')
		];
		
		return $aAccountTypes;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aAccountTypes = self::getOptions();
		
		return $aAccountTypes[$mValue];
	}

}
