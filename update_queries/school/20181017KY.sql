CREATE TABLE `ts_sponsors` (
	`id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`active` tinyint(4) NOT NULL DEFAULT '1',
	`valid_until` date NOT NULL DEFAULT '0000-00-00',
	`creator_id` smallint(5) UNSIGNED NOT NULL,
	`editor_id` smallint(5) UNSIGNED NOT NULL,
	`address_id` int(11) NOT NULL,
	`numberrange_id` smallint(5) UNSIGNED NOT NULL,
	`number` varchar(255) NOT NULL,
	`name` varchar(255) NOT NULL,
	`abbreviation` varchar(50) NOT NULL,
	`language_iso` varchar(50) NOT NULL,
	`comment` text NOT NULL,
	`sponsoring` enum('course','all') DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `ts_sponsors_to_schools` (
	`sponsor_id` smallint(5) UNSIGNED NOT NULL,
	`school_id` smallint(5) UNSIGNED NOT NULL,
	PRIMARY KEY (`sponsor_id`, `school_id`)
) ENGINE = InnoDB;

CREATE TABLE `ts_sponsors_to_contacts` (
	`sponsor_id` smallint(5) UNSIGNED NOT NULL,
	`contact_id` INT(11) NOT NULL,
	PRIMARY KEY (`sponsor_id`, `contact_id`)
) ENGINE = InnoDB;

CREATE TABLE `ts_sponsors_payment_conditions_validity` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
	`creator_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`editor_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`sponsor_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`payment_condition_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`valid_from` date NOT NULL,
	`valid_until` date NOT NULL,
	PRIMARY KEY (`id`),
	KEY `sponsor_id` (`sponsor_id`),
	KEY `payment_condition_id` (`payment_condition_id`)
) ENGINE = InnoDB;

CREATE TABLE `ts_sponsors_cancellations_validity` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
	`creator_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`editor_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`sponsor_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`cancellation_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`valid_from` DATE NOT NULL,
	`valid_until` DATE NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `ts_inquiries`
	ADD `sponsored` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status_id`,
	ADD `sponsor_id` smallint(5) UNSIGNED NOT NULL AFTER `sponsored`,
	ADD `sponsor_contact_id` smallint(5) UNSIGNED NOT NULL AFTER `agency_contact_id`;

CREATE TABLE `ts_inquiries_sponsoring_guarantees` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
	`creator_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`editor_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
	`inquiry_id` INT(11) NOT NULL,
	`from` DATE NULL DEFAULT NULL,
	`until` DATE NULL DEFAULT NULL,
	`path` VARCHAR(255) NULL,
	PRIMARY KEY (`id`),
	KEY `inquiry_id` (`inquiry_id`)
) ENGINE = InnoDB;