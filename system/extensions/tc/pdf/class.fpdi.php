<?php

class Ext_TC_Pdf_Fpdi extends \Pdf\Service\Fpdi {

	protected $producer = '';

	public function Error($msg) {
		// unset all class variables
		$this->_destroy(true);
		// exit program and print error
		throw new PDF_Exception($msg);
	}

	/**
	 * Defines the producer of the document. This is typically the name of the application that generates the PDF.
	 * @param string $creator The name of the creator.
	 * @access public
	 * @since 1.2
	 * @see SetAuthor(), SetKeywords(), SetSubject(), SetTitle()
	 */
	public function SetProducer($producer) {
		//Producer of document
		$this->producer = $producer;
	}

	public function getFonts($sFont=false) {

		if($sFont) {
			return $this->fonts[$sFont];
		} else {
			return $this->fonts;
		}

	}

	/**
	 * Ableitung, damit lokale Bild-URLs aus Performancegründen in lokale Pfade gewandelt werden.
	 * 
	 * @param string $html
	 * @param boolean $ln
	 * @param boolean $fill
	 * @param boolean $reseth
	 * @param boolean $cell
	 * @param string $align
	 * @return type
	 */
	public function writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='') {
	
		$sCurrentDocumentRoot = $_SERVER['DOCUMENT_ROOT'];

		// Es dürfen alle Dateien aus dem Host-Ordner eingebunden werden
		$_SERVER['DOCUMENT_ROOT'] = \Util::getDocumentRoot(true);

		$sHost = preg_quote(System::d('domain'), '/');

		/*
		 * Geht nur mit öffentlichen URLs! 
		 * /storage Pfade klappen nicht, weil diese in TCPDF nochmal umgeschrieben werden mit DOCUMENT_ROOT
		 */
		$html = preg_replace('/src="('.$sHost.'\/media)\//', 'src="'.$_SERVER['DOCUMENT_ROOT'].'storage/public/', $html);
		$html = preg_replace('/src="\/admin\/media\//', 'src="/system/legacy/admin/media/', $html);

		parent::writeHTML($html, $ln, $fill, $reseth, $cell, $align);
		
		$_SERVER['DOCUMENT_ROOT'] = $sCurrentDocumentRoot;
		
	}

	/**
	 * returns array with single lines from text and width of text box
	 * @param	string	text
	 * @param	int	width of text box
	 * @return	array	array with number of lines, lines and wrappen text
	 */
	function getArrayFromText($strText,$intWidth) {

		$arrBox[2] = $this->GetStringWidth($strText);

		$intBoxSpace = $this->GetStringWidth(" ");

		// Text mehrzeilig?
		$bolMulti = 0;
		if(strpos($strText,"\n") !== false) {
			$bolMulti = 1;
}

		$intCheck = 0;
		$intTotal = 0;
		if($bolMulti || $arrBox[2] > $intWidth) {
			$arrWidth = array();
			$strText = preg_replace("/(\r\n|\n|\r)/", "\n", $strText);
			$arrLines = explode("\n",$strText);
			foreach((array)$arrLines as $strLine) {
				$arrText = explode(" ",trim($strLine));
				foreach($arrText as $k=>$v) {
					$arrBox[2] = $this->GetStringWidth($v);
					// if word is longer than textbox, cut word.
					while($arrBox[2] > $intWidth) {
						$v = substr($v, 0, -1);
						$arrBox[2] = $this->GetStringWidth($v);
					}
					$arrWidth[] = array($v,$arrBox[2]);
					$intTotal++;
				}
				$arrWidth[] = array("\n",0);
			}

			array_pop($arrWidth);

			$arrRows = array();

			$c=0;
			$i=0;
			while(isset($arrWidth[$c])) {
				$intRowWidth = 0;
				do {
					if($arrWidth[$c][0] == "\n") {
						$c++;
						$bolNewline = 1;
						$i--;
						break;
					}
					$arrRows[$i][] = $arrWidth[$c][0];
					$intRowWidth += $arrWidth[$c][1]+$intBoxSpace;
					$c++;
				} while($arrWidth[$c][1] > 0 && ($intRowWidth+$arrWidth[$c][1]) <= $intWidth);
				if($bolNewline) {
					$bolNewline = 0;
					$i++;
					continue;
				}
				$arrLines[$i] = implode(" ",$arrRows[$i]);
				$i++;
			}
			$aResult['text'] = implode("\n",$arrLines);
			$aResult['lines'] = $arrLines;
		} else {
			$aResult['text'] = $strText;
			$aResult['lines'] = array($strText);
		}

		$aResult['count'] = count($aResult['lines']);

		return $aResult;

	}

	public function getCurrentLineHeight() {
		$intLineHeight = $this->getFontSizePt() * 0.44;
		return $intLineHeight;
	}

}

/**
 * PDF exception class.
 */
class PDF_Exception extends Exception {}