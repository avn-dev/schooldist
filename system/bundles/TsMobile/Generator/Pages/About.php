<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class About extends AbstractPage {
	
	public function render(array $aData = array()) {
		
		$sTemplate = $this->generatePageHeading($this->oApp->t('About…'));
		
		$oSchool = $this->oApp->getSchool();
		/* @var $oSchool \Ext_Thebing_School */
		
		$oCountryFormat = new \Ext_Thebing_Gui2_Format_Country($this->_sInterfaceLanguage);
		
		$sSchool = 
			$oSchool->address.' <br>
			'.$oSchool->zip.' '.$oSchool->city.' <br>
			'.$oCountryFormat->format($oSchool->country_id).' <br><br>
			'.$oSchool->phone_1.' <br>
			'.$oSchool->email;
		
		$sTemplate .= $this->generateBlock($oSchool->getName(), $sSchool);
		
		$sPublisher = '<p>
			Fidelo Software GmbH<br>
			Buchheimer Ring 87<br>
			51067 Cologne<br>
			Germany</p>
		';
		
		$sTemplate .= $this->generatePageBlock($this->t('App is provided by…'), $sPublisher);

		return $sTemplate;
	}
	
}