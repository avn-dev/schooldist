<?php

namespace Tc\Service\Import;

class ErrorPointer {

	private $worksheet;

	private $rowIndex;

	private $columnIndex;

	public function __construct($worksheet, int $rowIndex, int $columnIndex = null) {
		$this->worksheet = $worksheet;
		$this->rowIndex = $rowIndex;
		$this->columnIndex = $columnIndex;
	}

	public function getWorkSheet(): ?string {
		return $this->worksheet;
	}

	public function getRowIndex(): int {
		return $this->rowIndex;
	}

	// TODO - wird noch nicht verwendet
	public function getColumnIndex(): ?int {
		return $this->columnIndex;
	}
}
