<?php

namespace Core\Console;

class Scheduler extends \Illuminate\Console\Scheduling\Schedule {

	/**
     * Add a new Artisan command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])  {
        if (class_exists($command)) {
            $command = (new $command)->getName();
        }
		
        return $this->exec(
            sprintf('%s %s %s', \System::d('php_executable', 'php'), 'console', $command), $parameters
        );
    }
	
	/**
     * Add a new job callback event to the schedule.
     *
     * @param  object|string  $job
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function job($job, $queue = null, $connection = null) {
		throw new \RuntimeException('Jobs are currently not supported');
	}

}

