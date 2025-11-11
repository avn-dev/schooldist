<?php

namespace Tc\Events\Conditions;

use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;

class SpoolEmailsAvailable implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Entwürfe sind vorhanden');
	}

	public function passes(): bool
	{
		$first = \Ext_TC_Communication_Message::query()
			->where('sent', 0)
			->first();

		return $first !== null;
	}
}