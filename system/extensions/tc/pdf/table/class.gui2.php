<?php


class Ext_TC_Pdf_Table_Gui2 extends Ext_TC_Gui2
{
	/**
	 *
	 * @var Ext_TC_Pdf_Table
	 */
	protected $_oPdfTable;
	
	/**
	 *
	 * @param Ext_TC_Pdf_Table $oPdfTable 
	 */
	public function setPdfTable(Ext_TC_Pdf_Table $oPdfTable)
	{
		$this->_oPdfTable = $oPdfTable;
	}
	
	/**
	 *
	 * @return Ext_TC_Pdf_Table 
	 */
	public function getPdfTable()
	{
		return $this->_oPdfTable;
	}
}