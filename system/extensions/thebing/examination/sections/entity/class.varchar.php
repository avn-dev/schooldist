<?php

class Ext_Thebing_Examination_Sections_Entity_Varchar extends Ext_Thebing_Examination_Sections_Entity_Abstract
{
	// Tabellenname
	protected $_sTable = 'kolumbus_examination_sections_entity_varchar';

	public function getInput()
	{
		return 'input';
	}

	public function getEntityKey()
	{
		return 'entity_varchar';
	}
}