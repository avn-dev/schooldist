<?php

namespace Office\Controller;

include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");

/**
 * 
 */
class DocumentController extends \MVC_Abstract_Controller{
	
	public function getPaymentsAction() {
		
		$iDocumentId = (int)$this->_oRequest->get('document_id');

		$oExtensionDaoOffice = new \classExtensionDao_Office();
		
		$aPayments = $oExtensionDaoOffice->getPayments($iDocumentId);
		
		$this->set('payments', $aPayments);
		
	}
	
	public function getTemplatesAction() {

		$aTextBlocks = \DB::getQueryRows("SELECT `name` `title`, `text` `content` FROM office_templates ORDER BY `name`");
		
		echo json_encode($aTextBlocks);
		die();
	}
	
	public function postDeleteAction() {

		$iDocumentId = (int)$this->_oRequest->get('document_id');

		$bSuccess = \DB::updateData('office_documents', array('active'=>0), '`id` = '.$iDocumentId);

		$this->set('success', $bSuccess);
		
	}
	
	public function deletePayment($paymentId) {
		
		$payment = \Office\Entity\Payment::getInstance($paymentId);
		
		if($payment->exist()) {
			
			$document = new \Ext_Office_Document($payment->document_id);
			$document->state = 'released';
			$document->save();
			
			$payment->delete();

		}

		return response()->json(['success'=>true]);
	}
	
	public function openPdf($documentId, $documentPath) {

		$oDocument = new \Ext_Office_Document((int)$documentId);
		$sFile = $oDocument->getFilePath();
		$sName = $oDocument->getPDFFilename();

		header('Content-Type: application/pdf');
		header('Content-Length: '. filesize( $sFile ));
		header('Content-disposition: inline; filename="' . $sName . '"');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($sFile)).' GMT');

		readfile($sFile);
		die();

	}
	
}