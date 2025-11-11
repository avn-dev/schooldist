<?php

namespace Tc\Exception\Import;

use Tc\Service\Import\ErrorPointer;

class ImportRowException extends \RuntimeException {

	private $pointer;

	public function pointer(string $worksheet, int $rowIndex, int $columnIndex = null): self {
		$this->pointer = new ErrorPointer($worksheet, $rowIndex, $columnIndex);
		return $this;
	}

	public function getPointer(): ?ErrorPointer {
		return $this->pointer;
	}

	public function hasPointer(): bool {
		return !is_null($this->pointer);
	}
}
