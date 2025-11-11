ALTER TABLE `tc_frontend_combinations` DROP `refresh_status`;
ALTER TABLE `tc_frontend_combinations` ADD `status` ENUM('ready','pending','fail') NOT NULL DEFAULT 'ready';
