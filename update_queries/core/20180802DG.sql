
ALTER TABLE `tc_communication_automatictemplates` ADD `ignore_cancellation` TINYINT(1) NOT NULL DEFAULT '1' AFTER `event_type`;

ALTER TABLE `tc_communication_automatictemplates` CHANGE `ignore_cancellation` `ignore_cancellation` TINYINT(1) NOT NULL DEFAULT '0';
