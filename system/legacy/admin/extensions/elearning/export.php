<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess("elearning_exam_reports");

$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);

$oExam->getResultSummary();
$aParticipantSummary = $oExam->getParticipantSummary();

// Create new PHPExcel object
$oPHPExcel = new PHPExcel();

// Set properties
$oPHPExcel->getProperties()->setCreator($system_data['project_name'])
							 ->setLastModifiedBy($system_data['project_name'])
							 ->setTitle($sName)
							 ->setSubject($sName);

$oPHPExcel->removeSheetByIndex(0);

$aTabs = array(
	'failed' => 'Komplett durchgefallen (ersten und zweiten Durchlauf teilgenommen und nicht bestanden)',
	'succeded' => 'Bestanden',
	'not_succeded' => 'Noch nicht bestanden (nicht teilgenommen oder durchgefallen)',
	'not_participated' => 'Nicht teilgenommen (erste oder zweite Einladung ist noch offen)',
	'second_not_participated' => 'Beim 2. Durchlauf nicht teilgenommen (die zweite Einladung ist noch offen)'
);

$iSheetId = 0;
foreach($aParticipantSummary as $sType=>$aParticipants) {
	
	if(empty($aParticipants)) {
		continue;
	}
	

	$oSheet = $oPHPExcel->createSheet($iSheetId);
	$oPHPExcel->setActiveSheetIndex($iSheetId);
	
	$oSheet->setTitle(ucfirst($sType));
	
	$aFirstParticipants = reset($aParticipants);

	$iRow = 1;
	$aColumns = array();

	$oSheet->getCell('A1')->setValueExplicit($aTabs[$sType]);
	$oSheet->getStyle('A1')->getFont()->setBold(true);
	$iRow += 2;

	$iCol = 0;
	foreach($aFirstParticipants as $sField=>$sValue) {

		$sColumn = Util::getColumnCodeForExcel($iCol);
		$sCell = $sColumn.$iRow;

		$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL;

		$sField = str_replace('_', ' ', $sField);
		
		$oSheet->getCell($sCell)->setValueExplicit(ucwords($sField), $sFormat);
		$oSheet->getStyle($sCell)->getNumberFormat()->setFormatCode($sStyle);

		$aColumns[] = $sColumn;
		
		$iCol++;   
	}
	
	$iRow++;

	foreach($aParticipants as $aParticipant) {

		$iCol = 0;
		foreach($aParticipant as $sField=>$sValue) {

			$sColumn = Util::getColumnCodeForExcel($iCol);
			$sCell = $sColumn.$iRow;

			$sFormat = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
			$sStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL;

			$oSheet->getCell($sCell)->setValueExplicit($sValue, $sFormat);
			$oSheet->getStyle($sCell)->getNumberFormat()->setFormatCode($sStyle);

			$iCol++;
		}

		$iRow++;
	}

	foreach($aColumns as $sColumn) {
		$oSheet->getColumnDimension($sColumn)->setAutoSize(true);
	}

	$iSheetId++;
	
}

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$oPHPExcel->setActiveSheetIndex(0);

$sExcelType = 'Excel2007';
$sExcelExt = 'xlsx';

// Redirect output to a client`s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.\Util::getCleanFileName($oExam->name).'_'.date('YmdHis').'.'.$sExcelExt.'"');
header('Cache-Control: max-age=0');

// Ausgabe
$objWriter = PHPExcel_IOFactory::createWriter($oPHPExcel, $sExcelType);
$objWriter->save('php://output');