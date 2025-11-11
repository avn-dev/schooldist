ALTER TABLE `ts_activities_blocks` ADD `released_for_app` TINYINT(1) NOT NULL DEFAULT '0' AFTER `repeat_weeks`;
ALTER TABLE `ts_activities_i18n` ADD `description` TEXT NOT NULL AFTER `name`;
