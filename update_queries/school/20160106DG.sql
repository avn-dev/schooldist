
CREATE TABLE `kolumbus_pdf_templates_services` (
  `template_id` mediumint(8) UNSIGNED NOT NULL,
  `service_id` mediumint(8) UNSIGNED NOT NULL,
  `service_type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `kolumbus_pdf_templates_services`
  ADD PRIMARY KEY (`template_id`,`service_id`,`service_type`);
