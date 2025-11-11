<?php

namespace Core\Traits\WdBasic;

use Core\Service\LockedEntity;

/**
 * @TODO Warum sind die Methoden so generell benannt und dann noch in der WDBasic eingebunden?
 */
trait HasEntityLock
{
	/**
	 * @internal
	 */
	public function lock(int $minutes = 1): static|LockedEntity
	{
		if (!$this->exist()) {
			return $this;
		}

		if (
			(int)\System::d('debugmode') !== 2 &&
			$this->isLocked()
		) {
			throw new \Core\Exception\Entity\EntityLockedException($this);
		}

		\Core\Facade\Cache::put($this->buildLockKey(), $minutes * 60, 1, 'entity_lock');

		return new LockedEntity($this);
	}

	/**
	 * @internal
	 */
	public function isLocked(): bool
	{
		return \Core\Facade\Cache::get($this->buildLockKey()) !== null;
	}

	/**
	 * @internal
	 */
	public function unlock(): static
	{
		\Core\Facade\Cache::forget($this->buildLockKey());
		return $this;
	}

	/**
	 * @internal
	 */
	private function buildLockKey(): string
	{
		return sprintf('entity_lock_%s::%s', $this::class, $this->id);
	}

}