<?php


class Ext_Thebing_Examination_Sections_Entity_Decimal extends Ext_Thebing_Examination_Sections_Entity_Abstract
{
	// Tabellenname
	protected $_sTable = 'kolumbus_examination_sections_entity_decimal';

	protected $_aFormat = array(

		'value' => array(
			'validate' => 'FLOAT'
		),

	);

	public function getInput()
	{
		return 'input';
	}

	public function getEntityKey()
	{
		return 'entity_decimal';
	}

	public function getFormat($mValue)
	{
		if(empty($mValue))
		{
			return $mValue;
		}
		
		return Ext_Thebing_Format::Number($mValue, null, 0, false);
	}

	public function  __set($sName, $mValue)
	{
		if( 'value' == $sName )
		{
			$this->_aData['value'] = Ext_Thebing_Format::convertFloat($mValue);
		}
		else
		{
			return parent::__set($sName, $mValue);
		}

	}

	public function setValue($mValue)
	{
		$mValueNew	= Ext_Thebing_Format::convertFloat($mValue);
		
			$this->_aData['value'] = $mValueNew;
		}

	public function getStringValue()
	{
		return $this->getFormat($this->value);
	}

}