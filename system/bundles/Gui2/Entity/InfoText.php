<?php

namespace Gui2\Entity;

/**
 * @method static InfoTextRepository getRepository()
 */
class InfoText extends \WDBasic {
	
	protected $_sTable = 'gui2_dialog_infotexts';
	
	protected $_aJoinTables = [
		'i18n_texts' => [
			'table' => 'gui2_dialog_infotexts_i18n',
			'primary_key_field' => 'infotext_id',
			'foreign_key_field' => ['language', 'value'],
		]
	];
	
	public function getInfoTexts() {
		$aValues = [];
		foreach($this->i18n_texts as $aI18NText) {
			$aValues[$aI18NText['language']] = $aI18NText['value'];
		}
		return $aValues;
	}
	
	public function getInfoText($sLanguage) { 
		$aValues = $this->getInfoTexts();
		return (isset($aValues[$sLanguage])) ? $aValues[$sLanguage] : '';
	}

	public function setInfoText($sLanguage, $sValue) { 
		$aJointableData = $this->i18n_texts;
		$bFound = false;
		
		foreach($aJointableData as $iIndex => $aEntry) {
			if($aEntry['language'] === $sLanguage) {
				$aJointableData[$iIndex]['value'] = $sValue;
				$bFound = true;
				break;
			}
		}
		
		if(!$bFound && !empty($sValue)) {
			$aJointableData[] = [
				'language' => $sLanguage,
				'value' => $sValue
			];
		}
		
		$this->i18n_texts = $aJointableData;
	}
}

