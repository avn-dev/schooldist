ALTER TABLE `tc_enquiries_offers` ADD `offer_key` VARCHAR(255) NOT NULL AFTER `school_currency_iso`;
ALTER TABLE `tc_enquiries_offers` ADD INDEX(`offer_key`);
