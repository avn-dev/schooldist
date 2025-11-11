ALTER TABLE `kolumbus_tuition_courses` CHANGE `price_calculation` `price_calculation` ENUM('week','unit','month','fixed') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'week';

UPDATE `kolumbus_tuition_courses` SET `price_calculation` = 'unit', `changed` = `changed` WHERE `per_unit` IN (1, 2);
