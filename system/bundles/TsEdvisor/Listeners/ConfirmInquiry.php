<?php

namespace TsEdvisor\Listeners;

use Core\Enums\AlertLevel;
use TcExternalApps\Interfaces\ExternalApp as TcExternalApp;
use Ts\Interfaces\Events\InquiryEvent;
use TsEdvisor\Service\Api;

class ConfirmInquiry
{
	public function handle(InquiryEvent $event): void
	{
		if (!\TcExternalApps\Service\AppService::hasApp(\TsEdvisor\Handler\ExternalApp::APP_NAME)) {
			return;
		}

		$inquiry = $event->getInquiry();
		$id = (int)$inquiry->getMeta('edvisor_id');
		$status = (int)$inquiry->getMeta('edvisor_status');

		if (empty($id)) {
			return;
		}

		if (
			empty($status) ||
			$status === Api::ENROLLMENT_STATUS_PENDING ||
			$status === Api::ENROLLMENT_STATUS_PROCESSING
		) {
			$user = \System::getCurrentUser();

			try {

				Api::default()->acceptEnrollment($id);

				\Core\Service\NotificationService::sendToUser($user, strtr(
					\L10N::t('Booking ":booking" from student ":student" marked as accepted in Edvisor.', TcExternalApp::L10N_PATH),
					[':booking' => $inquiry->getNumber(), ':student' => $inquiry->getFirstTraveller()->getCustomerNumber()]
				), AlertLevel::SUCCESS);

			} catch (\Throwable $e) {

				if (str_contains($e->getMessage(), 'Student enrollment status id must be one of PENDING, PROCESSING')) {

					\Core\Service\NotificationService::sendToUser($user, strtr(
						\L10N::t('Booking ":booking" from student ":student" is already marked as accepted in Edvisor.', TcExternalApp::L10N_PATH),
						[':booking' => $inquiry->getNumber(), ':student' => $inquiry->getFirstTraveller()->getCustomerNumber()]
					));

				} else {

					\Core\Service\NotificationService::sendToUser($user, strtr(
						\L10N::t('Booking ":booking" from student ":student" could not be marked as accepted in Edvisor. Please check the status in Edvisor. (:message)', TcExternalApp::L10N_PATH),
						[':booking' => $inquiry->getNumber(), ':student' => $inquiry->getFirstTraveller()->getCustomerNumber(), ':message' => $e->getMessage()]
					), AlertLevel::DANGER);

					throw $e;

				}

			}

		}
	}
}