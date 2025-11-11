<?php

namespace Admin\Router;

use Admin\Dto\Component\Parameters;
use Admin\Enums\ContentType;
use Admin\Helper\ComponentParameters;
use Admin\Traits\WithTranslations;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class Content implements Arrayable
{
	use WithTranslations;

	public function __construct(
		protected readonly ContentType $type,
		protected readonly array|Arrayable $payload,
	) {}

	public function getType(): ContentType
	{
		return $this->type;
	}

	public function getPayload(): array
	{
		$payload = ($this->payload instanceof Arrayable)
			? $this->payload->toArray()
			: $this->payload;

		return $payload;
	}

	public function toArray(): array
	{
		return [
			'type' => $this->type->value,
			'payload' => $this->getPayload()
		];
	}

	public static function fromArray(array $array): static
	{
		$content =  (new static(ContentType::from($array['type']), $array['payload']))
			->l10n($array['l10n'] ?? []);

		return $content;
	}
}