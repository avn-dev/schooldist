<?php

namespace Admin\Traits;

use Illuminate\Support\Arr;

trait WithTranslations
{
	protected array $l10n = [];

	public function l10n(string|array $payload, string $translation = null): static
	{
		if (is_array($payload)) {
			$this->l10n = array_merge($this->l10n, Arr::dot($payload));
		} else if ($translation !== null) {
			$this->l10n[$payload] = $translation;
		}
		return $this;
	}

	public function getTranslations(): array
	{
		return $this->l10n;
	}
}