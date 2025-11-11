<?php

namespace OpenBanking\Interfaces\Processes;

use OpenBanking\Enums\Transaction\Direction;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use OpenBanking\Interfaces\Process;

interface LoadTransactionsTask extends Task
{
	public function get(Process $process, Carbon $from, Carbon $until, Direction $direction = null): Collection;
}