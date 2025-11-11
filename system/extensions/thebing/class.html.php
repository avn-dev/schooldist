<?php
	
class Ext_Thebing_Html {
	
	function printFormCountrySelect($title, $name, $selected) {
	
		$sLanguage = LANGUAGE;
		if ($sLanguage != 'de') {
			$sLanguage = 'en';
			$sLocale = 'en_EN';
		} else {
			$sLocale = 'de_DE';
		}

		$aCountries = Ext_Thebing_Country_Search::getLocalizedCountries();
		
		asort($aCountries);
		
	?>
	<tr>
		<th><?=$title?></th>
		<td>
			<select name="<?=$name?>" class="txt" style="width: 300px;">
				<option value=""<? if ($selected == "") { ?> selected<? } ?>></option>
	<?
		foreach ($aCountries as $key => $country) {
	?>
				<option value="<?=$key?>"<? if ($selected == $key) { ?> selected<? } ?>><?=$country?></option>
	<?
		}
	?>
			</select>
		</td>
	</tr>
	<?
	}
	
	function printFormDaySelect($name, $selected) {
	?>
	<select name="<?=$name?>" class="txt">
		<option value=""></option>
	<?
		for ($i = 1; $i <= 31; $i++) {
	?>
		<option value="<?=sprintf("%02d", $i)?>"<? if (sprintf("%02d", $i) == $selected) { ?> selected<? } ?>><?=sprintf("%02d", $i)?></option>
	<?
		}
	?>
	</select>
	<?
	}
	
	function printFormMonthSelect($name, $selected, $sAddon = '') {
	?>
	<select name="<?=$name?>" class="txt" <?=$sAddon?>>
		<option value=""></option>
	<?
		for ($i = 1; $i <= 12; $i++) {
	?>
		<option value="<?=sprintf("%02d", $i)?>"<? if (sprintf("%02d", $i) == $selected) { ?> selected<? } ?>><?=sprintf("%02d", $i)?></option>
	<?
		}
	?>
	</select>
	<?
	}
	
	function printFormYearSelect($name, $selected, $thisYear = 0) {
	?>
	<select name="<?=$name?>" class="txt">
		<option value=""></option>
	<?
		for ($i = 1900; $i <= date("Y", time()) + 1; $i++) {
			if (!$thisYear || $thisYear && $i >= date("Y", time())) {
	?>
		<option value="<?=sprintf("%02d", $i)?>"<? if (sprintf("%02d", $i) == $selected) { ?> selected<? } ?>><?=sprintf("%02d", $i)?></option>
	<?
			}
		}
	?>
	</select>
	<?
	}
	
	function printFormYearSelectNow($name, $selected, $thisYear = 0, $sAddon = '') {
	?>
	<select name="<?=$name?>" class="txt" <?=$sAddon?>>
		<option value=""></option>
	<?
		for ($i = date('Y', time()); $i <= date("Y", time()) + 1; $i++) {
			if (!$thisYear || $thisYear && $i >= date("Y", time())) {
	?>
		<option value="<?=sprintf("%02d", $i)?>"<? if ($i == $selected) { ?> selected<? } ?>><?=sprintf("%02d", $i)?></option>
	<?
			}
		}
	?>
	</select>
	<?
	}
}