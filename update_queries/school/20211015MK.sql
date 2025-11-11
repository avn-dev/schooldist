ALTER TABLE `kolumbus_classroom` ADD `comment` TEXT NOT NULL;
CREATE TABLE `ts_classrooms_tags` (
  `id` int(11) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `editor_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `ts_classrooms_tags` ADD PRIMARY KEY (`id`);
ALTER TABLE `ts_classrooms_tags` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
CREATE TABLE `ts_classrooms_to_tags` (`classroom_id` int(11) NOT NULL, `tag_id` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `ts_classrooms_to_tags` ADD UNIQUE KEY `unqiue` (`classroom_id`,`tag_id`) USING BTREE;