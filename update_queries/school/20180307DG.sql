
ALTER TABLE `kolumbus_costs` ADD `dependency_on_age` TINYINT(1) NOT NULL DEFAULT '0' AFTER `dependency_on_duration`;

CREATE TABLE `kolumbus_costs_dependencies_age` ( `fee_id` MEDIUMINT NOT NULL , `operator` VARCHAR(1) NOT NULL , `age` TINYINT NOT NULL ) ENGINE = InnoDB;
