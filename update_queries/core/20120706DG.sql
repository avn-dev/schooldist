ALTER TABLE `tc_communication_messages_flags` DROP PRIMARY KEY;

ALTER TABLE `tc_communication_messages_flags` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

CREATE TABLE IF NOT EXISTS `tc_communication_messages_flags_relations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `flag_id` int(10) unsigned NOT NULL,
  `relation` varchar(60) NOT NULL,
  `relation_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_communication_messages_flags_relations_relations` (
  `flagrelation_id` INT UNSIGNED NOT NULL ,
  `relation` VARCHAR( 75 ) NOT NULL ,
  `relation_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY ( `flagrelation_id` , `relation` , `relation_id` )
) ENGINE = InnoDB;

