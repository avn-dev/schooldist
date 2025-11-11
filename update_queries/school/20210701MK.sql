ALTER TABLE `kolumbus_tuition_courses` CHANGE `price_calculation` `price_calculation` ENUM('week','month','fixed') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'week';
