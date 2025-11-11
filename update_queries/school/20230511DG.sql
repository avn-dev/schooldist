ALTER TABLE `ts_inquiries_contacts_logins` ADD `credentials_locked` TINYINT(1) NOT NULL DEFAULT '0' AFTER `password`;
