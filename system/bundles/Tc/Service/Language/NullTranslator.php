<?php

namespace Tc\Service\Language;

class NullTranslator extends \Tc\Service\LanguageAbstract
{
	public function __construct()
	{
		parent::__construct('en');
	}

	public function translate($translate)
	{
		return $translate;
	}
}
