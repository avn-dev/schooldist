CREATE TABLE IF NOT EXISTS `tc_communication_messages_app_index` (
    `message_id` int(10) UNSIGNED NOT NULL,
    `device_relation` varchar(100) NOT NULL,
    `device_relation_id` int(11) UNSIGNED NOT NULL,
    `thread_relation` varchar(100) NOT NULL,
    `thread_relation_id` int(11) NOT NULL,
    PRIMARY KEY (`message_id`,`device_relation`,`device_relation_id`,`thread_relation`,`thread_relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;