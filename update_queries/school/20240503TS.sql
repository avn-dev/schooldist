CREATE TABLE IF NOT EXISTS `ts_tuition_courses_startdates_courselanguages` (
    `type_id` mediumint(8) UNSIGNED NOT NULL,
    `courselanguage_id` smallint(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`type_id`,`courselanguage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;