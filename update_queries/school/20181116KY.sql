RENAME TABLE kolumbus_kontakt TO ts_agencies_contacts;
ALTER TABLE `ts_agencies_contacts` CHANGE `parent_id` `agency_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `ts_agencies_contacts` DROP `parent_typ`;
ALTER TABLE `ts_agencies_contacts` COMMENT = 'Tabellenname wurde von kolumbus_kontakt umbenannt und die muss irgendwann in die tc_contacts migriert werden';
UPDATE `customer_db_config` SET `db_name` = 'Agency contacts',`db_encode_pw` = 1,`multi_login` = 1,`allow_accesscode` = 0,`external_table` = 'ts_agencies_contacts',`external_table_accesscode` = '',`external_table_groups` = '',`external_table_pk` = 'id',`external_table_email` = 'email',`external_table_user` = 'nickname',`external_table_pass` = 'password',`external_table_active` = 'active',`active` = 1 WHERE `id` = 13;