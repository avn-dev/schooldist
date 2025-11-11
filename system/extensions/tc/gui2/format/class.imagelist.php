<?php

class Ext_TC_Gui2_Format_Imagelist extends Ext_TC_Gui2_Format {

	/**
	 * @var Ext_TC_Gui2_Format_Language_Image 
	 */
	protected $oFormatImage;

	public function __construct() {

		$this->oFormatImage = new Ext_TC_Gui2_Format_Language_Image();

	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){
		$aBack = array();

		if(is_numeric($mValue)) {
			$oUpload = Ext_TC_Upload::getInstance($mValue);
			$mValue = implode(",", (array)$oUpload->languages);
		}

		$aLanguages = explode(",", $mValue);

		foreach((array)$aLanguages as $sLang){
			$aBack[] = $this->oFormatImage->format($sLang);
		}
		$sBack = implode('&nbsp;', $aBack);

		return $sBack;
	}

	public function align(&$oColumn = null) {
		return 'center';
	}

}
