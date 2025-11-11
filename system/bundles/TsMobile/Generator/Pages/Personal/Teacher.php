<?php

namespace TsMobile\Generator\Pages\Personal;

use TsMobile\Generator\AbstractPage;

class Teacher extends AbstractPage {
	
	public function render(array $aData = array()) {

		/** @var \Ext_Thebing_Teacher $oTeacher */
		$oTeacher = $this->oApp->getUser();

		$sTemplate = $this->generatePageHeading($this->oApp->t('Personal data'));

		$oNationalityFormat = new \Ext_Thebing_Gui2_Format_Nationality($this->oApp->getInterfaceLanguage());
		$oCountryFormat = new \Ext_Thebing_Gui2_Format_Country($this->oApp->getInterfaceLanguage());
		$aLanguages	= \Ext_Thebing_Data::getLanguageSkills();
		$sMotherTongue = $aLanguages[$oTeacher->mother_tongue];

		$sTemplate .= '
			<div class="ui-body ui-body-a ui-corner-all">
				<strong>'.$this->t('Name').':</strong> '. $oTeacher->getName(). '<br>
				<strong>'.$this->t('Day of birth').':</strong> '. $this->formatDate($oTeacher->birthday). '<br>
				<strong>'.$this->t('E-Mail').':</strong> '. $oTeacher->email. '<br>
				<strong>'.$this->t('Nationality').': </strong>'.$oNationalityFormat->format($oTeacher->nationality).'<br>
				<strong>'.$this->t('Mother tongue').': </strong>'.$sMotherTongue.'<br>
			</div>
		';

		$sAddress = '<div class="ui-body ui-body-a ui-corner-all">';
		$sAddress .= $oTeacher->street.'<br>';
		$sAddress .= $oTeacher->zip.' '.$oTeacher->city.'<br>';
		$sAddress .= $oCountryFormat->format($oTeacher->country_id);
		$sAddress .= '</div>';
		$sTemplate .= $this->generatePageBlock($this->t('Addresses'), $sAddress);

		$aContactDetails = array(
			'Phone' => $oTeacher->phone,
			'Phone (Office)' => $oTeacher->phone_business,
			'Cellphone' => $oTeacher->mobile_phone,
			'Fax' => $oTeacher->fax,
			'E-mail' => $oTeacher->email,
			'Skype' => $oTeacher->skype
		);

		$sContactDetails = '<div class="ui-body ui-body-a ui-corner-all">';
		foreach($aContactDetails as $sLabel => $mValue) {
			if(!empty($mValue)) {
				$sContactDetails .= '<strong>'.$this->t($sLabel).'</strong>: '.$mValue.'<br>';
			}
		}
		$sContactDetails .= '</div>';

		$sTemplate .= $this->generatePageBlock($this->t('Contact details'), $sContactDetails);

		return $sTemplate;
	}
	
}