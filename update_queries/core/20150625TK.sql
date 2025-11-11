ALTER TABLE `tc_positiongroups` ADD `creator_id` INT(10) NOT NULL;
ALTER TABLE `tc_positiongroups` ADD INDEX `creator_id` ( `creator_id` ); 
ALTER TABLE `tc_positiongroups` ADD `editor_id` INT(10) NOT NULL;
ALTER TABLE `tc_positiongroups` ADD INDEX `editor_id` ( `editor_id` ); 

ALTER TABLE `tc_positiongroups_sections` ADD `creator_id` INT(10) NOT NULL;
ALTER TABLE `tc_positiongroups_sections` ADD INDEX `creator_id` ( `creator_id` ); 
ALTER TABLE `tc_positiongroups_sections` ADD `editor_id` INT(10) NOT NULL;
ALTER TABLE `tc_positiongroups_sections` ADD INDEX `editor_id` ( `editor_id` ); 
