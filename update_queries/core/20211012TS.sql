CREATE TABLE IF NOT EXISTS `tc_contacts_to_system_types` (
    `contact_id` mediumint(9) NOT NULL,
    `type` char(100) NOT NULL,
    PRIMARY KEY (`contact_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
