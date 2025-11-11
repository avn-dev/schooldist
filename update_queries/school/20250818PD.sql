CREATE TABLE IF NOT EXISTS `ts_accounting_companies_combinations_to_course_categories` (
    `company_combination_id` int(11) unsigned NOT NULL,
    `course_category_id` int(11) unsigned NOT NULL,
    PRIMARY KEY (`company_combination_id`,`course_category_id`) USING BTREE,
    KEY `company_combination_id` (`company_combination_id`),
    KEY `course_category_id` (`course_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
