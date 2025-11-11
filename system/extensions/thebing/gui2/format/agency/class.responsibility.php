<?php
class Ext_Thebing_Gui2_Format_Agency_Responsibility extends Ext_Gui2_View_Format_Abstract
{
	protected $_sDescriptionPart;

	public function __construct()
	{
		$this->_sDescriptionPart = Ext_Thebing_Agency_Gui2::getDescriptionPart();
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$sReturn = '';

		$aFields = array(
		    L10N::t('Transfer',		$this->_sDescriptionPart) => $aResultData['transfer'],
			L10N::t('Unterkunft',	$this->_sDescriptionPart) => $aResultData['accommodation'],
			L10N::t('Mahnung',		$this->_sDescriptionPart) => $aResultData['reminder'],
		);

		foreach( $aFields as $sTitle => $iChecked )
		{
			if( 1 == $iChecked )
			{
				$sReturn .= $sTitle.', ';
			}
		}

		$sReturn = rtrim($sReturn, ', ');

		return $sReturn;
	}

}