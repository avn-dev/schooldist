<?php

class Ext_Thebing_Gui2_Format_School_Tuition_Block_Description extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var bool
	 */
	private $bShort;

	public function __construct($bShort = false) {
		$this->bShort = $bShort;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$mValue = htmlentities($mValue);

		if($this->bShort) {
			$mValue = preg_replace('/(\r\n|\n)/', ', ', $mValue);
		} else {
			$mValue = nl2br($mValue);
		}

		return $mValue;

	}

}