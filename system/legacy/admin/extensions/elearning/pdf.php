<?php

/**
 * 
 */
require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess("elearning_exam_reports");

$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);

$aCharts = array();

$objPdf = new Ext_Elearning_Exam_Pdf($oExam);

$sTitle = $oExam->name." - ".L10N::t('Auswertung', 'E-Learning');
$objPdf->writeHeadline($sTitle);

$sSubTitle = L10N::t('Stand', 'E-Learning').": ".strftime("%x %X", time());
$objPdf->writeSubHeadline($sSubTitle);

$objPdf->fpdi->SetXY(25, 65);

$aTableConfigHead = array();
$aTableConfigHead[0] = array('width'=>68, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigHead[1] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigHead[2] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigHead[3] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigHead[4] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigHead[5] = array('width'=>20, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigHead[6] = array('width'=>20, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');

$aTableConfigFooter = array();
$aTableConfigFooter[0] = array('width'=>68, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'dedede');
$aTableConfigFooter[1] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'dedede');
$aTableConfigFooter[2] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'dedede');
$aTableConfigFooter[3] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'dedede');
$aTableConfigFooter[4] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'dedede');
$aTableConfigFooter[5] = array('width'=>20, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'dedede');
$aTableConfigFooter[6] = array('width'=>20, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'dedede');

$aTableConfigBody = array();
$aTableConfigBody[0] = array('width'=>68, 'size'=>8, 'font'=>'helvetica', 'align'=>'L', 'background'=>'f7f7f7');
$aTableConfigBody[1] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'f7f7f7');
$aTableConfigBody[2] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'f7f7f7');
$aTableConfigBody[3] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'f7f7f7');
$aTableConfigBody[4] = array('width'=>25, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'f7f7f7');
$aTableConfigBody[5] = array('width'=>20, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'f7f7f7');
$aTableConfigBody[6] = array('width'=>20, 'size'=>8, 'font'=>'helvetica', 'align'=>'R', 'background'=>'f7f7f7');

$aGroupItems = $oExam->getParticipantGroups();

$aLevelItems = $oExam->getParticipantLevels();

$aLocationsItems = $oExam->getParticipantLocations();

$aGroupResults = $oExam->getResultSummary();

$aAllResults = array(
	'group' => $aGroupResults
);

// Wenn es Level gibt
if(!empty($aLevelItems)) {
	$aLevelResults = $oExam->getResultSummary('level');
	$aAllResults['level'] = $aLevelResults;
}

// Wenn es Standorte gibt
if(!empty($aLocationsItems)) {
	$aLocationResults = $oExam->getResultSummary('location');
	$aAllResults['location'] = $aLocationResults;
}

$iTableHeight = (count($aGroups) + 2) * 5.5;

foreach($aAllResults as $sResult => $aResults) {

	if($sResult == 'group') {
		$aGroups = $aGroupItems;
		$sTitle = 'Teilnehmergruppen';
		$sTh = 'Teilnehmergruppe / Abteilung';
	} elseif($sResult == 'location') {
		$aGroups = $aLocationsItems;
		$sTitle = 'Standorte/Gesellschaften';
		$sTh = 'Standort/Gesellschaft';
	} else {
		$aGroups = $aLevelItems;
		$sTitle = 'Führungsebenen';
		$sTh = 'Führungsebene';
	}
		
	$objPdf->fpdi->Ln(5);

	$objPdf->fpdi->setWDFont('helvetica', 12);
	$objPdf->fpdi->SetTextColor(0, 0, 0);
	$objPdf->WriteHTML(L10N::t($sTitle, 'E-Learning'));

	/*
	* first attempt
	*/
	$objPdf->fpdi->Ln(5);

	$objPdf->fpdi->setWDFont('helvetica', 10);
	$objPdf->fpdi->SetTextColor(0, 0, 0);
	$objPdf->WriteHTML(L10N::t('Erster Durchlauf', 'E-Learning'));


	$objPdf->setTableConfig($aTableConfigHead);

	$aCells = array(L10N::t($sTh, 'E-Learning'), L10N::t('Eingeladen', 'E-Learning'), L10N::t('Bestanden', 'E-Learning'), L10N::t('Durchgefallen', 'E-Learning'), L10N::t('Nicht absolviert', 'E-Learning'));

	$objPdf->printRow($aCells);

	$objPdf->setTableConfig($aTableConfigBody);

	$aEmpty = array();
	$iMax = 0;
	foreach((array)$aGroups as $iGroup=>$sGroup) {

		if(empty($sGroup) || $sGroup == 'no_group') {
			$sGroupTitle = L10N::t('ohne Zuordnung');
			$sGroup = 'no_group';
		} else {
			$sGroupTitle = $sGroup;
		}

		$aCells = array();
		$aCells[] = $sGroupTitle;
		$aCells[] = (int)$aResults['first'][$sGroup]['invited'];
		$aCells[] = (int)$aResults['first'][$sGroup]['succeeded'];
		$aCells[] = (int)$aResults['first'][$sGroup]['failed'];
		$aCells[] = ((int)$aResults['first'][$sGroup]['invited'] - (int)$aResults['first'][$sGroup]['succeeded'] - (int)$aResults['first'][$sGroup]['failed']);

		if($sGroup == 'no_group') {
			$aEmpty[] = $aCells;
		} else {	
			$objPdf->printRow($aCells);
		}

	}

	// print row without group at the end
	if(!empty($aEmpty)) {
		foreach((array)$aEmpty as $aRow) {
			$objPdf->printRow($aRow);
		}
	}

	$objPdf->setTableConfig($aTableConfigFooter);

	$aCells = array();
	$aCells[] = L10N::t('Gesamt', 'E-Learning');
	$aCells[] = (int)$aResults['first']['total']['invited'];
	$aCells[] = (int)$aResults['first']['total']['succeeded'];
	$aCells[] = (int)$aResults['first']['total']['failed'];
	$aCells[] = ((int)$aResults['first']['total']['invited'] - (int)$aResults['first']['total']['succeeded'] - (int)$aResults['first']['total']['failed']);

	$aCharts[0] = array('title'=>L10N::t('Erster Durchlauf', 'E-Learning'), 'data'=>array($aCells[2], $aCells[3], $aCells[4]));

	$objPdf->printRow($aCells);

	/*
	* second attempt
	*/
	$iY = $objPdf->fpdi->getY();
	if(($objPdf->fpdi->getPageHeight() - 20) < ($iY+$iTableHeight)) {
		$objPdf->fpdi->AddPage();
	}

	$objPdf->fpdi->Ln(5);

	$objPdf->fpdi->setWDFont('helvetica', 12);
	$objPdf->fpdi->SetTextColor(0, 0, 0);
	$objPdf->WriteHTML(L10N::t('Zweiter Durchlauf', 'E-Learning'));

	$objPdf->setTableConfig($aTableConfigHead);

	$aCells = array(L10N::t($sTh, 'E-Learning'), L10N::t('Eingeladen', 'E-Learning'), L10N::t('Bestanden', 'E-Learning'), L10N::t('Durchgefallen', 'E-Learning'), L10N::t('Nicht absolviert', 'E-Learning'));

	$objPdf->printRow($aCells);

	$objPdf->setTableConfig($aTableConfigBody);

	$aEmpty = array();
	$iMax = 0;
	foreach((array)$aGroups as $iGroup=>$sGroup) {

		if(empty($sGroup) || $sGroup == 'no_group') {
			$sGroupTitle = L10N::t('ohne Zuordnung');
			$sGroup = 'no_group';
		} else {
			$sGroupTitle = $sGroup;
		}

		$aCells = array();
		$aCells[] = $sGroupTitle;
		$aCells[] = (int)$aResults['second'][$sGroup]['invited'];
		$aCells[] = (int)$aResults['second'][$sGroup]['succeeded'];
		$aCells[] = (int)$aResults['second'][$sGroup]['failed'];
		$aCells[] = ((int)$aResults['second'][$sGroup]['invited'] - (int)$aResults['second'][$sGroup]['succeeded'] - (int)$aResults['second'][$sGroup]['failed']);

		if($sGroup == 'no_group') {
			$aEmpty[] = $aCells;
		} else {	
			$objPdf->printRow($aCells);
		}

	}

	// print row without group at the end
	if(!empty($aEmpty)) {
		foreach((array)$aEmpty as $aRow) {
			$objPdf->printRow($aRow);
		}
	}

	$objPdf->setTableConfig($aTableConfigFooter);

	$aCells = array();
	$aCells[] = L10N::t('Gesamt', 'E-Learning');
	$aCells[] = (int)$aResults['second']['total']['invited'];
	$aCells[] = (int)$aResults['second']['total']['succeeded'];
	$aCells[] = (int)$aResults['second']['total']['failed'];
	$aCells[] = ((int)$aResults['second']['total']['invited'] - (int)$aResults['second']['total']['succeeded'] - (int)$aResults['second']['total']['failed']);

	$aCharts[1] = array('title'=>L10N::t('Zweiter Durchlauf', 'E-Learning'), 'data'=>array($aCells[2], $aCells[3], $aCells[4]));

	$objPdf->printRow($aCells);

	/*
	* total
	*/
	$iY = $objPdf->fpdi->getY();
	if(($objPdf->fpdi->getPageHeight() - 20) < ($iY+$iTableHeight)) {
		$objPdf->fpdi->AddPage();
	}

	$objPdf->fpdi->Ln(5);

	$objPdf->fpdi->setWDFont('helvetica', 12);
	$objPdf->fpdi->SetTextColor(0, 0, 0);
	$objPdf->WriteHTML(L10N::t('Total', 'E-Learning'));

	$objPdf->setTableConfig($aTableConfigHead);

	$aCells = array(L10N::t($sTh, 'E-Learning'), L10N::t('Eingeladen', 'E-Learning'), L10N::t('Bestanden', 'E-Learning'), L10N::t('Durchgefallen', 'E-Learning'), L10N::t('Nicht absolviert', 'E-Learning'));

	$objPdf->printRow($aCells);

	$objPdf->setTableConfig($aTableConfigBody);

	$aEmpty = array();
	$iMax = 0;
	foreach((array)$aGroups as $iGroup=>$sGroup) {

		if(empty($sGroup) || $sGroup == 'no_group') {
			$sGroupTitle = L10N::t('ohne Zuordnung');
			$sGroup = 'no_group';
		} else {
			$sGroupTitle = $sGroup;
		}

		$aCells = array();
		$aCells[] = $sGroupTitle;
		$aCells[] = (int)$aResults['total'][$sGroup]['invited'];
		$aCells[] = (int)$aResults['total'][$sGroup]['succeeded'];
		$aCells[] = (int)$aResults['total'][$sGroup]['failed'];
		$aCells[] = ((int)$aResults['total'][$sGroup]['invited'] - (int)$aResults['total'][$sGroup]['succeeded'] - (int)$aResults['total'][$sGroup]['failed']);

		if($sGroup == 'no_group') {
			$aEmpty[] = $aCells;
		} else {	
			$objPdf->printRow($aCells);
		}

	}

	// print row without group at the end
	if(!empty($aEmpty)) {
		foreach((array)$aEmpty as $aRow) {
			$objPdf->printRow($aRow);
		}
	}

	$objPdf->setTableConfig($aTableConfigFooter);

	$aCells = array();
	$aCells[] = L10N::t('Gesamt', 'E-Learning');
	$aCells[] = (int)$aResults['total']['total']['invited'];
	$aCells[] = (int)$aResults['total']['total']['succeeded'];
	$aCells[] = (int)$aResults['total']['total']['failed'];
	$aCells[] = ((int)$aResults['total']['total']['invited'] - (int)$aResults['total']['total']['succeeded'] - (int)$aResults['total']['total']['failed']);

	$aCharts[2] = array('title'=>L10N::t('Total', 'E-Learning'), 'data'=>array($aCells[2], $aCells[3], $aCells[4]));

	$objPdf->printRow($aCells);
	
	$objPdf->fpdi->AddPage();
	
}


/**
 * print legend
 */
/*
$objPdf->fpdi->Ln(2);
$objPdf->fpdi->setWDFont('helvetica', 6);
$objPdf->fpdi->SetTextColor(90, 90, 90);
$objPdf->WriteHTML(L10N::t('Angelegt: Alle Teilnehmer wurden noch nicht eingeladen / konnten nicht eingeladen werden.', 'E-Learning'));
$objPdf->WriteHTML(L10N::t('Eingeladen: Teilnehmer wurden eingeladen, aber haben den Test noch nicht begonnen.', 'E-Learning'));
$objPdf->WriteHTML(L10N::t('Angefangen: Teilnehmer haben den Test begonnen, aber noch Nicht absolviert.', 'E-Learning'));
$objPdf->WriteHTML(L10N::t('Bestanden: Teilnehmer haben den Test erfolgreich beendet.', 'E-Learning'));
$objPdf->WriteHTML(L10N::t('Durchgefallen: Teilnehmer haben den Test nicht erfolgreich beendet.', 'E-Learning'));
*/
$objPdf->fpdi->Ln(10);

/**
 * get charts
 */

$iY = $objPdf->fpdi->getY();
$iX = 25;
$i=0;
foreach((array)$aCharts as $iChart=>$aChart) {

	if(($objPdf->fpdi->getPageHeight() - 20) < ($iY+80)) {
		$objPdf->fpdi->AddPage();
		$iY = 20;
	}

	// Kein Chart wenn keine Daten vorliegen
	if(array_sum($aChart['data']) === 0) {
		continue;
	}

	// Dataset definition    
	$oDataSet = new WDChart_Data;   
	$oDataSet->AddPoint($aChart['data'], "Serie1");   
	$oDataSet->AddPoint(array(L10N::t('Bestanden', 'E-Learning'), L10N::t('Durchgefallen', 'E-Learning'), L10N::t('Nicht absolviert', 'E-Learning')), "Serie2");   
	$oDataSet->AddAllSeries();   
	$oDataSet->SetAbsciseLabelSerie("Serie2");
	  
	// Initialise the graph   
	$oChart = new WDChart(1000, 750);   
	//$Test->loadColorScheme("softtones"); 
	$oChart->setColorPalette(0, 57, 229, 57);
	$oChart->setColorPalette(1, 254, 64, 64);  
	$oChart->setColorPalette(2, 254, 254, 64);
	  
	// This will draw a shadow under the pie chart   
	$oChart->drawFilledCircle(384, 349, 300, 200, 200, 200);   

	// Draw the pie chart   
	$oChart->setFontProperties("arial.ttf", 16);
	$oChart->drawBasicPieGraph($oDataSet->GetData(),$oDataSet->GetDataDescription(), 380, 345, 300, PIE_PERCENTAGE, 255, 255, 218);   
	$oChart->drawPieLegend(750, 45, $oDataSet->GetData(),$oDataSet->GetDataDescription(),250,250,250);   
	$oChart->setFontProperties("arial.ttf", 26);
	$oChart->drawTitle(1, 30, $aChart['title'], 50, 50, 50);  

	$sTarget = \Util::getDocumentRoot()."storage/elearning_chart_".$iChart.".png";
	
	$oChart->save($sTarget);

	$objPdf->fpdi->Image($sTarget, $iX, $iY, 0, 80);

	$iY += 80;
	$iX = 25;

	$i++;
	unlink($sTarget);

}

$sExamName = \Util::getCleanFileName($oExam->name);

$objPdf->showPDFFile('elearning_exam_report_'.$sExamName.'.pdf');
