<?php

namespace Core\Interfaces;

interface HumanReadable
{
	public function getHumanReadableText($l10n): string;
}