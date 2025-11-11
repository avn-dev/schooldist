ALTER TABLE `kolumbus_course_startdates` ADD `single_date` TINYINT(1) NOT NULL DEFAULT '0' AFTER `end_date`;

ALTER table `kolumbus_course_startdates_levels` COMMENT '';

DROP TABLE IF EXISTS `kolumbus_tuition_courses_modulegroups`;

DROP TABLE IF EXISTS `kolumbus_tuition_courses_modulegroups_modules`;
