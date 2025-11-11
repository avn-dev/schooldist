<?php

class Ext_Thebing_Examination_Sections_Entity_Text extends Ext_Thebing_Examination_Sections_Entity_Abstract
{
	// Tabellenname
	protected $_sTable = 'kolumbus_examination_sections_entity_text';

	public function getInput()
	{
		return 'textarea';
	}

	public function getEntityKey()
	{
		return 'entity_text';
	}

	public function  addValue(Ext_Gui2_Html_Abstract $oInput, $mValue)
	{
		if(!empty($mValue))
		{
			$oInput->setElement($mValue);
		}

		return $oInput;
	}
}