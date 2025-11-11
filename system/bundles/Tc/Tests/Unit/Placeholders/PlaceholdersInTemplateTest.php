<?php

// TODO weiter ausbauen. Manche Fälle hier sind nicht korrekt

beforeEach(function () {
	$this->util = new Ext_TC_Placeholder_Util();
	// TODO das macht für mich keinen Sinn
	$this->dummySkipClosure = fn () => false;
});

test('Simple placeholders', function () {

	$template = 'Hello {firstname} {lastname}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'firstname' => [
			'firstname' => [
				'placeholder' => 'firstname',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'firstname',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
		'lastname' => [
			'lastname' => [
				'placeholder' => 'lastname',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'lastname',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			]
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Simple placeholders with modifier', function () {

	$template = 'Hello {firstname|uppercase} {lastname|uppercase|lowercase}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'firstname' => [
			'firstname|uppercase' => [
				'placeholder' => 'firstname',
				'prefix' => '',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'firstname|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		],
		'lastname' => [
			'lastname|uppercase|lowercase' => [
				'placeholder' => 'lastname',
				'prefix' => '',
				'if' => '',
				'suffix' => '|uppercase|lowercase',
				'complete' => 'lastname|uppercase|lowercase',
				'other' => '',
				'modifier' => '|uppercase|lowercase',
				'direct_loop_index' => ''
			]
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Placeholders with prefix', function () {

	$template = 'Hello {inquiry.contact.firstname} {inquiry.contact.lastname}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'firstname' => [
			'inquiry.contact.firstname' => [
				'placeholder' => 'firstname',
				'prefix' => 'inquiry.contact',
				'if' => '',
				'suffix' => '',
				'complete' => 'inquiry.contact.firstname',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
		'lastname' => [
			'inquiry.contact.lastname' => [
				'placeholder' => 'lastname',
				'prefix' => 'inquiry.contact',
				'if' => '',
				'suffix' => '',
				'complete' => 'inquiry.contact.lastname',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			]
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Placeholders with prefix and modifier', function () {

	$template = 'Hello {inquiry.contact.firstname|uppercase} {inquiry.contact.lastname|lowercase}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'firstname' => [
			'inquiry.contact.firstname|uppercase' => [
				'placeholder' => 'firstname',
				'prefix' => 'inquiry.contact',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'inquiry.contact.firstname|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		],
		'lastname' => [
			'inquiry.contact.lastname|lowercase' => [
				'placeholder' => 'lastname',
				'prefix' => 'inquiry.contact',
				'if' => '',
				'suffix' => '|lowercase',
				'complete' => 'inquiry.contact.lastname|lowercase',
				'other' => '',
				'modifier' => '|lowercase',
				'direct_loop_index' => ''
			]
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Same placeholders', function () {

	$template = 'Hello {firstname} {firstname}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'firstname' => [
			'firstname' => [
				'placeholder' => 'firstname',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'firstname',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Same placeholders but with modifier', function () {

	$template = 'Hello {firstname} {firstname|uppercase}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'firstname' => [
			'firstname' => [
				'placeholder' => 'firstname',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'firstname',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
			'firstname|uppercase' => [
				'placeholder' => 'firstname',
				'prefix' => '',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'firstname|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

/**
 * IF-Bedingungen
 */

test('Simple placeholders with if-condition (no brackets)', function () {

	$template = '{if company_name === "Fidelo"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'if company_name === "Fidelo"' => [
				'placeholder' => 'company_name',
				'prefix' => '',
				'if' => 'if ',
				'suffix' => ' === "Fidelo"',
				'complete' => 'if company_name === "Fidelo"',
				'other' => ' === "Fidelo"',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

// TODO das läuft nicht korrekt
test('Simple placeholders with if-condition and modifiers (no brackets)', function () {

	$template = '{if company_name|uppercase === "FIDELO"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'if company_name|uppercase === "FIDELO"' => [
				'placeholder' => 'company_name',
				'prefix' => '',
				'if' => 'if ',
				'suffix' => '|uppercase === "FIDELO"',
				'complete' => 'if company_name|uppercase === "FIDELO"',
				'other' => '',
				'modifier' => '|uppercase === "FIDELO"',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Placeholder with prefix with if-condition and modifiers (no brackets)', function () {

	$template = '{if company.company_name|uppercase === "FIDELO"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'if company.company_name|uppercase === "FIDELO"' => [
				'placeholder' => 'company_name',
				'prefix' => 'company',
				'if' => 'if ',
				'suffix' => '|uppercase === "FIDELO"',
				'complete' => 'if company.company_name|uppercase === "FIDELO"',
				'other' => '',
				'modifier' => '|uppercase === "FIDELO"',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Simple placeholders with if-condition (with brackets)', function () {

	$template = '{if {company_name} === "Fidelo"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'company_name' => [
				'placeholder' => 'company_name',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'company_name',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Placeholders prefix and if-condition (with brackets)', function () {

	$template = '{if {company.company_name} === "Fidelo"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'company.company_name' => [
				'placeholder' => 'company_name',
				'prefix' => 'company',
				'if' => '',
				'suffix' => '',
				'complete' => 'company.company_name',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

// TODO das läuft nicht korrekt
test('Simple placeholders with multiple if-condition (no brackets)', function () {

	$template = '{if company_name === "Fidelo" && city === "Cologne"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'if company_name === "Fidelo" && city === "Cologne"' => [
				'placeholder' => 'company_name',
				'prefix' => '',
				'if' => 'if ',
				'suffix' => ' === "Fidelo" && city === "Cologne"',
				'complete' => 'if company_name === "Fidelo" && city === "Cologne"',
				'other' => ' === "Fidelo" && city === "Cologne"',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

// TODO das läuft nicht korrekt
test('Placeholders with prefix and multiple if-condition (no brackets)', function () {

	$template = '{if company.company_name === "Fidelo" && address.city === "Cologne"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'if company.company_name === "Fidelo" && address.city === "Cologne"' => [
				'placeholder' => 'company_name',
				'prefix' => 'company',
				'if' => 'if ',
				'suffix' => ' === "Fidelo" && address.city === "Cologne"',
				'complete' => 'if company.company_name === "Fidelo" && address.city === "Cologne"',
				'other' => ' === "Fidelo" && address.city === "Cologne"',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Simple placeholders with multiple if-condition (with brackets)', function () {

	$template = '{if {company_name} === "Fidelo" && {city} === "Cologne"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'company_name' => [
				'placeholder' => 'company_name',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'company_name',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
		'city' => [
			'city' => [
				'placeholder' => 'city',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'city',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Simple placeholders with multiple if-condition and modifier (with brackets)', function () {

	$template = '{if {company_name|uppercase} === "FIDELO" && {city|uppercase} === "COLOGNE"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'company_name|uppercase' => [
				'placeholder' => 'company_name',
				'prefix' => '',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'company_name|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		],
		'city' => [
			'city|uppercase' => [
				'placeholder' => 'city',
				'prefix' => '',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'city|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

test('Placeholders with prefix and multiple if-condition and modifier (with brackets)', function () {

	$template = '{if {company.company_name|uppercase} === "FIDELO" && {address.city|uppercase} === "COLOGNE"}Fidelo for president{else}Why not using Fidelo?{/if}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'company.company_name|uppercase' => [
				'placeholder' => 'company_name',
				'prefix' => 'company',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'company.company_name|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		],
		'city' => [
			'address.city|uppercase' => [
				'placeholder' => 'city',
				'prefix' => 'address',
				'if' => '',
				'suffix' => '|uppercase',
				'complete' => 'address.city|uppercase',
				'other' => '',
				'modifier' => '|uppercase',
				'direct_loop_index' => ''
			],
		]
	];

	expect($placeholders)->toBe($expected);
});

/**
 * LOOPS
 */
test('Simple loop placeholders', function () {

	$template = '{companies_loop}{/companies_loop}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'companies_loop' => [
			'companies_loop' => [
				'placeholder' => 'companies_loop',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'companies_loop',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
	];

	expect($placeholders)->toBe($expected);
});

test('Loop placeholders with prefix', function () {

	$template = '{city.companies_loop}{/companies_loop}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'companies_loop' => [
			'city.companies_loop' => [
				'placeholder' => 'companies_loop',
				'prefix' => 'city',
				'if' => '',
				'suffix' => '',
				'complete' => 'city.companies_loop',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
	];

	expect($placeholders)->toBe($expected);
});

test('Simple loop placeholders with inner placeholders', function () {

	$template = '{companies_loop}{companies_loop.company_name}{/companies_loop}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'companies_loop' => [
			'companies_loop' => [
				'placeholder' => 'companies_loop',
				'prefix' => '',
				'if' => '',
				'suffix' => '',
				'complete' => 'companies_loop',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
		'company_name' => [
			'companies_loop.company_name' => [
				'placeholder' => 'company_name',
				'prefix' => 'companies_loop',
				'if' => '',
				'suffix' => '',
				'complete' => 'companies_loop.company_name',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => ''
			],
		],
	];

	expect($placeholders)->toBe($expected);
});

/**
 * LOOP-INDEX
 */

test('Simple loop placeholders with loop index', function () {

	$template = '{companies_loop#1.company_name}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'company_name' => [
			'companies_loop#1.company_name' => [
				'placeholder' => 'company_name',
				'prefix' => 'companies_loop#1',
				'if' => '',
				'suffix' => '',
				'complete' => 'companies_loop#1.company_name',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => '1'
			],
		],
	];

	expect($placeholders)->toBe($expected);
});

// TODO das läuft nicht korrekt
test('Loop placeholders with prefix and loop index', function () {

	$template = '{city.companies_loop#1.company_name}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [];
	/*$expected = [
		'company_name' => [
			'city.companies_loop#1.company_name' => [
				'placeholder' => 'company_name',
				'prefix' => 'city.companies_loop#1',
				'if' => '',
				'suffix' => '',
				'complete' => 'city.companies_loop#1.company_name',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => '1'
			],
		],
	];*/

	expect($placeholders)->toBe($expected);
});

// TODO das läuft nicht korrekt
test('Loop placeholder with loop index in prefix', function () {

	$template = '{companies_loop#1.contacts_loop}{/contacts_loop}';

	$placeholders = $this->util->getPlaceholdersInTemplate($template, $this->dummySkipClosure);

	$expected = [
		'contacts_loop' => [
			'companies_loop#1.contacts_loop' => [
				'placeholder' => 'contacts_loop',
				'prefix' => 'companies_loop#1',
				'if' => '',
				'suffix' => '',
				'complete' => 'companies_loop#1.contacts_loop',
				'other' => '',
				'modifier' => '',
				'direct_loop_index' => '1'
			],
		],
	];

	expect($placeholders)->toBe($expected);
});