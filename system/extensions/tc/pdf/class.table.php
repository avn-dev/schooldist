<?php


class Ext_TC_Pdf_Table extends Ext_TC_Pdf_Table_Abstract
{
	/**
	 *
	 * @var Ext_TC_Pdf_Table_Element_Row <array>
	 */
	protected $_aRows = array();
	
	/**
	 *
	 * @var Ext_TC_Pdf_Table_Element_Col <array>
	 */
	protected $_aCols = array();

	/**
	 *
	 * @return Ext_TC_Pdf_Table_Element_Row 
	 */
	public function createRow()
	{
		$oRow = new Ext_TC_Pdf_Table_Element_Row();
		
		return $oRow;
	}
	
	/**
	 *
	 * @return Ext_TC_Pdf_Table_Element_Col 
	 */
	public function createCol()
	{
		$oCol = new Ext_TC_Pdf_Table_Element_Col();
		
		return $oCol;
	}
	
	/**
	 *
	 * @param Ext_TC_Pdf_Table_Element_Row $oRow
	 * @return Ext_TC_Pdf_Table 
	 */
	public function addRow(Ext_TC_Pdf_Table_Element_Row $oRow, $sKey)
	{
		$this->_aRows[$sKey] = $oRow;
		
		return $this;
	}
	
	/**
	 *
	 * @param Ext_TC_Pdf_Table_Element_Col $oCell
	 * @return Ext_TC_Pdf_Table 
	 */
	public function addCol(Ext_TC_Pdf_Table_Element_Col $oCol, $sKey)
	{
		$this->_aCols[$sKey] = $oCol;
		
		return $this;
	}
	
	/**
	 *
	 * @return Ext_TC_Pdf_Table_Element_Col <array> 
	 */
	public function getCols()
	{
		return $this->_aCols;
	}
	
	/**
	 *
	 * @return Ext_TC_Pdf_Table_Element_Row <array> 
	 */
	public function getRows()
	{
		return $this->_aRows;
	}
	
	/**
	 *
	 * @param type $iPosCol
	 * @return Ext_TC_Pdf_Table_Element_Col
	 */
	public function getCol($sKey)
	{
		if(isset($this->_aCols[$sKey]))
		{
			$oCol = $this->_aCols[$sKey];
		}
		else
		{
			throw new Exception('Col ' . $sKey . ' not found!');
		}
		
		return $oCol;
	}
	
	/**
	 *
	 * @param type $iPosRow
	 * @return Ext_TC_Pdf_Table_Element_Row
	 */
	public function getRow($sKey)
	{
		if(isset($this->_aRows[$sKey]))
		{
			$oRow = $this->_aRows[$sKey];
		}
		else
		{
			throw new Exception('Row ' . $sKey . ' not found!');
		}
		
		return $oRow;
	}
	
	/**
	 *
	 * @return Ext_TC_Pdf_Table_Element_Cell 
	 */
	public function createCell()
	{
		$oCell = new Ext_TC_Pdf_Table_Element_Cell();
		
		return $oCell;
	}

	/**
	 *
	 * Zelle einfügen nach Positionsangaben Zeile/Spalte
	 * 
	 * @param type $iPosCol
	 * @param type $iPosRow
	 * @param Ext_TC_Pdf_Table_Element_Cell $oCell 
	 */
	public function addCell($sKeyCol, $sKeyRow, Ext_TC_Pdf_Table_Element_Cell $oCell)
	{
		$oCol = $this->getCol($sKeyCol);
		$oRow = $this->getRow($sKeyRow);
		
		$oCol->addCell($oCell);
		$oRow->addCell($oCell);
	}
	
	/**
	 *
	 * Spalte löschen
	 * 
	 * @param string $sColKey 
	 */
	public function removeCol($sColKey)
	{
		$oCol	= $this->getCol($sColKey);
		
		$aCells = (array)$oCol->getCells();
		
		foreach($aCells as $oCell)
		{
			$oCell->setData('removed', true);
		}
		
		$aRows = (array)$this->getRows();
		
		foreach($aRows as $oRow)
		{
			$oRow->updateCells();
		}
		
		unset($this->_aCols[$sColKey]);
	}
	
	protected function _getAllowedData()
	{
		$aAllowed = array(
		);
		
		return $aAllowed;
	}
}