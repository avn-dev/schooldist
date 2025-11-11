<?php 

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

Access_Backend::checkAccess("office");

if(
	isset($_VARS['template']) &&
	$_VARS['template'] == 'false'
) {
	$bTemplate = false;
} else {
	$bTemplate = true;
}

if(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'show_checklist_pdf'
) {
	$oPDF = new Ext_Office_PDF(1, $_VARS['document_id']);
	$oPDF->showCheckListPDF('Checkliste.pdf');
	exit();
}

if(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'export_list_xlsx'
) {

	$sFormat = '';
	if(isset($_VARS['format'])) {
		$sFormat = (string)$_VARS['format'];
	}

	switch($sFormat) {

		case 'extended1': // Format für Consulimus (siehe KOM-#813)
			$aExport = array(
				array(
					'Status',
					'ID',
					'Nr.',
					'Art',
					'Betreff',
					'Kunde',
					'Ansprechpartner',
					'Produktbereich',
					'Datum',
					'Buchung',
					'Netto'
				)
			);
			$iRow = count((array)$aExport);
			$aSpecials = array();
			for($i = 0; $i < 10; $i++) {
				$aSpecials['cell_format'][$iRow][$i] = array(
					'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
					'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
					'bold' => true,
					'italic' => false
				);
			}
			break;

		default:
			$aExport = array(
				array(
					'Nr.',
					'Art',
					'Betreff',
					'Kunde',
					'Land',
					'USt.-ID',
					'Bearbeiter',
					'Datum',
					'Netto',
					'Brutto'
				)
			);
			$iRow = count((array)$aExport);
			$aSpecials = array();
			for($i = 0; $i < 10; $i++) {
				$aSpecials['cell_format'][$iRow][$i] = array(
					'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
					'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
					'bold' => true,
					'italic' => false
				);
			}
			break;

	}

	foreach((array)$_VARS['id'] as $iDocumentId) {

		$aDocument = $_SESSION['office']['cache']['documents'][$iDocumentId];
		if(empty($aDocument)) {
			continue;
		}

		// PHPExcel arbeitet mit UTC-Timestamps, deswegen hier einen passenden UTC-Stimestamp um 0 Uhr generieren
		// damit im Excel-Dokument nachher keine Zeitangabe je nach Timezone-Offset steht
		$iDate = strtotime($aDocument['date']);
		$sDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
			gmmktime(0, 0, 0, date('m', $iDate), date('d', $iDate), date('Y', $iDate))
		);
		$iBookingDate = strtotime($aDocument['booking_date']);
		$sBookingDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
			gmmktime(0, 0, 0, date('m', $iBookingDate), date('d', $iBookingDate), date('Y', $iBookingDate))
		);

		$sDocumentState = $aDocument['state'];
		if(isset($aDocumentStates[$sDocumentState])) {
			$sDocumentState = (string)$aDocumentStates[$sDocumentState];
		}

		switch($sFormat) {

			case 'extended1': // Format für Consulimus (siehe KOM-#813)
				$aExport[] = array(
					$sDocumentState,
					(int)$aDocument['id'],
					(int)$aDocument['number'],
					$aDocument['type'],
					$aDocument['subject'],
					$aDocument['k_matchcode'],
					implode(' ', [$aDocument['c_lastname'], $aDocument['c_firstname']]),
					$aDocument['product_area'],
					$sDate,
					$sBookingDate,
					$aDocument['price_net']
				);
				$iRow = count((array)$aExport);
				for($i = 0; $i < 10; $i++) {
					if(in_array($i, array(1, 2))) { // ID, Nr.
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
							'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER,
							'bold' => false,
							'italic' => false
						);
					} elseif(in_array($i, array(8, 9))) { // Datum, Buchung
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
							'style' => 'dd.mm.yyyy',
							'bold' => false,
							'italic' => false
						);
					} elseif(in_array($i, array(10))) { // Netto
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
							'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1.' '.$aDocument['currency'],
							'bold' => false,
							'italic' => false
						);
					} else {
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
							'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
							'bold' => false,
							'italic' => false
						);
					}
				}
				break;

			default:
				$aExport[] = array(
					(int)$aDocument['number'],
					$aDocument['type'],
					$aDocument['subject'],
					$aDocument['k_matchcode'],
					$aDocument['k_country'],
					$aDocument['vat_id_nr'],
					$aDocument['u_firstname'].' '.$aDocument['u_lastname'],
					$sDate,
					$aDocument['price_net'],
					$aDocument['price']
				);
				$iRow = count((array)$aExport);
				for($i = 0; $i < 10; $i++) {
					if(in_array($i, array(0))) { // Nr.
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
							'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER,
							'bold' => false,
							'italic' => false
						);
					} elseif(in_array($i, array(7))) { // Datum
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
							'style' => 'dd.mm.yyyy',
							'bold' => false,
							'italic' => false
						);
					} elseif(in_array($i, array(8, 9))) { // Netto, Brutto
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
							'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1.' '.$aDocument['currency'],
							'bold' => false,
							'italic' => false
						);
					} else {
						$aSpecials['cell_format'][$iRow][$i] = array(
							'format' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
							'style' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
							'bold' => false,
							'italic' => false
						);
					}
				}
				break;

		}

	}

	WDExport::exportXLSX('Dokumente', $aExport, $aSpecials);
	die();

} elseif(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'export_list_pdf'
) {

	$oPdfMerger = new WDPdf_Merger();

	foreach((array)$_VARS['id'] as $iDocumentId) {
		$oDocument = new Ext_Office_Document($iDocumentId);
		$sPdfPath = $oDocument->getFilePath();
		if(is_file($sPdfPath)) {
			$oPdfMerger->addPDF($sPdfPath);
		}
	}

	$oPdfMerger->merge();

} elseif(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'sendMail'
) {

	$sEmail = rawurldecode($_VARS['email']);
	$sSubject = rawurldecode($_VARS['subject']);
	$sText = rawurldecode($_VARS['text']);

	$oAPI = new Ext_Office_Document($_VARS['document_id']);

	$aMail = array();
	$aMail['to'] = $sEmail;
	$aMail['subject'] = $sSubject;
	$aMail['body'] = $sText;

	$bSuccess = $oAPI->sendFile($aMail);

	$aTransfer = array();

	// if mail was sent
	if($bSuccess) {

		// Log
		$aLog = array(
			'id' => 0,
			'customer_id' => $oAPI->customer_id,
			'contact_id' => $oAPI->contact_person_id,
			'editor_id' => $user_data['id'],
			'document_id' => $oAPI->id,
			'topic' => $oAPI->type,
			'subject' => 'Versendet per E-Mail an ' . $sEmail,
			'state' => 'send'
		);
		$objOfficeDao->manageProtocols($aLog);

		$aTransfer['success'] = 1;
		$aTransfer['message'] = L10N::t('Die E-Mail wurde erfolgreich versendet.');

	} else {

		$aTransfer['success'] = 0;
		$aTransfer['message'] = L10N::t('Die E-Mail konnte nicht versendet werden.');

	}

	$sJson = json_encode($aTransfer);
	echo $sJson;

} elseif(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'sendPost'
) {

	$oAPI = new Ext_Office_Document($_VARS['document_id']);

	$mSuccess = $oAPI->sendFileByPost();

	$aTransfer = array();

	// if mail was sent
	if($mSuccess === true) {

		// Log
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> $oAPI->customer_id,
			'contact_id'	=> $oAPI->contact_person_id,
			'editor_id'		=> $user_data['id'],
			'document_id'	=> $oAPI->id,
			'topic'			=> $oAPI->type,
			'subject'		=> 'Versendet per Post',
			'state'			=> 'send'
		);
		$objOfficeDao->manageProtocols($aLog);

		$aTransfer['success'] = 1;
		$aTransfer['message'] = L10N::t('Der Brief wurde erfolgreich versendet.');

	} else {

		$aTransfer['success'] = 0;
		$aTransfer['message'] = L10N::t('Der Brief konnte nicht versendet werden.')." (".$mSuccess.")";

	}

	$sJson = json_encode($aTransfer);
	echo $sJson;

} elseif(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'sendFax'
) {

	$oAPI = new Ext_Office_Document($_VARS['document_id']);

	$sFaxNumber = $_VARS['fax'];

	$mSuccess = $oAPI->sendFileByFax($sFaxNumber);

	$aTransfer = array();

	// if mail was sent
	if($mSuccess === true) {

		// Log
		$aLog = array(
			'id'			=> 0,
			'customer_id'	=> $oAPI->customer_id,
			'contact_id'	=> $oAPI->contact_person_id,
			'editor_id'		=> $user_data['id'],
			'document_id'	=> $oAPI->id,
			'topic'			=> $oAPI->type,
			'subject'		=> 'Versendet per Fax',
			'state'			=> 'send'
		);
		$objOfficeDao->manageProtocols($aLog);

		$aTransfer['success'] = 1;
		$aTransfer['message'] = L10N::t('Das Fax wurde erfolgreich versendet.');

	} else {

		$aTransfer['success'] = 0;
		$aTransfer['message'] = L10N::t('Das Fax konnte nicht versendet werden.')." (".$mSuccess.")";

	}

	$sJson = json_encode($aTransfer);
	echo $sJson;

} elseif(
	isset($_VARS['action']) &&
	$_VARS['action'] == 'openPdf'
) {

	$oDocument = new Ext_Office_Document((int)$_VARS['document_id']);

	$sLocation = \Core\Helper\Routing::generateUrl('Office.office_open_pdf', ['documentId'=>$oDocument->id, 'documentPath'=>$oDocument->getPDFFilename()]);
	
	header('Location: '.$sLocation);
	die();	

} else {

	$oDocument = new Ext_Office_Document((int)$_VARS['document_id']);

	$oPDF = new Ext_Office_PDF($oDocument->form_id, $_VARS['document_id'], $bTemplate);

	$sFileName = $oDocument->pdf_filename;

	$oPDF->showPDFFile($sFileName);
	die();

}
