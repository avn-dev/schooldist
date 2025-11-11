INSERT INTO
	`tc_frontend_combinations_items` (`combination_id`, `item`, `item_value`)
SELECT
	`id` `combination_id`,
	'payment_provider' `item`,
	'paypal' `item_value`
FROM
	`tc_frontend_combinations` `tc_fc` LEFT JOIN
	`tc_frontend_combinations_items` `tc_fci` ON
		`tc_fci`.`combination_id` = `tc_fc`.`id` AND
		`tc_fci`.`item` = 'payment_provider'
WHERE
	`tc_fc`.`usage` = 'payment_form' AND
	`tc_fc`.`active` = 1 AND
	`tc_fci`.`item_value` IS NULL