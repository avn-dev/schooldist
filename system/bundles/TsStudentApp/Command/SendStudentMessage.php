<?php

namespace TsStudentApp\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TsStudentApp\Service\MessengerService;

/**
 * @deprecated
 */
class SendStudentMessage extends AbstractCommand {

	protected function configure() {

		$this->setName("ts-student-app:message:student")
			->setDescription("Send test notification to student");

	}

	/**
	 * Gibt den Stack als JSON aus
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);

		$io = new SymfonyStyle($input, $output);

		do {
			$id = $io->ask('Student Login ID');

			$login = \Ext_TS_Inquiry_Contact_Login::getInstance((int) $id);

		} while(!$login->exist());

		$io->text('Sending message to devices...');

		$student = \Ext_TS_Inquiry_Contact_Traveller::getInstance($login->contact_id);

		MessengerService::sendMessageToStudent(
			$student,
			$student->getClosestInquiry(),
			$student->getClosestInquiry(),
			'Test Notification',
			'This is just a test notification from your language school.'
		);

		$io->text('...finished');

		return Command::SUCCESS;
	}

}
