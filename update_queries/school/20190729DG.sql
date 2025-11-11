ALTER TABLE `kolumbus_accounting_agency_payments`
	CHANGE `amount` `amount` DECIMAL(16,5) NOT NULL,
	CHANGE `amount_school` `amount_school` DECIMAL(16,5) NOT NULL;

/* Ungenauigkeiten nach Umstellung korrigieren (betrifft nur Centbeträge) */
UPDATE `kolumbus_accounting_agency_payments` SET
	`amount` = ROUND(`amount`, 2),
	`amount_school` = ROUND(`amount_school`, 2),
    `changed` = `changed`
