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
class SendNicknameMessage extends AbstractCommand {

	protected function configure() {

		$this->setName("ts-student-app:message:nickname")
			->setDescription("Search customer by nickname and send test notification");

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

			$nickname = $io->ask('Please insert student nickname for app');

			$studentLogin = \Ext_TS_Inquiry_Contact_Login::query()
				->where('nickname', $nickname)
				->first();

			if(is_null($studentLogin)) {
				$io->caution('Student not found');
			}

		} while(is_null($studentLogin));

		/* @var \Ext_TS_Inquiry_Contact_Login $studentLogin */

		$customer = \Ext_TS_Inquiry_Contact_Traveller::getInstance($studentLogin->contact_id);

		$continue = $io->ask(sprintf('Send message to "%s"? [y/n]', $customer->getName()));

		if(strtolower($continue) === 'y') {

			$devices = $studentLogin->getDevices();

			if(!empty($devices)) {
				$table = [];
				foreach($devices as $index => $device) {
					$table[] = [$index, $device->os, $device->os_version, (!empty($device->getFcmToken())) ? 'x' : ''];
				}

				$io->table(['#', 'OS', '', 'fcm'], $table);

				$io->text('Sending message to devices...');

				$student = \Ext_TS_Inquiry_Contact_Traveller::getInstance($customer->getId());

				MessengerService::sendMessageToStudent(
					$student,
					$student->getClosestInquiry(),
					$student->getClosestInquiry(),
					'Test Notification',
					'This is just a test notification from your language school.'
				);

			} else {
				$io->caution('No devices found');
			}


		}

		return Command::SUCCESS;
	}

}
