<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WDExport {

	public static $sExcelDateFormat = null;
	
	public static $sSeparator = ';';

	public static function isNumericFormat($sNumeric) {

		if(!is_scalar($sNumeric)) {
			return false;
		}
		
		// strip unneeded chars
		$sNumeric = preg_replace('/[^0-9\,\.]/', '', $sNumeric);

		$bFormat1 = preg_match('/^([0-9]+)(\.[0-9]{3})*(\,[0-9]{1,4})?$/', $sNumeric);
		$bFormat2 = preg_match('/^([0-9]+)(\,[0-9]{3})*(\.[0-9]{1,4})?$/', $sNumeric);

		if($bFormat1 || $bFormat2) {
			return true;
		} else {
			return false;
		}

	}

	public static function exportXLSX($sName, &$aExport, $aSpecials = array(), $bSave = false) {

		// Create new PHPExcel object
		$oSpreadsheet = new Spreadsheet();

		// Set properties
		$oSpreadsheet->getProperties()->setCreator(System::d('project_name'))
									 ->setLastModifiedBy(System::d('project_name'))
									 ->setTitle($sName)
									 ->setSubject($sName);

		$aAutoColumn = array();
		$iRow = 1;

		foreach((array)$aExport as $aLine) {

			$bHighlight = false;

			if(
				is_array($aSpecials) &&
				array_key_exists('highlight_empty', $aSpecials) &&
				is_array($aSpecials['highlight_empty'])
			) {
				foreach($aSpecials['highlight_empty'] as $iCol) {
					if(empty($aLine[$iCol])) {
						$bHighlight = true;
					}
				}
			}

			$iCol = 0;
			foreach((array)$aLine as $mValue) {

				$sCell = Util::getColumnCodeForExcel($iCol).$iRow;

				$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
				$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL;
				$bSetBold = false;
				$bBold = false;
				$bSetItalic = false;
				$bItalic = false;

				if(
					is_array($aSpecials) &&
					isset($aSpecials['cell_format'][$iRow][$iCol]['format']) &&
					isset($aSpecials['cell_format'][$iRow][$iCol]['style'])
				) {

					$sFormat = $aSpecials['cell_format'][$iRow][$iCol]['format'];
					$sStyle = $aSpecials['cell_format'][$iRow][$iCol]['style'];

				} elseif(is_int($mValue)) {

					if($bHighlight) {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
					} else {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
					}
					$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER;
					$aAutoColumn[] = Util::getColumnCodeForExcel($iCol);

				} elseif(is_float($mValue)) {

					if($bHighlight) {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
					} else {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
					}
					$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
					$aAutoColumn[] = Util::getColumnCodeForExcel($iCol);

				} elseif(is_numeric($mValue)) {
					
					if($bHighlight) {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
					} else {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
					}
					$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
					$aAutoColumn[] = Util::getColumnCodeForExcel($iCol);
					
				} elseif(self::isNumericFormat($mValue)) {
					
					if($bHighlight) {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
					} else {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
					}
					$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL;
					$aAutoColumn[] = Util::getColumnCodeForExcel($iCol);

				} elseif($mValue instanceof \DateTime) {
					
					// Format nur einmal pro Request ermitteln
					if(self::$sExcelDateFormat === null) {						
						$oFormat = Factory::getObject('Ext_Gui2_View_Format_Date');
						$dTestDate = new DateTime('1990-12-31');
						$sTestDate = $oFormat->formatByValue($dTestDate);
						self::$sExcelDateFormat = str_replace(array('31','12','1990','90'), array('dd', 'mm', 'yyyy', 'yy'), $sTestDate);
					}

					$oSpreadsheet->getActiveSheet()->setCellValue($sCell, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($mValue));
					$oSpreadsheet->getActiveSheet()->getStyle($sCell)->getNumberFormat()->setFormatCode(self::$sExcelDateFormat);
					
					$iCol++;
					continue;
					
				} else {

					if($bHighlight) {
						$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
					}
					$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL;

				}

				if(
					is_array($aSpecials) &&
					isset($aSpecials['cell_format'][$iRow][$iCol]['bold'])
				) {
					$bSetBold = true;
					$bBold = (bool)$aSpecials['cell_format'][$iRow][$iCol]['bold'];
				}

				if(
					is_array($aSpecials) &&
					isset($aSpecials['cell_format'][$iRow][$iCol]['italic'])
				) {
					$bSetItalic = true;
					$bItalic = (bool)$aSpecials['cell_format'][$iRow][$iCol]['italic'];
				}

				$oSpreadsheet->getActiveSheet()->getCell($sCell)->setValueExplicit($mValue, $sFormat);
				$oSpreadsheet->getActiveSheet()->getStyle($sCell)->getNumberFormat()->setFormatCode($sStyle);

				if($bSetBold) {
					$oSpreadsheet->getActiveSheet()->getStyle($sCell)->getFont()->setBold($bBold);
				}

				if($bSetItalic) {
					$oSpreadsheet->getActiveSheet()->getStyle($sCell)->getFont()->setItalic($bItalic);
				}

				$iCol++;
			}

			$iMaxCols = max($iMaxCols, $iCol);
			$iRow++;

		}

		foreach($aAutoColumn as $sColumn) {
			$oSpreadsheet->getActiveSheet()->getColumnDimension($sColumn)->setAutoSize(true);
		}

		// Rename sheet
		$oSpreadsheet->getActiveSheet()->setTitle($sName);

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$oSpreadsheet->setActiveSheetIndex(0);

		$sExcelExt = 'xlsx';

		if($bSave) {

			Util::checkDir(\Util::getDocumentRoot().'storage/exports');

			$sName = str_replace('-', '_', $sName);
			$sFilename = \Util::getCleanFileName($sName).'.'.$sExcelExt;
			
			$oWriter = new Xlsx($oSpreadsheet);
			$oWriter->save(\Util::getDocumentRoot().'storage/exports/'.$sFilename);

			return $sFilename;

		} else {

			// Redirect output to a client`s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="'.\Util::getCleanFileName($sName).'.'.$sExcelExt.'"');
			header('Cache-Control: max-age=0');

			$oWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($oSpreadsheet, 'Xlsx');
			$oWriter->save('php://output');

			die();

		}

	}

	public static function exportCSV($sName, &$aExport) {

	    while(ob_get_level() > 0) {
			ob_end_clean();
		}

		$sName = str_replace('-', '_', $sName);
		$sFilename = \Util::getCleanFileName($sName).'.csv';

		header('Content-Disposition: inline; filename="'.$sFilename.'"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: text/x-comma-separated-values');

		$sSeparator = self::$sSeparator;

		$sLine = "";

		foreach((array)$aExport as $aLine) {

			foreach((array)$aLine as $mValue) {

				if(is_numeric($mValue)) {
					$mValue = ''. strip_tags($mValue);
				} else {
					$mValue = iconv('UTF-8', 'cp1252//IGNORE', $mValue);
					$mValue = str_replace(array('<br/>', '<br>'), chr(10).chr(13), $mValue);
					$mValue = '"'. strip_tags($mValue);
				}

			}

			$sLine .= implode($sSeparator, $aLine)."\n";

		}

		echo $sLine;
		die();

	}

}
