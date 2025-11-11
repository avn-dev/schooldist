<?php

class Ext_TS_System_Checks_Inquiry_ContactDetailTax extends GlobalChecks
{
	public function getTitle()
	{
		return 'Corrects vat_number/tax_code detail types';
	}

	public function getDescription()
	{
		return 'Corrects vat_number/tax_code detail types.';
	}

	public function executeCheck()
	{
		$backup = Ext_Thebing_Util::backupTable('tc_contacts_details');

		if (!$backup) {
			__pout('backup error!');
			return false;
		}

		$sql1 = "UPDATE `tc_contacts_details` SET `type` = 'vat_number' WHERE `type` = 'detail_vat_number';";
		$sql2 = "UPDATE `tc_contacts_details` SET `type` = 'tax_code' WHERE `type` = 'detail_tax_code'";
		$sql3 =	"UPDATE
					`tc_contacts_details`
				SET
					`active` = 0,
					`changed` = `changed`
				WHERE id IN (
					SELECT `id_to_deactivate`
					FROM (
						SELECT MIN(`id`) AS `id_to_deactivate`
						FROM `tc_contacts_details`
						WHERE
							`type` = 'vat_number' AND
							`active` = 1
						GROUP BY `contact_id`
						HAVING COUNT(*) > 1
					) AS `subquery`
				);";
		$sql4 =	"UPDATE
					`tc_contacts_details`
				SET
					`active` = 0,
					`changed` = `changed`
				WHERE id IN (
					SELECT `id_to_deactivate`
					FROM (
						SELECT MIN(`id`) AS `id_to_deactivate`
						FROM `tc_contacts_details`
						WHERE
							`type` = 'tax_code' AND
							`active` = 1
						GROUP BY `contact_id`
						HAVING COUNT(*) > 1
					) AS `subquery`
				);";

		DB::begin('Ext_TS_System_Checks_Inquiry_ContactDetailTax');

		try {
			DB::executeQuery($sql1);
			DB::executeQuery($sql2);
			DB::executeQuery($sql3);
			DB::executeQuery($sql4);
		} catch (Exception $e) {
			__pout($e);
			DB::rollback('Ext_TS_System_Checks_Inquiry_ContactDetailTax');
			return false;
		}

		DB::commit('Ext_TS_System_Checks_Inquiry_ContactDetailTax');
		return true;
	}
}