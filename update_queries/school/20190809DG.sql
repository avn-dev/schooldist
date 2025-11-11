ALTER TABLE `ts_companies` ADD `always_create_claim_debt` TINYINT(1) NOT NULL AFTER `invoice_item_description_changeable`;

DELETE FROM `system_gui2_flex_data` WHERE `gui_hash` = 'f78999cf63aa9e050a7f9459aa8f6a0c';
