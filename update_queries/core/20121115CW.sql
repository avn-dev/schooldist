ALTER TABLE `system_user_messages` ADD INDEX `recipient_id` ( `recipient_id` ) ;
ALTER TABLE `tc_flex_data` ADD INDEX `db_column` ( `db_column` ) ;
ALTER TABLE `language_files` CHANGE `file` `file` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `language_files` ADD INDEX `file` ( `file` ) ;
ALTER TABLE `tc_contacts_numbers` ADD INDEX `numberrange_id` ( `numberrange_id` ) ;