<?php

class Ext_TS_System_Checks_Tuition_OwnOverview_GlobalRessources extends \GlobalChecks {

    public function getTitle() {
        return "Tuition Own Overview";
    }

    public function getDescription() {
        return "Remove school dependency for own overviews";
    }

    public function executeCheck() {

        $exists = \DB::getDefaultConnection()->checkField('kolumbus_tuition_reports', 'school_id', true);
        if(!$exists) {
            // bereits ausgeführt
            return true;
        }

        $backup = \Util::backupTable('kolumbus_tuition_reports');
        if(!$backup) {
            __pout('Backup error');
            return false;
        }

        \DB::begin(__METHOD__);

        try {

            $sql = "SELECT `id`,`school_id`,`background_pdf` FROM `kolumbus_tuition_reports` WHERE `active` = 1";
            $entries = \DB::getQueryData($sql);

            foreach($entries as $entry) {

                $school = \Ext_Thebing_School::getInstance($entry['school_id']);

                if(!$school->exist() || !$school->isActive() ) {
                    // wenn die Schule nicht mehr existiert den upload auf active=0 setzen
                    \DB::updateData('kolumbus_tuition_reports', ['active' => 0], ['id' => $entry['id']]);
                }

                \DB::insertData('kolumbus_tuition_reports_to_schools', [
                    'report_id' => $entry['id'],
                    'school_id' => $entry['school_id'],
                    'background_pdf' => $entry['background_pdf'],
                ]);

            }

            DB::executeQuery("ALTER TABLE `kolumbus_tuition_reports` DROP `background_pdf`, DROP `school_id`");

        } catch (\Exception $e) {
            __pout($e);
            \DB::rollback(__METHOD__);
            return false;
        }

        \DB::commit(__METHOD__);

        return true;

    }
}
