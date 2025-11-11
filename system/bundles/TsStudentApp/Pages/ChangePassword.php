<?php

namespace TsStudentApp\Pages;

use Illuminate\Http\Request;
use TsStudentApp\AppInterface;
use TsStudentApp\Service\AccessService;

class ChangePassword extends AbstractPage {

	const MESSAGE_PASSWORD_MISMATCH = 'The two passwords do not match.';
	const MESSAGE_PASSWORD_LENGTH = 'The new password must have at least %s characters.';
	const MESSAGE_FORM_EMPTY = 'Please fill out all fields.';
	const MESSAGE_OLD_PASSWORD = 'Please fill in your correct old password.';
	const MESSAGE_SUCCESS = 'Your password has been changed successfully.';

	const PASSWORD_MIN_LENGTH = 6;
	const TOAST_DURATION = 3000; // 3 Sek

	private $appInterface;

	private $school;

	public function __construct(AppInterface $appInterface, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->school = $school;
	}

	public function init(): array {
		return [
			'min_length' => self::PASSWORD_MIN_LENGTH,
			'toast_duration' => self::TOAST_DURATION
		];
	}

	public function save(Request $request, AccessService $accessService) {

		$success = false;

		if(
			$request->has('password') &&
			$request->has('password2') &&
			$request->has('old_password')
		) {

			$password = $request->input('password');
			$password2 = $request->input('password2');
			$passwordOld = $request->input('old_password');

			if($password === $password2) {
				if(strlen($password) >= self::PASSWORD_MIN_LENGTH) {

					if($accessService->checkPassword($passwordOld)) {

						$success = $accessService->changePassword($password);

						$message = $this->appInterface->t(self::MESSAGE_SUCCESS);
					} else {
						$message = $this->appInterface->t(self::MESSAGE_OLD_PASSWORD);
					}

				} else {
					$message = sprintf($this->appInterface->t(self::MESSAGE_PASSWORD_LENGTH), self::PASSWORD_MIN_LENGTH);
				}
			} else {
				$message = $this->appInterface->t(self::MESSAGE_PASSWORD_MISMATCH);
			}
		} else {
			$message = $this->appInterface->t(self::MESSAGE_FORM_EMPTY);
		}

		return [
			'success' => $success,
			'message' => $message
		];

	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.change-password.password' => $appInterface->t('New password'),
			'tab.change-password.password2' => $appInterface->t('Repeat password'),
			'tab.change-password.old_password' => $appInterface->t('Old password'),
			'tab.change-password.confirm' => $appInterface->t('Please confirm password change with your old password.'),
			'tab.change-password.btn.submit' => $appInterface->t('Save'),
			'tab.change-password.toast.close' => $appInterface->t('Close'),
			// Fehlermeldungen für JS Validierung
			'tab.change-password.failed.empty' => $appInterface->t(self::MESSAGE_FORM_EMPTY),
			'tab.change-password.failed.mismatch' => $appInterface->t(self::MESSAGE_PASSWORD_MISMATCH),
			'tab.change-password.failed.min_length' => sprintf($appInterface->t(self::MESSAGE_PASSWORD_LENGTH), self::PASSWORD_MIN_LENGTH),
		];
	}

}
