<?php

/**
 * Check zum Korrigieren von position = 0 (position setzen wie in GUI angezeigt)
 */
abstract class Ext_TC_System_Checks_Gui2_FixSortableRow extends GlobalChecks {

	protected $sTable = '';
	protected $sGroupBy = '';

	public function executeCheck() {

		Util::backupTable($this->sTable);

		DB::begin(get_class($this));

		$aRows = DB::getQueryRows("
			SELECT
				*
			FROM
				{$this->sTable}
			ORDER BY
				`position`,
				`id`
		");

		$aNewPositions = [];

		foreach($aRows as $aRow) {

			if(empty($aNewPositions[$aRow[$this->sGroupBy]])) {
				$aNewPositions[$aRow[$this->sGroupBy]] = 1;
			}

			$aRow['position'] = $aNewPositions[$aRow[$this->sGroupBy]]++;

			DB::executePreparedQuery("
				UPDATE
					{$this->sTable}
				SET
					`position` = :position
				WHERE
					`id` = :id
			", $aRow);

		}

		DB::commit(get_class($this));

		return true;

	}

}