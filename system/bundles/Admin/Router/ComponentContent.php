<?php

namespace Admin\Router;

use Admin\Dto\Component\Parameters;
use Admin\Enums\ContentType;
use Admin\Helper\ComponentParameters;
use Admin\Traits\WithDateAsOf;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class ComponentContent extends Content
{
	use WithDateAsOf;

	private ?Parameters $parameters = null;

	public function __construct(
		array|Arrayable $payload,
		// Wenn false wird beim Aufruf des Contents ein init-Request abgeschickt
		private bool $initialize = true
	) {
		parent::__construct(ContentType::COMPONENT, $payload);
	}

	public function initialize(bool $initialize): static
	{
		$this->initialize = $initialize;
		return $this;
	}

	public function parameters(Parameters $parameters): static
	{
		$this->parameters = $parameters;
		return $this;
	}

	public function getParameters(): ?Parameters
	{
		return $this->parameters;
	}

	public function toArray(): array
	{
		$array = parent::toArray();
		$array['parameters'] = $this->parameters
			? ComponentParameters::encrypt($this->parameters->toArray())
			: null;
		$array['initialized'] = $this->initialize;
		return $array;
	}

	public static function fromArray(array $array): static
	{
		$content = (new static($array['payload'], $array['initialized']))
			->l10n($array['l10n'] ?? []);

		if (!empty($init = $array['parameters'])) {
			$content->parameters(new Parameters(ComponentParameters::decrypt($init)));
		}

		return $content;
	}
}