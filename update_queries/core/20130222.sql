ALTER TABLE `system_translations` ADD `trace` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `active` ,
ADD `created_language` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `trace`;

ALTER TABLE `language_data` ADD `created_language` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `trace`;