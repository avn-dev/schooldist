<?php

uses(\Core\Tests\DatabaseConnection::class);

beforeEach(function () {
	// Leider zwingend notwendig für _oDB und Tabellendefinition
	$this->setupConnection();
});

/**
 * Teaching times
 */

test('Teacher - test teaching time with no tuition blocks', function () {

	$blockRepository = Mockery::mock(\WDBasic_Repository::class)
		->shouldReceive('getTuitionBlocks')->andReturn([])
		->getMock();

	\Ext_Thebing_School_Tuition_Block::setRepository($blockRepository);

	$teacher = new \Ext_Thebing_Teacher();

	expect($teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::MINUTES))->toBe(0.0);
	expect($teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::HOURS))->toBe(0.0);
	expect($teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::DAYS))->toBe(0.0);
});

test('Teacher - test teaching time units', function () {

	$blockRepository = Mockery::mock(\WDBasic_Repository::class)
		->shouldReceive('getTuitionBlocks')->andReturn([
			['from' => '00:00:00', 'until' =>'03:00:00'],
			['from' => '08:00:00', 'until' =>'12:00:00'],
			['from' => '12:30:00', 'until' =>'16:00:00'],
			['from' => '16:00:00', 'until' =>'17:00:00'],
		])
		->getMock();

	\Ext_Thebing_School_Tuition_Block::setRepository($blockRepository);

	$teacher = new \Ext_Thebing_Teacher();
	$teacher->schools = [1];

	expect($teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::MINUTES))->toBe(690.0);
	expect($teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::HOURS))->toBe(11.5);
	expect($teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::DAYS))->toBe(0.48);
});

test('Teacher - test teaching times invalid unit exception', function () {

	$blockRepository = Mockery::mock(\WDBasic_Repository::class)
		->shouldReceive('getTuitionBlocks')->andReturn([
			['from' => '00:00:00', 'until' =>'03:00:00']
		])
		->getMock();

	\Ext_Thebing_School_Tuition_Block::setRepository($blockRepository);

	$teacher = new \Ext_Thebing_Teacher();
	$teacher->schools = [1];
	$teacher->getTeachingTime(\Carbon\Carbon::now(), Carbon\Carbon::now(), \TsTuition\Enums\TimeUnit::SECONDS);

})->throws(InvalidArgumentException::class);

test('Teacher - test teaching times placeholders', function () {

	$blockRepository = Mockery::mock(\WDBasic_Repository::class)
		->shouldReceive('getTuitionBlocks')->andReturn([
			['from' => '00:00:00', 'until' =>'03:00:00'],
			['from' => '08:00:00', 'until' =>'12:00:00'],
			['from' => '12:30:00', 'until' =>'16:00:00'],
			['from' => '16:00:00', 'until' =>'17:00:00'],
		])
		->getMock();

	\Ext_Thebing_School_Tuition_Block::setRepository($blockRepository);

	$teacher = new \Ext_Thebing_Teacher();
	$teacher->schools = [1];

	$school = new Ext_Thebing_School();
	$school->number_format = 3;

	$placeholder = new \Ext_Thebing_Teacher_Placeholder($teacher);
	$placeholder->setSchool($school);
	$replace = $placeholder->replace('{teacher_teaching_minutes}|{teacher_teaching_hours}|{teacher_teaching_days}');

	expect($replace)->toBe('690|11,5|0,48');
});
