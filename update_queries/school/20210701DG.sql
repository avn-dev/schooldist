DROP TABLE IF EXISTS `kolumbus_accounting`;
DROP TABLE IF EXISTS `kolumbus_accounting_accounts`;
DROP TABLE IF EXISTS `kolumbus_accounting_accounts_transactions`;
DROP TABLE IF EXISTS `kolumbus_accounting_accounts_transactions_creditnotes`;
DROP TABLE IF EXISTS `kolumbus_accounting_agency_provisions_payments`;
DROP TABLE IF EXISTS `kolumbus_accounting_agency_provisions_payments_transactions`;
DROP TABLE IF EXISTS `kolumbus_accounting_allocation_accounts`;
DROP TABLE IF EXISTS `kolumbus_accounting_category`;
DROP TABLE IF EXISTS `kolumbus_accounting_category_subcategories`;
DROP TABLE IF EXISTS `kolumbus_accounting_checkoptions`;
DROP TABLE IF EXISTS `kolumbus_accounting_inquiryposition_account`;
DROP TABLE IF EXISTS `kolumbus_accounting_manual_transactions`;
DROP TABLE IF EXISTS `kolumbus_accounting_static_clearings`;
DROP TABLE IF EXISTS `kolumbus_accounting_static_costs`;
DROP TABLE IF EXISTS `kolumbus_accounting_static_returns`;
DROP TABLE IF EXISTS `kolumbus_accounting_suppliers`;
DROP TABLE IF EXISTS `kolumbus_accounting_teacher`;
DROP TABLE IF EXISTS `kolumbus_accounting_transfer`;

ALTER TABLE `kolumbus_clients`
	DROP `accounting`,
	DROP `one_zentral_accounting`;
