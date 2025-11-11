ALTER TABLE `kolumbus_school_customerupload`
	DROP position,
	ADD `release_sl` TINYINT NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `ts_inquiries_flex_uploads` (
  `inquiry_id` int(10) unsigned NOT NULL,
  `upload_id` smallint(5) unsigned NOT NULL,
  `released_student_login` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ts_inquiries_flex_uploads`
  ADD PRIMARY KEY (`inquiry_id`,`upload_id`);