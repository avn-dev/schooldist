ALTER TABLE `wdbasic_attributes` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `class_id` `class_id` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE `wdbasic_attributes_decimal` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `value` `value` DECIMAL( 16, 5 ) NOT NULL;

ALTER TABLE `wdbasic_attributes_int` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `wdbasic_attributes_tinyint` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `wdbasic_attributes_varchar` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE `wdbasic_attributes_float` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `attribute_id` INT UNSIGNED NOT NULL , 
  `value` FLOAT( 5, 2 ) NOT NULL ,
  INDEX ( `attribute_id` )
) ENGINE = InnoDB;