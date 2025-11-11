<?php

namespace Core\Command;

use Core\Traits\Console\WithDebug;
use Illuminate\Console\Command;

abstract class AbstractCommand extends Command {
	use WithDebug;
}