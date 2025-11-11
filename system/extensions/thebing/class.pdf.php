<?php

define('PDF_FONT_LINE_HEIGHT', 5);
define('PDF_FONT_NAME', 'dejavusans');
define('PDF_FONT_BOLD', 'B');
define('PDF_FILE_EXTENSION', '.pdf');

class Ext_Thebing_Pdf extends Ext_Thebing_Pdf_Fpdi {

    function __get($name) {
        switch ($name) {
            case 'objcopy':
                return $this->objcopy;
			default:
				throw new Exception('Undefined property "'.$name.'"');
        }
    }

	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false) {
		
		if(is_array($format)) {
			if($format[0] > $format[1]) {
				$orientation = 'L';
			} else {
				$orientation = 'P';
			}
		}

		parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
		
		$this->SetCreator('Thebing - School Management Software');

        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		if($iSessionSchoolId) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$this->SetAuthor($oSchool->ext_1);
		}

		//$this->AddFont(PDF_FONT_NAME, '', FPDF_FONTPATH.'arial.php');
		//$this->AddFont(PDF_FONT_NAME, PDF_FONT_BOLD, FPDF_FONTPATH.'arialbd.php');

		$this->setPrintHeader(false); 
		$this->setPrintFooter(false);

		$this->setFontSubsetting(false);

	}
	
}

?>