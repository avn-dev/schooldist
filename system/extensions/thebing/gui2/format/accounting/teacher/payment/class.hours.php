<?php

class Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Hours extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Keine Formatierung für Excel-Export
		if($this->sFlexType === 'excel') {
			return $mValue/60;
		}
		
		if($aResultData['select_type'] === 'week' && empty($mValue)) {
			$iMin = $aResultData['lessons_normal'] * $aResultData['lession_durration'];
			$iMin += $aResultData['lessons_holiday'] * $aResultData['lession_durration'];
		} else {
			$iMin = $mValue;
		}

		if($iMin > 0) {
			$mValue = floor($iMin / 60);
			$mValue	.= L10N::t('h', 'Thebing » Accounting » Teachers') . ' ';
			$mValue .= $iMin % 60;
			$mValue	.= L10N::t('m', 'Thebing » Accounting » Teachers');
		} else {
			$mValue	= '0' . L10N::t('h', 'Thebing » Accounting » Teachers');
		}

		return $mValue;
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {
		
		$oCell->setValueExplicit(
			$mValue,
			\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
		);

	}
	
}