ALTER TABLE `newsletter2_recipients` ADD `company_name` VARCHAR(50) NOT NULL
AFTER `email`, ADD `country` VARCHAR(20) NOT NULL
AFTER `company_name`, ADD `comment` VARCHAR(255) NOT NULL
AFTER `country`;