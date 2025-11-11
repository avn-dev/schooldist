<?php

namespace Core\Traits;

use Core\Helper\BitwiseOperator;

trait BitwiseFlag {

	public function hasFlag(string $field, int $flag): bool {
		return BitwiseOperator::has($this->$field, $flag);
	}

	public function addFlag(string $field, int $flag) {
		$value = (int)$this->$field;
		BitwiseOperator::add($value, $flag);
		$this->$field = $value;
		return $this;
	}

	public function removeFlag(string $field, int $flag) {
		$value = (int)$this->$field;
		BitwiseOperator::remove($value, $flag);
		$this->$field = $value;
		return $this;
	}

}
