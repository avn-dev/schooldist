
ALTER TABLE `kolumbus_email_log` CHANGE `created` `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE `kolumbus_email_log` SET `attachments` = REPLACE(`attachments`, '\\/media\\/secure\\/', '\\/storage\\/'), `created` = `created` WHERE `attachments` LIKE '%\\\\/media\\\\/secure\\\\/%';
