ALTER TABLE `ts_companies`
CHANGE `currency_iso` `currency_iso` CHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `interface` `interface` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
