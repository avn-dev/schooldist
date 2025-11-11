<?php

namespace TsTeacherLogin\Command;

use Core\Command\AbstractCommand;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Attendance  extends AbstractCommand {

	/**
	 * Configure a new Command Line
	 */
	protected function configure() {
		$this
			->setName('ts-teacherlogin:attandance:server')
			->setDescription('Start the attandance server.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		return 1; // TODO: rausnehmen, wenn nicht benutzt wird
		$this->_setDebug($output);

		// Run the server application through the WebSocket protocol on port 8080
		$app = new \Ratchet\App(\Util::getSystemHost(), 8001);
		$app->route('/qr-code', new \TsTeacherLogin\Service\Attendance());
		$app->run();
		return Command::SUCCESS;
	}

}