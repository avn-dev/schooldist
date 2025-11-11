ALTER TABLE `kolumbus_tuition_courses`
    ADD `lessons_list` TEXT NULL DEFAULT NULL AFTER `lessons_per_week`,
    ADD `lessons_fix` TINYINT NOT NULL DEFAULT '0' AFTER `lessons_list`;

UPDATE
    `system_elements`
SET
    `element` = 'bundle',
    `file` = 'TsTuition'
WHERE
    `file` = 'tstuition';
