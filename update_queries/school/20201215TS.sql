CREATE TABLE IF NOT EXISTS `ts_inquiries_payments_release` (
    `payment_id` int(11) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `creator_id` int(11) NOT NULL,
    PRIMARY KEY (`payment_id`),
    KEY `created` (`created`),
    KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ts_booking_stacks` ADD `payment_id` INT NOT NULL AFTER `document_id`;
ALTER TABLE `ts_companies` ADD `automatic_payment_release_after` TINYINT(3) NOT NULL AFTER `automatic_release_after`;
ALTER TABLE `ts_companies` CHANGE `automatic_release_after` `automatic_document_release_after` TINYINT(3) NOT NULL;
