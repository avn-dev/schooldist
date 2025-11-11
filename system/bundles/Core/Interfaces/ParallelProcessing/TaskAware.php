<?php

namespace Core\Interfaces\ParallelProcessing;

interface TaskAware
{
	public function setTask(array $task): void;
}