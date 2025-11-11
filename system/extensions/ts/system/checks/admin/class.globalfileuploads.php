<?php

class Ext_TS_System_Checks_Admin_GlobalFileUploads extends \GlobalChecks {

    public function getTitle() {
        return "File uploads";
    }

    public function getDescription() {
        return "Remove school dependency for file uploads";
    }

    public function executeCheck() {

        $exists = \DB::getDefaultConnection()->checkField('kolumbus_upload', 'school_id', true);
        if(!$exists) {
            // bereits ausgeführt
            return true;
        }

        set_time_limit(3600);
        ini_set("memory_limit", '1G');

        $backup = \Util::backupTable('kolumbus_upload');
        if(!$backup) {
            __pout('Backup error');
            return false;
        }

        \DB::begin(__METHOD__);

        try {

            $sql = "SELECT `id`, `school_id`, `filename` FROM `kolumbus_upload` WHERE `active` = 1";
            $entries = \DB::getQueryData($sql);

            \Util::checkDir(\Util::getDocumentRoot(true).'storage/ts/uploads');
            \Util::checkDir(\Util::getDocumentRoot(true).'storage/public/ts/uploads');

            foreach($entries as $entry) {
                \DB::insertData('kolumbus_upload_to_schools', [
                    'upload_id' => $entry['id'],
                    'school_id' => $entry['school_id']
                ]);

                $school = \Ext_Thebing_School::getInstance($entry['school_id']);

                if(!$school->exist() || !$school->isActive() ) {
                    // wenn die Schule nicht mehr existiert den upload auf active=0 setzen
                    \DB::updateData('kolumbus_upload', ['active' => 0], ['id' => $entry['id']]);
                }

                if($school->exist()) {
                    $path = str_replace('/storage', '', $school->getSchoolFileDir(false));
                    $path .= '/uploads/' . $entry['filename'];

                    $storage = \Util::getDocumentRoot(true).'storage';

                    if(file_exists($storage.$path)) {
                        // verschieben nach /storage/ts/uploads/
                        $success = copy($storage.$path, $storage.'/ts/uploads/'.$entry['filename']);
                        if($success === true) {
                            // kein unlink da es evtl noch Zugriffe auf die alte Datei gibt
                            Util::changeFileMode($storage.'/ts/uploads/'.$entry['filename']);
                        }
                    }

                    // Bilder werden in public kopiert (siehe save())
                    if(file_exists($storage.'/public'.$path)) {
                        // verschieben nach /storage/public/ts/uploads/
                        $success = copy($storage.'/public'.$path, $storage.'/public/ts/uploads/'.$entry['filename']);
                        if($success === true) {
                            // kein unlink da es evtl noch Zugriffe auf die alte Datei gibt
                            Util::changeFileMode($storage . '/public/ts/uploads/' . $entry['filename']);
                        }
                    }
                }

            }

            DB::executeQuery("ALTER TABLE `kolumbus_upload` DROP `school_id`");
            DB::executeQuery("ALTER TABLE `kolumbus_upload` DROP `client_id`");

        } catch (\Exception $e) {
            __pout($e);
            \DB::rollback(__METHOD__);
            return false;
        }

        \DB::commit(__METHOD__);

        return true;

    }

}
