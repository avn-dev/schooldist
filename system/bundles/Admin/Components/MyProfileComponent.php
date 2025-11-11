<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Entity\Device;
use Admin\Entity\User\Passkey;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Admin\Notifications\NewPasskeyNotification;
use Admin\Service\Auth\Authentication;
use Core\Exception\ValidatorException;
use Core\Factory\ValidatorFactory;
use Core\Handler\SessionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class MyProfileComponent implements VueComponent
{
	const KEY = 'myProfile';

	public function __construct(
		private \Access_Backend $access,
		private Instance $admin
	) {}

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('MyProfile', '@Admin/components/MyProfile.vue');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$user = $this->access->getUser();

		/* @var \Ext_Gui2_View_Format_Date_Time $dateFormat */
		$dateFormat = \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class);
		$authMethods = \User::getAuthenticationMethods();

		[$currentDevice, $payload] = Authentication::readDeviceFromCookie();

		return (new InitialData([
				'user' => Arr::only($user->getData(), [
					'firstname', 'lastname', 'email', 'phone', 'sex', 'birthday',
					'street', 'city', 'zip', 'country',
					'authentication'
				]),
				'sexes' => [
					['value' => 0, 'text' => $this->translate('Männlich')],
					['value' => 1, 'text' => $this->translate('Weiblich')],
				],
				'authentications' => collect($authMethods)
					->map(fn ($label, $key) => ['value' => $key, 'text' => $label])
					->values(),
				'passkeys' => collect($user->getPasskeys())
					->map(fn (Passkey $passkey) => ['value' => $passkey->id, 'text' => $passkey->name, 'created' => $dateFormat->formatByValue($passkey->created), 'last_login' => $dateFormat->formatByValue($passkey->last_login)])
					->values(),
				'devices' => $user->getDevices()
					->map(fn (array $device) => [
						'value' => $device[0]->id,
						'text' => $device[0]->name,
						'current' => (int)$currentDevice?->id === (int)$device[0]->id,
						'last_login' => $dateFormat->formatByValue($device[1]['last_login']),
						'standard' => $device[0]->isStandardDeviceForUser($user),
						'created' => $dateFormat->formatByValue($device[1]['created']),
					])
					->values(),
			]))
			->l10n([
				'my_profile.tab.profile' => $this->translate('Mein Profil'),
				'my_profile.tab.security' => $this->translate('Sicherheit'),
				'my_profile.label.firstname' => $this->translate('Vorname'),
				'my_profile.label.lastname' => $this->translate('Nachname'),
				'my_profile.label.email' => $this->translate('E-Mail'),
				'my_profile.label.phone' => $this->translate('Telefonnummer'),
				'my_profile.label.sex' => $this->translate('Geschlecht'),
				'my_profile.label.birthday' => $this->translate('Geburtstag'),
				'my_profile.label.authentication' => $this->translate('Authentifizierung'),
				'my_profile.label.authentication.passkeys' => $this->translate('Authentifizierung'),
				'my_profile.label.authentication.passkeys.empty' => sprintf($this->translate('Um die Authentifizierung "%s" benutzen zu können müssen Sie mindestens einen Schlüssel erstellen. Andernfalls erfolgt der Login weiterhin mittels Passwort.'), $authMethods['passkeys'] ?? ''),
				'my_profile.label.authentication.passkeys.existing' => sprintf($this->translate('Sie haben erfolgreich einen Passkey erstellt. Eine Anmeldung mit dem herkömmlichen Passwort ist nicht mehr möglich.'), $authMethods['passkeys'] ?? ''),
				'my_profile.label.authentication.passkeys.extern' => $this->translate('Die Authentifizierung läuft über einen externen Server. Sie können daher keine Änderungen machen.'),
				'my_profile.label.authentication.passkeys.new' => $this->translate('Neuen Passkey erstellen'),
				'my_profile.label.street' => $this->translate('Straße'),
				'my_profile.label.zip' => $this->translate('PLZ'),
				'my_profile.label.city' => $this->translate('Stadt'),
				'my_profile.label.country' => $this->translate('Land'),
				'my_profile.label.submit' => $this->translate('Meine Daten ändern'),
				'my_profile.label.current_password' => $this->translate('Aktuelles Passwort'),
				'my_profile.label.password' => $this->translate('Neues Passwort'),
				'my_profile.label.password_repeat' => $this->translate('Passwort wiederholen'),
				'my_profile.label.password_submit' => $this->translate('Neues Passwort speichern'),
				'my_profile.address.heading' => $this->translate('Adresse'),
				'my_profile.devices.heading' => $this->translate('Ihre Geräte'),
				'my_profile.devices.description' => $this->translate('Alle Geräte, über die jemals ein Zugriff auf dieses Konto erfolgt ist.'),
				'my_profile.device.current' => $this->translate('Dieses Gerät'),
				'my_profile.device.standard' => $this->translate('Standardgerät'),
				'my_profile.password.heading' => $this->translate('Neues Passwort'),
				'my_profile.password.description' => $this->translate('Bitte füllen Sie das Passwort-Feld nur aus, sofern Sie ein neues Passwort vergeben möchten.'),
				'my_profile.last_login' => $this->translate('Letzer Login'),
				'password.strength.very_week' => $this->translate('Ganz schwach'),
				'password.strength.week' => $this->translate('Schwach'),
				'password.strength.sufficient' => $this->translate('Ausreichend'),
				'password.strength.good' => $this->translate('Gut'),
				'password.strength.very_good' => $this->translate('Sehr gut'),
			]);
	}

	public function save(Request $request)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(), [
					'firstname' => 'required|string',
					'lastname' => 'required|string',
					'email' => 'required|email',
					'sex' => [Rule::in([0, 1])],
					'birthday' => 'date',
					'authentication' => ['required', Rule::in(array_keys(\User::getAuthenticationMethods()))],
					'password' => 'string',
					'password1' => 'required_with:password|string|same:password',
					'current_password' => 'required_with:password|string',
				],
				customAttributes: [
					'current_password' => $this->translate('Aktuelles Passwort'),
					'password' => $this->translate('Passwort'),
					'password1' => $this->translate('Passwort wiederholen'),
				]
			);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$user = $this->access->getUser();
		$user->firstname = $validated['firstname'];
		$user->lastname = $validated['lastname'];
		$user->email = $validated['email'];
		$user->sex = (int)$validated['sex'];
		$user->birthday = $validated['birthday'];
		$user->authentication = $validated['authentication'];
		$user->street = $validated['street'];
		$user->city = $validated['city'];
		$user->zip = $validated['zip'];
		$user->country = $validated['country'];

		if (is_array($entityValidate = $user->validate())) {
			// TODO
			return response()
				->json([
					'success' => false, 
					'messages' => [
						['type' => 'error', 'message' => $this->translate('Daten konnten nicht gespeichert werden')]
					]
				]);
		}

		if (!empty($newPassword = $validated['password'])) {
			$passwordVerify = password_verify($validated['current_password'], $user->getPasswordHash());

			if(!$passwordVerify) {
				return response()->json([
					'success' => false,
					'messages' => [
						['type'	=> 'error', 'message' => $this->translate('Das aktuelle Passwort ist nicht korrekt!')]
					]
				]);
			}

			$passwordStrength = (new \ZxcvbnPhp\Zxcvbn())
				->passwordStrength($newPassword, array_values(Arr::only($user->getData(), ['username', 'firstname', 'lastname', 'email'])));

			if($passwordStrength['score'] < \System::getMinPasswordStrength()) {
				return response()->json([
					'success' => false,
					'error' => 'WEAK_PASSWORD',
					'messages' => [
						['type'	=> 'error', 'message' => $this->translate('Das Passwort ist nicht sicher genug!')]
					]
				]);
			}

			$user->setPassword($newPassword);
		}

		$user->save();

		return response()->json([
			'success' => true,
			'messages' => [
				['type'	=> 'success', 'message' => $this->translate('Daten gespeichert')]
			]
		]);
	}

	public function createPasskey()
	{
		$user = $this->access->getUser();

		$options = \Webauthn\PublicKeyCredentialCreationOptions::create(
			rp: \Webauthn\PublicKeyCredentialRpEntity::create(
				name: sprintf('%s - %s', \System::d('project_name'), $this->translate('Administration')),
				// Die RP-ID darf nicht auf die Subdomain gehen damit der Support-Login funktioniert
				id: \Access_Backend::PASSKEY_RPID
			),
			user: \Webauthn\PublicKeyCredentialUserEntity::create(
				name: $user->email,
				// Das ist die ID welche nachher auch wieder in ->response->userHandle ankommt
				id: sprintf('%s::%d', $this->access->getPasskeyPrefix(), $user->id),
				displayName: $user->name,
			),
			challenge: random_bytes(16)
		);

		$json = $this->access::getWebauthnSerializer()->serialize(
			$options,
			'json',
			[
				AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
				JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
			]
		);

		// In der Session speichern um in der savePasskey() die Antwort validieren zu können
		SessionHandler::getInstance()->getFlashBag()->add('webauthn.passkey.registration.options', $json);
		// Nur zum debuggen!
		// SessionHandler::getInstance()->set('webauthn.passkey.registration.options', $json);

		return JsonResponse::fromJsonString($json);
	}

	public function savePasskey(Request $request)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(), [
				'passkey' => 'required|string',
			]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$user = $this->access->getUser();

		try {
			$serializer = $this->access::getWebauthnSerializer();

			/** @var \Webauthn\PublicKeyCredential $publicKeyCredential */
			$publicKeyCredential = $serializer->deserialize($validated['passkey'], \Webauthn\PublicKeyCredential::class, 'json');

			if (!$publicKeyCredential->response instanceof \Webauthn\AuthenticatorAttestationResponse) {
				throw new \RuntimeException('Passkey: invalid public key credential response.');
			}

			$json = SessionHandler::getInstance()->getFlashBag()->get('webauthn.passkey.registration.options')[0];
			// Nur zum debuggen!
			// $options = SessionHandler::getInstance()->get('webauthn.passkey.registration.options');

			if (empty($json)) {
				throw new \RuntimeException('Passkey: missing session registration object.');
			}

			$options = $serializer->deserialize($json, \Webauthn\PublicKeyCredentialCreationOptions::class, 'json');

			$creationCSM = (new \Webauthn\CeremonyStep\CeremonyStepManagerFactory())->creationCeremony();

			$publicKeyCredentialSource = \Webauthn\AuthenticatorAttestationResponseValidator::create($creationCSM)->check(
				authenticatorAttestationResponse: $publicKeyCredential->response,
				publicKeyCredentialCreationOptions: $options,
				host: \Access_Backend::PASSKEY_RPID,
			);
		} catch (\Throwable $e) {

			$this->admin->getLogger('User data')->error('Passkey creation failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);

			return response()->json([
				'success' => false,
				'error' => 'PASSKEY_VALIDATION_ERROR',
				'messages' => [
					['type'	=> 'error', 'message' => $this->translate('Beim Erstellen des Passkeys ist ein Fehler aufgetreten.')]
				]
			]);

		}

		$latestNumber = collect($user->getPasskeys())
			->filter(fn ($passkey) => preg_match('/^Passkey #\d+$/', $passkey->name))
			->map(fn ($passkey) => (int)str_replace('Passkey #', '', $passkey->name))
			->max();

		/* @var Passkey $passkey */
		$passkey = $user->getJoinedObjectChild('passkeys');
		$passkey->name = sprintf('Passkey #%d', $latestNumber + 1);
		$passkey->credential_id = bin2hex($publicKeyCredentialSource->publicKeyCredentialId);
		$passkey->data = $serializer->serialize($publicKeyCredentialSource, 'json');

		$user->authentication = \User::AUTH_PASSKEYS;
		$user->save();

		$client = ['ip' => $request->ip(), 'browser' => \Core\Helper\Agent::getInfo()];

		$this->admin->getLogger('User data')->info('Passkey created', ['user' => $user->id, 'passkey' => $passkey->id, ...$client]);

		$user->notifyNow((new NewPasskeyNotification($passkey, $client))->queue(1), ['admin-mail']);

		/* @var \Ext_Gui2_View_Format_Date_Time $dateFormat */
		$dateFormat = \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class);

		return response()->json([
			'success' => true,
			'passkey' => ['value' => $passkey->id, 'text' => $passkey->name, 'created' => $dateFormat->formatByValue($passkey->created)],
		]);
	}

	public function updatePasskey(Request $request)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(), [
				'passkey' => 'required|array',
				'passkey.value' => 'required|integer',
				'passkey.text' => 'required|string',
			]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$passkey = $this->access->getUser()->getJoinedObjectChild('passkeys', $validated['passkey']['value']);

		if ($passkey && $passkey->exist()) {
			$passkey->name = $validated['passkey']['text'];
			$passkey->save();

			return response()->json(['success' => true]);
		}

		return response()->json(['success' => false]);

	}

	public function deletePasskey(Request $request)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(), [
				'id' => 'required|integer'
			]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$passkey = $this->access->getUser()->getJoinedObjectChild('passkeys', $validated['id']);

		if ($passkey && $passkey->exist()) {
			$passkey->delete();
			return response()->json(['success' => true]);
		}

		return response()->json(['success' => false]);
	}

	public function deleteDevice(Request $request, Device $currentDevice)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($request->all(), [
				'id' => 'required|integer'
			]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		/* @var Device $device */
		$device = Device::query()->find($validated['id']);

		if (
			$device &&
			$currentDevice->id != $device->id &&
			!$device->isStandardDeviceForUser($this->access->getUser())
		) {
			$users = array_filter($device->users, fn($data) => $data['user_id'] != $this->access->getUser()->id);

			if (!empty($users)) {
				$device->users = $users;
				$device->save();
			} else {
				$device->delete();
			}

			return response()->json(['success' => true]);
		}

		return response()->json(['success' => false]);
	}

	private function translate(string $translate)
	{
		return $this->admin->translate($translate, 'User data');
	}
}