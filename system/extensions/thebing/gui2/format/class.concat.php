<?php


class Ext_Thebing_Gui2_Format_Concat extends Ext_Thebing_Gui2_Format_Select {

	/**
	 * @param string $sSelectedAllIds
	 * @param bool $bUseEmtyValues
	 * @param bool $sExtraFormatClass
	 * @param bool $bUseIdFields
	 * @param string $sDelimiter
	 */
	public function  __construct(
			$sSelectedAllIds = 'all_ids',
			$bUseEmtyValues = false,
			$sExtraFormatClass = false,
			$bUseIdFields = true,
			$sDelimiter = ','
	) {
		$this->sDelimiter = $sDelimiter;
		$this->sDelimiterOutput	= '<br />';
		$this->sSelectedAllIds = $sSelectedAllIds;
		$this->sRegex = '/ID_([0-9]*)_(.*)'.preg_quote($sDelimiter).'$/';
		$this->bUseIdFields = $bUseIdFields;
		$this->bUseEmptyValues = $bUseEmtyValues;
		$this->oExtraFormatClass = false;

		if($sExtraFormatClass) {
			if($sExtraFormatClass == 'Ext_Thebing_Gui2_Format_Float') {
				$this->oExtraFormatClass = new Ext_Thebing_Gui2_Format_Float(2, false);
			} else if($sExtraFormatClass == 'Ext_Thebing_Gui2_Format_Date') {
				$this->oExtraFormatClass = new Ext_Thebing_Gui2_Format_Date();
			}
		}
	}

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sGroupFormatOutput = '';
		$sDelimiter = $this->sDelimiter;
		$sDelimiterOutput = $this->sDelimiterOutput;
		$oExtraFormat = $this->oExtraFormatClass;

		if(
			!empty($sDelimiter) &&
			!empty($sDelimiterOutput) &&
			(
				!empty($mValue) ||
				$this->bUseEmptyValues
			)
		) {

			// benutze diesen Flag nur, falls du mehrere concats in verschiedenen Spalten in gleicher Reihenfolge anzeigen willst
			if($this->bUseIdFields === true) {

				$sSelectedAllIds = $this->sSelectedAllIds;

				if(array_key_exists($sSelectedAllIds, $aResultData)) {

					$sResultAllIds = $aResultData[$sSelectedAllIds];
					$aResultAllIds = explode($sDelimiter,$sResultAllIds);
					$aData = array();

					$aDataPart = explode($sDelimiter, $mValue);

					foreach($aDataPart as $iKey => $sPart) {
						$aPart = explode('_', $sPart);
						if(count($aPart) >= 3) {
							unset($aPart[0]);
							$iId = (int)array_shift($aPart);
							$sPartValue = (string)  implode('_', $aPart);
							$aData[$iId] = $sPartValue;
						}
					}

					foreach($aResultAllIds as $iGroupedId) {
						if(array_key_exists($iGroupedId, $aData)) {
							$mNewValue = $aData[$iGroupedId];
							if($oExtraFormat) {
								$mNewValue = $oExtraFormat->format($mNewValue, $oColumn, $aResultData);
							}
							$sGroupFormatOutput .= $mNewValue;
						}
						$sGroupFormatOutput .= $sDelimiterOutput;
					}

				}

			} else {

				$aValue = explode($sDelimiter, $mValue);
				$iLimit = count($aValue)-1;
				
				foreach($aValue as $iKey => $mSplittedValue) {
					if(!empty($mSplittedValue) || $this->bUseEmptyValues) {
						if($oExtraFormat) {
							$mSplittedValue = $oExtraFormat->format($mSplittedValue, $oColumn, $aResultData);
						}
						$sGroupFormatOutput .= $mSplittedValue;
						if($iKey<$iLimit) {
							$sGroupFormatOutput .= $sDelimiterOutput;
						}
					}
				}

			}

		}
	
		return $sGroupFormatOutput;
	}
}