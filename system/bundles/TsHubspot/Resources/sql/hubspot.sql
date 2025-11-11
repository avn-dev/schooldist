CREATE TABLE IF NOT EXISTS `ts_hubspot_ids` (
    `hubspot_id` BIGINT UNSIGNED NOT NULL ,
    `entity` VARCHAR(255) NOT NULL ,
    `entity_id` INT(11) UNSIGNED NOT NULL,
	PRIMARY KEY (`hubspot_id`,`entity`,`entity_id`)
    ) ENGINE = InnoDB;