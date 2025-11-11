INSERT INTO
	`kolumbus_forms_pages_blocks_settings`
SELECT
	`block_id`,
	'availability_start_end' `setting`,
	IF(`value` = 1, 'course_start_end', 'course_period') `value`
FROM
	`kolumbus_forms_pages_blocks_settings`
WHERE
	`setting` = 'accommodation_at_course_start_and_end';

DELETE FROM
	`kolumbus_forms_pages_blocks_settings`
WHERE
	`setting` = 'accommodation_at_course_start_and_end';
