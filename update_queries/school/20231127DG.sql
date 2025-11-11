ALTER TABLE `kolumbus_inquiries_payments`
    CHANGE `id` `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
    CHANGE `creator_id` `creator_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    CHANGE `editor_id` `editor_id` SMALLINT UNSIGNED NOT NULL,
    CHANGE `inquiry_id` `inquiry_id` MEDIUMINT UNSIGNED NOT NULL,
    CHANGE `method_id` `method_id` SMALLINT UNSIGNED NOT NULL,
    CHANGE `type_id` `type_id` TINYINT(1) UNSIGNED NOT NULL,
    CHANGE `grouping_id` `grouping_id` MEDIUMINT UNSIGNED NOT NULL;

ALTER TABLE `kolumbus_inquiries_payments`
    ADD `currency_inquiry` SMALLINT UNSIGNED NOT NULL AFTER `amount_school`,
    ADD `currency_school` SMALLINT UNSIGNED NOT NULL AFTER `currency_inquiry`;

ALTER TABLE `kolumbus_inquiries_payments_overpayment` CHANGE `inquiry_document_id` `inquiry_document_id` INT(11) NULL COMMENT 'Nur wichtig für Rechnung/Creditnote';

ALTER TABLE `kolumbus_inquiries_payments_documents` COMMENT 'Zahlungsbelege';

ALTER TABLE `ts_documents_to_inquiries_payments` COMMENT 'Verknüpfung zw. Zahlungen und Rechnungen';

DROP TABLE `kolumbus_inquiries_payments_overpayment_transaction`;
