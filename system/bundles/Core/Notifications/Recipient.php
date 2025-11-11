<?php

namespace Core\Notifications;

class Recipient
{
	public function __construct(
		private string $route,
		private ?string $name = null,
		private ?\WDBasic $model = null,
	) {}

	public function getRoute(): string
	{
		return $this->route;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function getModel(): ?\WDBasic
	{
		return $this->model;
	}

	public function toArray(): array
	{
		return [
			'route' => $this->route,
			'name' => $this->name,
			'model' => $this->model ? sprintf('%s::%d', $this->model::class, $this->model->id) : null,
		];
	}

	public static function fromArray(array $array): static
	{
		$model = null;
		if (!empty($array['model'])) {
			[$class, $id] = explode('::', $array['model']);
			$model = \Factory::getInstance($class, $id);
		}

		return new static($array['route'], $array['name'], $model);
	}

}