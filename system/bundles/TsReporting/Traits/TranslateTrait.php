<?php

namespace TsReporting\Traits;

trait TranslateTrait
{
	public function t(string $translation): string
	{
		return \L10N::t($translation, \TsReporting\Entity\Report::TRANSLATION_PATH);
	}
}