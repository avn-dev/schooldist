ALTER TABLE `tc_communication_automatictemplates`
	CHANGE `days` `days` SMALLINT NOT NULL,
	ADD `days_after_last_message` SMALLINT NOT NULL AFTER `event_type`;
