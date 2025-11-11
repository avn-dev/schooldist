<?php

beforeEach(function () {
	$base = new \TsReporting\Generator\Bases\BookingServicePeriod();
	$this->column = new \TsReporting\Generator\Columns\Booking\Weeks();
	$this->column->setBase($base);
	$this->period = new \TsReporting\Generator\Filter\Period();
	$this->values = new \TsReporting\Generator\ValueHandler('en');
	$this->values->setFilter($this->period);
});

test('Course week period split for startday Monday', function () {
	$data = collect([
		[
			'course_from' => '2023-07-17',
			'course_until' => '2023-08-25',
			'course_weeks' => 6,
			'course_startday' => 1
		]
	]);

	// (15 Tage - 4 Tage WE) / 5 = 2.2
	$this->period->setValue(new \Carbon\CarbonPeriod('2023-07-01', '2023-07-31'));
	$result = $this->column->prepare($data, $this->values);
	$this->assertSame(2.2, round($result->first()['result'], 1));

	// (25 Tage − 6 Tage WE) / 5 = 3.8
	$this->period->setValue(new \Carbon\CarbonPeriod('2023-08-01', '2023-08-31'));
	$result = $this->column->prepare($data, $this->values);
	$this->assertSame(3.8, round($result->first()['result'], 1));

	// (40 Tage − 10 Tage WE) / 5 = 6
	$this->period->setValue(new \Carbon\CarbonPeriod('2023-07-01', '2023-08-31'));
	$result = $this->column->prepare($data, $this->values);
	$this->assertSame(6.0, round($result->first()['result'], 1));
});

test('Course week period split for startday Wednesday', function () {
	$data = collect([
		[
			'course_from' => '2023-07-19',
			'course_until' => '2023-07-28',
			'course_weeks' => 6,
			'course_startday' => 3
		]
	]);

	// (10 Tage - 2 Tage "WE") / 5 = 1.6
	$this->period->setValue(new \Carbon\CarbonPeriod('2023-07-01', '2023-08-31'));
	$result = $this->column->prepare($data, $this->values);
	$this->assertSame(1.6, round($result->first()['result'], 1));
});

test('Course week period split for one-day-course at weekend', function () {
	$data = collect([
		[
			'course_from' => '2023-03-18',
			'course_until' => '2023-03-18',
			'course_weeks' => 1,
			'course_startday' => 1
		]
	]);

	$this->period->setValue(new \Carbon\CarbonPeriod('2023-03-01', '2023-03-31'));
	$result = $this->column->prepare($data,  $this->values);
	$this->assertSame(0.2, round($result->first()['result'], 1));
});