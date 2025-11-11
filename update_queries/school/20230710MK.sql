ALTER TABLE `kolumbus_course_startdates` ADD `type` ENUM('start_date','not_available') NULL DEFAULT NULL AFTER `user_id`;
UPDATE `kolumbus_course_startdates` SET changed = changed, type = 'start_date' WHERE type IS NULL
