ALTER TABLE `ts_inquiries` ADD `frontend_log_id` INT UNSIGNED NULL DEFAULT NULL COMMENT '0 = reg_form = 1' AFTER `reg_form`;

ALTER TABLE `ts_enquiries` ADD `frontend_log_id` INT UNSIGNED NULL DEFAULT NULL COMMENT '0 = form_enquiry = 1' AFTER `form_enquiry`;
