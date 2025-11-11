ALTER TABLE `kolumbus_forms_schools` ADD `at_school_fees` TINYINT NOT NULL DEFAULT '0' AFTER `tpl_id`, ADD `generate_invoice` TINYINT NOT NULL DEFAULT '0' AFTER `at_school_fees`;

