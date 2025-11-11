ALTER TABLE `ts_inquires_payments_unallocated` CHANGE `status` `status` ENUM('registered','paid','pending') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'paid';
