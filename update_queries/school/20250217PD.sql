ALTER TABLE `kolumbus_prices_new` ADD `country_group_id` TINYINT NULL DEFAULT NULL;
ALTER TABLE `kolumbus_prices_new` DROP INDEX `unique`, ADD UNIQUE `unique` (`idSchool`, `idSaison`, `idCurrency`, `idWeek`, `idParent`, `typeParent`, `payment_condition_id`, `nationality`, `agency_category_id`, `country_group_id`);
ALTER TABLE `kolumbus_accommodation_nightprices_periods` ADD `country_group_id` TINYINT NULL DEFAULT NULL;
ALTER TABLE `kolumbus_accommodation_fee` ADD `country_group_id` TINYINT NULL DEFAULT NULL;
ALTER TABLE `kolumbus_accommodation_fee` DROP INDEX `unqiue`, ADD UNIQUE `unqiue` (`school_id`, `categorie_id`, `saison_id`, `cost_id`, `currency_id`, `nationality`, `agency_category_id`, `country_group_id`);
ALTER TABLE `kolumbus_course_fee` ADD `country_group_id` TINYINT NULL DEFAULT NULL;
ALTER TABLE `kolumbus_course_fee` DROP INDEX `unique`, ADD UNIQUE `unique` (`school_id`, `course_id`, `saison_id`, `cost_id`, `currency_id`, `nationality`, `agency_category_id`, `country_group_id`);