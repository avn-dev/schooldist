<?php

namespace TsStudentApp\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TsStudentApp\Messenger\Notification;

class SendDeviceNotification extends AbstractCommand {

	protected function configure() {

		$this->setName("ts-student-app:notification:device")
			->addArgument('device', InputArgument::OPTIONAL, 'Device ID (no interactive mode)')
			->setDescription("Send test notification to single device");

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

		/** @var \Ext_TS_Inquiry_Contact_Login_Device $device */
		$device = \Ext_TS_Inquiry_Contact_Login_Device::query()
			->where('app_id', $input->getArgument('device'))
			->where('push_permission', 1)
			->first();

		if (!$device) {
			$io->text('No device found ...');
			return 1;
		}

		$student = \Ext_TS_Inquiry_Contact_Traveller::getInstance($device->getLoginContact()->contact_id);

		$messengerService = \TsStudentApp\Service\MessengerService::getInstance($student, $student->getClosestInquiry());
		$notificationService = $messengerService->createNotificationServiceByDevice($device);

		if (!$notificationService->canNotify($device)) {
			$io->text('Device is not notifiable ...');
			return 1;
		}

		$io->text('Sending notification to device ...');

		$notification = new Notification('Test Notification', 'This is just a test notification from your language school.');
		$notification->openPage('booking');

		$notificationService->notify($device, $notification->getTitle(), $notification->getMessage(), $notification->getAdditional(), $notification->getImage());

		$io->text('... finished');

		return Command::SUCCESS;

	}

}
