<?php

class Ext_Thebing_Examination_Gui2_Format_TermTooltip extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$oFormat = new Ext_Thebing_Gui2_Format_Date();
		return $oFormat->format($mValue, $oColumn, $aResultData);
	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {
		$aReturn = [];

		if(!empty($aResultData['examination_term_id'])) {

			$oTerm = Ext_Thebing_Examination_Templates_Terms::getInstance($aResultData['examination_term_id']);
			$aData = [];

			if($oTerm->type === 'fix') {
				$aData[] = $this->oGui->t('Fester Termin');
			} else {
				$aData[] = $this->oGui->t('Individueller Termin');
			}

			// Periode: Einmalig oder regelmäßig
			$aData[] = Ext_Thebing_Examination_Templates_Gui2::getPeriodSelectOptions($this->oGui)[$oTerm->period];
			$sUnitLabel = Ext_Thebing_Examination_Templates_Gui2::getPeriodUnitSelectOptions($this->oGui)[$oTerm->period_unit];

			if($oTerm->type === 'fix' && $oTerm->period === 'one_time') {

				// Fester Termin, einmalig, Datum
				$aData[] = $this->format($oTerm->start_date);

			} elseif($oTerm->type === 'fix' && $oTerm->period === 'recurring') {

				// Fester Termin, regelmäßig, alle X Tage, ab Datum
				$aData[] = sprintf('%s %d %s', $this->oGui->t('alle'), $oTerm->period_length, $sUnitLabel);
				$aData[] = sprintf('%s %s', $this->oGui->t('ab'), $this->format($oTerm->start_date));

			} elseif($oTerm->type === 'individual' && $oTerm->period === 'one_time') {

				// Individueller Termin, einmalig, X Tage, nach Kursstart
				$aData[] = sprintf('%d %s', $oTerm->period_length, $sUnitLabel);
				$aData[] = Ext_Thebing_Examination_Templates_Gui2::getStartFromSelectOptions($this->oGui)[$oTerm->start_from];

			} elseif($oTerm->type === 'individual' && $oTerm->period === 'recurring') {

				// Individueller Termin, regelmäßig, alle X Tage, nach Kursstart
				$aData[] = sprintf('%s %d %s', $this->oGui->t('alle'), $oTerm->period_length, $sUnitLabel);
				$aData[] = Ext_Thebing_Examination_Templates_Gui2::getStartFromSelectOptions($this->oGui)[$oTerm->start_from];

			}

			$aReturn['content'] = join(', ', $aData);
			$aReturn['tooltip'] = true;
		}

		return $aReturn;
	}

}