<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class Timetable extends AbstractPage {

	public function render(array $aData = array()) {
		$sTemplate = $this->generatePageHeading($this->oApp->t('Timetable'));

		return $sTemplate;
	}

	/**
	 * @TODO Aktuell ist die Funktion nur auf die Student-App ausgelegt
	 * @TODO Aktuell wird hier die vertikale Ansicht als HTML zurückgegeben. Das muss aber die App rendern!
	 *
	 * @return array
	 */
	public function getStorageData() {

		$aList = array('items' => array());

        $oInquiry = $this->oApp->getInquiry();

		// @TODO Freigabe der Wochen?
		$aClassWeeks = \Ext_Thebing_Tuition_Class::getClassWeeksByInquiry($oInquiry->id);
		$aCalendarWeeks = array();

		$oFormatWeekday = new \Ext_Thebing_Gui2_Format_Day('%A');
		$oFormatTime = new \Ext_Thebing_Gui2_Format_Time();
		$oFormatTeacher = new \Ext_Thebing_Gui2_Format_School_Tuition_Teachers();
		$oFormatTeacher->oLanguageObject = new \Tc\Service\Language\Frontend($this->oApp->getInterfaceLanguage());
		$oFormatTeacher->bLabel = false;

		foreach($aClassWeeks as $sClassWeek) {

			$sHtml = '';

			$dMonday = new \DateTime($sClassWeek);
			$aCalendarWeeks[] = $dMonday->format('Y-W');

			// Blöcke nach Tagen gruppieren
			$aGroupedBlocks = $aDaysOfBlocks = array();
			$aClassBlocks = \Ext_Thebing_Tuition_Class::getClassesByInquiryAndWeek($oInquiry->id, $sClassWeek);

			foreach($aClassBlocks as $aClassBlockData) {

				// GROUP-CONCAT teilen (pro Tag des Blocks durchlaufen)
				$aDaysData = (array)explode(',', $aClassBlockData['days']);
				foreach($aDaysData as $aDayData) {

					// Beispiel-Format: 8328_1_08:00:00_09:00:00
					list($iBlockId, $iDay, $sStartTime, $sEndTime) = explode('_', $aDayData);
					$aClassBlockData['block_id'] = $iBlockId;
					$aClassBlockData['start_time'] = $sStartTime;
					$aClassBlockData['end_time'] = $sEndTime;

					// Zusätzlich die Tage eines Blocks merken
					$aDaysOfBlocks[$iBlockId][] = $iDay;
					$aGroupedBlocks[$iDay][] = $aClassBlockData;
				}
			}

			// Nach Tagen sortieren
			// Reihenfolge wäre ansonsten nur auf »Gut Glück« richtig
			ksort($aGroupedBlocks);

			foreach($aGroupedBlocks as $iDay => $aDayBlock) {

				// Datum für Überschrift des Tages bestimmen
				$dTmp = clone $dMonday;
				$dTmp->add(new \DateInterval('P'.($iDay - 1).'D'));

				// Wochentag und Datum formatieren für Überschrift des Tages
				$sHtml .= $this->generatePageBlock($oFormatWeekday->format($iDay).', '.$this->formatDate($dTmp), '');

				// Blöcke pro Tag durchlaufen
				foreach($aDayBlock as $aBlock) {

					// Tage formatieren
					/** @TODO Durch die \Ext_Thebing_Util::buildJoinedWeekdaysString() ersetzen */
					sort($aDaysOfBlocks[$aBlock['block_id']]);
					$aDiff = array_diff(array(1, 2, 3, 4, 5), $aDaysOfBlocks[$aBlock['block_id']]);
					if(empty($aDiff)) {
						// Sonderbedingung: Bei Montag – Freitag eben dieses anzeigen
						$sDays = $oFormatWeekday->format(1).' – '.$oFormatWeekday->format(5);
					} else {
						$sDays = join(', ', array_map(function($iDay) use($oFormatWeekday) {
							return $oFormatWeekday->format($iDay);
						}, $aDaysOfBlocks[$aBlock['block_id']]));
					}

					$sBlock = '<table class="table-th-left-aligned">';
					$sBlock .= '<tbody>';

					$sBlock .= '<tr>';
					$sBlock .= '<th>'.$this->t('Time').': </th>';
					$sBlock .= '<td>'.$sDays.'<br />';
					$sBlock .= $oFormatTime->format($aBlock['start_time']).' – '.$oFormatTime->format($aBlock['end_time']);
					$sBlock .= '</td>';
					$sBlock .= '</tr>';

					$sBlock .= '<tr>';
					$sBlock .= '<th>'.$this->t('Teacher').': </th>';
					$sBlock .= '<td>'.$oFormatTeacher->format(null, $oDummy, $aBlock).'</td>';
					$sBlock .= '</tr>';

					if(!empty($aBlock['building'])) {
						$sBlock .= '<tr>';
						$sBlock .= '<th>'.$this->t('Building').': </th>';
						$sBlock .= '<td>'.$aBlock['building'].'</td>';
						$sBlock .= '</tr>';
					}

					if(!empty($aBlock['classroom'])) {
						$sBlock .= '<tr>';
						$sBlock .= '<th>'.$this->t('Room').': </th>';
						$sBlock .= '<td>'.$aBlock['classroom'].'</td>';
						$sBlock .= '</tr>';
					}

					$sBlock .= '</tbody>';
					$sBlock .= '</table>';

					$sHtml .= $this->generateBlock($aBlock['class_name'], $sBlock);
				}
			}

			$aList['items'][] = array(
				'title' => $this->formatWeekTitle($dMonday),
				'key' => $dMonday->format('Y-W'),
				'html' => $sHtml
			);

		}

		// Standardwert des Wochenselects ermitteln
		if(!empty($aCalendarWeeks)) {
			$oCurrentMonday = new \DateTime('monday this week');
			$sCurrentWeek = $oCurrentMonday->format('Y-W');
			if(in_array($sCurrentWeek, $aCalendarWeeks)) {
				// Wenn aktuelle Woche vorhanden, dann diese nehmen
				$aList['select_default'] = $sCurrentWeek;
			} else {
				// Ansonsten die zuletzt gefundene Woche nehmen
				sort($aCalendarWeeks);
				$aList['select_default'] = array_pop($aCalendarWeeks);
			}
		}

		return $aList;
	}
}
