ALTER TABLE `kolumbus_prices_new` ADD `nationality` CHAR(2) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_prices_new` DROP INDEX `unique`, ADD UNIQUE `unique` (`idSchool`, `idSaison`, `idCurrency`, `idWeek`, `idParent`, `typeParent`, `payment_condition_id`, `nationality`);
ALTER TABLE `kolumbus_prices_new` DROP INDEX `idClient`, ADD UNIQUE `unique` (`idSchool`, `idSaison`, `idCurrency`, `idWeek`, `idParent`, `typeParent`, `payment_condition_id`, `nationality`);
ALTER TABLE `kolumbus_accommodation_nightprices_periods` ADD `nationality` CHAR(2) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_accommodation_fee` ADD `nationality` CHAR(2) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_accommodation_fee` DROP INDEX `client_id`, ADD UNIQUE `unqiue` (`school_id`, `categorie_id`, `saison_id`, `cost_id`, `currency_id`, `nationality`);
ALTER TABLE `kolumbus_course_fee` ADD `nationality` CHAR(2) NULL DEFAULT NULL;
ALTER TABLE `kolumbus_course_fee` DROP INDEX `client_id`, ADD UNIQUE `unique` (`school_id`, `course_id`, `saison_id`, `cost_id`, `currency_id`, `nationality`);