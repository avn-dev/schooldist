<?php


abstract class Ext_TC_Pdf_Table_Element_Abstract extends Ext_TC_Pdf_Table_Abstract
{
	/**
	 *
	 * @var array
	 */
	protected $_aCells = array();
	
	/**
	 *
	 * @param Ext_TC_Pdf_Table_Element_Cell $oCell
	 * @return Ext_TC_Pdf_Table_Element_Abstract 
	 */
	public function addCell(Ext_TC_Pdf_Table_Element_Cell $oCell)
	{
		$this->_aCells[] = $oCell;
		
		return $this;
	}
	
	/**
	 *
	 * @return Ext_TC_Pdf_Table_Element_Cell <array> 
	 */
	public function getCells()
	{
		return $this->_aCells;
	}
	
	public function updateCells()
	{
		$aCells = (array)$this->getCells();
		
		foreach($aCells as $iCellKey => $oCell)
		{
			if($oCell->hasData('removed'))
			{
				unset($this->_aCells[$iCellKey]);
			}
		}
	}
}