<?php

uses(\Core\Tests\DatabaseConnection::class);

beforeEach(function () {
	// Leider zwingend notwendig für _oDB und Tabellendefinition
	$this->setupConnection();
});

test('Document overview service amounts for course + others', function () {

	$version = new Ext_Thebing_Inquiry_Document_Version();
	$version->tax = 1;

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'additional_course';
	$item->amount = 30.0;
	$item->amount_net = 30.0;
	$item->amount_discount = 0;
	$item->description = 'Learning material';
	$item->onPdf = 0;
	$version->setJoinedObjectChild('items', $item);

	// Special mit Discount testen
	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'special';
	$item->parent_id = PHP_INT_MAX;
	$item->amount = -150;
	$item->amount_net = -150;
	$item->amount_discount = 10.0;
	$item->description = 'Special: Registration fee';
	$version->setJoinedObjectChild('items', $item);

	// TODO Das mit der ID (und parent_id im Special) ist auch nicht schön, aber nicht anders möglich
	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->id = PHP_INT_MAX;
	$item->type = 'additional_course';
	$item->amount = 150.0;
	$item->amount_net = 150.0;
	$item->amount_provision = 0;
	$item->amount_discount = 10.0;
	$item->description = 'Registration fee';
	$version->setJoinedObjectChild('items', $item);

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'course';
	$item->amount = 640.0;
	$item->amount_net = 576.0;
	$item->amount_provision = 64.0;
	$item->amount_discount = 10.0;
	$item->description = '4 weeks Group classes (20 lessons/week) (20.02.2023 - 17.03.2023) (English)';
	$version->setJoinedObjectChild('items', $item);

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'additional_accommodation';
	$item->amount = 55.0;
	$item->amount_net = 55.0;
	$item->amount_discount = 50.0;
	$item->description = 'Placement fee';
	$version->setJoinedObjectChild('items', $item);

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'accommodation';
	$item->amount = 600.0;
	$item->amount_net = 600.0;
	$item->amount_discount = 0;
	$item->description = '4 weeks Homestay (SR/BF) (19.02.2023 - 18.03.2023)';
	$version->setJoinedObjectChild('items', $item);

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'transfer';
	$item->amount = 140.0;
	$item->amount_net = 140.0;
	$item->amount_discount = 0;
	$item->description = 'Arrival and Departure (19.02.2023, 18.03.2023)';
	$version->setJoinedObjectChild('items', $item);

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'insurance';
	$item->amount = 250.0;
	$item->amount_net = 250.0;
	$item->amount_discount = 0;
	$item->description = 'Health Insurance ((19.02.2023 - 18.03.2023))';
	$version->setJoinedObjectChild('items', $item);

	$document = mock(Ext_Thebing_Inquiry_Document::class);
	$document->shouldReceive('getLastVersion')->andReturn($version);
	$document->shouldReceive('getServiceAmountForOverview')->passthru();
	$document->shouldReceive('getCurrencyId')->andReturn(1);

	$this->assertEqualsWithDelta(640.0, $document->getServiceAmountForOverview('course', 'gross'), .01);
	$this->assertEqualsWithDelta(57.6, $document->getServiceAmountForOverview('course', 'commission'), .01);
	$this->assertEqualsWithDelta(64.0, $document->getServiceAmountForOverview('course', 'discount'), .01);
	$this->assertEqualsWithDelta(518.4, $document->getServiceAmountForOverview('course', 'open'), .01);

	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('additional_course', 'gross'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('additional_course', 'commission'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('additional_course', 'discount'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('additional_course', 'open'), .01);

	$this->assertEqualsWithDelta(140.0, $document->getServiceAmountForOverview('transfer', 'gross'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('transfer', 'commission'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('transfer', 'discount'), .01);
	$this->assertEqualsWithDelta(140.0, $document->getServiceAmountForOverview('transfer', 'open'), .01);

	$this->assertEqualsWithDelta(250.0, $document->getServiceAmountForOverview('insurance', 'gross'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('insurance', 'commission'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('insurance', 'discount'), .01);
	$this->assertEqualsWithDelta(250.0, $document->getServiceAmountForOverview('insurance', 'open'), .01);

});

test('Document overview service amounts for accommodation', function () {

	$version = new Ext_Thebing_Inquiry_Document_Version();
	$version->tax = 1;

	$item = new Ext_Thebing_Inquiry_Document_Version_Item();
	$item->type = 'extra_nights';
	$item->amount = 55.0;
	$item->amount_net = 55.0;
	$item->amount_discount = 15;
	$item->description = '1 additional night (HS / SR / BF: 18.03.2023 - 19.03.2023)';
	$version->setJoinedObjectChild('items', $item);

	$document = mock(Ext_Thebing_Inquiry_Document::class);
	$document->shouldReceive('getLastVersion')->andReturn($version);
	$document->shouldReceive('getServiceAmountForOverview')->passthru();
	$document->shouldReceive('getCurrencyId')->andReturn(1);

	$this->assertEqualsWithDelta(55.0, $document->getServiceAmountForOverview('accommodation', 'gross'), .01);
	$this->assertEqualsWithDelta(0.0, $document->getServiceAmountForOverview('accommodation', 'commission'), .01);
	$this->assertEqualsWithDelta(8.25, $document->getServiceAmountForOverview('accommodation', 'discount'), .01);
	$this->assertEqualsWithDelta(46.75, $document->getServiceAmountForOverview('accommodation', 'open'), .01);

});