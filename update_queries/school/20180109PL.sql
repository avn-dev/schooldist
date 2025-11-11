ALTER TABLE `ts_agencies_activation_codes`
ADD `contact_id` int(10) UNSIGNED NOT NULL;

ALTER TABLE `ts_agencies_activation_codes`
ADD PRIMARY KEY (`agency_id`,`contact_id`) USING BTREE;