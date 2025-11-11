<?php
 
class Ext_TC_Import_Add {
	
	public $sTable;
	public $aCheckFields;
	public $aData;
	public $iOriginalId = null;
	public $sImportKey = null;
	public $bUpdateEntry = true;

	public $bNewEntry = false;

	/**
	 * Prüft, ob ein Eintrag schon vorhanden ist und legt ihn dann an oder aktualisiert ihn
	 * Ausnahmsweise mehr Parameter wg. Zeitmangel!
	 * 
	 * @param string $sTable
	 * @param array $aCheckFields
	 * @param array $aData
	 * @return int 
	 */
	public function execute() {
	
		$this->bNewEntry = false;
		
		if($this->aCheckFields !== null) {
			$sSql = "
					SELECT
						*
					FROM
						#table
					WHERE
						1
					";
			$aCheckKeys = array_keys($this->aCheckFields);
			foreach($aCheckKeys as $sKey) {
				$sSql .= " AND `".$sKey."` = :".$sKey." ";
			}
			$sSql .= " LIMIT 1";
			$aCheckSql = $this->aCheckFields;
			$aCheckSql['table'] = $this->sTable;
			$aCheck = DB::getQueryRow($sSql, $aCheckSql);
		} else {
			$aCheck = null;
		}

		if(empty($aCheck)) {

			$iEntryId = DB::insertData($this->sTable, $this->aData);

			$this->bNewEntry = true;
			
		} else {

			unset($this->aData['created']);

			// Daten vervollständigen
			$aIntersect = array_intersect_key($aCheck, $this->aData);

			foreach($this->aData as $sKey=>&$sValue) {
				if(
					empty($sValue) ||
					!empty($aIntersect[$sKey])
				) {
					unset($this->aData[$sKey]);
				}
			}

			// Wenn es neue Daten gibt, Datensatz aktualisieren
			if(
				!empty($this->aData) &&
				$$bUpdateEntrybUpdateEntry === true
			) {
				DB::updateData($this->sTable, $this->aData, "`id` = ".(int)$aCheck['id']);
			}

			$iEntryId = $aCheck['id'];

		}

		if($this->iOriginalId > 0) {
			$sSql = "
				REPLACE 
					`__import_mapping`
				SET
					`table` = :table,
					`original_id` = :original_id,
					`new_id` = :new_id,
					`import_key` = :import_key
				";
			$aSql = array(
				'table' => $this->sTable,
				'original_id' => (int)$this->iOriginalId,
				'new_id' => (int)$iEntryId,
				'import_key' => $this->sImportKey
			);
			DB::executePreparedQuery($sSql, $aSql);
		}

		return $iEntryId;

	}
	
}