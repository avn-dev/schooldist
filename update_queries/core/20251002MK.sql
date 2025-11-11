ALTER TABLE `tc_enquiries` ADD INDEX `partner_id`(`partner_id`);
ALTER TABLE `tc_enquiries`  ADD INDEX idx_enquiries_active_office_created (active, office_id, created);
