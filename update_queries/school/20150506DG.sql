UPDATE
    `kolumbus_email_templates` `ket` LEFT JOIN
    `system_user` `su` ON
    `su`.`id` = `ket`.`default_identity_id`
SET
    `ket`.`default_identity_id` = 0
WHERE
    `ket`.`default_identity_id` != 0 AND
    `ket`.`active` = 1 AND (
        `su`.`active` = 0 OR
        `su`.`id` IS NULL
    );


DELETE FROM
    `kolumbus_user_identities`
WHERE
    `user_id` IN (
        SELECT
            `id`
        FROM
            `system_user`
        WHERE
            `active` = 0
        ) OR
    `identity_id` IN (
        SELECT
            `id`
        FROM
            `system_user`
        WHERE
            `active` = 0
    );