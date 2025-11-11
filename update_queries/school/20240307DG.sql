ALTER TABLE `kolumbus_tuition_courses` ADD `automatic_renewal` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `frontend_min_bookable_days_ahead`;

ALTER TABLE `ts_inquiries_journeys_courses` ADD `automatic_renewal_origin` INT UNSIGNED NULL DEFAULT NULL AFTER `index_attendance_warning_latest_date`, ADD `automatic_renewal_cancellation` DATE NULL DEFAULT NULL AFTER `automatic_renewal_origin`;