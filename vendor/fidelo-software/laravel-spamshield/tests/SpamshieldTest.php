<?php

beforeEach(function () {
	$this->form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);

	$this->strategy = Mockery::mock(\FideloSoftware\Spam\Strategies\AbstractStrategy::class);
});

test('Banned false', function() {

	$maxAttempts = \FideloSoftware\Spam\SpamShield::MAX_ATTEMPTS;

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->once()->andReturn($maxAttempts - 1);

	$request = Mockery::mock(\Illuminate\Http\Request::class);
	$request->shouldReceive('ip')->once()->andReturn('testing');
	$request->shouldReceive('exists')->once()->andReturn(false);

	$banned = \FideloSoftware\Spam\SpamShield::isBanned($store, $request);

	$this->assertFalse($banned);
});

test('Banned true', function() {

	$maxAttempts = \FideloSoftware\Spam\SpamShield::MAX_ATTEMPTS;

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->once()->andReturn($maxAttempts + 1);

	$request = Mockery::mock(\Illuminate\Http\Request::class);
	$request->shouldReceive('ip')->once()->andReturn('testing');
	$request->shouldReceive('exists')->once()->andReturn(false);

	$banned = \FideloSoftware\Spam\SpamShield::isBanned($store, $request);

	$this->assertTrue($banned);
});

test('Banned exception', function() {

	$maxAttempts = \FideloSoftware\Spam\SpamShield::MAX_ATTEMPTS;

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->atLeast()->once()->andReturn($maxAttempts + 1);
	$store->shouldReceive('put')->atLeast()->once()->andReturn(null);

	$request = Mockery::mock(\Illuminate\Http\Request::class);
	$request->shouldReceive('ip')->atLeast()->once()->andReturn('testing');
	$request->shouldReceive('exists')->atLeast()->once()->andReturn(false);

	$spamShield = new \FideloSoftware\Spam\SpamShield([], $store);
	$spamShield->detect($this->form, $request);

})->throws(\FideloSoftware\Spam\Exceptions\BannedException::class);

test('Detect no spam', function() {

	expect(function () {
		$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);

		$request = Mockery::mock(\Illuminate\Http\Request::class);

		$strategy = $this->strategy->expect(detect: fn () => false, cleanUp: fn () => null);

		$spamShield = new \FideloSoftware\Spam\SpamShield([$strategy], $store);
		$spamShield->detect($this->form, $request);
	})->not->toThrow(\FideloSoftware\Spam\Exceptions\SpamDetectionException::class);

});

test('Detect spam', function() {

	$store = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
	$store->shouldReceive('get')->atLeast()->once()->andReturn(0);
	$store->shouldReceive('put')->atLeast()->once()->andReturn(null);

	$request = Mockery::mock(\Illuminate\Http\Request::class);
	$request->shouldReceive('ip')->atLeast()->once()->andReturn('testing');
	$request->shouldReceive('exists')->atLeast()->once()->andReturn(false);

	$this->strategy->shouldReceive('detect')->atLeast()->once()->andReturn(true);
	$this->strategy->shouldReceive('cleanUp')->atLeast()->once()->andReturn(null);

	$spamShield = new \FideloSoftware\Spam\SpamShield([$this->strategy], $store);
	$spamShield->detect($this->form, $request);

})->throws(\FideloSoftware\Spam\Exceptions\SpamDetectionException::class);;