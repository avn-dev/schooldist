ALTER TABLE `ts_payment_conditions` CHANGE `surcharge_calculation` `surcharge_calculation` ENUM('one_time','per_installment','per_month') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
