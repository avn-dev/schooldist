ALTER TABLE `kolumbus_tuition_classes` ADD `internal_comment` TEXT NOT NULL;
CREATE TABLE `kolumbus_tuition_blocks_days_comments` (`block_id` int(11) NOT NULL, `day` tinyint(4) NOT NULL, `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `comment` mediumtext NOT NULL);
ALTER TABLE `kolumbus_tuition_blocks_days_comments` ADD UNIQUE KEY `unique` (`block_id`,`day`);
