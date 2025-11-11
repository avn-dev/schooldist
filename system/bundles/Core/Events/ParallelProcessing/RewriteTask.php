<?php

namespace Core\Events\ParallelProcessing;

use Illuminate\Foundation\Events\Dispatchable;

class RewriteTask
{
	use Dispatchable;

	public function __construct(private array $task) {}

}