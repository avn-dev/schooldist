<?php

return [
	// \Ext_Thebing_Util::updateLanguageFields()
	'language_fields' => [
		['customer_db_4', 'family_description', 'TEXT NOT NULL'],
		['customer_db_4', 'way_description', 'TEXT NOT NULL'],
		['customer_db_4', 'portal_family_description', 'TEXT NOT NULL'],
		['customer_db_4', 'portal_way_description', 'TEXT NOT NULL'],
		['kolumbus_accommodations_categories', 'name', 'VARCHAR(255) NOT NULL'],
		['kolumbus_accommodations_categories', 'short', 'VARCHAR(255) NOT NULL'],
		['kolumbus_accommodations_categories', 'description', 'TEXT NOT NULL'],
		['kolumbus_accommodations_meals', 'name', 'VARCHAR(255) NOT NULL'],
		['kolumbus_accommodations_meals', 'short', 'VARCHAR(255) NOT NULL'],
		['kolumbus_accommodations_roomtypes', 'name', 'VARCHAR(255) NOT NULL'],
		['kolumbus_accommodations_roomtypes', 'short', 'VARCHAR(255) NOT NULL'],
		['kolumbus_costs', 'name', 'VARCHAR(255) NOT NULL'],
		['kolumbus_costs', 'description', 'VARCHAR(255) NOT NULL'],
		['kolumbus_insurances', 'name', 'VARCHAR(255) NOT NULL'],
		['kolumbus_insurances', 'description', 'VARCHAR(255) NOT NULL'],
		['kolumbus_periods', 'title', 'VARCHAR(255) NOT NULL'],
		['kolumbus_tuition_courses', 'name', 'VARCHAR(255) NOT NULL'],
		['kolumbus_tuition_courses', 'description', 'TEXT NOT NULL'],
		['kolumbus_tuition_courses', 'frontend_name', 'VARCHAR(255) NOT NULL'],
		['ts_tuition_courselanguages', 'name', 'VARCHAR(255) NOT NULL'],
		['system_translations', '', 'TEXT NOT NULL'],
		['ts_tuition_levels', 'name', 'VARCHAR(255) NOT NULL'],
		['ts_tuition_coursecategories', 'name', 'VARCHAR(255) NOT NULL'],
		['ts_tuition_absence_reasons', 'name', 'VARCHAR(255) NOT NULL'],
	],
];
