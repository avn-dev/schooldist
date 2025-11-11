CREATE TABLE IF NOT EXISTS `tc_frontend_templates_fields_i18n` (
  `field_id` mediumint(8) unsigned NOT NULL,
  `language_iso` char(2) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`language_iso`,`field_id`),
  KEY `fk_ta_courses_i18n_t_languages1` (`language_iso`),
  KEY `fk_ta_accommodations_categories_i18n_ta_accommodations1` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
