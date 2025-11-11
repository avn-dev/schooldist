<?php

/**
 * Class Ext_Gui2_View_Format_StringParts
 *
 * Formatklasse die einen String, der zusammengeführt wurde mit einem Seperator in zwei teilt
 * und diesen mit einem neuen Seperator wieder zusammen führt.
 *
 */
class Ext_Gui2_View_Format_StringParts extends Ext_Gui2_View_Format_Abstract {

	/**
	 * Aktueller Seperator mit dem die Strings zusammengeführt wurden z.B aus der Datenbank
	 * @default: {|}
	 *
	 * @var string
	 */
	private $sCurrentSeperator = '';

	/**
	 * Der Seperator der den String wieder zusammenführt um diesen in der View anzuzeigen
	 * @default: <br />
	 *
	 * @var string
	 */
	private $sNextSeperator = '';

	/**
	 * @param string $sCurrentSeperator Optional, wenn der Standard zutrifft
	 * @param string $sNextSeperator Optional, wenn der Standard zutrifft
	 */
	public function  __construct($sCurrentSeperator = '{|}', $sNextSeperator = '<br />') {
		$this->sCurrentSeperator = $sCurrentSeperator;
		$this->sNextSeperator = $sNextSeperator;
	}

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			$mValue !== '' &&
			strpos($mValue, $this->sCurrentSeperator) !== false
		) {
			$aCourses = explode($this->sCurrentSeperator, $mValue);
			$sCourseNames = implode($this->sNextSeperator, $aCourses);
		} else {
            $sCourseNames = $mValue;
        }

		return $sCourseNames;
	}

}