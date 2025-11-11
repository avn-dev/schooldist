<?php


$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);


if ($config->view == '' || $config->view == 'calendar') {

	$sTimeNow     = time();
	$sMonthDayNow = date("d", $sTimeNow);
	$sWeekDayNow  = date("w", $sTimeNow);
	$sMonthNow    = date("m", $sTimeNow);
	$sYearNow     = date("Y", $sTimeNow);

	$aWeekDayNamesTime = mktime(0, 0, 0, 2, 6, 2006);
	for ($k = 1; $k <= 7; $k++) {
		$aW[$k] = strftime("%a", $aWeekDayNamesTime);
		$aWeekDayNamesTime += 86400;
	}
	$aWeekDayNames = array(1 => $aW[1], 2 => $aW[2], 3 => $aW[3], 4 => $aW[4], 5 => $aW[5], 6 => $aW[6], 0 => $aW[7]);

	for ($i = 1; $i <= 12; $i++) {
		$aSelectMonth[$i] = strftime("%B", mktime(0, 0, 0, $i, 1, 2006));
	}
	for ($j = $sYearNow-1; $j <= $sYearNow+2; $j++) {
		$aSelectYear[$j] = $j;
	}

	$calendar_id = $config -> calendar_id;

	if (!($_VARS['month'])) {
		$_VARS['month'] = date("m", $sTimeNow);
	}
	if (!($_VARS['year'])) {
		$_VARS['year'] = date("Y", $sTimeNow);
	}

	$sMonthFwd  = ($_VARS['month'] == 12)?1:$_VARS['month']+1;
	$sMonthBack = ($_VARS['month'] == 1)?12:$_VARS['month']-1;
	$sYearFwd   = ($_VARS['month'] == 12)?$_VARS['year']+1:$_VARS['year'];
	$sYearBack  = ($_VARS['month'] == 1)?$_VARS['year']-1:$_VARS['year'];

	$sMonthStart = mktime(0, 0, 0, $_VARS['month'], 1, $_VARS['year']);

	$sMonthNameBack = strftime("%B %Y", mktime(0, 0, 0, $sMonthBack, 1, $sYearBack));
	$sMonthName     = strftime("%B %Y", $sMonthStart);
	$sMonthNameFwd  = strftime("%B %Y", mktime(0, 0, 0, $sMonthFwd, 1, $sYearFwd));

	$sMonthWeekDayStart = date("w", $sMonthStart);
	$sMonthDay          = date("j", $sMonthStart);
	$sMonthDayAmount    = date("t", $sMonthStart);
	$sYearWeek          = ($_VARS['month'] == 1) ? 1 : date("W", $sMonthStart);
	$sMonth             = date("m", $sMonthStart);
	$sYear              = date("Y", $sMonthStart);
}

if ($_VARS['task'] == "detail" && $_VARS['id'] != "") {

	$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'detail');

	// Zurück-Button
	$buffer = str_replace("<#back_url#>", $_SERVER['PHP_SELF']."?calendar_id=".$calendar_id."&year=".$_VARS['year']."&month=".$_VARS['month'], $buffer);

	// Eintrag
	$aDate = get_data(db_query("SELECT *, UNIX_TIMESTAMP(`date`) as date FROM calendar_data WHERE id = '".$_VARS['id']."' AND calendar_id = '".$calendar_id."'"));
	$aCalendar = get_data(db_query("SELECT * FROM calendar_init WHERE id = '".$calendar_id."'"));
	$aCalendar['categories'] = unserialize($aCalendar['categories']);
	foreach ($aCalendar['categories'] as $id => $category) {
		$aCategories[$id] = $category;
	}

	$buffer = str_replace("<#entry_title#>", $aDate['title'], $buffer);
	$buffer = str_replace("<#entry_text#>", $aDate['text'], $buffer);

	$buffer_strf_date 		= \Cms\Service\PageParser::checkForBlock($element_data['content'],'entry_strf_date');
	$buffer_strf_duration 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'entry_strf_duration');
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "entry_strf_date", strftime($buffer_strf_date, $aDate['date']));
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "entry_strf_duration", strftime($buffer_strf_duration, $aDate['duration']-3600));

	$buffer = str_replace("<#category_title#>", $aCalendar['categories'][$aDate['category']]['title'], $buffer);
	$buffer = str_replace("<#category_description#>", $aCalendar['categories'][$aDate['category']]['description'], $buffer);
	$buffer = str_replace("<#category_color#>", $aCalendar['categories'][$aDate['category']]['color'], $buffer);

} else {

	// Prüfen, welche Ansicht gewählt wurde
	if ($config->view == 'timetable') {

		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'timetable');

		// Zeitschranken prüfen und ggf. setzen
		if ($config->date_from > 0) {
			$sQueryDateFrom = " AND UNIX_TIMESTAMP(`date`) >= ".$config->date_from;
		}
		if ($config->date_to > 0) {
			$sQueryDateTo = " AND UNIX_TIMESTAMP(`date`) <= ".$config->date_to;
		}

		$sQuery = "
					SELECT 
						*, 
						UNIX_TIMESTAMP(`date`) as date_ts 
					FROM 
						calendar_data d
					WHERE 
						calendar_id = '".$config->calendar_id."' AND
						".$sQueryDateFrom.$sQueryDateTo." AND
						(
							d.language_code = '' OR 
							d.language_code = '".\DB::escapeQueryString($page_data['language'])."'
						) AND 
						active = 1 
					ORDER BY 
						`date` ASC";

		$aYears = array();

		$rDate = db_query($sQuery);
		while ($aDate = get_data($rDate)) {

			$iYear = date('Y', $aDate['date_ts']);
			$iMonth = date('m', $aDate['date_ts']);
			$iDay = date('d', $aDate['date_ts']);

			if (!in_array($iYear, $aYears)) {
				$aYears[] = $iYear;
			}

			$aDates[$iYear][$iMonth][$iDay][] = $aDate;
		}

		// Jahre
		$buffer_years = \Cms\Service\PageParser::checkForBlock($buffer, 'years');

		// Standardwerte, falls nicht gesetzt
		if (!isset($_VARS['year'])) {
			// use the current year if it is in the list of available years, or
			// of no years are available...
			$iCurrentYear = date("Y", time());
			if (in_array($iCurrentYear, $aYears) || count($aYears) < 1) {
				$_VARS['year'] = $iCurrentYear;
			}
			// ...otherwise use the latest year in the list.
			else {
				$_VARS['year'] = end($aYears);
			}
		}

		if (!isset($_VARS['month'])) {
			$_VARS['month'] = date("m", time()); // current month
		}

		foreach ($aYears as $iYear) {
			$output_years_temp = $buffer_years;

			$output_years_temp = str_replace("<#year#>", $iYear, $output_years_temp);
			$output_years_temp = str_replace("<#year_link#>", $_SERVER['PHP_SELF']."?calendar_id=".$config->calendar_id."?year=".$_VARS['year']."&month=".$_VARS['month'], $output_years_temp);

			$output_years .= $output_years_temp;
		}

		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "years", $output_years);

		// Monate
		$buffer_months = \Cms\Service\PageParser::checkForBlock($buffer, 'months');

		for ($i = 1; $i <= 12; $i++) {
			$output_months_temp = $buffer_months;
			
			$output_months_temp = str_replace("<#month#>", strftime('%B', mktime(23, 59, 59, $i, 1, 2007)), $output_months_temp);
			$output_months_temp = str_replace("<#month_link#>", $_SERVER['PHP_SELF']."?calendar_id=".$config->calendar_id."?year=".$_VARS['year']."&month=".sprintf("%02d", $i), $output_months_temp);
			
			$output_months .= $output_months_temp;
		}

		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "months", $output_months);

		$buffer_days = \Cms\Service\PageParser::checkForBlock($buffer, 'days');

		// Tage
		foreach ((array)$aDates[$_VARS['year']][$_VARS['month']] as $aDay) {
			$output_days_temp = $buffer_days;

			// strftime / date
			$buffer_strf_date = \Cms\Service\PageParser::checkForBlock($buffer_days, 'day_strf_date');
			$output_days_temp = \Cms\Service\PageParser::replaceBlock($output_days_temp, "day_strf_date", strftime($buffer_strf_date, $aDay[0]['date_ts']));

			$buffer_entries = \Cms\Service\PageParser::checkForBlock($buffer, 'day_entries');

			// Einträge
			foreach ((array)$aDay as $aEntry) {
				$output_entries_temp = $buffer_entries;

				$output_entries_temp = str_replace("<#entry_title#>", $aEntry['title'], $output_entries_temp);

				// strftime / date from
				$buffer_strf_date_from = \Cms\Service\PageParser::checkForBlock($buffer_days, 'entry_strf_date_from');
				$output_entries_temp = \Cms\Service\PageParser::replaceBlock($output_entries_temp, "entry_strf_date_from", strftime($buffer_strf_date_from, $aEntry['date_ts']));

				// strftime / date to
				$buffer_strf_date_to = \Cms\Service\PageParser::checkForBlock($buffer_days, 'entry_strf_date_to');
				$output_entries_temp = \Cms\Service\PageParser::replaceBlock($output_entries_temp, "entry_strf_date_to", strftime($buffer_strf_date_to, $aEntry['date_ts'] + $aEntry['duration']));

				$output_entries .= $output_entries_temp;
			}

			$output_days_temp = \Cms\Service\PageParser::replaceBlock($output_days_temp, "day_entries", $output_entries);

			unset($output_entries);

			$output_days .= $output_days_temp;
		}

		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "days", $output_days);

		// nicht ersetzte Tags entfernen
		$pos = 0;
		while($pos = strpos($buffer, '<#', $pos)) {
			$end = strpos($buffer, '#>', $pos);
			$var = substr($buffer, $pos+2, $end-$pos-2);
			$buffer = substr($buffer, 0, $pos) . $$var . substr($buffer, $end+2);
		}

	} else {
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'calendar');

		// Navigation
		$buffer = str_replace("<#backlink_url#>", $_SERVER['PHP_SELF']."?calendar_id=".$calendar_id."&year=".$sYearBack."&month=".$sMonthBack, $buffer);
		$buffer = str_replace("<#backlink_text#>", $sMonthNameBack, $buffer);
		$buffer = str_replace("<#forwardlink_url#>", $_SERVER['PHP_SELF']."?calendar_id=".$calendar_id."&year=".$sYearFwd."&month=".$sMonthFwd, $buffer);
		$buffer = str_replace("<#forwardlink_text#>", $sMonthNameFwd, $buffer);
		$buffer = str_replace("<#current_month#>", $sMonthName, $buffer);

		// Wochentage
		$buffer_days = \Cms\Service\PageParser::checkForBlock($element_data['content'],'days');
		foreach ($aWeekDayNames as $day) {
			$buffer_week .= str_replace("<#day#>", $day, $buffer_days);
		}
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "days", $buffer_week);

		$buffer_row 		= \Cms\Service\PageParser::checkForBlock($element_data['content'],'row');
		$buffer_col 		= \Cms\Service\PageParser::checkForBlock($element_data['content'],'col');
		$buffer_col_today 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'col_today');
		$buffer_col_sunday 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'col_sunday');
		$buffer_col_none 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'col_none');
		$buffer_entries 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'entries');

		$iFrom = mktime(0, 0, 0, $_VARS['month'], 1, $_VARS['year']);
		$iUntil = mktime(23, 59, 59, date("m", $iFrom), date("t", $iFrom), date("Y", $iFrom));

		$sSql = "
					SELECT 
						*, 
						UNIX_TIMESTAMP(`date`) as date_ts 
					FROM 
						calendar_data d
					WHERE 
						calendar_id = '".$config->calendar_id."' AND
						date BETWEEN ".date("YmdHis", $iFrom)." AND ".date("YmdHis", $iUntil)." AND
						(
							d.language_code = '' OR 
							d.language_code = '".\DB::escapeQueryString($page_data['language'])."'
						) AND 
						active = 1 
					ORDER BY 
						`date` ASC";
		$rDate = db_query($sSql);
		while ($aDate = get_data($rDate)) {
			$aDates["i".date("j",$aDate['date_ts'])][] = $aDate;
		}

		$aCalendar = get_data(db_query("SELECT * FROM calendar_init WHERE id = '".$calendar_id."'"));
		$aCalendar['categories'] = unserialize($aCalendar['categories']);

		while ($sMonthDay <= $sMonthDayAmount) {
			$buffer_row_loop = $buffer_row;
			$buffer_row_loop = str_replace("<#week#>", $sYearWeek, $buffer_row_loop);

			foreach ($aWeekDayNames as $daynumber => $dayname) {
				// Tage
				if (($sMonthWeekDayStart == $daynumber && $sMonthDay == 1) xor ($sMonthDay <= $sMonthDayAmount && $sMonthDay > 1)) {
					if ($sMonthDay == $sMonthDayNow && $sMonth == $sMonthNow && $sYear == $sYearNow) {
						$buffer_col_loop = str_replace("<#date#>", $sMonthDay, $buffer_col_today);
					} elseif ($daynumber != 0) {
						$buffer_col_loop = str_replace("<#date#>", $sMonthDay, $buffer_col);
					} else {
						$buffer_col_loop = str_replace("<#date#>", $sMonthDay, $buffer_col_sunday);
					}
				} else {
					$buffer_col_loop = $buffer_col_none;
				}

				// Einträge
				if (is_array($aDates["i".$sMonthDay])) {
					foreach ($aDates["i".$sMonthDay] as $date) {
						$buffer_entries_loop = $buffer_entries;
						$buffer_entries_loop = str_replace("<#id#>", $date['id'], $buffer_entries_loop);
						$buffer_entries_loop = str_replace("<#entry_title#>", $date['title'], $buffer_entries_loop);
						$buffer_entries_loop = str_replace("<#entry_date#>", date("H:i", $date['date_ts']), $buffer_entries_loop);
						$buffer_entries_loop = str_replace("<#category_color#>", $aCalendar['categories'][$date['category']]['color'], $buffer_entries_loop);
						$buffer_entries_loop = str_replace("<#parameter#>", "calendar_id=".$_VARS['calendar_id']."&id=".$date['id']."&year=".$_VARS['year']."&month=".sprintf("%02d", $_VARS['month']), $buffer_entries_loop);
						$buffer_entries_output .= $buffer_entries_loop;
					}
				} else {
					$buffer_entries_output = "";
				}

				$buffer_col_loop = \Cms\Service\PageParser::replaceBlock($buffer_col_loop, "entries", $buffer_entries_output);

				if (($sMonthWeekDayStart == $daynumber && $sMonthDay == 1) xor ($sMonthDay <= $sMonthDayAmount && $sMonthDay > 1)) {
					$sMonthDay++;
				}

				$buffer_col_output .= $buffer_col_loop;
				unset($buffer_entries_output);
			}
			$buffer_row_loop = \Cms\Service\PageParser::replaceBlock($buffer_row_loop, "col", $buffer_col_output);
			$buffer_row_loop = \Cms\Service\PageParser::replaceBlock($buffer_row_loop, "col_today", "");
			$buffer_row_loop = \Cms\Service\PageParser::replaceBlock($buffer_row_loop, "col_sunday", "");
			$buffer_row_loop = \Cms\Service\PageParser::replaceBlock($buffer_row_loop, "col_none", "");
			$buffer_row_output .= $buffer_row_loop;
			unset($buffer_col_output);

			$sYearWeek++;
		}
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer,"row",$buffer_row_output);
	}

	$pos = 0;
	while ($pos = strpos($buffer, '<#', $pos)) {
		$end = strpos($buffer, '#>', $pos);
		$var = substr($buffer, $pos+2, $end-$pos-2);
		$buffer = substr($buffer, 0, $pos) . $$var . substr($buffer, $end+2);
	}
}


echo $buffer;


?>