<?php

class Ext_Gui2_View_Selection_Dependency extends Ext_Gui2_View_Selection_Abstract {

	private $sDependencyField;
	private $aOptions;
	private $bAddEmpty;
	
	public function __construct($sDependencyField, $aOptions, $bAddEmpty=true) {
		$this->sDependencyField = $sDependencyField;
		$this->aOptions = $aOptions;
		$this->bAddEmpty = $bAddEmpty;
	}
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = [];
		
		$aSelectedOptionKeys = $oWDBasic->{$this->sDependencyField};
		
		if(!empty($aSelectedOptionKeys)) {
			$aOptions = array_intersect_key($this->aOptions, array_flip($aSelectedOptionKeys));
			if($this->bAddEmpty === true) {
				$aOptions = Util::addEmptyItem($aOptions, '', '');
			}
		}
		
		return $aOptions;
	}

}