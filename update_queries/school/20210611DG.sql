CREATE TABLE IF NOT EXISTS `ts_inquiries_to_inquiries` (
	`parent_id` mediumint(8) UNSIGNED NOT NULL,
	`child_id` mediumint(8) UNSIGNED NOT NULL,
	PRIMARY KEY (`parent_id`,`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ts_inquiries`
    ADD `type` BIT(2) NOT NULL DEFAULT b'10' COMMENT 'BIT: 1=enquiry, 2=booking' AFTER `editor_id`,
    ADD `follow_up` DATE NULL DEFAULT NULL AFTER `currency_id`,
	ADD `converted` TIMESTAMP NULL DEFAULT NULL AFTER `follow_up`,
    DROP `arrival_date`,
    DROP `departure_date`,
	ADD INDEX (`type`);

ALTER TABLE `ts_inquiries_journeys`
	ADD `type` BIT(2) NOT NULL DEFAULT b'10' COMMENT 'BIT: 1=request, 2=booking' AFTER `school_id`,
	ADD `transfer_mode` BIT(2) NOT NULL DEFAULT b'0' COMMENT 'BIT: 1=arrival, 2=departure' AFTER `type`,
	ADD INDEX (`type`);

ALTER TABLE `ts_inquiries_journeys_courses` CHANGE `visible` `visible` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `ts_inquiries_journeys_accommodations` CHANGE `visible` `visible` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `ts_inquiries_journeys_insurances` CHANGE `visible` `visible` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `ts_groups_to_contacts`
    ADD `type` ENUM('inquiry','enquiry') NOT NULL DEFAULT 'enquiry' COMMENT 'Enquiry migration' AFTER `contact_id`,
	ADD PRIMARY KEY( `group_id`, `contact_id`, `type`);

UPDATE tc_gui2_filtersets_bars_elements_basedon t1 INNER JOIN tc_gui2_filtersets_bars_elements t2 ON t2.id = t1.element_id INNER JOIN tc_gui2_filtersets_bars t3 ON t3.id = t2.bar_id INNER JOIN tc_gui2_filtersets t4 ON t4.id = t3.set_id SET base_on = 'document_number_all' WHERE base_on = 'offer_document_number' AND t4.application = 'ts_enquiry';
UPDATE tc_gui2_filtersets_bars_elements_basedon t1 INNER JOIN tc_gui2_filtersets_bars_elements t2 ON t2.id = t1.element_id INNER JOIN tc_gui2_filtersets_bars t3 ON t3.id = t2.bar_id INNER JOIN tc_gui2_filtersets t4 ON t4.id = t3.set_id SET base_on = 'filter_enquiry_status' WHERE base_on = 'status_filter' AND t4.application = 'ts_enquiry';
UPDATE tc_gui2_filtersets_bars_elements_basedon t1 INNER JOIN tc_gui2_filtersets_bars_elements t2 ON t2.id = t1.element_id INNER JOIN tc_gui2_filtersets_bars t3 ON t3.id = t2.bar_id INNER JOIN tc_gui2_filtersets t4 ON t4.id = t3.set_id SET base_on = 'invoice_status' WHERE base_on = 'has_offers' AND t4.application = 'ts_enquiry';

ALTER TABLE `ts_enquiries` ADD `inquiry_id` MEDIUMINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ts_enquiries_combinations`
    ADD `journey_id` MEDIUMINT UNSIGNED NULL DEFAULT NULL,
	ADD INDEX(`enquiry_id`);
