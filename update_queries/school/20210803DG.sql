ALTER TABLE `kolumbus_costs` CHANGE `charge` `charge` ENUM('auto','semi','manual') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'auto';

UPDATE `kolumbus_costs` SET `charge` = 'manual', `calculate` = 0, `changed` = `changed` WHERE `type` = 2;

UPDATE `kolumbus_forms_pages` SET `type` = 'booking' WHERE `type` = '' AND `form_id` IN (SELECT id FROM `kolumbus_forms` WHERE `type` = 'registration_v3' AND `active` = 1);
