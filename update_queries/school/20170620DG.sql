
DROP TABLE IF EXISTS `kolumbus_payments`;

CREATE TABLE IF NOT EXISTS `kolumbus_payment_method_schools` (
  `payment_method_id` smallint(5) UNSIGNED NOT NULL,
  `school_id` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`payment_method_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `kolumbus_payment_method` DROP `is_switzerland`;

ALTER TABLE `kolumbus_payment_method` ADD `valid_until` DATE NOT NULL DEFAULT '0000-00-00' AFTER `active`;
