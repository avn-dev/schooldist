<?php

use Core\Helper\DateTime;

class Ext_Thebing_Gui2_Format_School_Tuition_DayInput extends Ext_Gui2_View_Format_Abstract {
	
	protected $iDay;

	public function __construct($iDay) {
		$this->iDay = $iDay;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		// Abfrage auch in Ext_Thebing_Tuition_Gui2_Attendance::setQueryOrderByDataManualSort() vorhanden
		$aDays = explode(',', $aResultData['days']);
		if(!in_array($this->iDay, $aDays)) {
			return '';
		}

		// Prüfen ob der Block an diesem Tag abgesagt wurde
		if (isset($aResultData['block_states'])) {
			$aStates = explode('|', $aResultData['block_states']);
			foreach($aStates as $sDayState) {
				[$iDay, $iState] = explode('-', $sDayState);
				if (
					(int)$iDay === (int)$this->iDay &&
					\Core\Helper\BitwiseOperator::has($iState, \TsTuition\Entity\Block\Unit::STATE_CANCELLED)
				) {
					return '';
				}
			}
		}

		// Kein Wert, wenn Kurs nicht im Zeitraum ist
		try {
			$dDate = new DateTime($aResultData['block_week']);
			$dDate->add(new DateInterval('P'.($this->iDay - 1).'D'));

			if(!$dDate->isBetween(new DateTime($aResultData['journey_course_from']), new DateTime($aResultData['journey_course_until']))) {
				return '';
			}
		} catch(Exception $e) {
			return '';
		}

		$fDayValue = (float)$mValue;

		$iMinutes = $fDayValue % 60;
		$iHours = (int)($fDayValue / 60);

		if($this->sFlexType != 'list') {
			//Für CSV-Export nur reinen Text anzeigen
			$mReturn = $this->getExportValue($iHours, $iMinutes, $aResultData);
		} else {
			//Für Liste HTML-Ausgabe mit Inputs
			$mReturn = $this->getHtmlValue($iHours, $iMinutes, $aResultData);
		}

		return $mReturn;

	}

	/**
	 * CSV-Export
	 *
	 * @param int $iHours
	 * @param int $iMinutes
	 * @param array $aResultData
	 * @return string
	 */
	protected function getExportValue($iHours, $iMinutes, array $aResultData) {

		$sReturn = vsprintf('%d%s %d%s', [
			$iHours,
			L10N::t('h', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH),
			$iMinutes,
			L10N::t('m', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH)
		]);

		if($this->isDayExcused($aResultData['excused'])) {
			$sReturn .= ' '.L10N::t('E', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
		}

		return $sReturn;

	}

	/**
	 * GUI-Spalte (HTML)
	 *
	 * @param int $iHours
	 * @param int $iMinutes
	 * @param array $aResultData
	 * @return string
	 */
	protected function getHtmlValue($iHours, $iMinutes, array $aResultData) {

		$iRowId = $aResultData['id'];

		$container = new Ext_Gui2_Html_Div();
		
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'attendance_input input-group';
		$oDiv->style = 'width: 155px';

		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'input-group-addon';
		
		$onlineIcon = new Ext_Gui2_Html_I();

		if(
			$aResultData['online'] !== null &&
			// Beim Lehrerportal kann man die Abwesenheit von einzelnen Tagen speichern
			// -> Also nur ein Icon anzeigen, wenn die Abwesenheit für diesen Tag auch gespeichert wurde
			$this->isDayAttendanceSaved($aResultData)
		) {
			if($this->isDayOnline($aResultData['online'])) {
				$onlineIcon->class = 'fas fa-fw fa-globe';
				$onlineIcon->title = L10N::t('Online teilgenommen', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
			} else {
				$onlineIcon->class = 'fas fa-fw fa-school';
				$onlineIcon->title = L10N::t('Vor-Ort teilgenommen', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
			}
		} else {
			$onlineIcon->class = 'fas fa-fw';
		}
		$oSpan->setElement($onlineIcon);
		$oDiv->setElement($oSpan);

		// Komplett abwesend
		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'input-group-addon';
		$oInput = new Ext_Gui2_Html_Input();
		$oInput->class = 'multiple_handle absence-reason-handle';
		$oInput->type = 'checkbox';
		$oInput->name = 'checkbox['.$iRowId.']['.$this->iDay.']';
		$oInput->value = '1';
		$oInput->title = L10N::t('Komplett abwesend', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
		$oSpan->setElement($oInput);
		$oDiv->setElement($oSpan);

		// Stunden
		$oSpan = new Ext_Gui2_Html_Span();
		$oInput = new Ext_Gui2_Html_Input();
		$oInput->class = 'form-control alignRightImportant time_input multiple_handle absence-reason-handle';
		$oInput->name = 'hours['.$iRowId.']['.$this->iDay.']';
		$oInput->value = $iHours;
		$oInput->style = 'width: 24px;';
		$oSpan->setElement($oInput);
		$oDiv->setElement($oSpan);

		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'input-group-addon';
		$oSpan->setElement(L10N::t('h', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH));
		$oDiv->setElement($oSpan);

		// Minuten
		$oSpan = new Ext_Gui2_Html_Span();
		$oInput = new Ext_Gui2_Html_Input();
		$oInput->class = 'form-control alignRightImportant time_input multiple_handle absence-reason-handle';
		$oInput->name = 'minutes['.$iRowId.']['.$this->iDay.']';
		$oInput->value = $iMinutes;
		$oInput->style = 'width: 24px;';
		$oSpan->setElement($oInput);
		$oDiv->setElement($oSpan);

		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'input-group-addon';
		$oSpan->setElement(L10N::t('m', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH));
		$oDiv->setElement($oSpan);

		// Entschuldigt
		$oSpan = new Ext_Gui2_Html_Span();
		$oSpan->class = 'input-group-addon';

		$oInput = new Ext_Gui2_Html_Input();
		$oInput->class = 'multiple_handle absence-excused';
		$oInput->type = 'checkbox';
		$oInput->name = 'excused['.$iRowId.']['.$this->iDay.']';
		$oInput->value = '1';
		$oInput->title = L10N::t('Abwesenheit entschuldigt', Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);

		$isExcused = false;
		if($this->isDayExcused($aResultData['excused'])) {
			$oInput->checked = 'checked';
			$isExcused = true;
		}

		$oSpan->setElement($oInput);
		$oDiv->setElement($oSpan);

		$container->setElement($oDiv);
		
		// Abwesenheitsgründe
		$absenceReasons = \TsTuition\Entity\AbsenceReason::getOptions();
		
		if(!empty($absenceReasons)) {
			$absenceReasonDays = json_decode($aResultData['absence_reasons'], true);
			
			$select = new Ext_Gui2_Html_Select;
			$select->class = 'form-control input-sm multiple_handle absence-reason-select';
			$select->style = 'width: 155px;';

			// Select nur anzeigen, wenn Abwesenheit gespeichert
			if(
				$aResultData[\Ext_Thebing_Tuition_Attendance::DAY_MAPPING[$this->iDay]] === null ||
				bccomp($aResultData[\Ext_Thebing_Tuition_Attendance::DAY_MAPPING[$this->iDay]], 0, 2) === 0
			) {
				$select->style .= 'display: none;';
			}
			$select->name = 'absence_reason['.$iRowId.']['.$this->iDay.']';
			$select->addOption('', '-- '.$this->oGui->t('Abwesenheitsgrund').' --');

			foreach($absenceReasons as $absenceReasonId=>$absenceReasonLabel) {
				$selected = ($absenceReasonDays[$this->iDay] == $absenceReasonId?true:false);
				$select->addOption($absenceReasonId, $absenceReasonLabel, $selected);
			}

			$container->setElement($select);
		}
		
		$sHtml = $container->generateHTML();

		return $sHtml;

	}

	private function isDayExcused($iExcused) {

		return $iExcused & pow(2, ($this->iDay - 1));

	}
	
	private function isDayOnline($iOnline) {

		return $iOnline & pow(2, ($this->iDay - 1));

	}

	private function isDayAttendanceSaved($resultData) {

		return $resultData[Ext_TC_Util::convertWeekdayToString($this->iDay)] !== null;

	}
	
}
