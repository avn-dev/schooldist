CREATE TABLE IF NOT EXISTS `kolumbus_tuition_reports_to_schools` (
  `report_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `background_pdf` int(11) NOT NULL,
  PRIMARY KEY (`report_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kolumbus_upload_to_schools` (
  `upload_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`upload_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
