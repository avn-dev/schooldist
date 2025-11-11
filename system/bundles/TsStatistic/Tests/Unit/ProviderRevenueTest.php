<?php

$periods = (function() {
	$periods = [];

	$values = new \TsStatistic\Dto\FilterValues();
	$values['from'] = \Carbon\Carbon::parse('2022-07-01');
	$values['until'] = \Carbon\Carbon::parse('2022-07-31')->endOfDay();
	$periods[] = $values;

	$values = new \TsStatistic\Dto\FilterValues();
	$values['from'] = \Carbon\Carbon::parse('2022-08-01');
	$values['until'] = \Carbon\Carbon::parse('2022-08-31')->endOfDay();
	$periods[] = $values;

	$values = new \TsStatistic\Dto\FilterValues();
	$values['from'] = \Carbon\Carbon::parse('2022-09-01');
	$values['until'] = \Carbon\Carbon::parse('2022-09-31')->endOfDay();
	$periods[] = $values;

	return $periods;
})();

test('Ticket #13584-19 Case 1', function () use ($periods) {
	$items = [
		[
			'label' => 'I-2022059',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31432',
			'item_type' => 'accommodation',
			'item_from' => '2022-07-31',
			'item_until' => '2022-08-06',
			'item_amount' => '250.00000',
			'item_amount_net' => '250.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => null,
			'item_index_special_amount_net' => null,
			'item_index_special_amount_gross_vat' => null,
			'item_index_special_amount_net_vat' => null,
			'item_additional_info' => '{"item_key":"QULRULUNJH3AVMVW","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"1","accommodation_category_id":"11","accommodation_roomtype_id":"1","accommodation_meal_id":"3","billing_type":"week","billing_units":"1","tooltip":["week","line","Price per week","line","01.01.2020 - 31.12.2030: "],"from":"2022-07-31","until":"2022-08-06"}',
			'course_startday' => '1',
			'item_costs_calculation' => null,
			'item_costs_booking_timepoint' => null,
			'allocation_periods' => '5,2022-07-30 00:00:00,2022-08-03 00:00:00',
			'head_grouping_id' => null,
			'head_grouping_label' => null,
			'grouping_id' => '5',
			'grouping_label' => 'City Residence',
		],
		[
			'label' => 'I-2022059',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31433',
			'item_type' => 'extra_nights',
			'item_from' => '2022-07-30',
			'item_until' => '2022-07-31',
			'item_amount' => '55.00000',
			'item_amount_net' => '55.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => null,
			'item_index_special_amount_net' => null,
			'item_index_special_amount_gross_vat' => null,
			'item_index_special_amount_net_vat' => null,
			'item_additional_info' => '{"item_key":"QULRULUNJH3AVMVW","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"1","accommodation_category_id":"11","accommodation_roomtype_id":"1","accommodation_meal_id":"3","billing_type":"week","billing_units":"1","nights":1,"nights_type":"nights_at_start","from":"2022-07-30","until":"2022-07-31"}',
			'course_startday' => '1',
			'item_costs_calculation' => null,
			'item_costs_booking_timepoint' => null,
			'allocation_periods' => '5,2022-07-30 00:00:00,2022-08-03 00:00:00',
			'head_grouping_id' => null,
			'head_grouping_label' => null,
			'grouping_id' => '5',
			'grouping_label' => 'City Residence',
		],
		[
			'label' => 'I-2022059',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31432',
			'item_type' => 'accommodation',
			'item_from' => '2022-07-31',
			'item_until' => '2022-08-06',
			'item_amount' => '250.00000',
			'item_amount_net' => '250.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => null,
			'item_index_special_amount_net' => null,
			'item_index_special_amount_gross_vat' => null,
			'item_index_special_amount_net_vat' => null,
			'item_additional_info' => '{"item_key":"QULRULUNJH3AVMVW","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"1","accommodation_category_id":"11","accommodation_roomtype_id":"1","accommodation_meal_id":"3","billing_type":"week","billing_units":"1","tooltip":["week","line","Price per week","line","01.01.2020 - 31.12.2030: "],"from":"2022-07-31","until":"2022-08-06"}',
			'course_startday' => '1',
			'item_costs_calculation' => null,
			'item_costs_booking_timepoint' => null,
			'allocation_periods' => '35,2022-08-03 00:00:00,2022-08-06 00:00:00',
			'head_grouping_id' => null,
			'head_grouping_label' => null,
			'grouping_id' => '35',
			'grouping_label' => 'Apartment A',
		],
		[
			'label' => 'I-2022059',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31433',
			'item_type' => 'extra_nights',
			'item_from' => '2022-07-30',
			'item_until' => '2022-07-31',
			'item_amount' => '55.00000',
			'item_amount_net' => '55.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => null,
			'item_index_special_amount_net' => null,
			'item_index_special_amount_gross_vat' => null,
			'item_index_special_amount_net_vat' => null,
			'item_additional_info' => '{"item_key":"QULRULUNJH3AVMVW","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"1","accommodation_category_id":"11","accommodation_roomtype_id":"1","accommodation_meal_id":"3","billing_type":"week","billing_units":"1","nights":1,"nights_type":"nights_at_start","from":"2022-07-30","until":"2022-07-31"}',
			'course_startday' => '1',
			'item_costs_calculation' => null,
			'item_costs_booking_timepoint' => null,
			'allocation_periods' => '35,2022-08-03 00:00:00,2022-08-06 00:00:00',
			'head_grouping_id' => null,
			'head_grouping_label' => null,
			'grouping_id' => '35',
			'grouping_label' => 'Apartment A',
		],
	];

	$column = new \TsStatistic\Generator\Tool\Columns\Accommodation\ProviderRevenue();
	$column->setBase(new \TsStatistic\Generator\Tool\Bases\BookingServicePeriod());

	$result = $column->prepareResult($items, $periods[0]);
	$this->assertArrayHasKey('5_', $result);
	$this->assertArrayHasKey('35_', $result);
	$this->assertSame(55.0, $result['5_']['result']);
	$this->assertSame(0.0, $result['35_']['result']);

	$result = $column->prepareResult($items, $periods[1]);
	$this->assertArrayHasKey('5_', $result);
	$this->assertArrayHasKey('35_', $result);
	$this->assertSame(125.0, $result['5_']['result']);
	$this->assertSame(125.0, $result['35_']['result']);

	$result = $column->prepareResult($items, $periods[2]);
	$this->assertArrayHasKey('5_', $result);
	$this->assertArrayHasKey('35_', $result);
	$this->assertSame(0.0, $result['5_']['result']);
	$this->assertSame(0.0, $result['35_']['result']);
});

test('Ticket #13584-19 Case 2', function () use ($periods) {
	$items = [
		[
			'label' => 'I-2022061',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31456',
			'item_type' => 'accommodation',
			'item_from' => '2022-07-10',
			'item_until' => '2022-07-30',
			'item_amount' => '600.00000',
			'item_amount_net' => '600.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => null,
			'item_index_special_amount_net' => null,
			'item_index_special_amount_gross_vat' => null,
			'item_index_special_amount_net_vat' => null,
			'item_additional_info' => '{"item_key":"5LNLPRBTLGE3SYH6","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"3","accommodation_category_id":"1","accommodation_roomtype_id":"1","accommodation_meal_id":"4","billing_type":"week","billing_units":"3","tooltip":["week","line","Price per week","line","01.01.2020 - 31.12.2030: "],"from":"2022-07-10","until":"2022-07-30"}',
			'course_startday' => '1',
			'item_costs_calculation' => null,
			'item_costs_booking_timepoint' => null,
			'allocation_periods' => '26,2022-07-10 00:00:00,2022-07-31 00:00:00;26,2022-07-31 00:00:00,2022-08-01 00:00:00',
			'head_grouping_id' => null,
			'head_grouping_label' => null,
			'grouping_id' => '26',
			'grouping_label' => 'Residence Maui',
		],
		[
			'label' => 'I-2022061',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31457',
			'item_type' => 'extra_nights',
			'item_from' => '2022-07-30',
			'item_until' => '2022-08-01',
			'item_amount' => '110.00000',
			'item_amount_net' => '110.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => null,
			'item_index_special_amount_net' => null,
			'item_index_special_amount_gross_vat' => null,
			'item_index_special_amount_net_vat' => null,
			'item_additional_info' => '{"item_key":"5LNLPRBTLGE3SYH6","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"3","accommodation_category_id":"1","accommodation_roomtype_id":"1","accommodation_meal_id":"4","billing_type":"week","billing_units":"3","nights":2,"nights_type":"nights_at_end","from":"2022-07-30","until":"2022-08-01"}',
			'course_startday' => '1',
			'item_costs_calculation' => null,
			'item_costs_booking_timepoint' => null,
			'allocation_periods' => '26,2022-07-10 00:00:00,2022-07-31 00:00:00;26,2022-07-31 00:00:00,2022-08-01 00:00:00',
			'head_grouping_id' => null,
			'head_grouping_label' => null,
			'grouping_id' => '26',
			'grouping_label' => 'Residence Maui',
		]
	];

	$column = new \TsStatistic\Generator\Tool\Columns\Accommodation\ProviderRevenue();
	$column->setBase(new \TsStatistic\Generator\Tool\Bases\BookingServicePeriod());

	$result = $column->prepareResult($items, $periods[0]);
	$this->assertArrayHasKey('26_', $result);
	$this->assertSame(655.0, $result['26_']['result']);

	$result = $column->prepareResult($items, $periods[1]);
	$this->assertArrayHasKey('26_', $result);
	$this->assertSame(55.0, $result['26_']['result']);

	$result = $column->prepareResult($items, $periods[2]);
	$this->assertArrayHasKey('26_', $result);
	$this->assertSame(0.0, $result['26_']['result']);
});

test('Ticket #13584-19 Case 3', function () use ($periods) {
	$items = [
		[
			'label' => 'I-2022062',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31462',
			'item_type' => 'accommodation',
			'item_from' => '2022-07-17',
			'item_until' => '2022-09-24',
			'item_amount' => '1300.00000',
			'item_amount_net' => '1300.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => NULL,
			'item_index_special_amount_net' => NULL,
			'item_index_special_amount_gross_vat' => NULL,
			'item_index_special_amount_net_vat' => NULL,
			'item_additional_info' => '{"item_key":"QUAPESSJXH9PJQX5","accommodation_class":"Ext_TS_Inquiry_Journey_Accommodation","accommodation_weeks":"10","accommodation_category_id":"3","accommodation_roomtype_id":"1","accommodation_meal_id":"1","billing_type":"week","billing_units":"10","tooltip":["week","line","Price per week","line","01.01.2020 - 31.12.2030: "],"from":"2022-07-17","until":"2022-09-24"}',
			'course_startday' => '1',
			'item_costs_calculation' => NULL,
			'item_costs_booking_timepoint' => NULL,
			'allocation_periods' => '8,2022-07-17 00:00:00,2022-09-24 00:00:00',
			'head_grouping_id' => NULL,
			'head_grouping_label' => NULL,
			'grouping_id' => '8',
			'grouping_label' => 'Family Baker',
		],
	];

	$column = new \TsStatistic\Generator\Tool\Columns\Accommodation\ProviderRevenue();
	$column->setBase(new \TsStatistic\Generator\Tool\Bases\BookingServicePeriod());

	// Erwartete Werte sind aus dem generierten Excel
	$result = $column->prepareResult($items, $periods[0]);
	$this->assertArrayHasKey('8_', $result);
	$this->assertSame(263.768116, round($result['8_']['result'], 6));

	$result = $column->prepareResult($items, $periods[1]);
	$this->assertArrayHasKey('8_', $result);
	$this->assertSame(584.057971, round($result['8_']['result'], 6));

	$result = $column->prepareResult($items, $periods[2]);
	$this->assertArrayHasKey('8_', $result);
	$this->assertSame(452.173913, round($result['8_']['result'], 6));
});