INSERT INTO `system_elements` (`id`, `title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`) VALUES (NULL, 'TcApi', '', 'bundle', 'Fidelo', 'TcApi', '0.010', '', '', '', '', '', '0', '1', '1', '0', '1');

ALTER TABLE `tc_communication_emailaccounts` ADD `oauth2_data` TEXT NULL DEFAULT NULL AFTER `imap_sent_mail_folder_root`;
ALTER TABLE `tc_communication_emailaccounts` ADD `oauth2_provider` VARCHAR(100) NULL DEFAULT NULL AFTER `imap_sent_mail_folder_root`;
ALTER TABLE `tc_communication_emailaccounts` ADD `smtp_auth` ENUM('password','oauth2') NOT NULL DEFAULT 'password' AFTER `smtp_connection`;
ALTER TABLE `tc_communication_emailaccounts` ADD `imap_auth` ENUM('password','oauth2') NOT NULL DEFAULT 'password' AFTER `imap_connection`;

INSERT INTO `system_config` (`c_key`, `c_value`) VALUES ('oauth2.google.client_id', '384517480350-hp5v68bqg0rej740sac761evp89us9fc.apps.googleusercontent.com'), ('oauth2.google.client_secret', 'F314slSii105CcTDYd3aGja6'), ('oauth2.microsoft.client_id', 'a7b32b29-24da-403e-a41c-1b7e4b6fc0c6'), ('oauth2.microsoft.client_secret', '8Pp8Q~O-cyHmoJuBV5F0VNgkjwgRdep1z4ThPded');