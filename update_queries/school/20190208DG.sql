ALTER TABLE `ts_payment_conditions`
	ADD `name` VARCHAR(255) NOT NULL AFTER `user_id`,
	ADD `comment` TEXT NOT NULL AFTER `name`,
	ADD `position` SMALLINT UNSIGNED NOT NULL AFTER `comment`;

ALTER TABLE `ts_payment_conditions`
	ADD `surcharge_amount` DECIMAL(16,5) NOT NULL AFTER `position`,
	ADD `surcharge_type` ENUM('', 'amount','percent') NOT NULL AFTER `surcharge_amount`,
	ADD `surcharge_calculation` ENUM('', 'one_time','per_installment') NOT NULL AFTER `surcharge_type`,
	ADD `surcharge_on` ENUM('', 'deposit','installments','final') NOT NULL AFTER `surcharge_calculation`;

CREATE TABLE `ts_payment_conditions_settings` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `payment_condition_id` smallint(5) UNSIGNED NOT NULL,
  `position` tinyint(3) UNSIGNED NOT NULL,
  `type` enum('deposit','final','installment') CHARACTER SET ascii NOT NULL,
  `due_days` int(11) NOT NULL,
  `due_direction` enum('before','after') CHARACTER SET ascii NOT NULL,
  `due_type` enum('document_date','course_start_date','course_start_date_month_end','begin','end') CHARACTER SET ascii NOT NULL,
  `installment_type` enum('weekly','monthly') CHARACTER SET ascii NOT NULL,
  `installment_split` enum('service_period','percentage') CHARACTER SET ascii NOT NULL,
  `installment_charging` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_condition_id` (`payment_condition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ts_payment_conditions_settings_amounts` (
  `setting_id` smallint(5) UNSIGNED NOT NULL,
  `setting` enum('amount','percent') CHARACTER SET ascii NOT NULL,
  `type` varchar(255) CHARACTER SET ascii NOT NULL,
  `type_id` smallint(6) NOT NULL,
  `amount` decimal(16,5) NOT NULL,
  PRIMARY KEY (`setting_id`,`setting`,`type`,`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ts_agencies_payment_conditions_validity` (
  `id` smallint(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `creator_id` smallint(9) UNSIGNED NOT NULL,
  `editor_id` smallint(9) UNSIGNED NOT NULL,
  `agency_id` mediumint(10) UNSIGNED NOT NULL,
  `payment_condition_id` smallint(11) UNSIGNED NOT NULL,
  `school_id` smallint(5) UNSIGNED DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_id` (`agency_id`),
  KEY `payment_condition_id` (`payment_condition_id`),
  KEY `school_id` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `kolumbus_inquiries_documents_versions` ADD `payment_condition_id` SMALLINT UNSIGNED NOT NULL AFTER `template_language`;

CREATE TABLE `ts_documents_versions_paymentterms` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `version_id` int(11) UNSIGNED NOT NULL,
  `setting_id` smallint(5) UNSIGNED NOT NULL,
  `type` enum('deposit','final','installment') CHARACTER SET ascii NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(16,5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `version_id` (`version_id`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;