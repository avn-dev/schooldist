<?php

/**
 * class for generatings pdfs
 * 
 * @author Mark Koopmann <m.koopmann@plan-i.de>
 */
class Ext_Elearning_Exam_Pdf extends wdPDF_ExtendedDocument {

	protected $_aTableConfig = array();
	protected $_aColor = array();
	protected $_oExam;

	protected $_sFont = 'helvetica';

	public function __construct($oExam) {

		parent::__construct();

		$this->_sFont = 'helvetica';

		$this->_oExam = $oExam;

		$this->_oFPDI->AddPage();
		$this->_oFPDI->SetMargins(25, 20, 15);

		$this->setFont($this->_sFont, 10);
		$this->_oFPDI->SetAutoPageBreak(true, 20);

		$this->_oFPDI->setPrintHeader(false);
		
		$this->_aColor = $this->getColorArray($this->_oExam->pdf_color);

		$strImagePath = $this->_oExam->getLogoPath();

		// print logos
		if($strImagePath) {
			$aCanvasSize = array(50, 30);
			$aDimensions = getimagesize($strImagePath);
			$iCanvasRate = $aCanvasSize[0] / $aCanvasSize[1];
			$iLogoRate = $aDimensions[0] / $aDimensions[1];
			$iLogoWidth = 0;
			$iLogoHeight = 0;
			$iPosition = 145;
			if($iCanvasRate > $iLogoRate) {
				$iLogoHeight = $aCanvasSize[1];
				$iLogoWidth = round($iLogoHeight * $iLogoRate, 2);
			} else {
				$iLogoWidth = $aCanvasSize[0];
				$iLogoHeight = round($iLogoWidth / $iLogoRate, 2);
			}
			$iPosition = round($iPosition + $aCanvasSize[0] - $iLogoWidth, 2);
			$this->_oFPDI->Image($strImagePath, $iPosition, 15, $iLogoWidth, $iLogoHeight);
		}

		$this->_oFPDI->SetXY(25, 50);

	}

	public function setFont($sFont, $iSize, $sStyle='') {
		$this->_oFPDI->SetFont($sFont, $sStyle, $iSize);
	}

	public function writeHeadline($sTitle) {

		$this->_oFPDI->SetTextColor($this->_aColor[0], $this->_aColor[1], $this->_aColor[2]);
		$this->setFont($this->_sFont, 18);

		$this->_oFPDI->SetTitle($sTitle);
		$this->_oFPDI->SetSubject($sTitle);

		$this->WriteHTML($sTitle);
		$this->_oFPDI->Ln(0);
		
	}

	public function writeSubHeadline($sSubTitle) {
		$this->setFont($this->_sFont, 8);
		$this->_oFPDI->SetTextColor(70, 70, 70);
		$this->WriteHTML($sSubTitle);
	}

	public function setTableConfig($aTableConfig) {
		$this->_aTableConfig = $aTableConfig;
	}
	
	/**
	 * prints row of table
	 */
	public function printRow($aCells, $intMinLines=1) {

		$intY = $this->_oFPDI->getY();
		$intLineHeight = 5;
		$aSize = array();
		$intMinLines = 0;
		$intSpacing = 0.5;
		
		foreach((array)$aCells as $iKey=>$sCell) {
			$aCells[$iKey] = $this->convertUTF8String($sCell);
			$this->setFont($this->_aTableConfig[$iKey]['font'], $this->_aTableConfig[$iKey]['size']);
			$aSize[$iKey] = $this->checkTextBoxSize($sCell, $this->_aTableConfig[$iKey]['width']);
			$intMinLines = max($intMinLines, $aSize[$iKey]['count']);
		}

		$intMinHeight = ($intMinLines * $intLineHeight);

		if(($intY+$intMinHeight) > 276) {
			$this->_oFPDI->AddPage();
			$intY = $this->_oFPDI->getY();
		}

		$intX = 25;
		foreach((array)$aCells as $iKey=>$sCell) {
			$bFill = 0;
			if($this->_aTableConfig[$iKey]['background']) {
				$aColor = $this->getColorArray($this->_aTableConfig[$iKey]['background']);
				$this->_oFPDI->SetFillColor($aColor[0], $aColor[1], $aColor[2]);
				$bFill = 1;
			}
			
			$this->_oFPDI->SetXY($intX, $intY);
			$this->setFont($this->_aTableConfig[$iKey]['font'], $this->_aTableConfig[$iKey]['size']);
			$this->_oFPDI->SetTextColor(0, 0, 0);
			$this->_oFPDI->MultiCell($this->_aTableConfig[$iKey]['width'], $intLineHeight, $sCell, 0, $this->_aTableConfig[$iKey]['align'], $bFill);
			$intX += $this->_aTableConfig[$iKey]['width'];
			$intX += $intSpacing;
			
			
			
		}

		// check if row is high enough
		$intYEnd = $this->_oFPDI->getY();
		$intDiffHeight = $intMinHeight - ($intYEnd - $intY);

		if($intDiffHeight > 0) {
			$this->_oFPDI->Ln($intDiffHeight);
		}

		$this->_oFPDI->Ln($intSpacing);

	}

	/**
	 * writes a cell
	 */
	public function MultiCell($intWidth, $intHeight, $strText) {
		
		$strText = $this->convertUTF8String($strText);
		$this->_oFPDI->MultiCell($intWidth, $intHeight, $strText);
		
	}

	/**
	 * writes html
	 */
	public function Write($strText) {

		$strText = html_entity_decode($strText, ENT_COMPAT, 'UTF-8');

		$strText = $this->convertUTF8String($strText);
		$this->_oFPDI->Write(5, $strText);

	}

	/**
	 * writes html
	 */
	public function WriteHTML($strText, $bNewline=true) {

		$strText = html_entity_decode($strText, ENT_COMPAT, 'UTF-8');

		$strText = $this->convertUTF8String($strText);
		$this->_oFPDI->writeHTML($strText, $bNewline, false, true);

	}

	public function runAdditionalHeader() {
		global $_LANG;

	}

	/**
	 * writes footer text
	 */
	public function runAdditionalFooter() {
		global $_LANG;

		// write pagination
		$intPageNo = $this->_oFPDI->PageNo();
		$strPagination = trim(sprintf(L10N::t("Seite %s von %s", 'E-Learning'), $intPageNo, $this->_oFPDI->getAliasNbPages()));
		$this->_oFPDI->SetXY(25, 280);
		$this->setFont($this->_sFont, 10);
		$this->_oFPDI->SetTextColor(0, 0, 0);
		//$this->_oFPDI->WriteHTML($strPagination);
		$this->_oFPDI->Cell(200, 10, $strPagination, 0, 1, 'C');

	}

	/**
	 * calculates size of textbox and wraps text
	 */
	function checkTextBoxSize($strText,$intWidth) {

		$arrBox[2] = $this->_oFPDI->GetStringWidth($strText);

		$intBoxSpace = $this->_oFPDI->GetStringWidth(" ");

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
					$arrBox[2] = $this->_oFPDI->GetStringWidth($v);
					// if word is longer than textbox, cut word.	
					while($arrBox[2] > $intWidth) {
						$v = substr($v, 0, -1);
						$arrBox[2] = $this->_oFPDI->GetStringWidth($v);
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
	
	public function getColorArray($sColor) {
		$aColor = array();
		$aColor[] = hexdec(substr($sColor, 0, 2)); 
		$aColor[] = hexdec(substr($sColor, 2, 2)); 
		$aColor[] = hexdec(substr($sColor, 4, 2));
		return $aColor;
	}

	public function generateResultPdf($oResult, $aWrongQuestions) {

		/**
		 * generate content
		 */

		$this->_oFPDI->SetLineWidth(0.1);
		$this->_oFPDI->setLastH(0);

		$sTitle = $this->_oExam->name." - ".L10N::t('Ergebnis', 'E-Learning');
		$this->writeHeadline($sTitle);

		$sSubTitle = L10N::t('Zeitpunkt', 'E-Learning').": ".strftime("%x %X", time());
		$this->writeSubHeadline($sSubTitle);

		$this->_oFPDI->setLastH(0);

		$this->_oFPDI->SetXY(25, 65);
		$this->_oFPDI->Ln(5);

		$this->setFont($this->_sFont, 12, 'B');
		$this->_oFPDI->SetTextColor(0, 0, 0);
		$this->WriteHTML(L10N::t('Falsch beantwortete Fragen', 'E-Learning').':');
		$this->_oFPDI->Ln();

		//$this->_oFPDI->setLastH(4.5);

		foreach((array)$aWrongQuestions as $iKey=>$aWrongQuestion) {

			if($iKey != 0) {
				$this->_oFPDI->AddPage();
			}
			
			$this->_oFPDI->SetTextColor($this->_aColor[0], $this->_aColor[1], $this->_aColor[2]);
			$this->setFont($this->_sFont, 12);
			$this->WriteHTML(L10N::t('Frage', 'E-Learning').' '.$aWrongQuestion['name']);
			
			$iY = $this->_oFPDI->GetY();
			$iY += 2;
			$this->_oFPDI->SetDrawColor($this->_aColor[0], $this->_aColor[1], $this->_aColor[2]);
			$this->_oFPDI->Line(25, $iY, 195, $iY);
			$iY += 2;
			$this->_oFPDI->SetY($iY);
			
			$this->_oFPDI->SetTextColor(0, 0, 0);
			$this->setFont($this->_sFont, 10, 'B');
			$this->WriteHTML($aWrongQuestion['question']);
			$this->_oFPDI->Ln();

			$this->setFont($this->_sFont, 10, 'B');
			$this->WriteHTML(L10N::t('Antworten', 'E-Learning').':', false);
			$this->setFont($this->_sFont, 10);
			
			$this->_oFPDI->SetMargins(35, 20, 15);
			
			foreach((array)$aWrongQuestion['answers'] as $iAnswer=>$aAnswer) {
				
				$iY = $this->_oFPDI->GetY();
				$iY += 4;
				$this->_oFPDI->SetTextColor(0, 0, 0);
				$this->_oFPDI->MultiCell(9, 5, ($iAnswer+1).'.', 0, 'R', false, 1, 25, $iY);
				
				$this->_oFPDI->SetY($iY);
				
				//$this->_oFPDI->setLastH(4.5);

				if($aAnswer['correct']) {
					$this->_oFPDI->SetTextColor(0, 133, 0);
				} elseif($aAnswer['checked']) {
					$this->_oFPDI->SetTextColor(220, 0, 0);
				} else {
					$this->_oFPDI->SetTextColor(0, 0, 0);
				}

				$this->WriteHTML($aAnswer['answer']);

			}

			$this->_oFPDI->SetMargins(25, 20, 15);
			
			/*
			foreach((array)$aWrongQuestion['wrong_answers'] as $aAnswer) {
				$this->_oFPDI->SetTextColor(0, 0, 0);
				$this->WriteHTML(L10N::t('Falsche Antwort', 'E-Learning').':', false);
				$this->_oFPDI->Ln();
				$this->_oFPDI->SetTextColor(220, 0, 0);
				$this->WriteHTML($aAnswer['answer']);
			}

			foreach((array)$aWrongQuestion['correct_answers'] as $aAnswer) {
				$this->_oFPDI->SetTextColor(0, 0, 0);
				$this->WriteHTML(L10N::t('Richtige Antwort', 'E-Learning').':', false);
				$this->_oFPDI->Ln();
				$this->_oFPDI->SetTextColor(0, 133, 0);
				$this->WriteHTML($aAnswer['answer']);
			}
			*/
			
			$this->_oFPDI->SetTextColor(70, 70, 70);

			$this->_oFPDI->Ln();

			$this->setFont($this->_sFont, 10, 'B');
			$this->WriteHTML(L10N::t('Kommentar', 'E-Learning').':', false);
			$this->_oFPDI->Ln();
			$this->setFont($this->_sFont, 10);
			$this->WriteHTML($aWrongQuestion['description']);

			/*
			if(($iKey+1) < count($aWrongQuestions)) {

				if(!$this->checkPageHeight(10)) {
					$this->_oFPDI->AddPage();
				}

				$iY = $this->_oFPDI->GetY();
				$iY += 5;
				$this->_oFPDI->SetDrawColor(70, 70, 70);
				$this->_oFPDI->Line(25, $iY, 195, $iY);
				$this->_oFPDI->SetY($iY);
			}
			*/

		}

		/**
		 * save file
		 */
		$sFileDir = \Util::getDocumentRoot().'storage/elearning/results';
		$bCheck = Util::checkDir($sFileDir);

		if($bCheck) {
			$sFilePath = $sFileDir.'/result_'.$oResult->id.'.pdf';
			$this->savePDFFile($sFilePath);
			return $sFilePath;
		}

		return false;

	}

	public function checkPageHeight($iHeight) {

		$iTotalPageHeight = $this->_oFPDI->getPageHeight();
		$aMargins = $this->_oFPDI->getMargins();

		$iTotalPageHeight = $iTotalPageHeight - $aMargins['bottom'];

		$iYPosition = $this->_oFPDI->GetY();

		if(($iYPosition + $iHeight) > $iTotalPageHeight) {
			return false;
		}

		return true;

	}

}

