<?php

namespace Communication\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static for(Collection $messages): static
 * @method static run(): array
 * @method static send(\Ext_TC_Communication_Message $message): array
 */
class MailSpool extends Facade {

	protected static function getFacadeAccessor() {
		// Laravel arbeitet bei Facades immer mit dem Singleton-Pattern, egal wie der Service in den Container gebunden wurde
		self::clearResolvedInstance(\Tc\Service\MailSpool::class);
		return \Tc\Service\MailSpool::class;
	}

}
