ALTER TABLE `kolumbus_groups` ADD `number` VARCHAR(40) NOT NULL, ADD `numberrange_id` INT UNSIGNED NOT NULL, ADD INDEX `numberrange_id` (`numberrange_id`);
ALTER TABLE `ts_groups` ADD `number` VARCHAR(40) NOT NULL, ADD `numberrange_id` INT UNSIGNED NOT NULL, ADD INDEX `numberrange_id` (`numberrange_id`);
ALTER TABLE `ts_inquiries` ADD `number` VARCHAR(40) NOT NULL, ADD `numberrange_id` INT UNSIGNED NOT NULL, ADD INDEX `numberrange_id` (`numberrange_id`);
INSERT INTO system_config SET c_key = 'customernumber_enquiry', c_value = (SELECT `customernumber_enquiry` FROM `kolumbus_clients` LIMIT 1);
ALTER TABLE `kolumbus_groups` ADD `inbox_id` INT UNSIGNED NOT NULL AFTER `school_id`;
ALTER TABLE `kolumbus_inboxlist` ADD `position` INT UNSIGNED NOT NULL AFTER `short`;
UPDATE kolumbus_groups g SET inbox_id = IFNULL((SELECT ib.id FROM kolumbus_inboxlist ib JOIN ts_inquiries i ON ib.short = i.inbox WHERE g.id = i.group_id LIMIT 1), (SELECT id FROM kolumbus_inboxlist WHERE active = 1 AND status = 1 ORDER BY position, id LIMIT 1));
ALTER TABLE `ts_enquiries` ADD `inbox_id` INT UNSIGNED NOT NULL AFTER `school_id`;

ALTER TABLE `kolumbus_email_templates` CHANGE `cc` `cc` TEXT NOT NULL, CHANGE `bcc` `bcc` TEXT NOT NULL;