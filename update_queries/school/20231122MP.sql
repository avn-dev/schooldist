CREATE TABLE `kolumbus_specials_countries_group` LIKE `kolumbus_agency_lists_agencies`;
ALTER TABLE `kolumbus_specials_countries_group` CHANGE `list_id` `country_group_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_specials_countries_group` CHANGE `agency_id` `special_id` INT(11) NOT NULL;