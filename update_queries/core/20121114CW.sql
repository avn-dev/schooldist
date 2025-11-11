ALTER TABLE `system_user_messages` ADD INDEX ( `recipient_id` ) ;
ALTER TABLE `system_user_messages` ADD INDEX ( `user_id` ) ;
ALTER TABLE `data_countries` ADD INDEX ( `cn_short_en` ) ;