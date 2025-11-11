<?php

namespace Communication\Exceptions;

use Core\Interfaces\HumanReadable;
use Tc\Service\LanguageAbstract;

class MessageTransportFailed extends \RuntimeException implements HumanReadable
{
	public function getHumanReadableText($l10n): string
	{
		if (\System::d('debugmode') != 2 && $l10n instanceof LanguageAbstract) {
			return sprintf(
				$l10n->translate('Die Nachricht konnte nicht übermittelt werden.'),
			);
		}

		return $this->message;
	}
}