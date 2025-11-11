CREATE TABLE IF NOT EXISTS `language_data_external` (
  `language_data_id` int(11) NOT NULL,
  `language` char(2) NOT NULL,
  `service` varchar(100) NOT NULL,
  PRIMARY KEY (`language_data_id`,`language`,`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;