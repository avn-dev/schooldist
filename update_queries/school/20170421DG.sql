
UPDATE `kolumbus_agency_comments` SET `follow_up` = '0000-00-00' WHERE `follow_up` IS NULL;
ALTER TABLE `kolumbus_agency_comments` CHANGE `follow_up` `follow_up` DATE NOT NULL DEFAULT '0000-00-00';

UPDATE `kolumbus_agency_comments` SET `documents` = '' WHERE `documents` = 'Array';
