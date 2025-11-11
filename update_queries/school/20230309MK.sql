CREATE TABLE `ts_accommodation_requests` (`id` INT NOT NULL AUTO_INCREMENT, `changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `created` TIMESTAMP NOT NULL , `user_id` INT NOT NULL , `inquiry_accommodation_id` INT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;
CREATE TABLE `ts_accommodation_requests_recipients` (`id` INT NOT NULL AUTO_INCREMENT, `request_id` INT NOT NULL , `accommodation_provider_id` INT NOT NULL , `accepted` TIMESTAMP NULL DEFAULT NULL , `rejected` TIMESTAMP NULL DEFAULT NULL , PRIMARY KEY (`id`), INDEX (`request_id`)) ENGINE = InnoDB;
ALTER TABLE `ts_accommodation_requests_recipients` ADD `sent` TIMESTAMP NULL DEFAULT NULL AFTER `accommodation_provider_id`;
ALTER TABLE `ts_accommodation_requests_recipients` ADD `key` CHAR(16) NOT NULL AFTER `rejected`;
ALTER TABLE `ts_accommodation_requests_recipients` ADD UNIQUE `key` (`key`);
