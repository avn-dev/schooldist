CREATE TABLE `kolumbus_examination_sections_categories_to_schools` ( `category_id` INT NOT NULL , `school_id` INT NOT NULL , UNIQUE (`category_id`, `school_id`)) ENGINE = InnoDB;
INSERT INTO kolumbus_examination_sections_categories_to_schools (category_id, school_id) SELECT id AS category_id, school_id FROM kolumbus_examination_sections_categories WHERE active = 1;
ALTER TABLE `kolumbus_examination_sections_categories` CHANGE `school_id` `__school_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_examination_sections` CHANGE `school_id` `__school_id` INT(11) NOT NULL;
