ALTER TABLE `kolumbus_tuition_progress` DROP INDEX `ktp_unique1`;
ALTER TABLE `kolumbus_tuition_progress` ADD UNIQUE `ktp_unique1` (`inquiry_id`, `courselanguage_id`, `inquiry_course_id`, `week`, `program_service_id`) USING BTREE;
