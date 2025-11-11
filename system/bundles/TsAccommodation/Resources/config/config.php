<?php

use Core\Service\Hook\AbstractHook;

return [

	'event_manager' => [
		'listen' => [
			[\TsAccommodation\Events\ExpiringAccommodationRequirements::class, ['access' => ['ts_event_manager_accommodation', 'requirements']]],
			[\TsAccommodation\Events\MissingAccommodationRequirements::class, ['access' => ['ts_event_manager_accommodation', 'missing_requirements']]],
		],
	],

	'communication' => [
		'recipients' => [
			'accommodation_provider' => 'Unterkunftsanbieter'
		],
		'applications' => [
			'accommodation_resources_provider' => \TsAccommodation\Communication\Application\Accommodation::class,
			'contract_accommodation' => \TsAccommodation\Communication\Application\AccommodationContract::class,
			'accommodation_communication_customer_agency' => \TsAccommodation\Communication\Application\Communication\CustomerAgency::class,
			'accommodation_communication_provider' => \TsAccommodation\Communication\Application\Communication\Accommodation::class,
			'accommodation_communication_history_customer_confirmed' => \TsAccommodation\Communication\Application\Communication\History\CustomerConfirmed::class,
			'accommodation_communication_history_customer_canceled' => \TsAccommodation\Communication\Application\Communication\History\CustomerCanceled::class,
			'accommodation_communication_history_accommodation_confirmed' => \TsAccommodation\Communication\Application\Communication\History\AccommodationConfirmed::class,
			'accommodation_communication_history_accommodation_canceled' => \TsAccommodation\Communication\Application\Communication\History\AccommodationCanceled::class,
		],
		'flags' => [
			'accommodation_contract_sent' => \TsAccommodation\Communication\Flag\ContractSent::class,
			'accommodation_confirmed_customer' => \TsAccommodation\Communication\Flag\ConfirmCustomer::class,
			'accommodation_canceled_customer' => \TsAccommodation\Communication\Flag\CancelCustomer::class,
			'accommodation_confirmed_provider' => \TsAccommodation\Communication\Flag\ConfirmProvider::class,
			'accommodation_canceled_provider' => \TsAccommodation\Communication\Flag\CancelProvider::class,
			'accommodation_confirmed_transfer' => \TsAccommodation\Communication\Flag\ConfirmedTransfer::class,
			'accommodation_arrival_requested' => \TsAccommodation\Communication\Flag\RequestArrival::class,
		]
	],

	'parallel_processing_mapping' => [
		'requirements-status' => [
			'class' => TsAccommodation\Handler\RequirementStatus::class
		],
		'requirements-status-updater' => [
			'class' => TsAccommodation\Handler\RequirementStatusUpdater::class
		],
	],

	'hooks' => [
        'tc_cronjobs_hourly_execute' => [
			'class' => TsAccommodation\Hook\CronjobHourlyHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_inquiry_document_modify_items' => [
			'class' => \TsAccommodation\Hook\DocumentModifyItems::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_inquiry_document_build_items' => [
			'class' => \TsAccommodation\Hook\DocumentBuildItems::class
		],
		'ts_navigation_left' => [
			'class' => TsAccommodation\Hook\NavigationHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_document_position_detail_form' => [
			'class' => TsAccommodation\Hook\DocumentPositionDetailForm::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_document_position_detail_save' => [
			'class' => TsAccommodation\Hook\DocumentPositionDetailSave::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_accommodation_provider_save' => [
			'class' => \Ts\Hook\FormatContactDataHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_accommodation_member_save' => [
			'class' => \Ts\Hook\FormatContactDataHook::class,
			'interface' => AbstractHook::BACKEND
		]
	],

    'external_apps' => [
        TsAccommodation\Handler\ExternalApp\CityTax::APP_NAME => [
            'class' => TsAccommodation\Handler\ExternalApp\CityTax::class
        ]
    ],

	'webpack' => [
		['entry' => 'scss/matching.scss', 'output' => '&', 'config' => 'backend']
	],

	'tailwind' => [
		'content' => [
			'./system/bundles/TsAccommodation/Resources/views/availability/availability.tpl'
		]
	]

];