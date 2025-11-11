<?php


class Ext_TC_Pdf_Table_WDBasic extends Ext_TC_Basic
{
	// Tabellenname
	protected $_sTable = 'tc_pdf_tables';

	// Format
	protected $_aFormat = array(
		'name' => array(
			'required' => true
		),
		'placeholder' => array(
			'required' => true
		),
	);

	//WDBasic Attribute
	protected $_aAttributes = array(
		'font_family' => array(
			'class'			=> 'WDBasic_Attribute_Type_Varchar',
		),
		'font_size' => array(
			'class'			=> 'WDBasic_Attribute_Type_Int',
		),
		'font_color' => array(
			'class'			=> 'WDBasic_Attribute_Type_Varchar',
			'required'		=> 1,
			'validate'		=> 'HEX_COLOR'
		),
		'color' => array(
			'class'			=> 'WDBasic_Attribute_Type_Varchar',
			'required'		=> 1,
			'validate'		=> 'HEX_COLOR'
		),
		'decimal_places' => array(
			'class'			=> 'WDBasic_Attribute_Type_Int',
			'required'		=> 1,
			'validate'		=> 'INT_NOTNEGATIVE',
		),
		'margin_left' => array(
			'class'			=> 'WDBasic_Attribute_Type_Float',
			'required'		=> 1,
			'validate'		=> 'FLOAT',
		),
		'margin_right' => array(
			'class'			=> 'WDBasic_Attribute_Type_Float',
			'required'		=> 1,
			'validate'		=> 'FLOAT',
		),
		'margin_top' => array(
			'class'			=> 'WDBasic_Attribute_Type_Float',
			'required'		=> 1,
			'validate'		=> 'FLOAT',
		),
		'margin_bottom' => array(
			'class'			=> 'WDBasic_Attribute_Type_Float',
			'required'		=> 1,
			'validate'		=> 'FLOAT',
		),
		'table_width' => array(
			'class'			=> 'WDBasic_Attribute_Type_Int',
			'required'		=> 1,
			'validate'		=> 'INT_POSITIVE',
		),
		'subtotal' => array(
			'class'			=> 'WDBasic_Attribute_Type_TinyInt',
		),
		'carry_over' => array(
			'class'			=> 'WDBasic_Attribute_Type_TinyInt',
		),
	);
	
	public function __get($sName)
	{
		switch($sName)
		{
			case 'default_table':
				$mValue = null;
				break;
			default:
				$mValue = parent::__get($sName);
		}
		
		return $mValue;
	}
	
	public function __set($sName, $mValue)
	{
		switch($sName)
		{
			case 'default_table':
				break;
			default:
				parent::__set($sName, $mValue);
		}
	}
	
	/**
	 *
	 * Neuen Standard-Platzhalternamen mit Counter generieren
	 * 
	 * @return string 
	 */
	public static function getDefaultPlaceholderName()
	{
		$sDefaultName	= 'ta_position_table_';
		$iLength		= strlen($sDefaultName) + 1;


		$sSql = "
			SELECT
				SUBSTRING(`placeholder`,:length) `placeholder_number`
			FROM
				`tc_pdf_tables`
			WHERE
				`placeholder` LIKE '%ta_position_table_%'
			ORDER BY
				`placeholder_number` DESC
		";
		
		$aSql = array(
			'length' => $iLength,
		);
		
		$iLastNumber = (int)DB::getQueryOne($sSql, $aSql);
		$iLastNumber++;
		
		$sDefaultName .= $iLastNumber;
		
		return $sDefaultName;
	}

}