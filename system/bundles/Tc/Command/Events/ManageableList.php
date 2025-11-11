<?php

namespace Tc\Command\Events;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Terminal;
use Tc\Service\EventManager;

class ManageableList extends AbstractCommand
{
	protected function configure() {
		$this->setName("events:manageable")
			->setDescription("List all manageable events and their dependencies");
	}

	public function handle(EventManager $eventManager) {

		$terminalWidth = (new Terminal())->getWidth();

		$detailedList = $eventManager->getDetailedList();

		if ($detailedList->isEmpty()) {
			$this->components->error('No manageable events registered');
			return Command::FAILURE;
		}

		$dots = function ($string, $prefix = '') use ($terminalWidth)  {
			return str_repeat('.', $terminalWidth - strlen($string) - strlen($prefix));
		};

		$colors = ['listeners' => 'yellow', 'conditions' => '#6C7280'];

		foreach ($detailedList as $eventName => $config) {

			$this->getOutput()->writeln(sprintf('<fg=white;options=bold>%s</>%s', $eventName, $dots($eventName)));

			$prefix = '....';
			foreach (['listeners', 'conditions'] as $type) {
				foreach ((array)$config[$type] as $class) {
					$name = implode('@', $class);
					$this->getOutput()->writeln(sprintf('<fg=#6C7280>%s</><fg=%s>%s</><fg=#6C7280>%s</>', $prefix, $colors[$type], $name, $dots($name, $prefix)));
				}
			}

		}

		return Command::SUCCESS;
	}

}