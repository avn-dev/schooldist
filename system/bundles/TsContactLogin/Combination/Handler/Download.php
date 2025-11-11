<?php

namespace TsContactLogin\Combination\Handler;

use MVC_Request;

class Download extends HandlerAbstract
{
	protected function handle(): void {

		if ($this->login->getRequest()->exists("document_id")) {
			$documentId = (int)$this->login->getRequest()->get("document_id");
			$document = \Ext_Thebing_Inquiry_Document::getInstance($documentId);
			$inquiryForDocument = $document->getInquiry();
			$inquiries = $this->login->getActiveInquiries();
			$version = $document->getLastVersion();
			if (
				$inquiryForDocument->id &&
				isset($inquiries[$inquiryForDocument->id]) &&
				$version instanceof \Ext_Thebing_Inquiry_Document_Version
			) {
				$sFileName = $version->getPath(true);
				//$this->_setHeader('application/pdf', $sFileName);
				if (!file_exists($sFileName)) {
					echo "file not found";
					exit;
				}
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="' . basename($sFileName) . '"');
				readfile($sFileName);
				exit;
			}
		}
	}
}