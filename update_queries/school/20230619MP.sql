ALTER TABLE `kolumbus_positions_order` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_positions_order` CHANGE `position` `position_key` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
ALTER TABLE `kolumbus_positions_order` CHANGE `order` `position` INT(11) NOT NULL DEFAULT '0';
