<?php

namespace Core\Service;

use Illuminate\Support\Traits\ForwardsCalls;

/**
 * Klasse dient als Wrapper für die save()-Methode einer gesperrten Entität
 */
class LockedEntity
{
	use ForwardsCalls;

	public function __construct(
		private readonly \WDBasic $entity
	) {}

	public function save()
	{
		try {
			$payload = $this->entity->save();
		} catch (\Throwable $e) {
			$this->entity->unlock();
			throw $e;
		}

		$this->entity->unlock();

		return $payload;
	}

	public function __get($name)
	{
		return $this->entity->__get($name);
	}

	public function __set($name, $value)
	{
		$this->entity->__set($name, $value);
	}

	public function __call($method, $parameters)
	{
		// Alles an die eigentliche Entität weiterleiten
		return $this->forwardCallTo($this->entity, $method, $parameters);
	}

}