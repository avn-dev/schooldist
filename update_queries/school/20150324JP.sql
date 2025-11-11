CREATE TABLE IF NOT EXISTS `ts_inquiries_payments_to_creditnote_payments` (
  `payment_id` int(11) NOT NULL,
  `creditnote_payment_id` int(11) NOT NULL,
  PRIMARY KEY (`payment_id`,`creditnote_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;