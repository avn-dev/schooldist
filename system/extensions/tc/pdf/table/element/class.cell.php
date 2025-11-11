<?php


class Ext_TC_Pdf_Table_Element_Cell extends Ext_TC_Pdf_Table_Abstract
{
	protected function _getAllowedData()
	{
		$aAllowed = array(
			'width',
			'removed',
		);
		
		return $aAllowed;
	}
}