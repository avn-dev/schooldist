ALTER TABLE `ts_commission_categories_rates` ADD `rate_type` ENUM('percent','amount') NOT NULL DEFAULT 'percent' AFTER `rate`;
ALTER TABLE `kolumbus_prices_new` ADD `agency_category_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_prices_new` DROP INDEX `unique`, ADD UNIQUE `unique` (`idSchool`, `idSaison`, `idCurrency`, `idWeek`, `idParent`, `typeParent`, `payment_condition_id`, `nationality`, `agency_category_id`);
ALTER TABLE `kolumbus_accommodation_nightprices_periods` ADD `agency_category_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_accommodation_fee` ADD `agency_category_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_accommodation_fee` DROP INDEX `unique`, ADD UNIQUE `unqiue` (`school_id`, `categorie_id`, `saison_id`, `cost_id`, `currency_id`, `nationality`, `agency_category_id`);
ALTER TABLE `kolumbus_course_fee` ADD `agency_category_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_course_fee` DROP INDEX `unique`, ADD UNIQUE `unique` (`school_id`, `course_id`, `saison_id`, `cost_id`, `currency_id`, `nationality`, `agency_category_id`);
