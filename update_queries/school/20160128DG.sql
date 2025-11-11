DELETE FROM
	`gui2_index_registry`
WHERE
	`object_class` = 'Ext_Thebing_School' OR
	`object_class` = 'Ext_Thebing_Client';

UPDATE
	`tc_gui2_filtersets_bars_elements_basedon`
SET
	`base_on` = 'amount_finalpay_due_original'
WHERE
	`base_on` = 'amount_finalpay_due' AND
	`element_id` IN (
		SELECT
			`tc_gfbe`.`id`
		FROM
			`tc_gui2_filtersets_bars_elements` `tc_gfbe` INNER JOIN
			`tc_gui2_filtersets_bars` `tc_gfb` ON
				`tc_gfb`.`id` = `tc_gfbe`.`bar_id` INNER JOIN
			`tc_gui2_filtersets` `tc_gf` ON
				`tc_gf`.`id` = `tc_gfb`.`set_id`
		WHERE
			`tc_gf`.`application` IN(
				'ts_inquiry',
				'ts_students_simple',
				'ts_students_arrival',
				'ts_students_departure',
				'ts_students_visum',
				'ts_students_agency_payments',
				'ts_students_payments'
			)
	);