ALTER TABLE `kolumbus_tuition_courses`
	CHANGE `minimum_duration` `minimum_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
	CHANGE `maximum_duration` `maximum_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
	CHANGE `fix_duration` `fix_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;

UPDATE `kolumbus_tuition_courses` SET `minimum_duration` = NULL WHERE `minimum_duration` = 0;
UPDATE `kolumbus_tuition_courses` SET `maximum_duration` = NULL WHERE `maximum_duration` = 0;
UPDATE `kolumbus_tuition_courses` SET `fix_duration` = NULL WHERE `fix_duration` = 0;

ALTER TABLE `kolumbus_course_runtimes`
	CHANGE `minimum_duration` `minimum_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
	CHANGE `maximum_duration` `maximum_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
	CHANGE `fix_duration` `fix_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;

UPDATE `kolumbus_course_runtimes` SET `minimum_duration` = NULL WHERE `minimum_duration` = 0;
UPDATE `kolumbus_course_runtimes` SET `maximum_duration` = NULL WHERE `maximum_duration` = 0;
UPDATE `kolumbus_course_runtimes` SET `fix_duration` = NULL WHERE `fix_duration` = 0;

ALTER TABLE `kolumbus_course_startdates`
	CHANGE `minimum_duration` `minimum_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
	CHANGE `maximum_duration` `maximum_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
	CHANGE `fix_duration` `fix_duration` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;

UPDATE `kolumbus_course_startdates` SET `minimum_duration` = NULL WHERE `minimum_duration` = 0;
UPDATE `kolumbus_course_startdates` SET `maximum_duration` = NULL WHERE `maximum_duration` = 0;
UPDATE `kolumbus_course_startdates` SET `fix_duration` = NULL WHERE `fix_duration` = 0;
