ALTER TABLE `ts_documents_to_gui2` CHANGE `set` `set` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
UPDATE `ts_documents_to_gui2` SET `set`= NULL WHERE `set`= ''