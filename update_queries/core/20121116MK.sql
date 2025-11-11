CREATE TABLE IF NOT EXISTS `tc_rounding_settings` (
  `currency_iso` varchar(3) NOT NULL,
  `invoice_precision` tinyint(4) NOT NULL DEFAULT '2',
  `invoice_line_item_tax_precision` tinyint(4) NOT NULL DEFAULT '5',
  `increment` tinyint(4) NOT NULL DEFAULT '1',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editor_id` int(11) NOT NULL,
  PRIMARY KEY (`currency_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;