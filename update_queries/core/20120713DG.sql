ALTER TABLE `tc_communication_messages_flags_relations` DROP `id`;

ALTER TABLE `tc_communication_messages_flags_relations` ADD PRIMARY KEY ( `flag_id` , `relation` , `relation_id` );

DROP TABLE `tc_communication_messages_flags_relations_relations`; 