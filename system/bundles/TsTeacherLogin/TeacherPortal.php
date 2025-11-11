<?php

namespace TsTeacherLogin;

use Tc\Service\Language\Frontend;
use Tc\Service\LanguageAbstract;

class TeacherPortal
{
	public static function l10n(string $language = null): LanguageAbstract {
		return (new Frontend($language ?: \System::getInterfaceLanguage()))
			->setContext('Fidelo » Teacher portal');
	}
}