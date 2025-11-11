ALTER TABLE `tc_communication_messages` CHANGE `seen` `seen` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Deprecated';
ALTER TABLE `tc_communication_messages` ADD `seen_at` TIMESTAMP NULL DEFAULT NULL AFTER `seen`;
ALTER TABLE `tc_communication_messages` ADD `status` ENUM('sent','received','seen','failed') NULL DEFAULT NULL AFTER `seen_at`;
ALTER TABLE `tc_communication_messages` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;