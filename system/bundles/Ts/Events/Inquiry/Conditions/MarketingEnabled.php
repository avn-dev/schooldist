<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class MarketingEnabled implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kunde hat automatische E-Mails aktiviert');
	}

	public function passes(InquiryEvent $event): bool
	{
		$customer = $event->getInquiry()->getCustomer();
		return $customer->isReceivingAutomaticEmails();
	}

}
