ALTER TABLE `tc_contacts_to_system_types` ADD `mapping_id` INT NOT NULL AFTER `type`;
ALTER TABLE `tc_contacts_to_system_types` DROP PRIMARY KEY, ADD PRIMARY KEY( `contact_id`, `type`, `mapping_id`);

CREATE TABLE IF NOT EXISTS `tc_system_type_mapping` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `editor_id` int(11) NOT NULL,
    `creator_id` int(11) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `type` char(100) NOT NULL DEFAULT 'users',
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tc_system_type_mapping_to_system_types` (
    `mapping_id` mediumint(9) NOT NULL,
    `type` char(100) CHARACTER SET utf8mb3 NOT NULL,
    PRIMARY KEY (`mapping_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tc_system_type_mapping` (`id`, `changed`, `created`, `editor_id`, `creator_id`, `active`, `name`) SELECT `id`, `changed`, `created`, `editor_id`, `creator_id`, `active`, `name` FROM `tc_employees_categories` WHERE 1;
INSERT INTO `tc_system_type_mapping_to_system_types` (`mapping_id`, `type`) SELECT `category_id`, `function` FROM `tc_employees_categories_to_functions`;

RENAME TABLE `tc_employees_categories` TO `__tc_employees_categories`;
RENAME TABLE `tc_employees_categories_to_functions` TO `__tc_employees_categories_to_functions`;