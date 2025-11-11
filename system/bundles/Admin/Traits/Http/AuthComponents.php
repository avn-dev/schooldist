<?php

namespace Admin\Traits\Http;

use Admin\Facades\Admin;
use Admin\Service\Auth\Authentication;
use Admin\Service\Auth\AuthenticationAddon\GoogleTwoFactor;
use Core\Handler\SessionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

trait AuthComponents
{
	public function buildLoginResponse(Request $request, Authentication $authentication): Response
	{
		// TODO TC-Sachen im Framework
		$sso = null;
		if (
			class_exists('\TcExternalApps\Service\AppService') &&
			\TcExternalApps\Service\AppService::hasApp(\Sso\Service\SsoApp::APP_NAME)
		) {
			$sso = '/admin/sso/login';
		}

		return Inertia::render('Login', [
			'force' => (bool)$request->get('force', 0),
			'sso' => $sso,
			'messages' => $this->buildMessagesFromSession($authentication->getSession()),
			'l10n' => [
				'field' => [
					'username' => Admin::translate('E-Mail', 'Login'),
					'password' => Admin::translate('Passwort', 'Login'),
					'language' => Admin::translate('Sprache', 'Login'),
				],
				'btn' => [
					'submit' => Admin::translate('Anmelden', 'Login'),
					'passkey' => Admin::translate('Mit Passkey einloggen', 'Login'),
					'sso' => Admin::translate('Login über Single Sign-On', 'Login'),
					'forgot' => Admin::translate('Passwort vergessen?', 'Login')
				],
				'alternatives' => Admin::translate('Oder', 'Login'),
			],
			'languages' => collect(\System::getBackendLanguages(true))
				->map(fn ($text, $iso) => ['value' => $iso, 'text' => $text])
				->values(),
			'defaultLanguage' => \System::d('systemlanguage'),
		]);
	}

	public function buildExistingSessionResponse(Request $request, Authentication $authentication): Response
	{
		return Inertia::render('ExistingSession', [
			'force' => (bool)$request->get('force', 0),
			'l10n' => [
				'btn' => [
					'submit' => Admin::translate('Anmelden', 'Login'),
					'cancel' => Admin::translate('Abbrechen', 'Login'),
				],
				'message' => Admin::translate('Es ist bereits eine Sitzung mit diesem Benutzer aktiv. Bitte bestätigen Sie den Login, um die aktive Sitzung zu beenden.', 'Login')
			]
		]);
	}

	public function buildGoogleTwoFactorResponse(Request $request, Authentication $authentication, GoogleTwoFactor $addon): Response
	{
		$viewValues = $addon->getViewValues();

		return Inertia::render('GoogleTwoFactor', [
			'force' => (bool)$request->get('force', 0),
			'messages' => $this->buildMessagesFromSession($authentication->getSession()),
			'l10n' => [
				'field' => [
					'code' => Admin::translate('Code', 'Login'),
				],
				'btn' => [
					'submit' => Admin::translate('Anmelden', 'Login'),
					'cancel' => Admin::translate('Abbrechen', 'Login'),
				],
				'qr_code' => Admin::translate('Bitte scannen Sie den folgenden Code mit der Google Authenticator App', 'Login'),
				'remember_device' => Admin::translate('Gerät merken', 'Login')
			],
			'qrCode' => $viewValues['qr_url'] ?? null
		]);
	}

	protected function buildMessagesFromSession(SessionHandler $session)
	{
		$success = Arr::wrap($session->getFlashBag()->get('success'));
		$errors = array_merge(
			Arr::wrap($session->getFlashBag()->get('error')),
			Arr::wrap($session->getFlashBag()->get('errors'))
		);

		$messages = array_merge(
			array_map(fn ($message) => ['type' => 'success', 'message' => $message], $success),
			array_map(fn ($message) => ['type' => 'error', 'message' => $message], $errors)
		);

		#dd($messages);

		return $messages;
	}

}