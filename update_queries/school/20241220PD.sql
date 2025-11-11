ALTER TABLE `kolumbus_tuition_attendance` ADD `completed` TINYINT(1) NULL;
ALTER TABLE `kolumbus_tuition_attendance` ADD INDEX `completed`(`completed`);