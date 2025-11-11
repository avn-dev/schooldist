<?php

use Core\Service\Hook\AbstractHook;
use TsAccounting\Command;
use TsAccounting\Handler\ParallelProcessing;
use TsAccounting\Hook;
use TsAccounting\Service\eInvoice;

return [

	'parallel_processing_mapping' => [
		'entity-release' => [
			'class' => ParallelProcessing\EntityRelease::class
		],
		'register-invoice' => [
			'class' => ParallelProcessing\RegisterInvoice::class
		]
	],

	'hooks' => [
		'ts_document_release_hook' => [
			'class' => eInvoice\Italy\Hook\DocumentReleaseGui2ListHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_document_release_dialog_html' => [
			'class' => eInvoice\Italy\Hook\DocumentReleaseGui2DialogHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_document_release_dialog_save' => [
			'class' => eInvoice\Italy\Hook\DocumentReleaseGui2DialogSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'tc_cronjobs_hourly_execute' => [
			'class' => Hook\CronjobHourlyHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_pdf_creation' => [
			'class' => Hook\eInvoice\PdfCreation::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_register_invoice' => [
			'class' => Hook\eInvoice\RegisterInvoice::class,
			'interface' => AbstractHook::BACKEND
		],
	],

	'communication' => [
		'applications' => [
			'invoice' => \TsAccounting\Communication\Application\Invoices::class,
			'agencies_payments' => \TsAccounting\Communication\Application\AgencyPayments::class,
			'client_payment' => \Ts\Communication\Application\Booking::class,
			'accounting_teacher' => \TsAccounting\Communication\Application\TeacherPayments::class,
			'accounting_accommodation' => \TsAccounting\Communication\Application\AccommodationPayments::class,
			'accounting_transfer' => \TsAccounting\Communication\Application\TransferPayments::class,
		]
	],

	'external_apps' => [
		eInvoice\Italy\ExternalApp\XmlIt::APP_NAME => [
			'class' => eInvoice\Italy\ExternalApp\XmlIt::class
		],
		eInvoice\Spain\ExternalApp\Verifactu::APP_NAME => [
			'class' => eInvoice\Spain\ExternalApp\Verifactu::class
		]
	],

    'commands' => [
        Command\EntityRelease::class,
        Command\BookingStackExport::class,
    ]
	
];

