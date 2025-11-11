<?php

class Ext_Thebing_Gui2_Format_Time extends Ext_Gui2_View_Format_Abstract {

	/**
	 * Formatiert die Tageszeit 00:00:00
	 *
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null){ 

		if ($mValue instanceof \DateTimeInterface) {
			$mValue = $mValue->format('H:i:s');
		}

		// Prüfen ob es ein Timestamp ist oder mehrere (GROUP_CONCAT)
		$aTimes = explode('|', $mValue);

		$aBack = array();
		foreach((array)$aTimes as $sTime) {

			$aSplit = explode(':', $sTime);
			if(count($aSplit) == 3) {
				// Sekunden können generell vernachlässigt werden bei Thebing
				$aBack[] = $aSplit[0] . ':' . $aSplit[1];
			} elseif(empty($sTime) ) {

			} else {
				$aBack[] = '';
			}

		}

		return implode('<br/>', $aBack);

	}

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function convert($mValue, &$oColumn = null, &$aResultData = null) {

		if(
			$mValue === '' ||
			$mValue === null
		) {
			return $mValue;
		}

		$aSplit = explode(':', $mValue);
		for($iCnt = count($aSplit); $iCnt < 3; $iCnt++) {
			$aSplit[] = '00';
		}

		return implode(':', $aSplit);
	}

}
