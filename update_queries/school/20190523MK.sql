ALTER TABLE `kolumbus_currency` ADD UNIQUE(`iso4217`);
ALTER TABLE `kolumbus_currency` ADD UNIQUE(`iso4217_num`);
INSERT INTO `kolumbus_currency` (`id`, `name`, `iso4217`, `iso4217_num`, `sign`, `changed`, `created`) VALUES (NULL, 'Argentine peso', 'ARS', '32', 'Arg$', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
