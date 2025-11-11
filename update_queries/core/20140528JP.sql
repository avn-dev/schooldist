ALTER TABLE `tc_feedback_questionaries_childs_questions_groups_questions` CHANGE `questionary_question_id` `questionary_question_group_id` MEDIUMINT(9) NOT NULL;

ALTER TABLE `tc_feedback_questionaries_results_values` DROP `inquiry_id`;
ALTER TABLE `tc_feedback_questionaries_results_values` CHANGE `questionary_result_id` `questionary_process_id` INT(10) NOT NULL;
RENAME TABLE `tc_feedback_questionaries_results_values` TO `tc_feedback_questionaries_processes_results`;

RENAME TABLE `tc_feedback_questionaries_results` TO `tc_feedback_questionaries_processes`;
ALTER TABLE `tc_feedback_questionaries_processes` DROP `inquiry_id`;

ALTER TABLE `tc_feedback_questionaries_processes` CHANGE `total_satisfaction` `overall_satisfaction` DECIMAL(5,2) NULL DEFAULT NULL;
ALTER TABLE `tc_feedback_questions` CHANGE `total_satisfaction` `overall_satisfaction` DECIMAL(5,2) NULL DEFAULT NULL;

ALTER TABLE `tc_feedback_questionaries_processes`
  ADD `contact_id` INT(10) UNSIGNED NOT NULL,
  ADD `journey_id`  INT(10) UNSIGNED NOT NULL,
  ADD `questionary_id`  INT(10) UNSIGNED NOT NULL,
  ADD `invited` TIMESTAMP NOT NULL,
  ADD `started` TIMESTAMP NOT NULL,
  ADD `answered` TIMESTAMP NOT NULL,
  ADD `link_key` CHAR(8) NOT NULL,
  ADD UNIQUE(`link_key`);