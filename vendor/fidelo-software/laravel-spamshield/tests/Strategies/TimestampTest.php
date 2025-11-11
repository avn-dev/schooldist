<?php

beforeEach(function () {
	$uid = \Illuminate\Support\Str::random(10);
	$this->form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);
	$this->form->shouldReceive('getUid')->atLeast()->once()->andReturn($uid);
	$this->request = new \Illuminate\Http\Request();
});

test('Timestamp - Detect true', function () {
	$begin = time();

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->atLeast()->once()->andReturn($begin);

	$strategy = new \FideloSoftware\Spam\Strategies\TimestampStrategy($store, 2);

	$detected = $strategy->detect($this->form, $this->request);

	// too fast - not exceeding the minimum seconds
	$this->assertTrue($detected);
});

test('Timestamp - Detect false', function () {
	$seconds = 1;
	$begin = time();

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->atLeast()->once()->andReturn($begin);

	$strategy = new \FideloSoftware\Spam\Strategies\TimestampStrategy($store, $seconds);

	sleep($seconds);

	$detected = $strategy->detect($this->form, $this->request);

	// Everything is fine - request was made after the given minimum seconds
	$this->assertFalse($detected);
});

test('Timestamp - Detect false (no store value)', function () {

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->atLeast()->once()->andReturn(null);

	$strategy = new \FideloSoftware\Spam\Strategies\TimestampStrategy($store, 2);
	$detected = $strategy->detect($this->form, $this->request);

	// No value in store - strategy should not detect spam
	$this->assertFalse($detected);
});