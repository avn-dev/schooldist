<?php

namespace Ts\Events\Inquiry;

use Carbon\Carbon;
use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Gui2\Data\EventManagementData;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\EventManager\TestableEvent;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\Manageable\WithManageableRecipientType;
use Ts\Events\Conditions\UploadCondition;
use Ts\Events\Inquiry\Conditions\InvoiceAddresse;
use Ts\Events\Inquiry\Conditions\SalesPersonExists;
use Ts\Events\Inquiry\Services\Conditions\TransferType;
use Ts\Interfaces\Events;
use Tc\Facades\EventManager;
use Ts\Traits\Events\Manageable\WithManageableExecutionTime;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Services\Conditions\TransferDataMissing;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Testable\WithInquiryTesting;

class InquiryDayEvent implements ManageableEvent, Events\InquiryEvent, TestableEvent, HasIcon
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableExecutionTime,
		WithManageableSystemUserCommunication,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableRecipientType,
		WithManageableIndividualCommunication,
		WithInquiryTesting,
		WithIcon;

	public function __construct(private readonly \Ext_TS_Inquiry $inquiry) {}

	public function getIcon(): ?string
	{
		return 'fas fa-user-clock';
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Buchungsevent');
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$inquiry = ($event) ? $event->getInquiry() : new \Ext_TS_Inquiry();
		return $inquiry->getPlaceholderObject();
	}

	public static function toReadable(Settings $settings): string
	{
		$days = (int)$settings->getSetting('days', 0);
		$daysTranslation = ($days === 1)
			? EventManager::l10n()->translate('Tag')
			: EventManager::l10n()->translate('Tage');

		if ($days === 0) {
			$data = [static::getSelectOptionsEvents()[$settings->getSetting('event_type')] ?? ''];
		} else {
			$data = [
				$days,
				$daysTranslation,
				static::getSelectOptionsDirection()[$settings->getSetting('direction')] ?? '',
				static::getSelectOptionsEvents()[$settings->getSetting('event_type')] ?? ''
			];
		}

		return
			EventManager::l10n()->translate('Buchungsevent').': '.
			implode(' ', $data);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, EventManagementData $dataClass): void
	{
		self::addExecutionTimeRow($dialog, $tab, $dataClass);
		self::addExecutionWeekendRow($dialog, $tab, $dataClass);

		$l10n = EventManager::l10n();

		$tab->setElement($dialog->createMultiRow($dataClass->t('Bedingung'), [
			'db_alias' => 'tc_emc',
			'items' => [
				[
					'db_column' => 'meta_days',
					'input' => 'input',
					'class' => 'txt w50',
					'style' => 'width: 50px;', // w50 funktioniert nicht
					'text_after' => $l10n->translate('Tage').'&nbsp;'
				],
				[
					'db_column' => 'meta_direction',
					'input' => 'select',
					'class' => 'txt auto_width',
					'select_options' => static::getSelectOptionsDirection(),
					'text_after' => '&nbsp;'
				],
				[
					'db_column' => 'meta_event_type',
					'input' => 'select',
					'class' => 'txt auto_width',
					'select_options' => static::getSelectOptionsEvents()
				]
			]
		]));

		self::addRecipientTypeRow($dialog, $tab);
	}

	protected static function getSelectOptionsEvents()
	{
		$l10n = EventManager::l10n();

		return [
			'created' => $l10n->translate('Erstellungsdatum'),
			'service_start' => $l10n->translate('erster Leistungstag'),
			'service_end' => $l10n->translate('letzter Leistungstag'),
			'course_start' => $l10n->translate('erster Kurstag'),
			'course_end' => $l10n->translate('letzter Kurstag'),
			'accommodation_end' => $l10n->translate('letzter Unterkunftstag'),
			'accommodation_start' => $l10n->translate('erster Unterkunftstag'),
			'transfer_date' => $l10n->translate('Transferdatum'),
			'visum_end' => $l10n->translate('letzter Visumtag'),
			'insurance_start' => $l10n->translate('erster Versicherungstag'),
			'insurance_end' => $l10n->translate('letzter Versicherungstag'),
			'reminder_date' => $l10n->translate('Fälligkeitsdatum'),
			'activity_start' => $l10n->translate('erster Aktivitätstag'),
			'activity_end' => $l10n->translate('letzter Aktivitätstag'),
			'every_accommodation_start'=> $l10n->translate('jeder Unterkunftsstart'),
			'every_insurance_start'=> $l10n->translate('jeder Versicherungsstart'),
			'every_course_start'=> $l10n->translate('jeder Kurssstart'),
			'feedback_invitation_sent'=> $l10n->translate('Feedbackeinladung versendet'),
			//'follow_up_date' => $l10n->translate('Nachhaken')
		];
	}

	public static function getSelectOptionsDirection(): array
	{
		$l10n = EventManager::l10n();

		return [
			'before' => $l10n->translate('vor'),
			'after' => $l10n->translate('nach'),
		];
	}

	protected static function getEventTypeIndexField($sField)
	{
		switch($sField) {
			case 'created':
				return 'created_original';
			case 'service_start';
				return 'all_start_original';
			case 'service_end':
				return 'all_end_original';
			case 'course_start':
				return 'first_course_start_original';
			case 'course_end':
				return 'course_last_end_date_original';
			case 'accommodation_end':
				return 'accommodation_last_end_original';
			case 'accommodation_start':
				return 'accommodation_first_start_original';
			case 'transfer_date':
				return 'transfer_dates';
			case 'visum_end':
				return 'visum_until_original';
			case 'insurance_start':
				return 'insurance_first_start_date';
			case 'insurance_end':
				return 'insurance_last_end_date_original';
			case 'follow_up_date':
				return 'follow_up_original';
			case 'activity_start':
				return 'activity_first_start_date';
			case 'activity_end':
				return 'activity_last_end_date';
			case 'every_accommodation_start':
				return 'accommodation_from_original';
			case 'every_insurance_start':
				return 'insurance_from_original';
			case 'every_course_start':
				return 'course_from_original';
			case 'feedback_invitation_sent':
				return 'feedback_invitation_sent';
		}

		throw new \InvalidArgumentException('Invalid field: '.$sField);
	}

	public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void
	{
		$oSearch = new \ElasticaAdapter\Facade\Elastica(\ElasticaAdapter\Facade\Elastica::buildIndexName('ts_inquiry'));

		$oQuery = new \Elastica\Query\Term();
		$oQuery->setTerm('type', \Ext_TS_Inquiry::TYPE_BOOKING_STRING);
		$oSearch->addQuery($oQuery);

		self::dispatchBySearch($oSearch, $process, $time, $school);
	}

	protected static function dispatchBySearch(\ElasticaAdapter\Facade\Elastica $oSearch, Process $process, Carbon $dateTime, \Ext_Thebing_School $school): void
	{
		try {
			$days = $process->getSetting('days', 0);
			$direction = $process->getSetting('direction', 'before');
			$eventType = $process->getSetting('event_type', 'created');
			$recipientType = $process->getSetting('recipient_type', 'all_customers');

			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('school_id', $school->getId());
			$oSearch->addQuery($oQuery);

			// Tage vor/nach
			// Wenn der Wert auf 0 steht, wird demnach der heutige Tag ($dDate) als Filter benutzt
			$dDateDays = $dateTime->clone();
			if($days > 0) {
				if($direction === 'after') {
					$dDateDays->subDays($days);
				} else {
					$dDateDays->addDays($days);
				}
			}

			$aCriteria = [
				'gte' => $dDateDays->format('Y-m-d'),
				'lte' => $dDateDays->format('Y-m-d'),
				//'format' => 'yyyy-MM-dd' // ab Elasticsearch 1.5 erst
			];

			if($eventType === 'reminder_date') {
				$oQuery = \Ext_TS_Inquiry_Index_Gui2_Data::getPaymentDueQuery($aCriteria);
			} else {
				$oQuery = new \Elastica\Query\Range(static::getEventTypeIndexField($eventType), $aCriteria);
			}

		$oSearch->addQuery($oQuery);

			// Empfänger-Typ
			if ($recipientType != 'all_customers') {
				switch ($recipientType) {
					case 'current_customers':
						$oQuery = new \Elastica\Query\Range('service_from', ['lte' => $dateTime->format('Y-m-d')]);
						$oSearch->addQuery($oQuery);

						// >=
						$greaterOrLessThan = 'gte';
						$field = 'service_until';
						break;
					case 'current_and_future_customers':
						$greaterOrLessThan = 'gte';
						$field = 'service_until';
						break;
					case 'current_and_old_customers':
						$greaterOrLessThan = 'lte';
						$field = 'service_from';
						break;
				}

			$oQuery = new \Elastica\Query\Range($field, [$greaterOrLessThan => $dateTime->format('Y-m-d')]);
			$oSearch->addQuery($oQuery);
			}

			$oSearch->setFields(['_id']);
			$oSearch->setLimit(1000);
			$aResult = $oSearch->search();

		} catch (\Throwable $e) {
			// TODO: Bei der Log-Nachricht die Queries ausgeben zu dem Search-Objekt ($oSearch->getQueries())
			// (Funktioniert nicht wirklich wenn man einfach nur die Methode als Nachricht nimmt)
			EventManager::logger('InquiryDayEvent')->error('Exception', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			throw $e;
		}

		if (empty($aResult['hits'])) {
			EventManager::logger('InquiryDayEvent')->info('No inquiries found', ['school_id' => $school->id, 'process' => $process->getIdentifier()]);
		}

		foreach ($aResult['hits'] as $aInquiry) {
			EventManager::logger('InquiryDayEvent')->info('Execute inquiry event', ['school_id' => $school->id, 'inquiry_id' => $aInquiry['_id'], 'process' => $process->getIdentifier()]);
			$oInquiry = \Ext_TS_Inquiry::getInstance($aInquiry['_id']);
			static::dispatch($oInquiry);
		}
	}

	public static function manageEventListenersAndConditions(): void
	{
		self::includeCustomerMarketingConditions();
		self::includeCustomerAgeLimitation();
		self::includeInquiryTypeConditions();
		self::includeInquiryCourseConditions();

		self::addManageableCondition(InvoiceAddresse::class);
		self::addManageableCondition(TransferType::class);
		self::addManageableCondition(TransferDataMissing::class);
		self::addManageableCondition(Conditions\AccommodationCategoryCustomer::class);
		self::addManageableCondition(UploadCondition::class);
		self::addManageableCondition(SalesPersonExists::class);
//		self::addManageableCondition(Conditions\CourseAttendance::class); # wo alles?
	}

}
