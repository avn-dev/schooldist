CREATE TABLE `tc_pdf_templates_settings` (
  `template_id` int(11) NOT NULL,
  `setting` varchar(100) NOT NULL,
  `value` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tc_pdf_templates_settings`
  ADD PRIMARY KEY (`template_id`,`setting`,`value`);