CREATE TABLE `ts_teachers_teacherlogin_attendance_codes` (
  `code_key` varchar(32) NOT NULL,
  `block_id` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `kolumbus_tuition_attendance` CHANGE `mo` `mo` FLOAT(10,2) NULL DEFAULT NULL, CHANGE `di` `di` FLOAT(10,2) NULL DEFAULT NULL, CHANGE `mi` `mi` FLOAT(10,2) NULL DEFAULT NULL, CHANGE `do` `do` FLOAT(10,2) NULL DEFAULT NULL, CHANGE `fr` `fr` FLOAT(10,2) NULL DEFAULT NULL, CHANGE `sa` `sa` FLOAT(10,2) NULL DEFAULT NULL, CHANGE `so` `so` FLOAT(10,2) NULL DEFAULT NULL;

ALTER TABLE `ts_teachers_teacherlogin_attendance_codes` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date`;