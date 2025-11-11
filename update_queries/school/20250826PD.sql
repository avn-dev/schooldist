ALTER TABLE `kolumbus_tuition_classes` ADD `confirmed` TIMESTAMP NULL DEFAULT NULL;
INSERT INTO `system_config` (`c_key`, `c_value`) VALUES ('ts_tuition_class_confirm', '0');
ALTER TABLE `kolumbus_tuition_classes` ADD INDEX `confirmed` (`confirmed`);