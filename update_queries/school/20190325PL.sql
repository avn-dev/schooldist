ALTER TABLE `ts_inquiries_journeys_activities` DROP INDEX `insurance_id`, ADD INDEX `activity_id` (`activity_id`) USING BTREE;

ALTER TABLE `ts_activities_blocks_to_travellers` ADD UNIQUE( `block_id`, `traveller_id`, `journey_activity_id`, `week`);