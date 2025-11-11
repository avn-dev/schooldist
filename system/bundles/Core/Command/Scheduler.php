<?php

namespace Core\Command;

use Core\Service\Cache\LaravelStore;
use Core\Traits\Console\WithDebug;
use Illuminate\Cache\Repository;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scheduler extends ScheduleRunCommand {
    use WithDebug;
	
	const HOOK_NAME = 'core_scheduler';
	const HOOK_TIMEZONE_NAME = 'core_scheduler_timezone';

    //protected $name = 'core:schedule:run';
	protected $signature = 'core:schedule:run {--whisper : Do not output message indicating that no jobs were ready to run}';

    protected static $defaultName = 'core:schedule:run';

    protected function execute(InputInterface $input, OutputInterface $output):int {

		$this->_setDebug($output);

        $webdynamics = \webdynamics::getInstance('backend');

		// Laravel Application mit allen benötigten Bindings

        $timezone = \System::d('timezone');

        $webdynamics->executeHook(self::HOOK_TIMEZONE_NAME, $timezone);

		$this->schedule = new \Core\Console\Scheduler($timezone);

		// Scheduler Events über Hook/Event sammeln

        $webdynamics->executeHook(self::HOOK_NAME, $this->schedule);

		// Events ausführen

        $this->handle(
            $this->schedule,
            $this->laravel->make(\Illuminate\Contracts\Events\Dispatcher::class),
            new Repository(new LaravelStore()),
            $this->laravel->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
        );

        return Command::SUCCESS;
	}

}
