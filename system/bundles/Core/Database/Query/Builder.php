<?php

namespace Core\Database\Query;

use DB;
use Core\Database\LaravelConnection;

class Builder extends \Illuminate\Database\Query\Builder {

	protected $fideloConnection;

	public function __construct(DB|LaravelConnection $connection = null) {

		if ($connection === null) {
			$connection = DB::getDefaultConnection();
		}

		if ($connection instanceof DB) {
			$this->fideloConnection = $connection;
			$connection = new LaravelConnection($connection);
		} else {
			$this->fideloConnection = $connection->getFideloConnection();
		}

		parent::__construct($connection);
	}

	/**
	 * Get a new instance of the query builder.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function newQuery() {
		return new static($this->fideloConnection);
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function latest($column = 'created') {
		return $this->orderBy($column, 'desc');
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param  string  $column
	 * @return $this
	 */
	public function oldest($column = 'created') {
		return $this->orderBy($column, 'asc');
	}

}
