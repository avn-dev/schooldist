<?php

// Achtung: Dieser Check wird in Ext_Thebing_System_Checks_Combination_InboxAndNumberRange aufgerufen!
class Ext_Thebing_System_Checks_Numberranges2 extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Number range update';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Number range update';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_inboxlist`
			WHERE
				`active` = 1
		";

		// Liste aller Inboxen holen
		// Davon gibt es ja eh nun mindestens eine
		$aInboxes = DB::getQueryCol($sSql);

		Util::backupTable('ts_number_ranges_allocations_sets_inboxes');
		DB::begin('Ext_Thebing_System_Checks_Numberranges2');

		$sSql = "
			SELECT
				`tc_nras`.`id`
			FROM
				`tc_number_ranges_allocations_sets` `tc_nras` LEFT JOIN
				`ts_number_ranges_allocations_sets_inboxes` `ts_nrasi` ON
					`ts_nrasi`.`set_id` = `tc_nras`.`id`
			WHERE
				`tc_nras`.`active` = 1 AND
				`ts_nrasi`.`set_id` IS NULL
		";

		// Alle Nummernkreiszuweiseungen holen, welche keine Inbox zugewiesen haben
		$aSets = DB::getQueryCol($sSql);

		// Jeder Nummernkreiszuweisung jede Inbox zuweisen
		$aSql = array();
		$aSqlBuilder = array();
		$iValueCount = 1;
		foreach((array)$aSets as $iSetId) {
			foreach($aInboxes as $iInbox) {
				$aSqlBuilder[] = "( :set_id_".$iValueCount.", :inbox_id_".$iValueCount." )";
				$aSql['set_id_'.$iValueCount] = $iSetId;
				$aSql['inbox_id_'.$iValueCount] = $iInbox;
				$iValueCount++;
			}

		}

		if(!empty($aSqlBuilder)) {
			$sSql = " INSERT INTO `ts_number_ranges_allocations_sets_inboxes` VALUES ";
			$sSql .= join(",\n" , $aSqlBuilder);

			DB::executePreparedQuery($sSql, $aSql);
		}

		DB::commit('Ext_Thebing_System_Checks_Numberranges2');
		return true;

	}

}
