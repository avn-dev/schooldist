CREATE TABLE `ts_tuition_attendance_tracking_sessions` (`teacher_id` int(11) NOT NULL,`block_id` int(11) NOT NULL,`day` tinyint(4) NOT NULL,`code` char(32) NOT NULL,`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `ts_tuition_attendance_tracking_sessions` ADD PRIMARY KEY (`code`);
