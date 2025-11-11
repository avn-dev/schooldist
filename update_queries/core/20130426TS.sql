ALTER TABLE `tc_marketing_questionaries_childs_questions_ratings` DROP PRIMARY KEY;

ALTER TABLE `tc_marketing_questionaries_childs_questions_ratings` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

ALTER TABLE `tc_marketing_questionaries_childs_questions_ratings` ADD `rating_id` MEDIUMINT( 9 ) NOT NULL DEFAULT '0';

ALTER TABLE `tc_marketing_questionaries_childs_questions_ratings` ADD `active` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `id`; 