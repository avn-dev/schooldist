<?php

namespace Admin\Commands;

use Admin\Instance;
use Core\Command\AbstractCommand;
use Illuminate\Http\Request;
use Symfony\Component\Console\Command\Command;

class Dashboard extends AbstractCommand {

    protected function configure() {   

        $this->setName('admin:dashboard:update')
             ->setDescription('Refreshes dashboard cache');

    }

	/**
	 * Gibt den Stack als JSON aus
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	public function handle(Instance $admin)
	{
		$this->_setDebug($this->output);

		$this->laravel->instance(\Access_Backend::class, new \Access_Backend(\DB::getDefaultConnection()));
		$this->laravel->instance('request', $request = new Request());

		/* @var $welcome \Admin\Helper\Welcome */
		$welcome = \Factory::getObject(\Admin\Helper\Welcome::class);
		$welcome->updateCache($admin, $request);

		$this->components->info('Dashboard updated successfully.');

		return Command::SUCCESS;
    }
	
}