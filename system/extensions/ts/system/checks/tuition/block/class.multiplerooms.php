<?php

/**
 * https://redmine.thebing.com/redmine/issues/8580
 */
class Ext_TS_System_Checks_Tuition_Block_MultipleRooms extends GlobalChecks {

    /**
     * @return string
     */
    public function getTitle() {
        return 'Update Tuition Block database';
    }

    /**
     * @return string
     */
    public function getDescription() {
        return 'Allow multiple rooms per tuition block';
    }

    /**
     * - Raum des Blockes in kolumbus_tuition_blocks_to_rooms eintragen
     * - Raum des Blockes in kolumbus_tuition_blocks_inquiries_courses eintragen
     *
     * @return boolean
     */
    public function executeCheck() {

        $exists = \DB::getDefaultConnection()->checkField('kolumbus_tuition_blocks', 'room_id', true);
        if(!$exists) {
            return true;
        }

        set_time_limit(3600);
        ini_set('memory_limit', '1G');

        $backup = [
            \Util::backupTable('kolumbus_tuition_blocks_inquiries_courses'),
            \Util::backupTable('kolumbus_tuition_blocks'),
        ];

        if(in_array(false, $backup)) {
            __pout('Backup error');
            return false;
        }

        \DB::begin(__METHOD__);

        try {

            $select = "SELECT `id`,`room_id` FROM `kolumbus_tuition_blocks` WHERE `room_id` != 0";
            $blocks = \DB::getQueryData($select);

            foreach($blocks as $block) {

                \DB::insertData('kolumbus_tuition_blocks_to_rooms', [
                    'block_id' => $block['id'],
                    'room_id' => $block['room_id']
                ]);

                $update = "UPDATE `kolumbus_tuition_blocks_inquiries_courses` SET `room_id` = :room_id WHERE `block_id` = :block_id";
                \DB::executePreparedQuery($update, [
                    'block_id' => $block['id'],
                    'room_id' => $block['room_id']
                ]);

            }

            $drop = "ALTER TABLE `kolumbus_tuition_blocks` DROP `room_id`";
            \DB::executeQuery($drop);

        } catch(\Exception $ex) {
            __pout($ex);
            \DB::rollback(__METHOD__);
            return false;
        }

        DB::commit(__METHOD__);

        return true;
    }

}
