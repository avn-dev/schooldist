<?php

namespace TsReporting\Services;

use Core\Database\Query\Builder;
use TsReporting\Generator\Bases\AbstractBase;
use TsReporting\Generator\Scopes\AbstractScope;
use TsReporting\Generator\ValueHandler;

class QueryBuilder extends Builder
{
	/**
	 * @var AbstractScope[]
	 */
	private array $scopes = [];

	private bool $applying = false;

	/**
	 * @template T
	 * @param class-string<T>|AbstractScope|\Closure $scope
	 * @return T
	 */
	public function requireScope(string|AbstractScope|\Closure $scope): AbstractScope
	{
		if ($this->applying) {
			throw new \RuntimeException('Can not require scope while already applying scopes.');
		}

		if ($scope instanceof \Closure) {
			$scope = $this->createScopeFromClosure($scope);
		}

		if ($scope instanceof AbstractScope) {
			$key = get_class($scope);
			$object = $scope;
		} else {
			$key = $scope;
			$object = new $scope();
		}

		if (!$this->scopes[$key]) {
			$this->scopes[$key] = $object;
		}

		return $this->scopes[$key];

	}

	public function applyScopes(AbstractBase $base, ValueHandler $values)
	{
		$this->applying = true;
		// TODO Vlt. muss eine Sortierung eingeführt werden wegen Abhängigkeiten
		foreach ($this->scopes as $scope) {
			$scope->setBase($base);
			$scope->apply($this, $values);
		}
		$this->applying = false;
	}

	public function hasScope(string $key): bool
	{
		return isset($this->scopes[$key]);
	}

	private function createScopeFromClosure(\Closure $closure): AbstractScope
	{
		return new class($closure) extends AbstractScope {
			public function __construct(readonly \Closure $closure) { }

			public function apply(QueryBuilder $builder, ValueHandler $values): void
			{
				($this->closure)($builder, $values);
			}
		};
	}
}