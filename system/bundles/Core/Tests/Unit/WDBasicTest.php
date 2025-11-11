<?php

uses(\Core\Tests\DatabaseConnection::class);

beforeEach(function () {
	// Leider zwingend notwendig für _oDB und Tabellendefinition
	$this->setupConnection();
});

test('WDBasic - set repository', function () {

	$mock = Mockery::mock(\WDBasic_Repository::class);

	\Core\Entity\System\Elements::setRepository($mock);

	expect(\Core\Entity\System\Elements::getRepository())->toBe($mock);
	// Sichergehen dass das Repository wirklich nur für das eine Model gesetzt wurde
	expect(\Core\Entity\System\Log::getRepository())->not()->toBe($mock);

	// Zurücksetzen
	\Core\Entity\System\Elements::setRepository(null);

	expect(\Core\Entity\System\Elements::getRepository())->not()->toBe($mock);

});