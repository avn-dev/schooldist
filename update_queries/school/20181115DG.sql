ALTER TABLE `kolumbus_email_flags` DROP `id`;
ALTER TABLE `kolumbus_email_flags` ADD PRIMARY KEY(`log_id`, `flag`);
ALTER TABLE `kolumbus_email_flags` CHANGE `flag` `flag` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '';
ALTER TABLE `kolumbus_email_log` CHANGE `created` `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
