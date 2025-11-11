<?php

class Checks_TCPDF_CopyFonts extends GlobalChecks {
	
	public function getTitle() {
		return 'Pdf Fonts';
	}
	
	public function getDescription() {
		return 'Copies pdf fonts to the right directory';
	}
	
	public function executeCheck() {
		
		try {

			// Wurde der Check schon ausgeführt?
			$sTCPDFFontDir = Util::getDocumentRoot().'system/includes/tcpdf/fonts/';
			if(is_dir($sTCPDFFontDir) !== true) {
				$this->_log('info', 'TCPDF not found!');
				return true;
			}

			$sNewFontDir = \Util::getDocumentRoot().'system/bundles/Pdf/Resources/fonts';

			if(!is_dir($sNewFontDir)) {
				\Util::checkDir($sNewFontDir);
			}
			
			$aFontFiles = glob($sTCPDFFontDir.'*');
			if(!empty($aFontFiles)) {
				$sOutputCopy = \Update::executeShellCommand('cp -R '.$sTCPDFFontDir.'* '.$sNewFontDir);

				if($sOutputCopy !== null) {
					throw new \Exception($sOutputCopy);
				}
			}

			// Altes TCPDF und FPDF löschen
			Util::recursiveDelete(Util::getDocumentRoot().'system/includes/tcpdf/');
			Util::recursiveDelete(Util::getDocumentRoot().'system/includes/fpdi/');

			$this->_log('info', ''.Util::$iDeletedFiles.' TCPDF files deleted!');

		} catch (Exception $ex) {
			__pout($ex);
			$this->_log('error', $ex->getMessage());
			return false;
		}

		return true;
	}
}
