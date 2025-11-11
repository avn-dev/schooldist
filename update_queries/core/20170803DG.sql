ALTER TABLE `tc_feedback_questionaries_processes` DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;

ALTER TABLE `tc_feedback_questionaries_processes_notices` DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
ALTER TABLE `tc_feedback_questionaries_processes_notices` CHANGE `commentary` `commentary` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `tc_feedback_questionaries_processes_results` DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
ALTER TABLE `tc_feedback_questionaries_processes_results` CHANGE `answer` `answer` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
