ALTER TABLE `ts_inquiries` ADD `checkin` TIMESTAMP NULL DEFAULT NULL AFTER `numberrange_id`, ADD `checkout` TIMESTAMP NULL DEFAULT NULL AFTER `checkin`;

