<?php

use Core\Service\Hook\AbstractHook;

return [

	'providers' => [
		\Ts\Providers\AppServiceProvider::class,
		\Ts\Providers\EventServiceProvider::class
	],

	'external_apps' => [
		'finAPI' => [
			'class' => \OpenBanking\Providers\finAPI\ExternalApp::class
		],
		Ts\Handler\VisaLetterVerification\ExternalApp::APP_NAME => [
			'class' => Ts\Handler\VisaLetterVerification\ExternalApp::class
		],
		Ts\Handler\SwissQrBill\ExternalApp::APP_NAME => [
			'class' => Ts\Handler\SwissQrBill\ExternalApp::class
		]
	],

	'parallel_processing_mapping' => [
		'automatic-email' => [
			'class' => Ts\Handler\ParallelProcessing\AutomaticEmail::class
		],
		'document-generating' => [
			'class' => Ts\Handler\ParallelProcessing\DocumentGenerating::class
		],
		'tuition-index' => [
			'class' => Ts\Handler\ParallelProcessing\TuitionIndex::class
		],
		'partial-invoice' => [
			'class' => Ts\Handler\ParallelProcessing\PartialInvoice::class
		],
		'post-document-save' => [
			'class' => Ts\Handler\ParallelProcessing\PostDocumentSave::class
		],
		'post-payment-save' => [
			'class' => Ts\Handler\ParallelProcessing\PostPaymentSave::class
		],
		'update-transactions' => [
			'class' => Ts\Handler\ParallelProcessing\UpdateTransactions::class
		],
		'open-banking-transaction' => [
			'class' => Ts\Handler\ParallelProcessing\OpenBanking\SyncTransaction::class
		],
	],

	'hooks' => [
		'tc_external_apps_categories' => [
			'class' => Ts\Hook\ExternalAppCategories::class,
			'interface' => AbstractHook::BACKEND
		],
		'control_sidebar_buttons' => [
			'class' => Ts\Hook\ControlSidebarButtons::class,
			'interface' => AbstractHook::BACKEND
		],
		\Core\Command\Scheduler::HOOK_NAME => [
			'class' => Ts\Hook\SchedulerHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'tc_mailspool_send_app' => [
			'class' => Ts\Hook\MailSpoolAppSend::class,
			'interface' => AbstractHook::BACKEND
		],
		\Ext_TC_Flexibility::HOOK_VALIDATE => [
			'class' => Ts\Hook\FlexFieldValidate::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_inquiry_save' => [
			'class' => \Ts\Hook\FormatContactDataHook::class,
			'interface' => AbstractHook::BACKEND
		],
	],

	'communication' => [
		'recipients' => [
			'customer' => 'Kunden',
			'transfer_provider' => 'Transferanbieter',
			'insurance_provider' => 'Versicherungsanbieter',
			'contract_partner' => 'Vertragspartner'
		],
		'applications' => [
			'enquiry' => \Ts\Communication\Application\Enquiry::class,
			'booking' => \Ts\Communication\Application\Booking::class,
			'arrival_list' => \Ts\Communication\Application\Booking::class,
			'departure_list' => \Ts\Communication\Application\Booking::class,
			'feedback_list' => \Ts\Communication\Application\FeedbackList::class,
			'visum_list' => \Ts\Communication\Application\Booking::class,
			'simple_view' => \Ts\Communication\Application\Booking::class,
			'transfer_provider_request' => \Ts\Communication\Application\Transfer\ProviderRequest::class,
			'transfer_provider_confirm' => \Ts\Communication\Application\Transfer\ProviderConfirm::class,
			'transfer_customer_agency_information' => \Ts\Communication\Application\Transfer\CustomerAgency::class,
			'transfer_customer_accommodation_information' => \Ts\Communication\Application\Transfer\Accommodation::class,
			'insurance_customer' => \Ts\Communication\Application\Insurance\CustomerAgency::class,
			'insurance_provider' => \Ts\Communication\Application\Insurance\Provider::class,
		],
		'flags' => [
			'payment_reminder' => \Ts\Communication\Flag\PaymentReminder::class,
			'inquiry_feedback_invited' => \Ts\Communication\Flag\FeedbackInvited::class,
			'inquiry_placementtest_invited' => \Ts\Communication\Flag\PlacementTestInvited::class,
			'inquiry_placementtest_halloai' => \Ts\Communication\Flag\PlacementTestInvitedHalloAi::class,
			'transfer_provider_request' => \Ts\Communication\Flag\Transfer\ProviderRequest::class,
			'transfer_provider_confirm' => \Ts\Communication\Flag\Transfer\ProviderConfirm::class,
			'transfer_customer_agency_information' => \Ts\Communication\Flag\Transfer\CustomerAgencyInformation::class,
			'transfer_customer_accommodation_information' => \Ts\Communication\Flag\Transfer\AccommodationInformation::class,
			'insurance_customer_confirmed' => \Ts\Communication\Flag\Insurance\CustomerConfirmed::class,
			'insurance_provider_confirmed' => \Ts\Communication\Flag\Insurance\ProviderConfirmed::class,
		],
		'attachments' => [
			'invoices' => 'Rechnungen'
		]
	],

	'event_manager' => [
		'listen' => [
			\Ts\Events\ManageableScheduler::class,
			[\Ts\Events\Inquiry\InquiryDayEvent::class, ['access' => ['ts_event_manager_inquiries', 'scheduled']]],
			[\Ts\Events\Inquiry\EnquiryDayEvent::class, ['access' => ['ts_event_manager_inquiries', 'scheduled']]],
			[\Ts\Events\Inquiry\CheckIn::class, ['access' => ['ts_event_manager_inquiries', 'check_in']]],
			[\Ts\Events\Inquiry\CheckOut::class, ['access' => ['ts_event_manager_inquiries', 'check_out']]],
			[\Ts\Events\Inquiry\ConfirmEvent::class, ['access' => ['ts_event_manager_inquiries', 'confirm']]],
			[\Ts\Events\Inquiry\CreatedEvent::class, ['access' => ['ts_event_manager_inquiries', 'created']]],
			[\Ts\Events\Inquiry\UpdatedEvent::class, ['access' => ['ts_event_manager_inquiries', 'updated']]],
			[\Ts\Events\Inquiry\SavedEvent::class, ['access' => ['ts_event_manager_inquiries', 'saved']]],
			[\Ts\Events\Inquiry\CustomerBirthday::class, ['access' => ['ts_event_manager_inquiries', 'birthday']]],
			[\Ts\Events\Inquiry\NewPayment::class, ['access' => ['ts_event_manager_payments', 'new']]],
			[\Ts\Events\Inquiry\PaymentFailed::class, ['access' => ['ts_event_manager_payments', 'failed' ]]],
			[\Ts\Events\Inquiry\PaymentAllocationFailed::class, ['access' => ['ts_event_manager_payments', 'allocation_failed']]],
			[\Ts\Events\Inquiry\Services\CourseBooked::class, ['access' => ['ts_event_manager_inquiries', 'course_booked']]],
			[\Ts\Events\Inquiry\Services\NewJourneyTransfer::class, ['access' => ['ts_event_manager_inquiries', 'transfer_booked']]],
			[\Tc\Events\EventManagerFailed::class, ['access' => ['core_event_manager_system', 'event_failed']]],
			[\Tc\Events\NewFideloNews::class, ['access' => ['core_event_manager_system', 'fidelo_news']]],
			[\OpenBanking\Events\ProcessFailed::class, ['access' => 'app:'.\OpenBanking\Providers\finAPI\ExternalApp::APP_KEY]],
			\Tc\Events\EmailAccountError::class,
			[\Core\Events\NewSystemUpdates::class, [
				'access' => ['core_event_manager_system', 'update'],
				'title' => 'Systemupdates verfügbar',
				'listeners' => [
					\Tc\Listeners\SendSystemUserNotification::class,
					\Ts\Listeners\SendIndividualEmail::class,
					\Ts\Listeners\SendSchoolNotification::class
				]]
			]
		],
	],

	'commands' => [
		\Ts\Command\OpenBanking\IncominPayments::class
	],

	'factory_allocations' => [
		Ext_TC_Import::class => Ext_Thebing_Import::class
	],

	'log_messages' => [
		// TODO Sollen das Titel oder Apple-Titel (Sätze) sein?
		\Ext_TS_Inquiry::LOG_INQUIRY_CREATED => 'Buchung erstellt',
		\Ext_TS_Inquiry::LOG_INQUIRY_UPDATED => 'Buchung aktualisiert',
		\Ext_TS_Inquiry::LOG_CHECKIN => 'Schüler wurde als angereist markiert.', // ts/checkin
		\Ext_TS_Inquiry::LOG_CHECKOUT => 'Schüler wurde als abgereist markiert.', // ts/checkout
		\Ext_TS_Inquiry::LOG_CHECKIN_UNDO => 'Anreisemarkierung entfernt.', // ts/checkin
		\Ext_TS_Inquiry::LOG_CHECKOUT_UNDO => 'Abreisemarkierung entfernt.', // ts/checkout
		\Ext_TS_Inquiry::LOG_PARTIALINVOICES_REFRESH => 'Teilrechnungen neu generiert.',
		\Ext_TS_Inquiry::LOG_PARTIALINVOICES_MARK => 'Teilrechnung als "Generiert" markiert.',
		\Ext_TS_Inquiry::LOG_PARTIALINVOICES_UNMARK => 'Teilrechnung als "Noch nicht generiert" markiert.',
		\Ext_TS_Inquiry::LOG_PARTIALINVOICES_ADDED => 'Teilrechnung hinzugefügt.'
	],

	'webpack' => [
		['entry' => 'scss/gui2.scss', 'output' => '&', 'config' => 'backend']
	],

	'tailwind' => [
		'content' => [
			'./system/extensions/ts/inquiry/index/gui2/class.data.php',
			'./system/legacy/admin/extensions/thebing/gui2/studentlists.js',
			'./system/bundles/Ts/Resources/views/invoices/*'.
			'./system/bundles/Ts/Resources/views/groups/customer_upload.tpl'
		]
	]

];
