ALTER TABLE `kolumbus_tuition_templates` CHANGE `lessons` `lessons` DECIMAL(12,4) NOT NULL;
ALTER TABLE `kolumbus_teacher_salary` CHANGE `lessons` `lessons` DECIMAL(12,4) NOT NULL;
ALTER TABLE `kolumbus_tuition_blocks_substitute_teachers` CHANGE `lessons` `lessons` DECIMAL(12,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `ts_inquiries_journeys_courses_tuition_index` CHANGE `allocated_lessons` `allocated_lessons` DECIMAL(12,4) UNSIGNED NOT NULL;
ALTER TABLE `ts_teachers_payments` CHANGE `salary_lessons` `salary_lessons` DECIMAL(12,4) NOT NULL;
