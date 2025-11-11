CREATE TABLE IF NOT EXISTS `gui2_indexes_stacks` (
  `index_name` varchar(255) NOT NULL,
  `index_id` int(11) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  PRIMARY KEY (`index_name`,`index_id`),
  KEY `index_id` (`index_id`),
  KEY `priority` (`priority`),
  KEY `index_name` (`index_name`,`index_id`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE `gui2_index_registry`;

CREATE TABLE IF NOT EXISTS `gui2_index_registry` (
  `index_name` varchar(255) NOT NULL,
  `index_id` int(11) NOT NULL,
  `object_class` varchar(255) NOT NULL,
  `object_id` int(11) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  PRIMARY KEY (`index_name`,`index_id`,`object_class`,`object_id`),
  KEY `object_class` (`object_class`,`object_id`),
  KEY `index_name` (`index_name`,`index_id`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;