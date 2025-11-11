CREATE TABLE IF NOT EXISTS `tc_documents_einvoice_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `creator_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `type` char(20) NOT NULL,
  `file` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`,`creator_id`),
  KEY `document_id` (`document_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;