<?php

namespace Tc\Interfaces\Wizard;

use Tc\Service\Wizard\Iteration;
use Tc\Service\Wizard\Structure\Step;

interface LogStorage
{
	/**
	 * @param \User|null $user
	 * @return Log[]
	 */
	public function getLogs(\User $user = null): array;

	public function getLastLog(\User $user = null): ?Log;

	public function writeLog(Iteration $iteration, Step $step, Log $log = null): Log;

	public function removeLogs(\User $user = null, Log $specific = null): static;
}