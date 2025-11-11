
ALTER TABLE `tc_communication_automatictemplates`
		CHANGE `type_id` `type` VARCHAR(255) NOT NULL,
		DROP `execution_time`,
		ADD `execution_time` TINYINT(2) NOT NULL AFTER `type`,
		ADD `recipient_type` VARCHAR(255) NOT NULL AFTER `date`,
		DROP `event_id`,
		DROP `additional_id`,
		ADD `temporal_direction` ENUM('before', 'after') NOT NULL AFTER `days`,
		ADD `event_type` VARCHAR(255) NOT NULL AFTER `temporal_direction`;

UPDATE `tc_communication_automatictemplates` SET `type` = 'registration_mail' WHERE `type` = '3'
