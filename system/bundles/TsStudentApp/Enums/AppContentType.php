<?php

namespace TsStudentApp\Enums;

use Tc\Service\LanguageAbstract;

enum AppContentType: string
{
	case FAQ = 'faq';
	case INTRO = 'intro';
	case ANNOUNCEMENT = 'announcement';

	public function getLabelText(LanguageAbstract $l10n): string
	{
		return match($this) {
			self::FAQ => $l10n->translate('FAQ'),
			self::INTRO => $l10n->translate('Intro'),
			self::ANNOUNCEMENT => $l10n->translate('Ankündigung'),
		};
	}
}