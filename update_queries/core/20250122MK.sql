ALTER TABLE tc_number_ranges_allocations_objects DROP FOREIGN KEY tc_number_ranges_allocations_objects_ibfk_1;
ALTER TABLE tc_number_ranges_allocations_sets_applications DROP FOREIGN KEY tc_number_ranges_allocations_sets_applications_ibfk_1;
ALTER TABLE `tc_frontend_combinations_items` DROP PRIMARY KEY, ADD INDEX `combination_id` (`combination_id`) USING BTREE;
