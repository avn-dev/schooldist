<?php

namespace TcApi\Client\Traits;

use Core\Entity\ParallelProcessing\Stack;
use TcApi\Client\Interfaces\Operation;

trait ShouldQueue
{
	abstract public function toArray(): array;

	abstract public static function fromArray(array $data): Operation;

	protected function getQueuePriority(): int
	{
		return 10;
	}

	public function writeToQueue(string $type, array $additional = []): void
	{
		$data = [
			'operation' => $this::class,
			'data' => $this->toArray()
		];

		if (!empty($additional)) {
			$data['additional'] = $additional;
		}

		Stack::getRepository()->writeToStack($type, $data, $this->getQueuePriority());
	}
}