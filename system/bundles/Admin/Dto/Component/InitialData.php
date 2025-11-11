<?php

namespace Admin\Dto\Component;

use Admin\Interfaces\HasTranslations;
use Admin\Traits\WithDateAsOf;
use Admin\Traits\WithTranslations;
use Illuminate\Contracts\Support\Arrayable;

class InitialData implements HasTranslations
{
	use WithTranslations,
		WithDateAsOf;

	public function __construct(
		private Arrayable|array $data = []
	) {}

	public function getData(): array
	{
		$array = ($this->data instanceof Arrayable)
			? $this->data->toArray()
			: $this->data;

		return $array;
	}
}