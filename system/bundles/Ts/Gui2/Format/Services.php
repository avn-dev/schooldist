<?php

namespace Ts\Gui2\Format;

class Services extends \Ext_Gui2_View_Format_Abstract
{

	public function format($value, &$column = null, &$resultData = null){

		$language = new \Tc\Service\Language\Backend(\System::getInterfaceLanguage());
		$language->setContext('Thebing » Inquiry');

		$options = \Ext_TS_Inquiry_Index_Gui2_Data::getServiceOptionsForIndex();

		return $options[$value];

	}
}