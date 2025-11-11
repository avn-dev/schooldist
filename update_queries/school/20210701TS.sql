CREATE TABLE IF NOT EXISTS `ts_tuition_courses_programs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `editor_id` int(11) NOT NULL,
    `creator_id` int(11) NOT NULL,
    `course_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `active` (`active`),
    KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ts_tuition_courses_programs_services` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `editor_id` int(11) NOT NULL,
    `creator_id` int(11) NOT NULL,
    `program_id` int(11) NOT NULL,
    `type` enum('course','activity') NOT NULL DEFAULT 'course',
    `type_id` int(11) NOT NULL,
    `from` date DEFAULT NULL,
    `until` date DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `active` (`active`),
    KEY `program_id` (`program_id`),
    KEY `program_id_type` (`program_id`,`type`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `ts_inquiries_journeys_courses` ADD `program_id` INT NOT NULL AFTER `level_id`, ADD INDEX (`program_id`);
ALTER TABLE `kolumbus_groups_courses` ADD `program_id` INT NOT NULL AFTER `level_id`, ADD INDEX (`program_id`);

ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` ADD `program_service_id` INT NOT NULL AFTER `course_id`, ADD INDEX (`program_service_id`);
ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` CHANGE `course_id` `course_id` INT(11) NOT NULL COMMENT 'deprecated - program_service_id benutzen';
ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` DROP INDEX `uniquie_ktbic`, ADD UNIQUE `uniquie_ktbic` (`block_id`, `inquiry_course_id`, `course_id`, `program_service_id`, `room_id`) USING BTREE;
ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` DROP INDEX `id`, ADD INDEX `id` (`id`, `block_id`, `inquiry_course_id`, `course_id`, `program_service_id`) USING BTREE;

ALTER TABLE `ts_inquiries_journeys_courses_tuition_index` ADD `program_service_id` INT NOT NULL AFTER `course_id`;
ALTER TABLE `ts_inquiries_journeys_courses_tuition_index` DROP PRIMARY KEY, ADD PRIMARY KEY (`journey_course_id`, `course_id`, `program_service_id`, `week`) USING BTREE;

ALTER TABLE `kolumbus_tuition_attendance` ADD `program_service_id` INT NOT NULL AFTER `course_id`, ADD INDEX (`program_service_id`);
ALTER TABLE `kolumbus_tuition_attendance` CHANGE `course_id` `course_id` INT(11) NOT NULL COMMENT 'deprecated - program_service_id benutzen';

ALTER TABLE `kolumbus_tuition_progress` ADD `program_service_id` INT NOT NULL AFTER `course_id`, ADD INDEX (`program_service_id`);
ALTER TABLE `kolumbus_tuition_progress` CHANGE `course_id` `course_id` INT(11) NOT NULL COMMENT 'deprecated - program_service_id benutzen';
ALTER TABLE `kolumbus_tuition_progress` DROP INDEX `ktp_unique1`, ADD UNIQUE `ktp_unique1` (`inquiry_id`, `levelgroup_id`, `inquiry_course_id`, `week`, `course_id`, `program_service_id`) USING BTREE;

ALTER TABLE `kolumbus_examination` ADD `program_service_id` INT NOT NULL AFTER `course_id`, ADD INDEX (`program_service_id`);
ALTER TABLE `kolumbus_examination` CHANGE `course_id` `course_id` INT(11) NOT NULL COMMENT 'deprecated - program_service_id benutzen';

CREATE TABLE IF NOT EXISTS `ts_companies_industries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `creator_id` int(11) NOT NULL DEFAULT '0',
    `editor_id` int(11) NOT NULL DEFAULT '0',
    `name` varchar(255) CHARACTER SET utf8 NOT NULL,
    `short_name` char(50) CHARACTER SET utf8 NOT NULL,
    `description` text CHARACTER SET utf8 NOT NULL,
    `parent_id` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `creator_id` (`creator_id`),
    KEY `parent_id` (`parent_id`),
    KEY `editor_id` (`editor_id`),
    KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ts_companies_job_opportunities` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `creator_id` int(11) NOT NULL DEFAULT '0',
    `editor_id` int(11) NOT NULL,
    `status` tinyint(1) NOT NULL DEFAULT '1',
    `name` varchar(255) CHARACTER SET utf8 NOT NULL,
    `short_name` varchar(100) CHARACTER SET utf8 NOT NULL,
    `company_id` int(11) NOT NULL,
    `industry_id` int(11) NOT NULL,
    `wage` decimal(16,5) NOT NULL,
    `wage_per` char(20) CHARACTER SET utf8 NOT NULL,
    `hours` decimal(16,5) NOT NULL,
    `hours_per` char(20) CHARACTER SET utf8 NOT NULL,
    `description` text CHARACTER SET utf8 NOT NULL,
    `comment` text CHARACTER SET utf8 NOT NULL,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    KEY `industry_id` (`industry_id`),
    KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ts_companies_job_opportunities_inquiries_courses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `creator_id` int(11) NOT NULL DEFAULT '0',
    `editor_id` int(11) NOT NULL,
    `inquiry_course_id` int(11) NOT NULL,
    `program_service_id` int(11) NOT NULL,
    `job_opportunity_id` int(11) NOT NULL,
    `status` bit(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `inquiry_course_id` (`inquiry_course_id`),
    KEY `job_opportunity_id` (`job_opportunity_id`),
    KEY `active` (`active`),
    KEY `inquiry_course_program` (`inquiry_course_id`,`program_service_id`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ts_companies_to_industries` (
    `company_id` int(11) NOT NULL,
    `industry_id` int(11) NOT NULL,
    PRIMARY KEY (`company_id`,`industry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `kolumbus_inquiries_documents` CHANGE `entity` `entity` ENUM('Ext_TS_Inquiry','Ext_TS_Inquiry_Journey','TsCompany\\Entity\\JobOpportunity\\StudentAllocation') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
