CREATE TABLE IF NOT EXISTS `ts_schools_teacherlogin_flex_fields` ( `school_id` MEDIUMINT(8) NOT NULL , `field_id` MEDIUMINT(8) NOT NULL , PRIMARY KEY (`school_id`, `field_id`)) ENGINE = InnoDB;

ALTER TABLE `customer_db_2` ADD `teacherlogin_flex_expand` TINYINT(1) NOT NULL DEFAULT '0' AFTER `teacherlogin_welcome_text`;