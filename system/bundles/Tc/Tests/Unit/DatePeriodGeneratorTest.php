<?php

test('Yearly dates for short range (<1 year)', function () {
	$from = \Carbon\Carbon::parse('2022-12-06');
	$until = \Carbon\Carbon::parse('2023-02-17');

	$periods = \Ext_TC_Util::generateDatePeriods($from, $until, 'year');

	$this->assertCount(2, $periods);

	$period2022 = $periods[0];
	$period2023 = $periods[1];

	$this->assertInstanceOf(\Carbon\CarbonPeriod::class, $period2022);

	$this->assertTrue($period2022->getStartDate()->equalTo(\Carbon\Carbon::parse('2022-12-06')->startOfDay()));
	$this->assertTrue($period2022->getEndDate()->equalTo(\Carbon\Carbon::parse('2022-12-31')->endOfDay()));

	$this->assertTrue($period2023->getStartDate()->equalTo(\Carbon\Carbon::parse('2023-01-01')->startOfDay()));
	$this->assertTrue($period2023->getEndDate()->equalTo(\Carbon\Carbon::parse('2023-02-17')->endOfDay()));

	$periods2 = \Ext_TC_Util::generateDatePeriods($from, $until, 'year', true);
	$this->assertCount(2, $periods2);

	$this->assertTrue($periods2[0]->getStartDate()->equalTo(\Carbon\Carbon::parse('2022-01-01')->startOfDay()));
	$this->assertTrue($periods2[1]->getEndDate()->equalTo(\Carbon\Carbon::parse('2023-12-31')->endOfDay()));
});
