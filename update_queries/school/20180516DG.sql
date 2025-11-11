ALTER TABLE
	`kolumbus_clients`
ADD `privacy_enquiry_action` VARCHAR(50) NOT NULL AFTER `execution_time_statistics_update`,
ADD `privacy_enquiry_quantity` TINYINT UNSIGNED NOT NULL AFTER `privacy_enquiry_action`,
ADD `privacy_enquiry_unit` VARCHAR(50) NOT NULL AFTER `privacy_enquiry_quantity`,
ADD `privacy_enquiry_basedon` VARCHAR(50) NOT NULL AFTER `privacy_enquiry_unit`,
ADD `privacy_inquiry_action` VARCHAR(50) NOT NULL AFTER `privacy_enquiry_basedon`,
ADD `privacy_inquiry_quantity` TINYINT UNSIGNED NOT NULL AFTER `privacy_inquiry_action`,
ADD `privacy_inquiry_unit` VARCHAR(50) NOT NULL AFTER `privacy_inquiry_quantity`,
ADD `privacy_inquiry_basedon` VARCHAR(50) NOT NULL AFTER `privacy_inquiry_unit`,
ADD `privacy_provider_action` VARCHAR(50) NOT NULL AFTER `privacy_inquiry_basedon`,
ADD `privacy_provider_quantity` TINYINT UNSIGNED NOT NULL AFTER `privacy_provider_action`,
ADD `privacy_provider_unit` VARCHAR(50) NOT NULL AFTER `privacy_provider_quantity`;

CREATE TABLE IF NOT EXISTS `ts_privacy_depuration` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `entity` varchar(255) NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `deletion_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity` (`entity`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ts_enquiries` ADD `anonymized` TINYINT NOT NULL DEFAULT '0' AFTER `active`;

ALTER TABLE `ts_inquiries` ADD `anonymized` TINYINT NOT NULL DEFAULT '0' AFTER `active`;

ALTER TABLE `ts_teachers` ADD `anonymized` TINYINT NOT NULL DEFAULT '0' AFTER `active`;

ALTER TABLE `customer_db_4` ADD `anonymized` TINYINT NOT NULL DEFAULT '0' AFTER `active`;

ALTER TABLE `kolumbus_companies` ADD `anonymized` TINYINT NOT NULL DEFAULT '0' AFTER `active`;
