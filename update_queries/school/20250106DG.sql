ALTER TABLE `kolumbus_tuition_classes` ADD `online_bookable_as_course` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `internal_comment`, ADD INDEX(`online_bookable_as_course`);

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
    t1.block_id = 1 AND
    t2.setting IS NULL;

DELETE FROM
    kolumbus_tuition_blocks_to_rooms
WHERE room_id IN (
    SELECT
        id
    FROM
        kolumbus_classroom
    WHERE
        active = 0
);