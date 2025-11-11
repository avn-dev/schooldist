<?php

class Ext_Thebing_Examination_Sections_Entity_Int extends Ext_Thebing_Examination_Sections_Entity_Abstract
{
	// Tabellenname
	protected $_sTable = 'kolumbus_examination_sections_entity_int';

	protected $_aFormat = array(

		'value' => array(
			'validate' => 'INT_POSITIVE'
		),

	);

	public function getInput()
	{
		return 'input';
	}

	public function getEntityKey()
	{
		return 'entity_int';
	}
}