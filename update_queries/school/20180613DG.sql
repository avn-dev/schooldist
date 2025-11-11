
UPDATE
    `kolumbus_forms_pages_blocks_settings` `kfpbs` INNER JOIN
    `kolumbus_forms_pages_blocks` `kfpb` ON
        `kfpb`.`id` = `kfpbs`.`block_id`
SET
    `setting` = REPLACE(`setting`, 'course_following_', 'coursefollowing_')
WHERE
    `kfpb`.`block_id` = 1;
