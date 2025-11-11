<?php

/**
 * Versucht bei Version-Items vom Typ Special die richtige parent_id zu finden.
 * Ausführlich beschrieben in: https://redmine.thebing.com/redmine/issues/4367
 *
 * @since 26.02.2013
 * @author DG <dg@thebing.com>
 */
class Ext_TS_System_Checks_Document_Version_Item_ParentSpecial extends GlobalChecks
{
	public function getTitle() {
		return 'Check Document Line Items';
	}

	public function getDescription() {
		return 'Check for valid allocations of document line items (specials).';
	}

	public function executeCheck() {

		set_time_limit(7200);
		ini_set('memory_limit', '1024M');

		Util::backupTable('kolumbus_inquiries_documents_versions_items');
		DB::begin('Ext_TS_System_Checks_Document_Version_Item_ParentSpecial');

		// Alle Version Items holen, wessen
		//	* Version eine LastVersion ist
		//	* Version mindestens ein Special hat
		//	* Anzahl der Special-Indexspalten-Einträge der Version nicht
		//		mit der Anzahl der Specials dieser Version übereinstimmt
		$sSql = "
			SELECT
				`kidvi`.`id`,
				`kidvi`.`version_id`,
				`kidvi`.`type`,
				`kidvi`.`type_id`,
				`kidvi`.`parent_id`, (
					SELECT
						COUNT(`sub`.`id`)
					FROM
						`kolumbus_inquiries_documents_versions_items` `sub`
					WHERE
						`sub`.`active` = 1 AND
						`sub`.`version_id` = `kidvi`.`version_id` AND
						`sub`.`type` = 'special'
				) `positions_special_count`, (
					SELECT
						COUNT(`sub`.`id`)
					FROM
						`kolumbus_inquiries_documents_versions_items` `sub`
					WHERE
						`sub`.`active` = 1 AND
						`sub`.`version_id` = `kidvi`.`version_id` AND
						`sub`.`index_special_amount_net` IS NOT NULL
				) `positions_with_special_amount_set`
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi`
			WHERE
				`kidvi`.`active` = 1 AND
				`kidvi`.`version_id` IN (
					SELECT
						`latest_version`
					FROM
						`kolumbus_inquiries_documents` `kid`
					WHERE
						`kid`.`active` = 1
				)
			HAVING
				`positions_special_count` > 0 AND
				`positions_special_count` != `positions_with_special_amount_set`
		";

		$oDb = DB::getDefaultConnection();
		$aCollection = $oDb->getCollection($sSql, array());
		$aVersions = array();

		// Version-Items nach Version sortieren
		foreach($aCollection as $aRow) {
			$aVersions[(int)$aRow['version_id']][] = $aRow;
		}

		try {
			foreach($aVersions as $aVersionPositions) {
				$this->_processItems($aVersionPositions);
			}
		} catch(Exception $e) {
			$this->logError($e->getMessage());
			throw $e;
		}

		DB::commit('Ext_TS_System_Checks_Document_Version_Item_ParentSpecial');
		$this->logInfo('Executed Ext_TS_System_Checks_Document_Version_Item_ParentSpecial');

		// Check nochmals ausführen, damit die Special-Indexspalten nun befüllt werden
		$oCheck = new Ext_Thebing_System_Checks_Documents_Items_IndexSpecials();
		$oCheck->bBackupTable = false;
		$oCheck->executeCheck();
		$this->logInfo('Executed Ext_Thebing_System_Checks_Documents_Items_IndexSpecials');

		return true;
	}

	protected function _processItems($aVersionPositions) {

		foreach($aVersionPositions as $aRow) {

			// Jedes Special-Item behandeln
			if($aRow['type'] === 'special') {

				// Alle Items aus der älteren Version holen, die das Special als falsches Parent-Item drinstehen hat
				$sSql = "
					SELECT
						`kidvi`.`id`,
						`kidvi`.`type`,
						`kidvi`.`type_id`
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi`
					WHERE
						`kidvi`.`version_id` IN (
							SELECT
								`version_id`
							FROM
								`kolumbus_inquiries_documents_versions_items` `kidvi2`
							WHERE
								`kidvi2`.`id` = :parent_id
						)
				";

				$aSql = array('parent_id' => $aRow['parent_id']);
				$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

				// Eigentlich sollte das hier nie auftreten, da der Query oben das schon abdecken sollte
				if(empty($aResult)) {
					continue;
				}

				// *Parent*-Special-Item der alten Version finden, die auf das jetzige Special-Item matcht
				foreach($aResult as $aTmpRow) {
					if($aTmpRow['id'] === $aRow['parent_id']) {
						$aParentRow = $aTmpRow;
						break;
					}
				}

				// Eigentlich schon abgefangen durch empty() und Query, aber dennoch prüfen
				if(empty($aParentRow)) {
					$this->logError('No matching old parent row has been found for special "'.$aRow['id'].'"!');
					continue;
				}

				// Hier das neue Parent-Item matchen anhand des Typen des alten Parent-Items
				// Da das hier nicht eindeutig ist, wird das erstbeste genommen
				foreach($aVersionPositions as $aTmpRow) {
					if(
						$aTmpRow['type'] === $aParentRow['type'] &&
						$aTmpRow['type_id'] === $aParentRow['type_id']
					) {
						$aMatchedParent = $aTmpRow;
						break;
					}
				}

				// Es kann sein, dass manche Positions nicht auf dem PDF sind
				if(empty($aMatchedParent)) {
					$this->logError('No matching new parent row has been found for special "'.$aRow['id'].'"!');
					continue;
				}

				// Wenn die IDs eh gleich sind, braucht man ja auch nichts zu updaten.
				if($aRow['id'] == $aMatchedParent['id']) {
					continue;
				}

				$sSql = "
					UPDATE
						`kolumbus_inquiries_documents_versions_items`
					SET
						`parent_id` = :parent_id
					WHERE
						`id` = :row_id
				";

				$aSql = array(
					'row_id' => (int)$aRow['id'],
					'parent_id' => (int)$aMatchedParent['id']
				);

				try {
					DB::executePreparedQuery($sSql, $aSql);
					$this->logInfo('Changed special "'.$aRow['id'].'" parent_id from "'.$aRow['parent_id'].'" to "'.$aMatchedParent['id'].'"');
				} catch(Exception $e) {
					$this->logError('Failed to change parent_id of special!', $e->getMessage());
					throw $e;
				}

			}
		}

	}

}