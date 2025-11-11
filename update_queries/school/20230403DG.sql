ALTER TABLE `ts_reporting_reports`
    ADD `visualization_grand_totals` TINYINT(1) NOT NULL DEFAULT '0' AFTER `visualization`,
    ADD `visualization_row_totals` TINYINT(1) NOT NULL DEFAULT '0' AFTER `visualization_grand_totals`;

UPDATE `ts_reporting_reports_settings` SET `object` = 'TsReporting\\Generator\\Groupings\\Document\\Fees' WHERE `object` = 'TsReporting\\Generator\\Groupings\\Booking\\Fees';
UPDATE ts_reporting_reports_settings SET config = REPLACE(config, 'TsReporting\\\\Generator\\\\Groupings\\\\Booking\\\\Fees', 'TsReporting\\\\Generator\\\\Groupings\\\\Document\\\\Fees') WHERE object = 'TsReporting\\Generator\\Groupings\\Aggregated';

UPDATE `ts_reporting_reports_settings` SET `object` = 'TsReporting\\Generator\\Groupings\\Document\\ItemType' WHERE `object` = 'TsReporting\\Generator\\Groupings\\Booking\\ItemType';
UPDATE ts_reporting_reports_settings SET config = REPLACE(config, 'TsReporting\\\\Generator\\\\Groupings\\\\Booking\\\\ItemType', 'TsReporting\\\\Generator\\\\Groupings\\\\Document\\\\ItemType') WHERE object = 'TsReporting\\Generator\\Groupings\\Aggregated';

/* Wenn Query oben mit TINYTEXT schon ausgeführt werden konnte */
ALTER TABLE `ts_reporting_reports` CHANGE `visualization_grand_totals` `visualization_grand_totals` TINYINT(1) NOT NULL DEFAULT '0';
