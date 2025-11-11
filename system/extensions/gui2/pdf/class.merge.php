<?php

define('PDF_FILE_EXTENSION', '.pdf');
define('PDF_FONT_NAME', 'arial');
define('PDF_FONT_BOLD', 'B');

class Ext_Gui2_Pdf_Merge { 

	protected $_aPDFList	= array();
	protected $_sFpdiClass	= '\Pdf\Service\Fpdi';

	public function addPdf($sPDFPath){

		$sPDFPath = str_replace(\Util::getDocumentRoot(), '', $sPDFPath);
		$sPDFPath = \Util::getDocumentRoot().$sPDFPath;

		$this->_aPDFList[] = $sPDFPath;
	}

	/**
	 * Wird für display und Save verwendet (zentral)
	 *
	 * @return FPDI
	 */
	public  function displaySaveWrapper(){
		
		$oPdf = new $this->_sFpdiClass('L', 'mm');
		$oPdf->setPrintHeader(false);
		$oPdf->setPrintFooter(false);

		foreach((array)$this->_aPDFList as $sPDF){

			if(is_file($sPDF)){

				$iPagecount = $oPdf->setSourceFile($sPDF);

				// Seiten druchgehen und auf dem Hauptdoc. positionieren
				for($i = 1; $i <= $iPagecount; $i++){

					$oPdf->setSourceFile($sPDF);
					$tplidx = $oPdf->importPage($i, '/MediaBox'); 
					$aTemplateSize = $oPdf->getTemplateSize($tplidx);

					$aFormat = array(round($aTemplateSize['width'],2), round($aTemplateSize['height'],2));

					$sOrientation = 'P';

					if($aFormat[0] > $aFormat[1]) {
						$sOrientation = 'L';
					}

					$oPdf->AddPage($sOrientation, $aFormat);
					// Seiten positionieren

					$oPdf->useTemplate($tplidx, 0, 0);

				}

			}

		}
		
		return $oPdf;
	}

	
	public function display(){

		$oPdf = $this->displaySaveWrapper();

		$iLength = ob_get_length();
		if($iLength>0){

			$oMail = new WDMail();
			$oMail->subject = "MultiplePdf Error";
			$sText .= $_SERVER['HTTP_HOST']."\n\n";
			$sText .= Util::getBacktrace()."\n\n";
			$sText .= "-".ob_get_contents()."-"."\n\n";

			$oMail->text = $sText;
			$oMail->send(array('md@plan-i.de'));

			ob_end_clean();
		}

		$oPdf->Output('PDF.pdf', 'D');

	}
	
	public function save($sFile){		
		$oPdf = $this->displaySaveWrapper();
		
		return $oPdf->Output($sFile, 'F');
	}
	


}