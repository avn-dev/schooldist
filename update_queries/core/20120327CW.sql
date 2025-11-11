CREATE TABLE IF NOT EXISTS `tc_frontend_templates_fields_to_extrafields` (
  `template_field_id` int(11) NOT NULL,
  `extrafield_id` int(11) NOT NULL,
  PRIMARY KEY (`template_field_id`,`extrafield_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_frontend_templates_fields_to_gui_design_elements` (
  `template_field_id` int(11) NOT NULL,
  `design_element_id` int(11) NOT NULL,
  PRIMARY KEY (`template_field_id`,`design_element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tc_frontend_templates_fields_to_mappings` (
  `template_field_id` int(11) NOT NULL,
  `mapping_alias` varchar(50) NOT NULL,
  `mapping_column` varchar(50) NOT NULL,
  PRIMARY KEY (`template_field_id`,`mapping_alias`,`mapping_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
