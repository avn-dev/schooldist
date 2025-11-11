<?php

beforeEach(function () {
	$this->request = new \Illuminate\Http\Request();
});

test('Blacklist - Detect true (value used)', function () {

	$form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);
	$form->shouldReceive('getFieldValues')->atLeast()->once()->andReturn(['name' => 'John Doe']);

	$strategy = new \FideloSoftware\Spam\Strategies\ValueBlacklistStrategy(['name' => 'John Doe']);

	$detected = $strategy->detect($form, $this->request);

	$this->assertTrue($detected);
});

test('Blacklist - Detect true (value not used)', function () {

	$form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);
	$form->shouldReceive('getFieldValues')->atLeast()->once()->andReturn(['name' => 'John Smith']);

	$strategy = new \FideloSoftware\Spam\Strategies\ValueBlacklistStrategy(['name' => 'John Doe']);

	$detected = $strategy->detect($form, $this->request);

	$this->assertFalse($detected);
});

test('Blacklist - Detect true (one value used)', function () {

	$form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);
	$form->shouldReceive('getFieldValues')->atLeast()->once()->andReturn(['name' => 'John Doe']);

	$strategy = new \FideloSoftware\Spam\Strategies\ValueBlacklistStrategy(['name' => ['John Doe', 'John Smith']]);

	$detected = $strategy->detect($form, $this->request);

	$this->assertTrue($detected);
});

test('Blacklist - Detect false (used in other field)', function () {

	$form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);
	$form->shouldReceive('getFieldValues')->atLeast()->once()->andReturn(['comment' => 'John Smith']);

	$strategy = new \FideloSoftware\Spam\Strategies\ValueBlacklistStrategy(['name' => 'John Doe']);

	$detected = $strategy->detect($form, $this->request);

	$this->assertFalse($detected);
});
