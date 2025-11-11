<?php

class Ext_TS_System_Checks_Inquiry_Group_TransferMode extends Ext_TS_System_Checks_Inquiry_Journey_TransferMode {

	public function getTitle() {
		return 'Migration of group transfer mode';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$fields = DB::describeTable('kolumbus_groups', true);
		if (!isset($fields['transfer'])) {
			return true;
		}

		Util::backupTable('kolumbus_groups');

		DB::addField('kolumbus_groups', 'transfer_mode', "BIT(2) NOT NULL DEFAULT b'0'", 'transfer');

		$sql = "
			SELECT
				id,
				transfer
			FROM
				kolumbus_groups
			WHERE
				transfer != '' AND
			    transfer != 'no'
		";

		$rows = (array)DB::getQueryRows($sql);
		foreach ($rows as $row) {

			// Neuer Wert wurde bereits ins alte Textfeld gespeichert
			if (in_array($row['transfer'], [\Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL, \Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE, \Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH])) {
				$row['transfer_mode'] = $row['transfer'];
			}

			// Alten Wert konvertieren
			if (empty($row['transfer_mode'])) {
				$row['transfer_mode'] = $this->calculateTransferMode($row['transfer']);
			}

			if (empty($row['transfer_mode'])) {
				$this->logError(sprintf('Unknown transfer_mode: %s (%d)', $row['transfer'], $row['id']));
			}

			$sql = "
				UPDATE
					kolumbus_groups
				SET
					transfer_mode = :transfer_mode,
				    `changed` = `changed`
				WHERE
					id = :id
			";

			$this->logInfo(sprintf('Set group transfer_mode to %s (%s, %d)', $row['transfer_mode'], $row['transfer'], $row['id']));

			DB::executePreparedQuery($sql, $row);

		}

		DB::executeQuery(" ALTER TABLE kolumbus_groups DROP transfer ");

		// YML-Cache für 2.022 Hotfix leeren (falls das überhaupt funktioniert)
		WDCache::delete('Ext_Gui2_Config_Parser::load_system/bundles/Ts/Resources/config/gui2/inquiry_group.yml');
		WDCache::deleteGroup('Ext_Gui2_Config_Parser::load');

		(new Ext_TS_System_Checks_Index_Reset_Inquiry_Group())->executeCheck();

		return true;

	}

}
