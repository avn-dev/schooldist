ALTER TABLE `ts_inquiries_contacts_logins_devices`
    ADD `app_environment` ENUM('development','production') NULL DEFAULT NULL AFTER `app_version`,
	ADD `apns_token` VARCHAR(1024) NULL DEFAULT NULL AFTER `fcm_token`,
	ADD `additional` TEXT NULL DEFAULT NULL AFTER `apns_token`,
	CHANGE `fcm_token` `fcm_token` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
;

UPDATE `ts_inquiries_contacts_logins_devices` SET `fcm_token` = NULL WHERE `fcm_token` = '';
