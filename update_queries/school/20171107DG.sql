UPDATE
	`tc_gui2_filtersets_bars_elements_basedon`
SET
	`base_on` = 'email_original'
WHERE
	`base_on` = 'email' AND
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
			`tc_gf`.`application` = 'ts_enquiry'
	)