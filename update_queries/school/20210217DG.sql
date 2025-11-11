INSERT INTO
	wdbasic_attributes (entity, entity_id, `key`, value)
SELECT
	'tc_frontend_combinations', combination_id, 'payment_providers', CONCAT('["', item_value, '"]')
FROM
	tc_frontend_combinations_items
WHERE
	item = 'payment_provider';

DELETE FROM tc_frontend_combinations_items WHERE item = 'payment_provider';
