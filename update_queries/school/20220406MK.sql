ALTER TABLE `kolumbus_course_startdates` CHANGE `end_date` `last_start_date` DATE NOT NULL;
ALTER TABLE `kolumbus_course_startdates` ADD `end_date` DATE NULL DEFAULT NULL AFTER `last_start_date`;
