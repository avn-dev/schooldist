<?php

namespace Gui2\Service\Export;

class Csv extends ExportAbstract {

	const SEPERATOR_FIX_WIDTH = 'fix_width';
	const ENCLOSURE_DOUBLE_QUOTES = 'double_quotes';
	
	protected $_sCharset = '';
	protected $_sSeperator = ';';

	protected $sExtension = 'csv';
	protected $sLinebreak = "unix";
	protected $aWidths = [];
	protected $sEnclosure = self::ENCLOSURE_DOUBLE_QUOTES;
	protected $bHeadlines = true;

	public function setCharset($sCharset) {
		$this->_sCharset = $sCharset;
	}
	
	public function setSeperator($sSeperator) {
		$this->_sSeperator = $sSeperator;
	}
	
	public function setLinebreak($sLinebreak) {
		$this->sLinebreak = $sLinebreak;
	}
	
	public function setWidths($aWidths) {
		$this->aWidths = $aWidths;
	}
	
	public function setEnclosure($sEnclosure) {
		$this->sEnclosure = $sEnclosure;
	}
	
	public function setHeadlines($bHeadlines) {
		$this->bHeadlines = $bHeadlines;
	}
	
	public function sendHeader() {
		global $_VARS;

		if(!isset($_VARS['gui_debugmode'])) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename=\"".$this->sFilename."\";" );
			header("Content-Transfer-Encoding: binary");
		} else {
			__pout('Warning: CSV export as MIME text/html!');
		}

	}
    
    public function createFromGuiTableData(array $aData) {
   
        $sCsv = '';

		if($this->bHeadlines === true) {
			$aLine = array();
			foreach($aData['head'] as $aRow) {
				$aLine[] = $aRow['title'];
			}
			$sCsv .= $this->getLine($aLine);
		}
   
        foreach($aData['body'] as $aRow) {
            $aLine = array();
            foreach($aRow['items'] as $aColumn) {
                $aLine[] = $aColumn['text'];
            }
            $sCsv .= $this->getLine($aLine);
        }
        
        return $sCsv;
    }	
    
    /**
     * erzeugt die Export Zeile
     * @param array $aLine
     * @return string 
     */
	public function getLine($aLine, $aResultData=null) {

		$sLine = '';

		foreach((array)$aLine as $n=> $mValue) {

			if(is_array($mValue)) {

				if(array_key_exists('text', $mValue)) {
					$mValue = $mValue['text'];
					// Sonst header
				} else {
					$mValue = $mValue['title'];
				}

			}
			
			if(!is_numeric($mValue)) {
				
				$mValue = html_entity_decode((string)$mValue, ENT_QUOTES, 'UTF-8');

				// Wenn bereits das Charset schon utf8 ist, dann muss nicht erneut konvertiert werden
				if($this->_sCharset !== 'UTF-8') {
					if(strpos($this->_sCharset, 'ASCII') !== false) {
						// Statisch 'de' damit ae etc. immer ersetzt werden.
						$mValue = \Illuminate\Support\Str::ascii($mValue, 'de');
					} else {
						$mValue = iconv('UTF-8', $this->_sCharset.'//TRANSLIT', $mValue);
					}
				}

				$mValue = $this->prepareValue($mValue);
				
				if($this->sEnclosure == \Gui2\Service\Export\Csv::ENCLOSURE_DOUBLE_QUOTES) {
					$mValue = str_replace('"', '""', $mValue);
					$mValue = '"'.$mValue.'"';
				}

			}

			if($this->_sSeperator == \Gui2\Service\Export\Csv::SEPERATOR_FIX_WIDTH) {

				$iLen = mb_strlen($mValue, $this->_sCharset);

				if($iLen > $this->aWidths[$n]) {
					$mValue = mb_substr($mValue, 0, $this->aWidths[$n], $this->_sCharset);
				} else {
					$mValue = self::mb_str_pad($mValue, $this->aWidths[$n], ' ', STR_PAD_RIGHT, $this->_sCharset);
				}

				$sLine .= $mValue;
				
			} else {
			
				$sLine .= $mValue;
				$sLine .= $this->_sSeperator;

			}
		}

		// Letzten Separator entfernen und einen Umbruch für neue Zeile einfügen
		if($this->_sSeperator !== \Gui2\Service\Export\Csv::SEPERATOR_FIX_WIDTH) {
			$sLine = substr($sLine, 0, -1);
		}

		switch($this->sLinebreak) {
			case 'windows':
				$sLine .= "\r\n";
				break;
			default:
				$sLine .= "\n";
				break;
		}
		
		return $sLine;
	}
	
	/**
	 * schließt den CSV Export ab
	 * setzt die INI Werte zurück und gibt schließt den OB 
	 */
	public function end() {
		
//		$this->oWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($this->oSpreadsheet);
//		$this->oWriter->setExcelCompatibility(true);
//		
//		parent::end();
		
		die();

	}
	
	static public function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL) {
		
		$encoding = $encoding === NULL ? mb_internal_encoding() : $encoding;
		$padBefore = $dir === STR_PAD_BOTH || $dir === STR_PAD_LEFT;
		$padAfter = $dir === STR_PAD_BOTH || $dir === STR_PAD_RIGHT;
		$pad_len -= mb_strlen($str, $encoding);
		$targetLen = $padBefore && $padAfter ? $pad_len / 2 : $pad_len;
		$strToRepeatLen = mb_strlen($pad_str, $encoding);
		$repeatTimes = ceil($targetLen / $strToRepeatLen);
		$repeatedString = str_repeat($pad_str, max(0, $repeatTimes)); // safe if used with valid utf-8 strings
		$before = $padBefore ? mb_substr($repeatedString, 0, floor($targetLen), $encoding) : '';
		$after = $padAfter ? mb_substr($repeatedString, 0, ceil($targetLen), $encoding) : '';
		
		return $before . $str . $after;
	}

}