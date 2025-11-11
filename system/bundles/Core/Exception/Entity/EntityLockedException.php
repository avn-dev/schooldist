<?php

namespace Core\Exception\Entity;

class EntityLockedException extends \RuntimeException
{
	public function __construct(private \WDBasic $entity)
	{
		parent::__construct(sprintf('Entity locked [%s::%s]', $this->entity::class, $this->entity->id));
	}

	public function entity(): \WDBasic
	{
		return $this->entity;
	}

	public function __toString(): string
	{
		return 'ENTITY_LOCKED';
	}
}