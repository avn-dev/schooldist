<?php

define('LANGUAGE', 'en');

mb_internal_encoding('UTF-8');
bcscale(5);

class thebing_frontend {

	function executeHook($strHook, & $mixInput) {
		global $_VARS, $strLanguage, $objWebDynamicsDAO, $system_data,$oL10N,$user_data;
		switch ($strHook) {

			case 'wdmail_send':

				Ext_TC_Communication_WDMail::manipulateClass($mixInput);
				
				break;

			case 'set_locale':

				Ext_Thebing_Util::getAndSetTimezone();

				break;
			case 'framework_logos':

				\Ext_Thebing_Util::getFrameworkLogosHook($mixInput);

				break;
			case 'system_color':

				$sSystemColor = Ext_Thebing_Util::getSystemColor();

				if(!empty($sSystemColor)) {
					$mixInput = $sSystemColor;
				}

				break;

			default :
				break;
		}
	}
}

Ext_Thebing_Util::setFactoryAllocations();

\System::wd()->addHook('wdmail_send', 'thebing');
\System::wd()->addHook('set_locale', 'thebing');
\System::wd()->addHook('framework_logos', 'thebing');
\System::wd()->addHook('system_color', 'thebing');
