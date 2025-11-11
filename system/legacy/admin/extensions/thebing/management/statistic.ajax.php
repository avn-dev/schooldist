<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

/**
 * @todo Voll mit Redundanzen, aber ich hoffe, das gibt es nicht mehr lange daher ändere ich das nicht.
 */

$sDescription = Ext_Thebing_Management_Statistic::$_sDescription;

$oFrom = Ext_Thebing_Format::ConvertDate($_VARS['from'], null, 3);
$oTill = Ext_Thebing_Format::ConvertDate($_VARS['till'], null, 3);

// ConvertDate liefert bei leeren Werten einen leeren String zurück
// Die Objekte benötigen DateTime-Objekte, aber die Prüfung des Datums erfolgt erst später
if(
	!is_object($oFrom) ||
	!is_object($oTill)
) {
	$oFrom = new DateTime();
	$oTill = new DateTime();
}

$oTill->setTime(23, 59, 59);

if(isset($_VARS['action']) && $_VARS['action'] == 'load_table') {

	$aStatistics = array();
	$bDateFilter = false;

	if(!empty($_VARS['class'])) {
		// Statische Statistik
		// Datumswerte werden wegen dem Datumscheck erst unten gesetzt
		$aStatistics[] = new $_VARS['class']($oFrom, $oTill);
	} elseif($_VARS['page_id'] == 0 && $_VARS['statistic_id'] > 0) {
		$aStatistics[] = (int)$_VARS['statistic_id'];
	} elseif($_VARS['page_id'] > 0 && $_VARS['statistic_id'] > 0) {
		$aStatistics[] = (int)$_VARS['statistic_id'];
	} else {
		$oPage = new Ext_Thebing_Management_Page($_VARS['page_id']);
		$aStatistics = $oPage->getStatisticsLinks();
	}

	// IDs in Objekte umwandeln und auf Datumsfilter prüfen
	foreach($aStatistics as &$mStatistic) {

		// IDs in Objekte umwandeln
		if(
			!(
				$mStatistic instanceof Ext_Thebing_Management_Statistic ||
				$mStatistic instanceof Ext_Thebing_Management_Statistic_Static_Abstract
			)
		) {
			$mStatistic = new Ext_Thebing_Management_Statistic($mStatistic);
		}

		// Prüfen, ob irgendeine Statistik einen Datumsfilter hat
		if(
			$mStatistic->type == 2 || (
				$mStatistic instanceof Ext_Thebing_Management_Statistic_Static_Abstract &&
				$mStatistic->getFakeStatisticObject()->type == 2
			)
		) {
			// Bei absoluten Statistiken den Datumsfilter aktivieren
			$bDateFilter = true;
		}
	}

	// Datumsfilter-Werte prüfen
	$sDateError = L10N::t('Bitte füllen Sie die beiden Datumsfilter korrekt aus.', $sDescription);
	$bValidDates = true;
	if($bDateFilter) {
		// Erst einmal so prüfen, da das der tolle GUI-Timefilter nicht kann
		if(empty($_VARS['from']) || empty($_VARS['till'])) {
			$bValidDates = false;
		} else {
			$mCheck = Ext_Gui2_Bar_Timefilter::checkFromAndUntilStatic($_VARS['from'], $_VARS['till'], $sDescription);
			if($mCheck !== true) {
				$sDateError = $mCheck;
				$bValidDates = false;
			}
		}
	}

	$aData = array();
	foreach((array)$aStatistics as $oStatistic) {
		$oBlock = new Ext_Thebing_Management_PageBlock($oStatistic, $oFrom->getTimestamp(), $oTill->getTimestamp());
		$aFilter = Ext_Thebing_Management_PageBlock::getFilterData($oStatistic, $oBlock);

		// IDs von statischen Statistiken entsprechen den jeweiligen Klassennamen
		if($oStatistic instanceof Ext_Thebing_Management_Statistic_Static_Abstract) {
			$mStatisticId = get_class($oStatistic);
		} else {
			$mStatisticId = $oStatistic->id;
		}

		if(empty($_VARS['from']) || empty($_VARS['till'])) {
			$aFilter['global']['from'] = $aFilter['global']['till'] = '';
		}

		parse_str(rawurldecode($_VARS['filter'][$mStatisticId]), $_VARS['filter'][$mStatisticId]);

		$aAdditionalCalendars = [
			['service_from_start', 'service_from_end'],
			['course_from_start', 'course_from_end'],
			['created_from', 'created_until']
		];

		foreach($aAdditionalCalendars as $aCalendar) {
			if(
				$bValidDates && (
					!empty($_VARS['filter'][$mStatisticId]['save'][$aCalendar[0]]) ||
					!empty($_VARS['filter'][$mStatisticId]['save'][$aCalendar[1]])
				)
			) {
				// Generell auf false setzen, da auch geprüft werden muss, ob einer der beiden Werte leer ist (macht checkFromAnndUntil nicht)
				$bValidDates = false;

				// Erst hier setzen mit Referenz setzen, da ansonsten die Werte leer in $_VARS geschrieben werden
				$sFrom = &$_VARS['filter'][$mStatisticId]['save'][$aCalendar[0]];
				$sUntil = &$_VARS['filter'][$mStatisticId]['save'][$aCalendar[1]];

				if(!empty($sFrom) && !empty($sUntil)) {
					$bValidDates = true;
					$mCheck = Ext_Gui2_Bar_Timefilter::checkFromAndUntilStatic($sFrom, $sUntil, $sDescription);
					if($mCheck !== true) {
						$bValidDates = false;
						$sDateError = $mCheck;
					}
				}
			}
		}

		$aTmpData = array(
			'hash' => $aFilter['hash'],
			'data' => $aFilter['data'],
			'statistic_id' => $mStatisticId,
			'block_title' => $oBlock->getTitle(),
			'filter_title' => L10N::t('Filter', $sDescription),
			'block_filter' => $aFilter['html'],
			'global_filter' => $aFilter['global'],
			'dates_error' => $sDateError,
			'school_error' => L10N::t('Die Übersetzungen der gewählten Schulen enthalten nicht alle die Standardsprache. Bitte wählen Sie passende Schulen aus.', $sDescription),
			'show_dates_error' => !$bValidDates,
			'has_export' => true,
			'user_has_filter_right' => Ext_Thebing_Access::hasRight('thebing_management_reports_filter'),
			'statistic_period' => $oStatistic->period
		);

		if($bValidDates) {
			if($oStatistic instanceof Ext_Thebing_Management_Statistic_Static_Abstract) {
				$oStatistic->setFilter($_VARS['filter'][$mStatisticId]['save']);
				$oStatistic->from = $oFrom;
				$oStatistic->until = $oTill;

				$aTmpData['block_title'] = $oStatistic::getTitle();
				$aTmpData['has_export'] = $oStatistic::isExportable();
				$aTmpData['block_table'] = $oStatistic->render();
			} else {
				$aTmpData['block_table'] = $oBlock->getResults($_VARS['filter'][$mStatisticId]);
			}

		}

		$aData[] = $aTmpData;
	}

	if(isset($_VARS['first_call']) && $_VARS['first_call'] == 1) {
		$aData['first_call'] = true;
	}

	echo json_encode($aData);

	exit;
}

elseif(isset($_VARS['action']) && $_VARS['action'] == 'export_excel') {

	if(!empty($_VARS['class'])) {
		$mStatisticId = $_VARS['class'];
	} else {
		$mStatisticId = (int)$_VARS['statistic_id'];
	}

	// Wenn der Wert als String ankommt
	if(is_scalar($_VARS['filter'][$mStatisticId])) {
		parse_str(rawurldecode($_VARS['filter'][$mStatisticId]), $_VARS['filter'][$mStatisticId]);
	}

	$oBlock = new Ext_Thebing_Management_PageBlock($mStatisticId, $oFrom->getTimestamp(), $oTill->getTimestamp(), true);

	$aAdditionalCalendars = [
		['service_from_start', 'service_from_end'],
		['course_from_start', 'course_from_end'],
		['created_from', 'created_until']
	];

	foreach($aAdditionalCalendars as $aCalendar) {
		if(
			true && (
				!empty($_VARS['filter'][$mStatisticId]['save'][$aCalendar[0]]) ||
				!empty($_VARS['filter'][$mStatisticId]['save'][$aCalendar[1]])
			)
		) {
			// Generell auf false setzen, da auch geprüft werden muss, ob einer der beiden Werte leer ist (macht checkFromAnndUntil nicht)
			$bValidDates = false;

			// Erst hier setzen mit Referenz setzen, da ansonsten die Werte leer in $_VARS geschrieben werden
			$sFrom = &$_VARS['filter'][$mStatisticId]['save'][$aCalendar[0]];
			$sUntil = &$_VARS['filter'][$mStatisticId]['save'][$aCalendar[1]];

			if(!empty($sFrom) && !empty($sUntil)) {
				$bValidDates = true;
				$mCheck = Ext_Gui2_Bar_Timefilter::checkFromAndUntilStatic($sFrom, $sUntil, $sDescription);
				if($mCheck !== true) {
					$bValidDates = false;
					$sDateError = $mCheck;
				}
			}
		}
	}

	if(!empty($_VARS['class'])) {
		$oStaticStatistic = new $_VARS['class']($oFrom, $oTill);
		$oStaticStatistic->setFilter($_VARS['filter'][$mStatisticId]['save']);
		$aTmpData['block_table'] = $oStaticStatistic->getExport();
	} else {
		$aFilter = $_VARS['filter'][$mStatisticId];
		$oBlock->getResults($aFilter);
	}

	exit;
}
