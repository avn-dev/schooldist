ALTER TABLE `system_user_messages` ADD `type` ENUM('info','success','error') NOT NULL DEFAULT 'info';
