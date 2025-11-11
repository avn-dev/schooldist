DROP TABLE `ts_activities_allocations`;

ALTER TABLE `ts_activities_blocks`
	CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
    ADD `school_id` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Experimentell' AFTER `active`,
	CHANGE `editor_id` `editor_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    CHANGE `creator_id` `creator_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
	CHANGE `weeks` `weeks` TINYINT UNSIGNED NOT NULL,
	CHANGE `start_date` `start_week` DATE NOT NULL,
	CHANGE `repeat_weeks` `repeat_weeks` TINYINT(1) NOT NULL
;

ALTER TABLE `ts_activities_blocks_days`
    CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
    CHANGE `editor_id` `editor_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    CHANGE `creator_id` `creator_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    CHANGE `activity_block_id` `block_id` INT UNSIGNED NOT NULL,
    CHANGE `weekday` `day` TINYINT(1) UNSIGNED NOT NULL
;

ALTER TABLE `ts_activities_blocks_travellers`
	DROP valid_until,
	CHANGE `editor_id` `editor_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
	CHANGE `creator_id` `creator_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
	ADD INDEX(`week`)
;
