<?php

namespace Admin\Http\Controller;

use Admin\Facades\Admin;
use Admin\Service\Auth\AuthenticationAddon\GoogleTwoFactor;
use Admin\Traits\Http\AuthComponents;
use Core\Facade\Cache;
use Core\Factory\ValidatorFactory;
use Core\Handler\CookieHandler;
use Core\Handler\SessionHandler;
use Core\Service\HtmlPurifier;
use Core\Service\RoutingService;
use Core\Validator\Rules\Base64Image;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

/**
 * TODO Übernommen aus dem alten AdminController und auf Inertia-Struktur angepasst, dieser ganze Login-Prozess mit allen
 * beteiligten Klassen ist unübersichtlich und könnte refaktorisiert werden
 */
class AuthController extends Controller
{
	use AuthComponents;

	private function session(): SessionHandler
	{
		return SessionHandler::getInstance();
	}

	public function login(\Access_Backend $access, \MVC_Request $request, RoutingService $router)
	{
		$authentication = $this->authentication($access, $request, true);

		if ($authentication instanceof RedirectResponse) {
			// TODO unsauber
			return Inertia::location($authentication->getTargetUrl());
		}

		if (
			!$this->alreadyLoggedIn($access) &&
			(null !== $addon = $authentication->getAddon())
		) {
			$viewValues = $addon->getViewValues();

			if (isset($viewValues['existing_session']) && $viewValues['existing_session'] === true) {
				return $this->buildExistingSessionResponse($request, $authentication);
			}

			if ($addon instanceof GoogleTwoFactor) {
				return $this->buildGoogleTwoFactorResponse($request, $authentication, $addon);
			}
		}

		if ($this->alreadyLoggedIn($access)) {
			return redirect($router->generateUrl('Admin.index'));
		}

		return $this->buildLoginResponse($request, $authentication);
	}

	public function passkeyChallenge(\Access_Backend $access, Request $request)
	{
		$username = !empty($request->input('username')) ? $request->get('username') : null;

		try {
			[$options,] = (new \Admin\Service\Auth\Authentication($access, $this->session()))
				->generatePasskeyChallenge($request->host(), $username);

			$json = $access::getWebauthnSerializer()->serialize(
				['success' => true, 'challenge' => $options],
				'json',
				[
					\Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
					\Symfony\Component\Serializer\Encoder\JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
				]
			);

			return JsonResponse::fromJsonString($json);

		} catch (ModelNotFoundException $e) {

			$access::getLogger('Passkey')->error('Unknown user', ['username' => $username, 'ip' => $request->ip()]);

			return response()->json([
				'success' => false,
				'errors' => [
					['type' => 'error', 'message' => Admin::translate('Einloggen fehlgeschlagen! Es wurden falsche Daten eingegeben.')]
				]
			]);

		} catch (\Throwable $e) {

			$access::getLogger('Passkey')->error('Challenge creation failed', ['username' => $username, 'ip' => $request->ip(), 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);

		}

		return response()->json([
			'success' => false,
			'errors' => [
				['type' => 'error', 'message' => Admin::translate('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.')]
			]
		]);
	}

	public function attempt(\Access_Backend $access, Request $request)
	{
		$authentication = $this->authentication($access, $request, false);

		if ($authentication instanceof RedirectResponse) {
			// TODO unsauber
			return Inertia::location($authentication->getTargetUrl());
		}

		$session = $authentication->getSession();

		$errors = Arr::wrap($session->getFlashBag()->get('error', []));

		if (!empty($error = $access->getLastErrorCode())) {
			$errors[] = \L10N::t($error, 'Admin » Login');
		}

		$session->getFlashBag()->set('errors', $errors);

		return $this->toLogin();
	}

	public function changeLanguage(Request $request)
	{
		$languages = \System::getBackendLanguages(true);
		$language = $request->input('language');

		if (isset($languages[$language])) {
			\System::setInterfaceLanguage($language);
			CookieHandler::set("systemlanguage", $language);
		}

		return $this->toLogin();
	}


	public function image()
	{
		$response = Cache::get('auth.image');

		if (!is_array($response)) {
			$response = Http::get(\Util::getProxyHost() . 'app/image')->json();

			if (is_array($response)) {
				$validator = (new ValidatorFactory())->make($response, ['image' => new Base64Image()]);

				if ($validator->fails()) {
					return response('No image', 500);
				}

				if (!empty($response['source'])) {

					$config = \HTMLPurifier_Config::createDefault();

					$config->set('HTML.AllowedElements', 'a');
					$config->set('HTML.AllowedAttributes', 'a.href,a.target,a.rel');
					$config->set('HTML.TargetBlank', true);
					$config->set('AutoFormat.Custom', [new \Core\Service\HtmlPurifier\InjectorRelNoreferrer()]);

					$def = $config->getHTMLDefinition(true);
					$def->addAttribute('a', 'rel', 'Text'); // erlaubt auch mehrere Tokens wie "noopener noreferrer"

					$purifier = new HtmlPurifier($config);
					$response['source'] = $purifier->purify($response['source']);
				}

				Cache::put('auth.image', 60 * 60 * 10, $response);
			}
		}

		if (is_array($response)) {
			return response()->json($response);
		}

		// TODO Fidelo-Signet als Fallback?
		return response('Not found', 404);
	}

	private function authentication(\Access_Backend $access, Request $request, bool $isViewRequest)
	{
		$session = $this->session();

		$authentication = new \Admin\Service\Auth\Authentication($access, $session);
		$redirect = $authentication->handleRequest($request, $isViewRequest);

		$authenticationAddon = (new \Admin\Factory\Auth\AuthenticationAddon($access))->getAddon($session);

		if ($authenticationAddon !== null) {
			$authentication->setAddon($authenticationAddon);
			$redirect = $authentication->handleAddonRequest($request, $isViewRequest);
		}

		if ($redirect instanceof RedirectResponse) {
			// TODO unsauber
			return $redirect;
		}

		return $authentication;
	}

	public function logout(\Access_Backend $access)
	{
		$access->logout();

		$this->session()->getFlashBag()->add('success', Admin::translate('Sie wurden erfolgreich ausgeloggt.', 'Login'));

		return $this->toLogin();
	}

	public function forgot()
	{
		return Inertia::render('ForgotPassword', [
			'messages' => $this->buildMessagesFromSession($this->session()),
			'l10n' => [
				'field' => [
					'email' => Admin::translate('Bitte geben Sie hier Ihre E-Mail-Adresse ein', 'Login'),
				],
				'btn' => [
					'submit' => Admin::translate('Passwort neu anfordern', 'Login'),
					'cancel' => Admin::translate('Abbrechen', 'Login'),
				]
			],
		]);
	}

	public function requestNewPassword(Request $request, RoutingService $router)
	{
		if (empty($email = $request->input('email'))) {
			$this->session()->getFlashBag()->add('errors', Admin::translate('Bitte geben Sie eine E-Mail-Adresse an', 'Login'));
			return redirect(\Core\Helper\Routing::generateUrl('Admin.forgot'));
		}

		$user = \User::query()->where('email', $email)->first();

		if ($user) {

			$existingToken = \DB::getRowData('system_user_password_resets', $user->id, 'user_id');

			if (!empty($existingToken)) {
				$token = $existingToken['token'];
			} else {
				do {
					$token = \Util::generateRandomString(32);

					$insertToken = ['user_id' => $user->id, 'token' => $token];

					try {
						\DB::insertData('system_user_password_resets', $insertToken);
						$added = true;
					} catch (\Exception $e) {
						$added = false;
					}
				} while ($added === false);
			}

			$link = $router->generateUrl('Admin.forgot.request', ['token' => $token]);

			// Die Route kann bereits die Domain beinhalten
			if (strpos($link, 'http') === false) {
				$link = \System::d('domain') . $link;
			}

			$projectLink = \System::d('domain') . '/admin/';

			$variables = [
				'sProjectName' => \System::d('project_name'),
				'sFirstname' => $user->firstname,
				'sLastname' => $user->lastname,
				'sEmail' => $user->email,
				'sForgotPasswordLink' => $link,
				'sProjectLink' => $projectLink
			];

			// E-Mail zusammenstellen und senden
			$email = new \Admin\Helper\Email('Admin');
			$success = $email->send('forgot_password', [$user->email], $variables);

			if (!$success) {
				\Log::getLogger()->error('Cannot send email to reset password', ['user' => $user->id]);
			}

		}

		$this->session()->getFlashBag()->add('success', Admin::translate('Falls ein Benutzer mit der eingegebenen E-Mail-Adresse existiert, haben wir einen Bestätigungslink an die E-Mail-Adresse gesendet.', 'Login'));

		return $this->toLogin();
	}

	public function reset(Request $request)
	{
		$token = $request->input('token');

		if (null === $this->validateToken($token)) {
			$this->session()->getFlashBag()->add('errors', Admin::translate('Der Link ist nicht mehr gültig!', 'Login'));
			return $this->toLogin();
		}

		return Inertia::render('ResetPassword', [
			'messages' => $this->buildMessagesFromSession($this->session()),
			'l10n' => [
				'field' => [
					'pw1' => Admin::translate('Bitte wählen Sie ein neues Passwort', 'Login'),
					'pw2' => Admin::translate('Passwort wiederholen', 'Login'),
				],
				'btn' => [
					'submit' => Admin::translate('Neues Passwort speichern', 'Login'),
					'cancel' => Admin::translate('Abbrechen', 'Login'),
				],
				'password' => [
					'strength' => [
						'very_weak' => Admin::translate('Ganz schwach', 'Login'),
						'weak' => Admin::translate('Schwach', 'Login'),
						'sufficient' => Admin::translate('Ausreichend', 'Login'),
						'good' => Admin::translate('Gut', 'Login'),
						'very_good' => Admin::translate('Sehr gut', 'Login'),
					]
				]
			],
			'token' => strip_tags($token)
		])->rootView('auth.password_strength');
	}

	public function resetPassword(Request $request)
	{
		if (null === $token = $this->validateToken($request->input('token'))) {
			$this->session()->getFlashBag()->add('errors', Admin::translate('Der Link ist nicht mehr gültig!', 'Login'));
			return $this->toLogin();
		}

		if (
			empty($password = $request->input('password')) ||
			empty($confirmation = $request->input('password_confirmation'))
		) {
			$this->session()->getFlashBag()->add('errors', Admin::translate('Bitte füllen Sie beide Felder aus.', 'Login'));
			return $this->toPasswordReset($token['token']);
		}

		if ($password !== $confirmation) {
			$this->session()->getFlashBag()->add('errors', Admin::translate('Die Passwörter stimmen nicht überein.', 'Login'));
			return $this->toPasswordReset($token['token']);
		}

		$user = \User::query()->findOrFail($token['user_id']);

		$strength = (new \ZxcvbnPhp\Zxcvbn())->passwordStrength($password, [
			$user->username,
			$user->firstname,
			$user->lastname,
			$user->email
		]);

		if ($strength['score'] < \System::getMinPasswordStrength()) {
			$this->session()->add('errors', Admin::translate('Das Passwort ist nicht sicher genug!', 'Login'));
			return $this->toPasswordReset($token);
		}

		$user->setPassword($password);
		$user->save();

		$this->session()->getFlashBag()->add('success', Admin::translate('Das Passwort wurde erfolgreich aktualisiert.', 'Login'));

		return $this->toLogin();
	}

	private function alreadyLoggedIn(\Access_Backend $access): bool
	{
		$hasAccess = false;
		if (
			CookieHandler::is($access->getPassCookieName()) &&
			CookieHandler::is($access->getUserCookieName())
		) {
			$hasAccess = $access->checkSession(CookieHandler::get($access->getUserCookieName()), CookieHandler::get($access->getPassCookieName()));
		}

		if ($hasAccess && $access->checkValidAccess()) {
			return true;
		}

		return false;
	}

	private function validateToken(?string $token)
	{
		if (empty($token)) {
			return null;
		}

		return \DB::table('system_user_password_resets')
			->where('token', $token)
			->first();
	}

	private function toLogin()
	{
		return redirect(
			\Core\Helper\Routing::generateUrl('Admin.login')
		);
	}

	private function toPasswordReset(string $token)
	{
		return redirect(
			\Core\Helper\Routing::generateUrl('Admin.forgot.reset', ['token' => $token])
		);
	}
}