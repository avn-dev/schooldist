<?php

namespace TsStudentApp\Pages;

use TsStudentApp\AppInterface;

class Settings extends AbstractPage {

	private $appInterface;

	public function __construct(AppInterface $appInterface) {
		$this->appInterface = $appInterface;
	}

	public function init(): array {

		$loginDevice = $this->appInterface->getLoginDevice();

		$data = [
			'settings' => [
				'intro_finished' => ($loginDevice) ? (bool)$loginDevice->intro_finished : true
			]
		];

		return $data;
	}

	public function finishIntro(AppInterface $appInterface) {

		$loginDevice = $appInterface->getLoginDevice();
		if ($loginDevice) {
			$loginDevice->intro_finished = 1;
			$loginDevice->save();
		}

		return response()->json(['success' => true]);

	}

	public function resetIntro() {

		$loginDevice = $this->appInterface->getLoginDevice();

		$success = false;
		$message = $this->appInterface->t('No Device used');

		if ($loginDevice) {
			$loginDevice->intro_finished = null;
			$loginDevice->save();

			$success = true;
			$message = $this->appInterface->t('Setting has been reset');
		}

		return response()
			->json(['success' => $success, 'message' => $message]);
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tabs.settings.intro.label' => $appInterface->t('Reset Intro'),
			'tabs.settings.intro.description' => $appInterface->t('Show intro on next app start.'),
			'tabs.settings.toast.confirm' => $appInterface->t('Okay'),
			'tabs.settings.app-settings.label' => $appInterface->t('Open app settings'),
			'tabs.settings.app-settings.description' => $appInterface->t('Open this app\'s settings in the device settings.'),
		];
	}

}
