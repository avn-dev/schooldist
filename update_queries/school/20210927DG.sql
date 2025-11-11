ALTER TABLE `kolumbus_payment_method` DROP `idClient`;

UPDATE `kolumbus_payment_method` SET `type` = '', `changed` = `changed` WHERE `type` = '0';

UPDATE `kolumbus_payment_method` SET `type` = 'credit_card', `changed` = `changed` WHERE `type` = '1';

UPDATE `kolumbus_payment_method` SET `type` = 'cheque', `changed` = `changed` WHERE `type` = '2';

