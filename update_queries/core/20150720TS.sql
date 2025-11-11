ALTER TABLE `tc_communication_messages_flags_relations` DROP PRIMARY KEY;

ALTER TABLE `tc_communication_messages_flags_relations` ADD PRIMARY KEY ( `flag_id` , `relation` , `relation_id` );
