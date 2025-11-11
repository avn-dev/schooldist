<?php

// Kurs endet am 30.09.2022, wird aber auf den 02.10.2022 korrigiert für korrektes Splitting
// Die Beträge dürfen dann auch nicht einfach im nächsten Monat verschwinden
test('Course amount period split over two months, modified course period', function () {
	$items = [
		[
			'label' => 'I-2022047',
			'document_type' => 'brutto',
			'item_tax_type' => '1',
			'item_id' => '31540',
			'item_type' => 'course',
			'item_from' => '2022-09-26',
			'item_until' => '2022-09-30',
			'item_amount' => '160.00000',
			'item_amount_net' => '160.00000',
			'item_amount_discount' => '0.00000',
			'item_amount_commission' => '0.00000',
			'item_tax' => '0.000',
			'item_index_special_amount_gross' => NULL,
			'item_index_special_amount_net' => NULL,
			'item_index_special_amount_gross_vat' => NULL,
			'item_index_special_amount_net_vat' => NULL,
			'item_additional_info' => '{"item_key":"6HENE5Y4WUP9RRQB","tooltip":["Preis pro Woche","line","01.01.2020 - 31.12.2030: ","W 1\\/1 160,00 * 1"],"tuition_course_id":4,"course_weeks":"1","course_units":"0.00","from":"2022-09-26","until":"2022-09-30","billing_type":"week","billing_units":"1","periods":{"2022-09-26":160}}',
			'course_startday' => '1',
			'item_costs_calculation' => NULL,
			'item_costs_booking_timepoint' => NULL,
			'head_grouping_id' => NULL,
			'head_grouping_label' => NULL,
			'grouping_id' => NULL,
			'grouping_label' => NULL,
		]
	];

	$column = new \TsStatistic\Generator\Tool\Columns\Revenue(null, null, 'net_course');
	$column->setBase(new \TsStatistic\Generator\Tool\Bases\BookingServicePeriod());

	$values = new \TsStatistic\Dto\FilterValues();
	$values['from'] = \Carbon\Carbon::parse('2022-09-01');
	$values['until'] = \Carbon\Carbon::parse('2022-09-30')->endOfDay();
	$result1 = $column->prepareResult($items, $values);
	$this->assertArrayHasKey('_', $result1);
//	$this->assertSame(114.285714, round($result1['_']['result'], 6));
	$this->assertSame(160.0, round($result1['_']['result'], 6)); // #18682

	$values = new \TsStatistic\Dto\FilterValues();
	$values['from'] = \Carbon\Carbon::parse('2022-10-01');
	$values['until'] = \Carbon\Carbon::parse('2022-10-31')->endOfDay();
	$result2 = $column->prepareResult($items, $values);
	$this->assertArrayHasKey('_', $result2);
//	$this->assertSame(45.714286, round($result2['_']['result'], 6));
	$this->assertSame(0.0, round($result2['_']['result'], 6)); // #18682

	$this->assertSame(160.0, $result1['_']['result'] + $result2['_']['result']);
});
