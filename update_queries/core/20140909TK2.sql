ALTER TABLE `tc_communication_emailaccounts` ADD `imap_sent_mail_folder` VARCHAR( 256 ) NOT NULL ;  

ALTER TABLE `tc_communication_emailaccounts` ADD `imap_append_sent_mail` TINYINT( 1 ) NOT NULL DEFAULT '0' ; 
