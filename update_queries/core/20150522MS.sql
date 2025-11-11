CREATE TABLE IF NOT EXISTS `tc_feedback_questionaries_processes_notices` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `editor_id` mediumint(9) NOT NULL,
  `creator_id` mediumint(9) NOT NULL,
  `commentary` text NOT NULL,
  `questionary_process_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

ALTER TABLE `tc_feedback_questionaries_processes` ADD `email` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `tc_feedback_questionaries_processes` ADD `read_feedback` TINYINT(1) NOT NULL DEFAULT '0' AFTER `email`;
ALTER TABLE `tc_feedback_questionaries_processes` ADD `follow_up` TIMESTAMP NULL DEFAULT NULL AFTER `read_feedback`;