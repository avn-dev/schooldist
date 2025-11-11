<?php

abstract class Ext_Gui2_Pdf_Abstract {

	/**
	 * @var Ext_Gui2_Data
	 */
	protected $oGuiData;
	
	public function __construct(Ext_Gui2_Data $oGuiData) {
		$this->oGuiData = $oGuiData;
	}

	abstract public function getPdfPath($iSelectedId);
	
}
