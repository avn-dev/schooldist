<?php

namespace Ts\Hook;

class ExternalAppCategories extends \Core\Service\Hook\AbstractHook
{
	const ACCOMMODATION = 'accommodation';
	const CRM = 'crm';
	const PAYMENT_PROVIDER = 'payment_provider';
	const TUITION = 'tuition';

	/**
	 * @see \TcExternalApps\Service\AppService::getCategories()
	 */
	public function run(array &$aCategories)
	{
		$aCategories[self::ACCOMMODATION] = \L10N::t('Unterkunft');
		$aCategories[self::CRM] = \L10N::t('CRM');
		$aCategories[self::PAYMENT_PROVIDER] = \L10N::t('Zahlungsanbieter');
		$aCategories[self::TUITION] = \L10N::t('Klassenplanung');
	}
}

