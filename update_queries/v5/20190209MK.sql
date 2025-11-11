ALTER TABLE `office_payments` ADD `account_activity_id` INT NOT NULL AFTER `document_id`;
ALTER TABLE `office_accounts_activities` ADD `sender` VARCHAR(255) NOT NULL AFTER `checksum`, ADD `reference` VARCHAR(255) NOT NULL AFTER `sender`, ADD `text` TEXT NOT NULL AFTER `reference`, ADD `data` TEXT NOT NULL AFTER `text`;
CREATE TABLE `office_accounts` (
  `id` int(11) NOT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` varchar(10) NOT NULL,
  `iban` varchar(34) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `office_accounts` ADD PRIMARY KEY (`id`);
ALTER TABLE `office_accounts` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
