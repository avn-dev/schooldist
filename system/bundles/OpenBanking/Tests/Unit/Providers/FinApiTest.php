<?php

use OpenBanking\Providers\finAPI;

beforeEach(function () {

	\Illuminate\Support\Facades\Http::fake();

	$this->logger = new \Psr\Log\NullLogger();
	$this->api = (new finAPI\DefaultApi('clientId', 'clientSecret'))
		->logger($this->logger);
});

test('FinAPI - Api', function () {
	$this->assertSame($this->api->getClientId(), 'clientId');
	$this->assertSame($this->api->getClientSecret(), 'clientSecret');
});

test('FinAPI - Api default', function () {
	// Nicht speichern!
	\System::s(finAPI\DefaultApi::CLIENT_ID_SETTING_KEY, 'clientId', false);
	\System::s(finAPI\DefaultApi::CLIENT_SECRET_SETTING_KEY, 'clientSecret', false);

	$api = finAPI\DefaultApi::default();

	$this->assertSame($api->getClientId(), 'clientId');
	$this->assertSame($api->getClientSecret(), 'clientSecret');
});

test('FinAPI - Sandbox', function () {
	// Standardmäßig nicht im Sandbox-Modus
	$this->assertFalse($this->api->isSandboxed());

	$this->api->sandboxed();
	$this->assertTrue($this->api->isSandboxed());

	$this->api->sandboxed(false);
	$this->assertFalse($this->api->isSandboxed());
});

test('FinAPI - Invalid client keys', function () {
	new finAPI\DefaultApi('', '');
})->throws(\RuntimeException::class);