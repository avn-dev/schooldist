<?php

include(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

accesschecker('control');

//Admin_Html::loadAdminHeader();

if(isset($_VARS['password'])) {

	if ($_VARS['password'] == 'TS_DELETE_0815') {

		$oUser = System::getCurrentUser();

		$oLog = Log::getLogger();
		$oLog->addInfo('Cleaning students tool', array('user' => $oUser->username, 'ip' => $_SERVER['REMOTE_ADDR']));

		// Tabellen die geleert werden können (Truncate) + backup
		$aTables = array(
			// Anfragen (Haben keine eigenen Tabellen mehr)
			// Buchungen
			'ts_inquiries',
			'ts_inquiries_contacts_logins',
			'ts_inquiries_contacts_logins_devices',
			'ts_inquiries_flex_uploads',
			'ts_inquiries_journeys',
			'ts_inquiries_journeys_accommodations',
			'ts_inquiries_journeys_accommodations_to_travellers',
			'ts_inquiries_journeys_activities',
			'ts_inquiries_journeys_activities_to_travellers',
			'ts_inquiries_journeys_additionalservices',
			'ts_inquiries_journeys_additionalservices_to_travellers',
			'ts_inquiries_journeys_courses',
			'ts_inquiries_journeys_courses_lessons_contingent',
			'ts_inquiries_journeys_courses_to_travellers',
			'ts_inquiries_journeys_insurances',
			'ts_inquiries_journeys_insurances_to_travellers',
			'ts_inquiries_journeys_transfers',
			'ts_inquiries_journeys_transfers_to_travellers',
			'ts_inquiries_matching_data',
			'ts_inquiries_sponsoring_guarantees',
			'ts_inquiries_partial_invoices',
			'ts_inquiries_payments_groupings',
			'ts_inquiries_to_contacts',
			'ts_inquiries_to_inquiries',
			'ts_inquiries_to_special_positions',
			'ts_journeys_travellers_visa_data',
			'ts_journeys_travellers_detail',
			'kolumbus_tuition_blocks_inquiries_courses',
			'kolumbus_inquiries_transfers_provider_request',
			'kolumbus_inquiries_positions_specials',
			'ts_inquiries_holidays',
			'ts_inquiries_holidays_splitting',
			'kolumbus_inquiries_group_flags',
			'ts_inquiries_tuition_index',
			'ts_inquiries_journeys_courses_tuition_index',
			// Gruppen,
			'kolumbus_groups',
			'kolumbus_groups_accommodations',
			'kolumbus_groups_courses',
			'kolumbus_groups_transfers',
			'ts_groups_to_contacts',
			'ts_groups_contacts_flags',
			// Dokumente
			'kolumbus_inquiries_documents',
			'kolumbus_inquiries_documents_versions',
			'ts_inquiries_documents_versions_addresses',
			'kolumbus_inquiries_documents_versions_items',
			'kolumbus_inquiries_documents_versions_items_specials',
			'kolumbus_inquiries_documents_versions_items_changes',
			'kolumbus_inquiries_documents_versions_priceindex',
			'kolumbus_inquiries_documents_versions_fields',
			'kolumbus_inquiries_documents_paymentdocuments',
			'ts_documents_release',
			'ts_documents_to_documents',
			'ts_documents_to_gui2',
			'ts_documents_release',
			'ts_documents_versions_paymentterms',
			// Kommunikation
			// TODO Das ist aber nicht so korrekt, da Nachrichten auch zu Agenturen oder Providern gehören
			'tc_communication_messages',
			'tc_communication_messages_addresses',
			'tc_communication_messages_addresses_relations',
			'tc_communication_messages_codes',
			'tc_communication_messages_creators',
			'tc_communication_messages_documents',
			'tc_communication_messages_files',
			'tc_communication_messages_files_relations',
			'tc_communication_messages_flags',
			'tc_communication_messages_flags_relations',
			'tc_communication_messages_incoming',
			'tc_communication_messages_relations',
			'tc_communication_messages_subjects',
			'tc_communication_messages_templates',
			'tc_communication_messages_templates_to_layouts',
			'tc_communication_messages_to_categories',
			'tc_communication_messages_to_messages',
			// Bezahlungen
			'kolumbus_inquiries_payments',
			'kolumbus_inquiries_payments_reminders',
			'kolumbus_inquiries_payments_overpayment',
			'kolumbus_inquiries_payments_items',
			'kolumbus_inquiries_payments_documents',
			'kolumbus_inquiries_payments_agencypayments',
			'kolumbus_inquiries_payments',
            'ts_inquiries_payments_release',
			'ts_teachers_payments_groupings',
			'ts_transfers_payments_groupings',
			'ts_inquiries_payments_groupings',
			'ts_inquiries_payments_processes',
			'kolumbus_accommodations_payments',
			'kolumbus_accounting_agency_payments',
			'kolumbus_agencies_manual_creditnotes_payments',
			'ts_accounts_transactions',
			#'kolumbus_agencies_payments_groups_assignments',
			'kolumbus_cheque_payment',
			'ts_teachers_payments',
			'kolumbus_template_payment_receipt',
			'kolumbus_transfers_payments',
			'ts_accommodations_payments_groupings',
			'ts_inquiries_payments_to_creditnote_payments',
			'ts_documents_to_inquiries_payments',
			// Matching
			'kolumbus_accommodations_allocations',
			// Klassenplanung
			'kolumbus_tuition_attendance',
			#'kolumbus_tuition_blocks',
			#'kolumbus_tuition_blocks_days',
			'kolumbus_tuition_blocks_inquiries_courses',
			#'kolumbus_tuition_blocks_substitute_teachers',
			'kolumbus_tuition_progress',
			// Transfer
			'kolumbus_groups_transfers',
			// Buchungsstapel
			'ts_booking_stacks',
			'ts_booking_stack_histories',
			'ts_documents_booking_stack_histories',
			// Einstufungstests
			'ts_placementtests_results',
			'ts_placementtests_results_details',
			'ts_placementtests_results_details_notices',
			'ts_placementtests_results_teachers',
		);

		// nur backup
		$aBackup = array(
			'wdbasic_attributes',
			'tc_contacts',
			'tc_contacts_details',
			'tc_contacts_to_addresses',
			'tc_contacts_to_emailaddresses',
			'tc_emailaddresses',
			'tc_addresses',
			'tc_contacts_numbers',
			'tc_gui2_designs_tabs_elements_values',
			'tc_flex_sections_fields_values',
		);

		$aFailBackup = array();
		$aDeleteContacts = array();

		// Backup zuerst!
		$aFinalTables = array_merge($aBackup, $aTables);

		$aBackupCache = array();

		foreach($aFinalTables as $sTable) {

			if(
				!$_VARS['no_backup'] &&
				!isset($aBackupCache[$sTable])
			) {
				// Backup der Tabelle anlegen
				$bBackup = Ext_Thebing_Util::backupTable($sTable);
				$aBackupCache[$sTable] = $sTable;
				// Wenn Backup fehlschlägt darf die Tabelle nicht bearbeitet werden
				if($bBackup == false) {
					$aFailBackup[] = $sTable;
					continue;
				}
			}

			// Tabellen von denen nur ein Backup gemacht wird dürfen nicht komplett geleert werden
			if(in_array($sTable, $aBackup)) {
				continue;
			}

			// Kontakte merken
			if(
				$sTable === 'ts_inquiries_to_contacts' ||
				$sTable === 'ts_enquiries_to_contacts' ||
				$sTable === 'ts_groups_to_contacts'
			) {
				$sDataSql = "SELECT `contact_id` FROM `" . $sTable . "`";
				$aData = (array)DB::getQueryData($sDataSql);
				foreach ($aData as $aTemp) {
					$aDeleteContacts[$aTemp['contact_id']] = $aTemp['contact_id'];
				}
			}

			$sSql = "DELETE FROM `" . $sTable . "`";
			DB::executeQuery($sSql);

			// Attribute zu Buchungen löschen
			DB::executeQuery("DELETE FROM `wdbasic_attributes` WHERE `entity` LIKE :table", ['table'=>$sTable]);

		}

		// Werte von Flex-Feldern mit Schülerdaten löschen
		$sSql = "
			SELECT
				tc_fsf.id
			FROM
				tc_flex_sections_fields tc_fsf INNER JOIN
				tc_flex_sections tc_fs ON
					tc_fs.id = tc_fsf.section_id
			WHERE
				tc_fs.category IN ('student_record', 'enquiries')
		";
		$aFlexFieldIds = DB::getQueryCol($sSql);
		DB::executePreparedQuery("DELETE FROM tc_flex_sections_fields_values WHERE field_id IN (:field_ids)", ['field_ids' => $aFlexFieldIds]);

		// Kontaktdaten löschen
		foreach ($aDeleteContacts as $iContactId) {
			$oContact = Ext_TC_Contact::getInstance($iContactId);

			$sSql = "DELETE FROM `tc_contacts_details` WHERE `contact_id` = " . $iContactId;
			DB::executeQuery($sSql);

			$aAddresses = $oContact->contacts_to_addresses;
			foreach ($aAddresses as $iAddress) {
				$sSql = "DELETE FROM `tc_addresses` WHERE `id` = " . $iAddress;
				DB::executeQuery($sSql);
			}

			$aEmailAddresses = $oContact->contacts_to_emailaddresses;
			foreach ($aEmailAddresses as $iEmail) {
				$sSql = "DELETE FROM `tc_emailaddresses` WHERE `id` = " . $iEmail;
				DB::executeQuery($sSql);
			}

			$sSql = "DELETE FROM `tc_contacts_to_addresses` WHERE `contact_id` = " . $iContactId;
			DB::executeQuery($sSql);
			$sSql = "DELETE FROM `tc_contacts_to_emailaddresses` WHERE `contact_id` = " . $iContactId;
			DB::executeQuery($sSql);
			$sSql = "DELETE FROM `tc_contacts_numbers` WHERE `contact_id` = " . $iContactId;
			DB::executeQuery($sSql);
			$sSql = "DELETE FROM `tc_contacts` WHERE `id` = " . $iContactId;
			DB::executeQuery($sSql);
			$sSql = "DELETE FROM `tc_gui2_designs_tabs_elements_values` WHERE `additional_id` = " . $iContactId . " AND ( `additional_class` = 'Ext_TA_Inquiry_Traveller' OR `additional_class` = 'Ext_TA_Inquiry_Booker')";
			DB::executeQuery($sSql);
		}

		// Registry löschen, damit nicht noch mehr Schwachsinn verknüpft wird
		DB::executeQuery("TRUNCATE TABLE `gui2_index_registry`");

		foreach($aFinalTables as $sTable) {
			try {
				$sSql = "ALTER TABLE `{$sTable}` AUTO_INCREMENT = 1";
				DB::executeQuery($sSql);
			} catch(DB_QueryFailedException $e) {
				continue;
			}
		}

		// Index zurücksetzen
		foreach((new Ext_TS_System_Tools())->getIndexes() as $sResetCheck) {
			$oCheck = new $sResetCheck();
			if($oCheck instanceof GlobalChecks) {
				__out($sResetCheck . ': ' . $oCheck->executeCheck());
			}
		}

		__out(array('deleted contacts' => count($aDeleteContacts), 'backup fail' => $aFailBackup));

		if (count($aFailBackup) > 0) {
			__out($aFailBackup);
		}

		__out(true);

	} else {

		__out(false);

	}

}

?>

<div class="divHeader">
	<h1>Thebing » Customer Data Reset (v3)</h1>
</div>

<div style="width: 1200px; padding: 30px;">

	<h3>Kundendaten bereinigen (Alle Anfragen, Buchungen und damit verbundene Kontakte, alle Dokumente und Versionen, etc.)</h3>
	<h4>Dieses Script sollte nicht bei bereits in länger in Verwendung befindlichen Installationen verwendet werden, da ansonsten die Chance von ID-Kollisionen hoch ist.</h4>
	<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
		<?=printTableStart()?>
		<?=printFormText('Passwort eingeben', 'password', '', 'autocomplete="off"')?>
		<?=printFormCheckbox('KEIN Backup ausführen', 'no_backup', '1', $_VARS['no_backup'])?>
		<?=printTableEnd()?>
		<?=printSubmit('Bereinigung starten')?>
	</form>
</div>

<?php
Admin_Html::loadAdminFooter();
