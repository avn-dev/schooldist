<?php

class Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo extends Ext_Gui2_View_Format_Abstract
{

	protected $_sTitle;
	
	protected $_sDescription = 'Thebing » Invoice » Inbox';
	
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{

		if(empty($mValue)) {
			$this->_sTitle = '';
			return '';
		}
		
		$aLines = explode('{||}', $mValue);
		
		$aReturn = array();
		$aTitle = array();
		
		foreach($aLines as $iLine=>$sLine) {
			
			$aToolTip = array();
			$aLine = explode('{|}', $sLine);
			
			$aReturn[$iLine] = $aLine[0].' | '.$aLine[1];
			
			$aToolTip[] = '<strong>'.$aLine[0].'</strong>';
			$aToolTip[] = L10N::t('Raum',$this->_sDescription).': '.$aLine[1];
			$aToolTip[] = $aLine[2];
			$aToolTip[] = $aLine[3].' '.$aLine[4]; 
			$aToolTip[] = L10N::t('Telefon',$this->_sDescription).': '.$aLine[5];
			$aToolTip[] = L10N::t('Telefon',$this->_sDescription).': '.$aLine[6];
			$aToolTip[] = L10N::t('Handy',$this->_sDescription).': '.$aLine[7];
			
			$aTitle[$iLine] = implode('<br />', $aToolTip);
			
		}
		
		$sReturn = implode('<br />', $aReturn);
		$this->_sTitle = implode('<br />', $aTitle);
		
		return $sReturn;

	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		$aReturn['content'] = (string)$this->_sTitle;
		$aReturn['tooltip'] = true;

		return $aReturn;

	}

}
?>
