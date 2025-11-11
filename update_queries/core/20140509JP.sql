CREATE TABLE IF NOT EXISTS `tc_marketing_questionaries_evaluation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `inquiry_id` int(10) unsigned NOT NULL,
  `questionary_id` int(10) unsigned NOT NULL,
  `questionary_question_rating_id` int(10) unsigned NOT NULL,
  `dependency_id` int(10) unsigned NOT NULL,
  `answer` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;