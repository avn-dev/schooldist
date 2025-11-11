<?php

class Ext_Thebing_Examination_Sections_Entity_Checkbox extends Ext_Thebing_Examination_Sections_Entity_Int
{

	public function getInput()
	{
		return 'checkbox';
	}

	public function addValue(Ext_Gui2_Html_Abstract $oInput, $mValue)
	{
		if( 1 == (int)$mValue )
		{
			$oInput->checked = "checked";
		}

		return $oInput;
	}

	public function addOptions(Ext_Gui2_Html_Abstract $oInput)
	{
		$oInput->value = '1';
		return $oInput;
	}

	public function getStringValue()
	{
		if( 1 == $this->value)
		{
			return L10N::t('Ja');
		}
		else
		{
			return L10N::t('Nein');
		}
	}
}