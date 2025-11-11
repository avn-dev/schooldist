  
ALTER TABLE `ts_schools_to_accommodations_costs_categories`
	RENAME TO `ts_accommodation_costs_categories_schools`;

ALTER TABLE `ts_schools_to_accommodations_costs_weeks`
	RENAME TO `ts_accommodation_costweeks_schools`;

ALTER TABLE `ts_schools_to_accommodations_meals`
	RENAME TO `ts_accommodation_meals_schools`;

ALTER TABLE `ts_schools_to_accommodations_roomtypes`
	RENAME TO `ts_accommodation_roomtypes_schools`;

ALTER TABLE `ts_schools_to_accommodation_providers`
	RENAME TO `ts_accommodation_providers_schools`;

ALTER TABLE `ts_schools_to_accommodation_providers_payment_categories`
	RENAME TO `ts_accommodation_provider_payment_categories_schools`;

ALTER TABLE `ts_schools_to_courseunits`
	RENAME TO `ts_courseunits_schools`;

ALTER TABLE `ts_schools_to_weeks`
	RENAME TO `ts_weeks_schools`;

ALTER TABLE `ts_schools_to_accommodations_categories`
	RENAME TO `ts_accommodation_categories_schools`;