ALTER TABLE `kolumbus_tuition_levelgroups` ADD `position` SMALLINT UNSIGNED NOT NULL AFTER `title`;

UPDATE `kolumbus_tuition_levelgroups` SET `title` = 'Default', `changed` = `changed` WHERE `title` = 'Default level group' AND `active` = 1;
