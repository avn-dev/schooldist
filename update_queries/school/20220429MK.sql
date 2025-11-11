ALTER TABLE `ts_tuition_courses_to_courselanguages` CHANGE `course_id` `course_id` INT(11) NOT NULL;
ALTER TABLE `ts_tuition_courses_to_courselanguages` ADD INDEX(`course_id`);

