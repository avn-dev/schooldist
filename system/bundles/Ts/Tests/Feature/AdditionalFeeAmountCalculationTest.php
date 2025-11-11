<?php

uses(\Core\Tests\DatabaseConnection::class);

beforeEach(function () {
	// Leider zwingend notwendig für _oDB und Tabellendefinition
	$this->setupConnection();
});

test('Additional fee amount calculation', function () {

	$inquiry = Ext_TS_Inquiry::getInstance();
	$inquiry->currency_id = 1;
	$journey = Ext_TS_Inquiry_Journey::getInstance();
	$journey->school_id = 1;
	$inquiry->setJoinedObjectChild('journeys', $journey);

	$from = new \DateTime('2023-06-04');
	$until = new \DateTime('2023-07-01');
		
	$additionalCost = new Ext_Thebing_School_Additionalcost;
	$additionalCost->type = Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION;
	$additionalCost->credit_provider = Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ALL;

	$accommodationCategoryId = 1;
	
	$amountService = mock('Ext_Thebing_Inquiry_Amount', [$inquiry])->makePartial();
	$amountService->setTimeData($from, $until);
	
	$amountService->shouldReceive('calculateAdditionalCost')->andReturn(40);
	
	// Einmalig: 40 * 1
	$additionalCost->calculate = Ext_Thebing_School_Additionalcost::CALCULATION_ONCE;
	$amount = $amountService->calculateAdditionalAccommodationCost($additionalCost, $from, $until, false, $accommodationCategoryId);
	
	$this->assertEquals(40, $amount);
	
	// Nächtlich: 40 * 27
	$additionalCost->calculate = Ext_Thebing_School_Additionalcost::CALCULATION_PER_NIGHT;
	$amount = $amountService->calculateAdditionalAccommodationCost($additionalCost, $from, $until, false, $accommodationCategoryId);
	
	$this->assertEquals(1080, $amount);
	
	// Wöchentlich: 40 * 4
	$additionalCost->calculate = Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK;
	$amount = $amountService->calculateAdditionalAccommodationCost($additionalCost, $from, $until, false, $accommodationCategoryId);
	
	$this->assertEquals(160, $amount);
	
});