<?php

namespace Core\Database;

use Illuminate\Database\Query\Expression;

class LaravelConnection implements \Illuminate\Database\ConnectionInterface {

	/**
	 * @var \DB
	 */
	private $fideloConnection;

	/**
	 * @param \DB|null $connection
	 */
	public function __construct(\DB $connection = null) {

		if($connection === null) {
			$connection = \DB::getDefaultConnection();
		}
		
		$this->fideloConnection = $connection;
	}

	public function getFideloConnection(): \DB {
		return $this->fideloConnection;
	}

	/**
	 * @inheritdoc
	 */
	public function getQueryGrammar() {
		return new \Illuminate\Database\Query\Grammars\MySqlGrammar(new \Illuminate\Database\MySqlConnection($this->fideloConnection->getConnectionResource()));
	}

	/**
	 * @inheritdoc
	 */
	public function getPostProcessor() {
		return new \Illuminate\Database\Query\Processors\MySqlProcessor();
	}

	/**
	 * Laravel-Bindings (?) zu Fidelo-Bindings (:binding) umwandeln
	 *
	 * @param $query
	 * @param array $bindings
	 */
	/*private function convertBindings(&$query, &$bindings = []) {

		if (empty($bindings)) {
			return;
		}

		// Fidelo-Bindings generieren (binding_1, binding_2, ...., binding_*)
		$bindingKeys = array_map(
			function ($count) { return 'binding_'.$count; },
			range(1, count($bindings))
		);

		// Binding-Keys und Values kombinieren
		$bindings = array_combine($bindingKeys, $bindings);

		// "?"-Platzhalter im Query ersetzen
		$count = 0;
		$query = preg_replace_callback('/(\?)/', function ($match) use (&$count) {
			$count++;
			return ':binding_'.$count;
		}, $query);

	}*/

	/**
	 * @inheritdoc
	 */
	public function table($table, $as = null) {
		return (new \Illuminate\Database\Query\Builder($this))->from($table, $as);
	}

	/**
	 * @inheritdoc
	 */
	public function raw($value) {
		return new Expression($value);
	}

	/**
	 * @inheritdoc
	 */
	public function selectOne($query, $bindings = [], $useReadPdo = true) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function select($query, $bindings = [], $useReadPdo = true) {
		//$this->convertBindings($query, $bindings);
		//return $this->connection->queryRows($query, $bindings);
		$stmt = $this->fideloConnection->getPreparedStatement($query, null, $this->fideloConnection);
		return $this->fideloConnection->fetchPreparedStatement($stmt, $bindings);
	}

	/**
	 * @inheritdoc
	 */
	public function cursor($query, $bindings = [], $useReadPdo = true) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function insert($query, $bindings = []) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function update($query, $bindings = []) {
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * @inheritdoc
	 */
	public function delete($query, $bindings = []) {
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * @inheritdoc
	 */
	public function statement($query, $bindings = []) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function affectingStatement($query, $bindings = []) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function unprepared($query) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function prepareBindings(array $bindings) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function transaction(\Closure $callback, $attempts = 1) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function beginTransaction() {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function commit() {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function rollBack() {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function transactionLevel() {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function pretend(\Closure $callback) {
		$this->throwNotImplementedException(__METHOD__);
	}

	/**
	 * @inheritdoc
	 */
	public function getDatabaseName() {
		return $this->fideloConnection->getDBName();
	}

	private function throwNotImplementedException(string $method) {
		throw new \RuntimeException(sprintf('Method "%s" currently not implemented on Laravel database wrapper', $method));
	}

	public function scalar($query, $bindings = [], $useReadPdo = true)
	{
		// TODO: Implement scalar() method.
	}
}
