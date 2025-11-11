RENAME TABLE `ts_specials_countries_group` TO `ts_specials_agencies_countries_group`;
CREATE TABLE IF NOT EXISTS `ts_specials_countries_group` (
    `special_id` int(11) NOT NULL,
    `country_group_id` int(11) NOT NULL,
    KEY `agency_id` (`special_id`),
    KEY `list_id` (`country_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;