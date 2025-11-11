<?php

beforeEach(function () {
	$this->form = Mockery::mock(\FideloSoftware\Spam\Contracts\Form::class);
});

test('Honeypot - Html', function () {
	$fieldsCount = 3;

	$honeypot = new \FideloSoftware\Spam\Strategies\HoneypotStrategy($fieldsCount);
	$html = $honeypot->html($this->form);

	$matches = [];
	preg_match_all('/\<input(.*)name="(.*)"(.*)\/>/U', $html, $matches);

	expect(array_unique($matches[2]))->toHaveCount($fieldsCount);

	$this->assertEmpty(array_diff($matches[2], $honeypot->getAttributePool()));
});

test('Honeypot - Detect true', function () {
	$honeypot = new \FideloSoftware\Spam\Strategies\HoneypotStrategy(3);
	$attrPool = $honeypot->getAttributePool();
	$field = $attrPool[array_rand($attrPool)];

	$request = new \Illuminate\Http\Request();
	$request->query->add([$field => 'test', 'no_honey' => 'test']);

	$detected = $honeypot->detect($this->form, $request);

	$this->assertTrue($detected);
});

test('Honeypot - Detect false (empty)', function () {
	$honeypot = new \FideloSoftware\Spam\Strategies\HoneypotStrategy(3);
	$attrPool = $honeypot->getAttributePool();
	$field = $attrPool[array_rand($attrPool)];

	$request = new \Illuminate\Http\Request();
	$request->query->add([$field => '', 'no_honey' => 'test']);

	$detected = $honeypot->detect($this->form, $request);

	$this->assertFalse($detected);
});

test('Honeypot - Detect false (not existing)', function () {
	$honeypot = new \FideloSoftware\Spam\Strategies\HoneypotStrategy(3);

	$request = new \Illuminate\Http\Request();
	$request->query->add(['no_honey' => 'test']);

	$detected = $honeypot->detect($this->form, $request);

	$this->assertFalse($detected);
});

test('Honeypot - Overwrite attr pool', function () {
	$attr = ['attr1', 'attr2', 'attr3'];

	$honeypot = new \FideloSoftware\Spam\Strategies\HoneypotStrategy(3, $attr);

	$html = $honeypot->html($this->form);

	$matches = [];
	preg_match_all('/\<input(.*)name="(.*)"(.*)\/>/U', $html, $matches);

	$this->assertEmpty(array_diff($attr, $matches[2]));
});