<?php

namespace Communication\Enums;

use Tc\Service\LanguageAbstract;

enum MessageStatus: string
{
	case NULL = 'null';
	case SENDING = 'sending';
	case SENT = 'sent';
	case RECEIVED = 'received';
	case SEEN = 'seen';
	case FAILED = 'failed';

	public function getLabelText(LanguageAbstract $l10n): string
	{
		return match ($this) {
			self::SENDING => $l10n->translate('Wird gesendet'),
			self::SENT => $l10n->translate('Gesendet'),
			self::RECEIVED => $l10n->translate('Empfangen'),
			self::SEEN => $l10n->translate('Gesehen'),
			self::FAILED => $l10n->translate('Fehlgeschlagen'),
		};
	}

	public function getIcon(): string
	{
		return match ($this) {
			self::SENDING => 'fa fa-spinner fa-spin',
			self::SENT => 'fas fa-check',
			self::RECEIVED => 'fas fa-check-double',
			self::SEEN => 'fas fa-check-circle',
			self::FAILED => 'fas fa-exclamation-circle',
		};
	}

}