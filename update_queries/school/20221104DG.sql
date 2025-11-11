ALTER TABLE `ts_inquiries`
    CHANGE `service_from` `service_from` DATE NULL DEFAULT NULL,
    CHANGE `service_until` `service_until` DATE NULL DEFAULT NULL;