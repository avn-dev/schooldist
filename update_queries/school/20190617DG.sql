UPDATE `tc_gui2_filtersets` SET `name` = 'Bookings' WHERE `name` = 'Invoice section' AND `application` = 'ts_inquiry';

UPDATE `tc_gui2_filtersets` SET `name` = 'Default - Bookings' WHERE `name` = 'Default - Invoice list' AND `application` = 'ts_inquiry';

UPDATE
	`tc_gui2_filtersets_bars_elements_basedon`
SET
	`base_on` = 'paymentterms_next_date_original'
WHERE
	`base_on` = 'amount_finalpay_due_original';
