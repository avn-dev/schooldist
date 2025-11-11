<?php

class Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Lesson extends Ext_Gui2_View_Format_Abstract {

	public $bFormatLessons = false;

	public function  get($mValue, &$oColumn = null, &$aResultData = null) {
		return (float)$aResultData['lessons'];
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Keine Formatierung für Excel-Export
		if($this->sFlexType === 'excel') {
			return $mValue;
		}
		
		if(
			$this->bFormatLessons ||
			$aResultData['select_type'] != 'week' && !empty($aResultData))
		{
			$aResultData['lessons_period'] = $aResultData['salary_lessons_period'];
			$aResultData['lessons'] = $aResultData['salary_lessons'];
			$oFormat = new Ext_Thebing_Gui2_Format_Teacher_Lessons();
		} else {
			$oFormat = new Ext_Thebing_Gui2_Format_Float(2, true);
		}

		$mValue = $oFormat->format($mValue, $oColumn, $aResultData);
		
		if($aResultData['select_type'] == 'week') {
			$mValue .= ' '.L10N::t('Lektionen', 'Thebing » Tuition » Teachers');
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
