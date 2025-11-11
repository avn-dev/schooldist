ALTER TABLE `ts_inquiries_form_processes` ADD `payload` TEXT NULL DEFAULT NULL AFTER `submitted`;

INSERT INTO
	kolumbus_forms_pages_blocks_settings
SELECT
	t1.id block_id,
	'based_on' setting,
	'availability' `value`
FROM
	kolumbus_forms_pages_blocks t1 LEFT JOIN
	kolumbus_forms_pages_blocks_settings t2 ON
		t2.block_id = t1.id AND
		t2.setting = 'based_on'
WHERE
	t1.block_id = 8 AND
	t2.setting IS NULL;
