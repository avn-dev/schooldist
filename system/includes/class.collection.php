<?php

class Collection implements Iterator, Countable
{
	private $key = 0;
	private $rows = [];
	private $current = false;

	public function __construct($stmt)
	{
		$this->rows = $stmt->fetchAll(PDO::FETCH_BOTH);;
		$this->key = 0;
		$this->current = isset($this->rows[0]) ? $this->rows[0] : false;
	}

	public function rewind()
	{
		$this->key = 0;
		$this->current = isset($this->rows[0]) ? $this->rows[0] : false;
	}

	public function reset()
	{
		$this->rewind();
	}

	public function current()
	{
		return $this->current;
	}

	public function key()
	{
		return $this->key;
	}

	public function next()
	{
		$this->key++;
		$this->current = isset($this->rows[$this->key]) ? $this->rows[$this->key] : false;
	}

	public function valid()
	{
		return $this->current !== false;
	}

	public function count()
	{
		return count($this->rows);
	}
}