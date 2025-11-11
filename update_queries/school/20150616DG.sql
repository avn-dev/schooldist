DROP TABLE IF EXISTS `ts_inquiries_flex_uploads`;

CREATE TABLE IF NOT EXISTS `ts_inquiries_flex_uploads` (
  `inquiry_id` int(10) unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET ascii NOT NULL,
  `type_id` smallint(5) unsigned NOT NULL,
  `released_student_login` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ts_inquiries_flex_uploads`
  ADD PRIMARY KEY (`inquiry_id`,`type`,`type_id`);