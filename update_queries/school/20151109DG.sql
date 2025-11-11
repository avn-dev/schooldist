ALTER TABLE `ts_inquiries_journeys_courses_tuition_index`
	ADD `course_id` MEDIUMINT UNSIGNED NOT NULL AFTER `journey_course_id`,
	ADD `allocated_lessons` SMALLINT UNSIGNED NOT NULL AFTER `until`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY( `journey_course_id`, `course_id`, `week`);
;