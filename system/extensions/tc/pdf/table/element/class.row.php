<?php


class Ext_TC_Pdf_Table_Element_Row extends Ext_TC_Pdf_Table_Element_Abstract
{
	protected function _getAllowedData()
	{
		$aAllowed = array(
			'title',
			'position',
		);
		
		return $aAllowed;
	}
}