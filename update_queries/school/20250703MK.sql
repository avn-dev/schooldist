ALTER TABLE `ts_inquires_payments_unallocated` CHANGE `status` `status` ENUM('initialized', 'registered','paid','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'paid';
