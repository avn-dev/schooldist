ALTER TABLE `kolumbus_pdf_templates` CHANGE `user_id` `editor_id` INT(11) NOT NULL;
ALTER TABLE `kolumbus_email_layouts` CHANGE `user_id` `editor_id` INT(11) NOT NULL DEFAULT '0';
