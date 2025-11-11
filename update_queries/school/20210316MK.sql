ALTER TABLE `kolumbus_costs` CHANGE `charge` `calculate` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_costs` ADD `charge` ENUM('auto','semi') NOT NULL DEFAULT 'auto';
ALTER TABLE `kolumbus_costs` ADD `limited_quantity` TINYINT(1) NOT NULL DEFAULT '0';
CREATE TABLE IF NOT EXISTS `ts_costs_validities` (`id` int(11) NOT NULL AUTO_INCREMENT, `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', `active` tinyint(1) NOT NULL DEFAULT '1', `creator_id` int(11) NOT NULL, `editor_id` int(11) NOT NULL, `cost_id` int(11) NOT NULL, `valid_from` date NOT NULL, `valid_until` date NOT NULL, `comment` text NOT NULL, PRIMARY KEY (`id`), KEY `cost_id` (`cost_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `kolumbus_costs` ADD `credit_provider` TINYINT(1) NOT NULL DEFAULT '0' AFTER `limited_quantity`;
ALTER TABLE `ts_accommodation_providers_payments` CHANGE `payment_category_id` `payment_category_id` INT(11) NULL DEFAULT NULL, CHANGE `cost_category_id` `cost_category_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `ts_accommodation_providers_payments` CHANGE `period_id` `period_id` INT(11) NULL DEFAULT NULL;
 