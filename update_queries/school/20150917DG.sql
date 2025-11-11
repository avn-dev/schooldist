ALTER TABLE `kolumbus_groups` ADD `editor_id` SMALLINT UNSIGNED NOT NULL AFTER `creator_id`;

UPDATE
		`kolumbus_groups`
SET
		`editor_id` = `creator_id`,
		`changed` = `changed`
WHERE
		`editor_id` = 0;

