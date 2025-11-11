<?php

class Ext_Thebing_Gui2_Format_Input extends Ext_Gui2_View_Format_Abstract {

	protected $_iWidth;
	
	protected $_sType;

	protected $_sInputClass = 'txt';

	public function  __construct($iWidth = false, $sInputClass=null, $sType = 'text') {
		
		$this->_iWidth = $iWidth;
		
		$this->_sType = $sType;

		if($sInputClass !== null) {
			$this->_sInputClass = $sInputClass;
		}
		
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		if($this->sFlexType != 'list')
		{
			return $mValue;
		}
		
		$iRowId = (int)$aResultData['id'];
		
		$iWidth	= $this->_iWidth;
		
		$sSelectColumn = $oColumn->select_column;

		if(!empty ($sSelectColumn))
		{
			$sName	= $sSelectColumn;
		}
		else
		{
			$sName	= $oColumn->db_column;
		}
		
		$sId	= $sName;
		
		$sName .= '['.$iRowId.']';
		
		$sId	.= '_'.$iRowId;

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'row_input';

		$oInput = new Ext_Gui2_Html_Input();
		$oInput->type = $this->_sType;
		
		if(!empty($iWidth))
		{
			$oInput->style = 'width: '.(int)$this->_iWidth.'px';
		}
		
		$oInput->class = $this->_sInputClass;

		$oInput->name = $sName;
		$oInput->id = $sId;

		if($oInput->type == 'checkbox')
		{
			$mValue = (int)$mValue;

			if($mValue === 1)
			{
				$oInput->checked = 'checked';
			}
			
			$oInput->value = 1;
		}
		else
		{
			$oInput->value = $mValue;
		}

		$oDiv->setElement($oInput); 

		return $oDiv->generateHTML();

	}

}