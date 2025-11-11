<?php

class Ext_TS_System_Checks_Admin_GiveRightVatSelection extends \GlobalChecks {

    public function getTitle() {
        return "Give the right vat_selection";
    }

    public function getDescription() {
        return "Gives the right 'ts_bookings_invoices-vat_selection' to groups and users with right 'thebing_invoice_document_refresh_always'";
    }

    public function executeCheck() {

        set_time_limit(3600);
        ini_set("memory_limit", '1G');

        $backup = \Util::backupTable('kolumbus_access_group_access');
        if(!$backup) {
            __pout('Backup error');
            return false;
        }

        \DB::begin(__METHOD__);

        try {

            $groups = \DB::getQueryData("SELECT `group_id` FROM `kolumbus_access_group_access` WHERE `access` = 'thebing_invoice_document_refresh_always'");

            foreach($groups as $group) {

				$alreadyHasRight = \DB::getPreparedQueryData("SELECT * FROM `kolumbus_access_group_access` WHERE `access` = 'ts_bookings_invoices-vat_selection' AND `group_id` = :group_id", ['group_id' => $group['group_id']]);

				if (empty($alreadyHasRight)) {
					\DB::executePreparedQuery("INSERT INTO `kolumbus_access_group_access` SET `access` = 'ts_bookings_invoices-vat_selection', `group_id` = :group_id", ['group_id' => $group['group_id']]);
				}

            }

        } catch (\Exception $e) {
            __pout($e);
            \DB::rollback(__METHOD__);
            return false;
        }

        \DB::commit(__METHOD__);

        return true;

    }

}
