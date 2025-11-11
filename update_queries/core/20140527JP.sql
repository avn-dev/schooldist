RENAME TABLE `tc_marketing_questionaries` TO `tc_feedback_questionaries`;
RENAME TABLE `tc_marketing_questionaries_childs` TO `tc_feedback_questionaries_childs`;
RENAME TABLE `tc_marketing_questionaries_childs_headings` TO `tc_feedback_questionaries_childs_headings`;
RENAME TABLE `tc_marketing_questionaries_childs_headings_i18n` TO `tc_feedback_questionaries_childs_headings_i18n`;
RENAME TABLE `tc_marketing_questionaries_childs_questions` TO `tc_feedback_questionaries_childs_questions_groups`;
RENAME TABLE `tc_marketing_questionaries_childs_questions_ratings` TO `tc_feedback_questionaries_childs_questions_groups_questions`;
RENAME TABLE `tc_marketing_questionaries_to_objects` TO `tc_feedback_questionaries_to_objects`;
RENAME TABLE `tc_marketing_questionaries_to_subobjects` TO `tc_feedback_questionaries_to_subobjects`;
RENAME TABLE `tc_marketing_questions` TO `tc_feedback_questions`;
RENAME TABLE `tc_marketing_questions_i18n` TO `tc_feedback_questions_i18n`;
RENAME TABLE `tc_marketing_questions_to_dependency_objects` TO `tc_feedback_questions_to_dependency_objects`;
RENAME TABLE `tc_marketing_questions_to_dependency_subobjects` TO `tc_feedback_questions_to_dependency_subobjects`;
RENAME TABLE `tc_marketing_topics` TO `tc_feedback_topics`;
RENAME TABLE `tc_marketing_topics_i18n` TO `tc_feedback_topics_i18n`;

DROP TABLE `tc_marketing_questionaries_childs_questions_to_questions`;

ALTER TABLE `tc_marketing_ratings` ADD `type` ENUM('desc','asc') NOT NULL;
RENAME TABLE `tc_marketing_ratings` TO `tc_feedback_ratings`;
RENAME TABLE `tc_marketing_ratings_childs` TO `tc_feedback_ratings_childs`;
RENAME TABLE `tc_marketing_ratings_childs_i18n` TO `tc_feedback_ratings_childs_i18n`;

DROP TABLE `tc_marketing_questionaries_evaluation`;

CREATE TABLE IF NOT EXISTS `tc_feedback_questionaries_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `total_satisfaction` DECIMAL(5,2) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tc_feedback_questionaries_results_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `inquiry_id` int(10) unsigned NOT NULL,
  `questionary_question_group_question_id` int(10) unsigned NOT NULL,
  `dependency_id` int(10) unsigned NOT NULL,
  `answer` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

