
CREATE TABLE `ts_inquires_payments_unallocated` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `file` varchar(255) NOT NULL,
  `transaction` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `amount` decimal(16,5) NOT NULL,
  `amount_currency` smallint(5) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
