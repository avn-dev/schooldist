<?php
 
namespace Gui2\Service\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Excel extends ExportAbstract {

	protected $sExtension = 'xlsx';

	protected $oSpreadsheet;
	
	protected $oWriter;
	
	protected $iRow = 0;

	public function __construct(string $sFilename) {
		
		parent::__construct($sFilename);

		$this->oSpreadsheet = new Spreadsheet;
		$oProperties = $this->oSpreadsheet->getProperties();
		
		$oProperties->setTitle($this->sTitle);
		
		$oAccess = \Access_Backend::getInstance();
		
		if($oAccess instanceof \Access_Backend) {
			$oUser = $oAccess->getUser();
			$oProperties->setCreator($oUser->getName());
		}

	}
	
	public function sendHeader() {
		global $_VARS;

		if(!isset($_VARS['gui_debugmode'])) {

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="'.$this->sFilename.'"');
			header('Cache-Control: max-age=0');
			
		} else {
			__pout('Warning: CSV export as MIME text/html!');
		}

	}
    
    /**
     * erzeugt die Export Zeile
     * @param array $aLine
     * @return string 
     */
	public function getLine($aLine, $aResultData=null) {

		$this->iRow++;
		
		$oWorksheet = $this->oSpreadsheet->getActiveSheet();

		$iCol = 1;
		foreach((array)$aLine as $iColumn=>$aValue) {
			
			if(array_key_exists('text', $aValue)) {
				$mValue = $aValue['text'];
			} else {
				// Sonst header
				$mValue = $aValue['title'];
			}
			
			$mValue = $this->prepareValue($mValue);
			
			$oColumn = $this->aColumnList[$iColumn];
			
			$oCell = $oWorksheet->getCell([$iCol++, $this->iRow]);
			
			if(
				$this->iRow > 1 &&
				$oColumn->format instanceof \Ext_Gui2_View_Format_Interface
			) {
				$oColumn->format->setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData);
			} else {
				$oCell->setValueExplicit(
					$mValue,
					\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
				);
			}
		
		}

		if($this->iRow === 1) {
			
			$sColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($iCol-1);
			
			$oWorksheet->getStyle('A1:'.$sColumn.'1')->getFont()->setBold(true);

		}

	}
  
	/**
	 * schließt den CSV Export ab
	 * setzt die INI Werte zurück und gibt schließt den OB 
	 */
	public function end() {
		
		// Erste Zeile fixieren
		$this->oSpreadsheet->getActiveSheet()->freezePane('A2');
		
		$this->oWriter = new Xlsx($this->oSpreadsheet);
		
		parent::end();
		
		die();
	}
	
}