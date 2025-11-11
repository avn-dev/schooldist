ALTER TABLE `kolumbus_inquiries_documents`
    ADD COLUMN `office_registered` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_inquiries_documents`
    ADD COLUMN `tax_registered` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_inquiries_documents`
    ADD COLUMN `draft` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `kolumbus_inquiries_documents`
    ADD INDEX `draft` (`draft`);