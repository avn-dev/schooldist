<?php

use TsCompany\Entity\JobOpportunity\StudentAllocation as StudentAllocationEntity;
use Core\Service\Hook\AbstractHook;

return [

	'student_allocation' => [

		'status_order' => [
			StudentAllocationEntity::STATUS_REQUESTED,
			StudentAllocationEntity::STATUS_CONFIRMED,
			StudentAllocationEntity::STATUS_ALLOCATED
		]

	],

	'communication' => [
		'recipients' => [
			'company' => 'Firma',
			'agency' => 'Agentur',
			'sponsor' => 'Sponsor'
		],
		'applications' => [
			'marketing_agencies' => [\TsCompany\Communication\Application\Agency::class, ['access' => 'thebing_marketing_agency_crm']],
			'marketing_agencies_contact' => [\TsCompany\Communication\Application\AgencyContact::class, ['access' => 'thebing_marketing_agency_crm']],
			//'job_opportunity_allocation' => [\TsCompany\Communication\Application\JopOpportunityAllocation::class],
		],
		'flags' => [
			'job_opportunity_requested' => \TsCompany\Communication\Flag\JobOpportunityRequested::class,
			'job_opportunity_allocated' => \TsCompany\Communication\Flag\JobOpportunityAllocated::class
		]
	],

	'hooks' => [
		'ts_navigation_left' => [
			'class' => \TsCompany\Hook\NavigationHook::class,
			'interface' => AbstractHook::BACKEND
		]
	],

	'external_apps' => [
		\TsCompany\Handler\CoOpApp::APP_NAME => [
			'class' => \TsCompany\Handler\CoOpApp::class
		]
	],

];
