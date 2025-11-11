<?php

namespace TsTuition\Service;

class TrackingSession {

    const TABLE = 'ts_tuition_attendance_tracking_sessions';
    const CODE_LENGTH = 32;

    /**
     * @param \Ext_Thebing_Teacher $teacher
     * @param \Ext_Thebing_School_Tuition_Block $block
     * @param int $day
     * @return string
     */
    public static function generate(\Ext_Thebing_Teacher $teacher, \Ext_Thebing_School_Tuition_Block $block, int $day): string {

        $code = \Util::generateRandomString(self::CODE_LENGTH);

        $session = [
            'teacher_id' => $teacher->getId(),
            'block_id' => $block->getId(),
            'day' => $day,
            'code' => $code
        ];

        \DB::insertData(self::TABLE, $session);

        return $code;
    }

    /**
     * @param string $code
     * @return array|null
     */
    public static function search(string $code): ?array {
        $sql = "SELECT * FROM #table WHERE `code` = :code LIMIT 1";
        $session = \DB::getQueryRow($sql, ['table' => self::TABLE, 'code' => $code]);

        return (!empty($session)) ? $session : null;
    }

    /**
     * @param string $code
     */
    public static function delete(string $code): void {
        \DB::executeQuery("DELETE FROM #table WHERE `code` = :code ", ['table' => self::TABLE, 'code' => $code]);
    }

}
