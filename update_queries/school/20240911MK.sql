ALTER TABLE `ts_specials_codes_usages` DROP PRIMARY KEY, ADD INDEX (`code_id`) USING BTREE;
ALTER TABLE `ts_specials_codes_usages` CHANGE `code_id` `code_id` INT(11) NOT NULL;
