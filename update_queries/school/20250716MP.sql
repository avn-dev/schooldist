ALTER TABLE `ts_hubspot_ids` ADD `traveller_hubspot_id` INT(11) NOT NULL AFTER `entity_id`;

ALTER TABLE `ts_hubspot_ids` DROP PRIMARY KEY, ADD PRIMARY KEY (`hubspot_id`, `entity_id`, `entity`, `traveller_hubspot_id`) USING BTREE;

ALTER TABLE `ts_hubspot_ids` CHANGE `traveller_hubspot_id` `traveller_hubspot_id` BIGINT(20) NOT NULL;


ALTER TABLE `kolumbus_groups` ADD `newsletter` BOOLEAN NOT NULL AFTER `sales_person_id`;