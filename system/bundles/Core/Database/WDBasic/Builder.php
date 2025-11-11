<?php

namespace Core\Database\WDBasic;

use Core\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin QueryBuilder
 * @method \Core\Database\WDBasic\Builder withTrashed(bool $withTrashed = true)
 * @method \Core\Database\WDBasic\Builder withoutTrashed()
 * @method \Core\Database\WDBasic\Builder onlyTrashed()
 * @method \Core\Database\WDBasic\Builder onlyValid(\DateTime $date = null)
 * @method \Core\Database\WDBasic\Builder whereFlexField($flexFieldId, $operator = null, $value = null, $boolean = 'and')
 * @method \Core\Database\WDBasic\Builder whereFlexFieldIn($flexFieldId, $values, $boolean = 'and', $not = false)
 * @method \Core\Database\WDBasic\Builder inPeriodRange(\DateTimeInterface $startDate, ?\DateTimeInterface $endDate = null, ?string $startColumn = null, ?string $endColumn = null)
 */
class Builder {
	use BuildsQueries, Macroable;

	private $query;

	private $entity;

	private $scopes;

	protected $passthru = [
		'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'dump', 'dd',
		'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection', 'raw', 'getGrammar',
		'pluck'
	];

	public function __construct(\WDBasic $entity, QueryBuilder $query) {
		$this->entity = $entity;
		$this->query = $query;
	}

	/**
	 * @return \WDBasic
	 */
	public function getModel() {
		return $this->entity;
	}

	/**
	 * @return QueryBuilder
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Register a new global scope.
	 *
	 * @param  string  $identifier
	 * @param  \Illuminate\Database\Eloquent\Scope|\Closure  $scope
	 * @return $this
	 */
	public function withGlobalScope($identifier, $scope) {

		$this->scopes[$identifier] = $scope;

		if (method_exists($scope, 'extend')) {
			$scope->extend($this);
		}

		return $this;
	}

	/**
	 * Remove a registered global scope.
	 *
	 * @param  Scope|string  $scope
	 * @return $this
	 */
	public function withoutGlobalScope($scope) {

		if (!is_string($scope)) {
			$scope = get_class($scope);
		}

		unset($this->scopes[$scope]);

		return $this;
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param  \Closure|string|array|\Illuminate\Database\Query\Expression  $column
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @param  string  $boolean
	 * @return $this
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and') {

		if ($column instanceof \Closure && is_null($operator)) {
			$column($query = $this->entity->newQuery());

			$this->query->addNestedWhereQuery($query->getQuery(), $boolean);
		} else {
			$this->query->where($column, $operator, $value, $boolean);
		}


		return $this;
	}

	/**
	 * Add a where clause on the primary key to the query.
	 *
	 * @param  mixed  $id
	 * @return $this
	 */
	public function whereKey($id) {

		if ($id instanceof \WDBasic) {
			$id = $id->getId();
		}

		$primaryColumn = $this->entity->qualifyColumn($this->entity->getPrimaryColumn());

		if (is_array($id) || $id instanceof Arrayable) {
			$this->whereIn($primaryColumn, $id);
			return $this;
		}

		return $this->where($primaryColumn, '=', $id);
	}

	/**
	 * Add a where clause on the primary key to the query.
	 *
	 * @param  mixed  $id
	 * @return $this
	 */
	public function whereKeyNot($id) {

		if ($id instanceof \WDBasic) {
			$id = $id->getId();
		}

		$primaryColumn = $this->entity->qualifyColumn($this->entity->getPrimaryColumn());

		if (is_array($id) || $id instanceof Arrayable) {
			$this->whereNotIn($primaryColumn, $id);
			return $this;
		}

		return $this->where($primaryColumn, '!=', $id);
	}


	/**
	 * Add a basic where clause to the query, and return the first result.
	 *
	 * @param  \Closure|string|array|\Illuminate\Database\Query\Expression  $column
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Eloquent\Model|static|null
	 */
	public function firstWhere($column, $operator = null, $value = null, $boolean = 'and') {
		return $this->where($column, $operator, $value, $boolean)->first();
	}

	/**
	 * Add an "or where" clause to the query.
	 *
	 * @param  \Closure|array|string|\Illuminate\Database\Query\Expression  $column
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @return $this
	 */
	public function orWhere($column, $operator = null, $value = null) {

		[$value, $operator] = $this->query->prepareValueAndOperator(
			$value, $operator, func_num_args() === 2
		);

		return $this->where($column, $operator, $value, 'or');
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param  string  $column
	 * @return $this
	 */
	public function latest($column = 'created') {
		$this->query->latest($column);
		return $this;
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param  string $column
	 * @return $this
	 */
	public function oldest($column = 'created') {
		$this->query->oldest($column);
		return $this;
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return \WDBasic|\Illuminate\Support\Collection
	 */
	public function find($id) {

		if (is_array($id) || $id instanceof Arrayable) {
			return $this->findMany($id);
		}

		return $this->whereKey($id)->first();
	}

	/**
	 * Find multiple models by their primary keys.
	 *
	 * @param  \Illuminate\Contracts\Support\Arrayable|array  $ids
	 * @return \Illuminate\Support\Collection
	 */
	public function findMany($ids) {

		$ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

		if (empty($ids)) {
			return new Collection();
		}

		return $this->whereKey($ids)->get();
	}

	/**
	 * Find a model by its primary key or throw an exception.
	 *
	 * @param  mixed $id
	 * @return \WDBasic
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function findOrFail($id) {

		if (null !== $entity = $this->find($id)) {
			return $entity;
		}

		throw (new ModelNotFoundException())->setModel(get_class($this->entity), $id);
	}

	/**
	 * Find a model by its primary key or return fresh model instance.
	 *
	 * @param  mixed  $id
	 * @return \WDBasic
	 */
	public function findOrNew($id) {

		if (null !== $entity = $this->find($id)) {
			return $entity;
		}

		return $this->newModelInstance();
	}

	/**
	 * Get the first record matching the attributes or instantiate it.
	 *
	 * @param  array  $attributes
	 * @param  array  $values
	 * @return \WDBasic
	 */
	public function firstOrNew(array $attributes = [], array $values = []) {

		if (null !== $entity = $this->where($attributes)->first()) {
			return $entity;
		}

		return $this->newModelInstance(array_merge($attributes, $values));
	}

	/**
	 * Get the first record matching the attributes or create it.
	 *
	 * @param  array  $attributes
	 * @param  array  $values
	 * @return \WDBasic
	 */
	public function firstOrCreate(array $attributes = [], array $values = []) {

		if (null !== $entity = $this->where($attributes)->first()) {
			return $entity;
		}

		return $this->create(array_merge($attributes, $values));
	}

	public function firstOrFail(array $attributes = [], array $values = []) {

		if (null !== $entity = $this->where($attributes)->first()) {
			return $entity;
		}

		throw (new ModelNotFoundException())->setModel(get_class($this->entity));
	}

	/**
	 * Save a new model and return the instance.
	 *
	 * @param  array  $attributes
	 * @return \WDBasic
	 */
	public function create(array $attributes = []) {
		return tap($this->newModelInstance($attributes), function (\WDBasic $entity) {
			$entity->validate(true);
			$entity->save();
		});
	}

	/**
	 * Get a single column's value from the first result of a query.
	 *
	 * @param  string|\Illuminate\Database\Query\Expression  $column
	 * @return mixed
	 */
	public function value($column) {
		if ($result = $this->first()) {
			return $result->{Str::afterLast($column, '.')};
		}

		return null;
	}

	/**
	 * Get a single column's value from the first result of the query or throw an exception.
	 *
	 * @param  string|\Illuminate\Database\Query\Expression  $column
	 * @return mixed
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function valueOrFail($column) {
		return $this->firstOrFail()->{Str::afterLast($column, '.')};
	}

	/**
	 * Get a base query builder instance.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function toBase() {
		return $this->applyScopes()->getQuery();
	}

	/**
	 * Apply the scopes to the Eloquent builder instance and return it.
	 *
	 * @return static
	 */
	public function applyScopes() {

		if (empty($this->scopes)) {
			return $this;
		}

		$builder = clone $this;

		foreach ($this->scopes as $scope) {
			if ($scope instanceof \Closure) {
				$scope($builder);
			}
			if ($scope instanceof Scope) {
				// Die Methode bei Eloquent lautet eigentlich apply(), da diese aber nur mit dem Eloquent-Builder und einem
				// Eloquent-Model funktioniert wurde applyLegacy() eingeführt
				$scope->applyLegacy($builder, $this->entity);
			}
		}

		return $builder;
	}

	/**
	 * Execute the query and build WDBasic objects from result
	 *
	 * @return Collection
	 */
	public function get(): Collection {

		$result = $this->toBase()->get();

		if ($result->isNotEmpty()) {

			$firstResultKeys = array_keys($result->first());
			$entityFieldKeys = array_keys($this->entity->getData());

			if (
				// Keys der ersten Row mit den Keys aus _aData abgleichen um WDBasic-Objekte zu erzeugen
				count($firstResultKeys) === count($entityFieldKeys) &&
				empty(array_diff($entityFieldKeys, $firstResultKeys))
			) {
				$entityClass = get_class($this->entity);
				$result = $result->map(function ($row) use($entityClass) {
					return call_user_func_array([$entityClass, 'getObjectFromArray'], [$row]);
				});
			} else {
				// Wenn ein Select- oder ein Join-Part eingebaut wurde und die Daten der Row nicht dem _aData entsprechen
				// wird eine Exception geworfen da getObjectFromArray() immer alle Werte braucht. Mit Eloquent-Models
				// aus Laravel ist es zwar möglich, bei uns allerdings nicht
				$this->entity->deleteTableCache();
				throw new \RuntimeException(sprintf('Please only select wdbasic table fields when using %s (query: %s)', __METHOD__, $this->toBase()->toSql()));
			}

		}

		return $result;
	}

	/**
	 * Add a generic "order by" clause if the query doesn't already have one.
	 *
	 * @return void
	 */
	protected function enforceOrderBy() {
		if (empty($this->query->orders) && empty($this->query->unionOrders)) {
			$this->orderBy($this->withAlias('id'), 'asc');
		}
	}

	/**
	 * Generate new entity instance
	 *
	 * @param array $attributes
	 * @return \WDBasic
	 */
	protected function newModelInstance($attributes = []) {

		$entityClass = $this->getEntityClass();
		$entity = new $entityClass();

		foreach ($attributes as $key => $value) {
			$entity->{$key} = $value;
		}

		return $entity;
	}

	/**
	 * Get class name of entity
	 *
	 * @return string
	 */
	private function getEntityClass(): string {
		return get_class($this->entity);
	}

	/**
	 * Get column name with alias or without
	 *
	 * @param string $column
	 * @return string
	 */
	public function withAlias(string $column) {
		$alias = $this->entity->getTableAlias();
		return (!empty($alias)) ? $alias.'.'.$column : $column;
	}

	/**
	 * @return string
	 */
	protected function defaultKeyName() {
		return $this->entity->qualifyColumn($this->entity->getPrimaryColumn());
	}

	/**
	 * Explains the query.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function explain() {

		$sql = $this->toSql();

		$bindings = $this->getBindings();

		$explanation = $this->getConnection()->select('EXPLAIN '.$sql, $bindings);

		return new Collection($explanation);
	}

	/**
	 * Checks if a global macro is registered.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function hasGlobalMacro($name)
	{
		return isset(static::$macros[$name]);
	}

	/**
	 * @param $method
	 * @param $parameters
	 * @return $this
	 */
	public function __call($method, $parameters) {

		if (static::hasGlobalMacro($method)) {
			$callable = static::$macros[$method];

			if ($callable instanceof \Closure) {
				$callable = $callable->bindTo($this, static::class);
			}

			return $callable($this, ...$parameters);
		}

		if (in_array($method, $this->passthru)) {
			// Bei allen Methodennamen aus $passthru die Anfrage direkt an $query weiterleiten und mit dessen Rückgabewert weiterarbeiten
			return $this->toBase()->{$method}(...$parameters);
		}

		// Bei allen anderen Anfragen den Part in den Query einbauen aber wieder den WDBasic-Builder zurückgeben damit
		// dessen Methoden weiter benutzt werden
		$this->query->{$method}(...$parameters);

		return $this;
	}

	public function toSql() {
		return $this->toBase()->toSql();
	}

	/**
	 * Force a clone of the underlying query builder when cloning.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->query = clone $this->query;
	}

}
