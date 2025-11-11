ALTER TABLE `ts_activities_i18n` ADD `description_short` TEXT NOT NULL AFTER `description`;

UPDATE `filemanager_tags` SET `tag` = 'App-Image' WHERE `entity` = 'TsActivities\\Entity\\Activity' AND `tag` = 'App Image';
