ALTER TABLE `tc_communication_messages` CHANGE `type` `type` ENUM('email','sms','notice','app') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'email';

ALTER TABLE `tc_communication_messages` ADD `seen` TINYINT(1) NOT NULL DEFAULT '0' AFTER `content_type`, ADD INDEX (`seen`);
