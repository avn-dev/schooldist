ALTER TABLE `kolumbus_costs` ADD `limited_availability` TINYINT(1) NOT NULL DEFAULT '0';
CREATE TABLE `ts_pos` (`id` int(11) NOT NULL, `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', `active` tinyint(1) NOT NULL DEFAULT '1', `user_id` int(11) NOT NULL, `creator_id` int(11) NOT NULL, `name` varchar(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `ts_pos` ADD PRIMARY KEY (`id`);
ALTER TABLE `ts_pos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
INSERT INTO `tc_flex_sections` (`id`, `changed`, `created`, `active`, `title`, `type`, `category`) VALUES (49, '0000-00-00 00:00:00', NOW(), '1', 'Verkaufsstellen', 'admin_pos', 'admin');
INSERT INTO `tc_flex_sections` (`id`, `changed`, `created`, `active`, `title`, `type`, `category`) VALUES (50, '0000-00-00 00:00:00', NOW(), '1', 'Kontakte', 'admin_contacts', 'admin');
CREATE TABLE `ts_pos_stock` ( `id` INT NOT NULL AUTO_INCREMENT , `changed` TIMESTAMP NOT NULL , `created` TIMESTAMP NOT NULL , `active` TINYINT NOT NULL DEFAULT '1' , `user_id` INT NOT NULL , `creator_id` INT NOT NULL , `pos_id` INT NOT NULL , `change` INT NOT NULL , `comment` TEXT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;
ALTER TABLE `ts_pos_stock` ADD `cost_id` INT NOT NULL;
ALTER TABLE `ts_pos_stock` ADD INDEX `cost_id` (`cost_id`);