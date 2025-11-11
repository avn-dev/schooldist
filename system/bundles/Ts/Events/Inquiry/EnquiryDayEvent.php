<?php

namespace Ts\Events\Inquiry;

use Carbon\Carbon;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\Events\Settings;

class EnquiryDayEvent extends InquiryDayEvent
{
	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Anfragenevent');
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

		return sprintf('%s: %s', EventManager::l10n()->translate('Anfragenevent'), implode(' ', $data));
	}

	protected static function getSelectOptionsEvents()
	{
		return [
			'created' => EventManager::l10n()->translate('Erstellungsdatum'),
			'follow_up_date' => EventManager::l10n()->translate('Nachhaken')
		];
	}

	public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void
	{
		$search = new \ElasticaAdapter\Facade\Elastica(\ElasticaAdapter\Facade\Elastica::buildIndexName('ts_inquiry'));

		$query = new \Elastica\Query\Term();
		$query->setTerm('type', \Ext_TS_Inquiry::TYPE_ENQUIRY_STRING);
		$search->addQuery($query);

		// Nur Anfragen beachten die noch nicht umgewandelt wurden
		$query = new \Elastica\Query\Term();
		$query->setTerm('type', \Ext_TS_Inquiry::TYPE_BOOKING_STRING);
		$search->addMustNotQuery($query);

		self::dispatchBySearch($search, $process, $time, $school);
	}

}
