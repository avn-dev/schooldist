<?php

use Core\Handler\SessionHandler as Session;
use Admin\Entity\User\Passkey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Serializer\SerializerInterface;

class Access_Backend extends Access {

	protected $_bExecuteLogin = true;

	protected $_sAccessPasskey = null;

	protected ?bool $_bMultiLogin = null;

	const LOG_LOGIN_SUCCESSFUL = 'admin/login-successful';
	const LOG_LOGIN_FAILED = 'admin/login-failed';
	const LOG_USER_LOCKED = 'admin/user-locked';
	const LOG_WRONG_PASSWORD = 'admin/wrong-password';
	const LOG_PASSWORD_DISABLED_DUE_PASSKEYS = 'admin/password-disabled-due-passkey';
	const LOG_UNKNOWN_USER = 'admin/unknown-user';

	const PASSKEY_RPID = 'fidelo.com';

	public function getPasskeyPrefix(): string
	{
		if (empty($prefix = \System::d('auth.passkey.prefix'))) {
			// Eindeutiger Key um zu ermitteln zu welcher Installation der Passkey gehört
			\System::s('auth.passkey.prefix', $prefix = Str::random(20));
		}
		return $prefix;
	}

	/**
	 * Prüft, ob der User eingeloggt ist
	 */
	public function checkSession($sAccessUser, $sAccessPass)
	{
		// Prüfung bei Frontend-User abbrechen
		if (str_starts_with($sAccessUser, 'customer_db_')) {
			return false;
		}

		$aAccess = $this->_checkAccess($sAccessUser, $sAccessPass);

		// Prüft, ob Abfrage erfolgreich
		if (!empty($aAccess)) {

			$user = $this->userQuery()
				->where(function ($query) use ($aAccess) {
					$query->where('username', $aAccess['username'])
						->orWhere('email', $aAccess['username']);
				})
				->first();

			if (!empty($user)) {

				$this->_prepareUserData($user, $this->_sAccessPasskey);

				$this->_bValidAccess = true;

				return true;
			}

		} else {

			$this->_sLastMessage = "Ihre Sessiondaten sind nicht mehr aktuell. Bitte loggen Sie sich erneut ein.";

		}

		return false;

	}

	protected function _checkAccess($sAccessUser, $sAccessPass)
	{
		if ($this->_bMultiLogin === null && !empty($sAccessUser)) {
			$user = $this->userQuery()->where('username', $sAccessUser)->first();
			if (!empty($user)) {
				$this->_bMultiLogin = (bool)$user['multi_login'];
			}
		}

		return parent::_checkAccess($sAccessUser, $sAccessPass);
	}

	public function reworkUserData(&$aUserData)
	{

//		$sCacheKey = 'access_backend_rework_'.$this->_aUserData['data']['id'];
//
//		$aCacheData = WDCache::get($sCacheKey);
//
//		if($aCacheData === null) {

		// check functions of user
		$aStatus = $this->_oDb->queryRow("SELECT * FROM system_roles WHERE id = " . (int)$this->_aUserData['data']['role']);

		// end check functions
		$this->_aUserData['group'] = $aStatus['id'];
		$this->_aUserData['groupname'] = $aStatus['name'];
		$this->_aUserData['id'] = $this->_aUserData['data']['id'];
		$this->_aUserData['email'] = $this->_aUserData['data']['email'];
		$this->_aUserData['tabData'] = explode('|', $this->_aUserData['data']['tab_data']);
		$this->_aUserData['toolbar_size'] = $this->_aUserData['data']['toolbar_size'];
		$this->_aUserData['toolbar_titles'] = $this->_aUserData['data']['toolbar_titles'];
		$this->_aUserData['cookie'] = true;

		if (
			$this->_aUserData['toolbar_size'] != 32 &&
			$this->_aUserData['toolbar_size'] != 64
		) {
			$this->_aUserData['toolbar_size'] = 64;
		}

		if ($this->_aUserData['data']['username']) {
			$name = $this->_aUserData['data']['username'];
		}

		$this->_aUserData['additional'] = Util::decodeSerializeOrJson($this->_aUserData['data']['additional']);
		$this->_aUserData['name'] = $name;
		$this->_aUserData['lastname'] = $this->_aUserData['data']['lastname'];
		$this->_aUserData['firstname'] = $this->_aUserData['data']['firstname'];
		$this->_aUserData['completename'] = $this->_aUserData['data']['firstname'] . " " . $this->_aUserData['data']['lastname'];

		$columns = array_keys((new \User())->getData());
		// Workaround für PHP52 weil es da kein natives get_called_class gibt
		$oUser = User::getObjectFromArray(\Illuminate\Support\Arr::only($this->_aUserData['data'], $columns));

		$this->_aUserData['rights'] = $oUser->getRights();

		$aUserData = $this->_aUserData;

		// execute hooks to modify Backend User Data
		\System::wd()->executeHook('user_data_backend', $this->_aUserData);

		$aUserData = $this->_aUserData;

//			// 5 Minuten cachen
//			WDCache::set($sCacheKey, (5*60), $aUserData);
//
//		} else {
//
//			$aUserData = $aCacheData;
//			$this->_aUserData['rights'] = $aUserData['rights'];
//
//		}

	}

	protected function _prepareUserData($aUser, string $passkeyId = null)
	{

		$this->_sAccessUser = $aUser['username'];
		$this->_sAccessPasskey = $passkeyId;

		$this->_bMultiLogin = (bool)$aUser['multi_login'];

		// Da im späteren Verlauf getObjectFromArray benutzt wird dürfen auch nur diese Werte drin stehen
		$this->_aUserData['data'] = $aUser;
		$this->_aUserData['passkey'] = $passkeyId;
		$this->_aUserData['cms'] = true;

		$key = [$aUser['id']];
		if (!empty($passkeyId)) {
			$key[] = $passkeyId;
		}

		// Eindeutigen Key für den Login generieren damit zb. bei einem Multi-Login die korrekten Gui2-Sessions gelöscht werden
		// können und nicht global für die user-Id
		$this->_aUserData['key'] = implode('_', $key);

	}

	public function isMultiLogin(): bool
	{
		return $this->_bMultiLogin;
	}

	public function getAccessPasskey(): ?string
	{
		return $this->_sAccessPasskey;
	}

	/**
	 * @param array $aVars
	 * @return bool
	 */
	public function executeLogin($aVars, Session $session = null)
	{
		if ($this->_bExecuteLogin === false) {
			return false;
		}

		\System::wd()->executeHook('login_check', $aVars);

		if (!empty($aVars['username']) && !empty($aVars['password'])) {

			return $this->executeUsernamePasswortLogin($aVars['username'], $aVars['password'], (bool)$aVars['force']);

		} else if ($session && !empty($aVars['passkey'])) {

			[$success, ] = $this->executePasskeyLogin($session, $aVars['passkey'], $aVars['host'], $aVars['username'], (bool)$aVars['force']);

			return $success;

		} else {
			$this->_sLastErrorCode = 'Einloggen fehlgeschlagen! Es wurden nicht alle Felder ausgef&uuml;llt.';
		}

		return false;
	}

	public function generatePasskeyChallenge(Session|callable $store, string $host, string $username = null, bool $forceLocal = false): array
	{
		$logger = self::getLogger('Passkey');
		$user = null;

		if (!empty($username)) {
			$user = $this->userQuery()
				->whereIn('authentication', [\User::AUTH_PASSKEYS, \User::AUTH_PASSKEYS_EXTERN])
				->where(function ($query) use ($username) {
					$query->where('username', $username)
						->orWhere('email', $username);
				})
				->first();

			if (!$user) {
				throw (new ModelNotFoundException)->setModel(\Factory::getClassName(\User::class), [$username]);
			}
		}

		$options = $storePayload = null;

		if (!$forceLocal && (!$user || $user['authentication'] === \User::AUTH_PASSKEYS_EXTERN)) {
			// Wenn nicht explizit ein Benutzer angegeben wurde oder wenn der Benutzer sowieso extern authentifiziert werden
			// muss dann immer eine Challenge auf dem externen Server generieren und lokal als Challenge speichern damit
			// auf beiden Systemen die Challenge im Store existiert
			$operation = new \Licence\Service\Office\Api\Object\Auth\PasskeyChallenge($host);
			$response = (new \Licence\Service\Office\Api)->request($operation);

			// Auf isSuccessful() ist nicht immer Verlass
			if ($response->isSuccessful() && !empty($challenge = $response->get('challenge'))) {

				$options = self::getWebauthnSerializer()->deserialize($challenge, \Webauthn\PublicKeyCredentialRequestOptions::class, 'json');

				$storePayload = ['type' => 'extern', 'key' => $response->get('key')];

				$logger->info('Extern passkey challenge', ['username' => $username, 'host' => $host, 'key' => $response->get('key')]);

			} else if ($user) {
				// Wenn man schon weiß dass der User auf jeden Fall eine externe Authentifizierung braucht kann hier
				// abgebrochen werden da der Login eh fehlschlagen würde
				throw new \RuntimeException('Server passkey challenge failed.');
			} else {
				$logger->error('Failed to create extern passkey challenge', ['username' => $username, 'host' => $host, 'response' => $response->all(), 'status_code' => $response->getHttpStatus()]);
			}
		}

		if (!$storePayload) {
			// Falls der externe Server nicht erreicht werden kann eine lokale Challenge als Fallback erstellen damit
			// die lokalen Benutzer sich trotzdem einloggen können
			$options = \Webauthn\PublicKeyCredentialRequestOptions::create(
				challenge: random_bytes(32),
				rpId: self::PASSKEY_RPID
			);

			$logger->info('Local passkey challenge', ['username' => $username, 'host' => $host]);

			$storePayload = ['type' => 'local'];
		}

		$json = self::getWebauthnSerializer()->serialize(
			$options,
			'json',
			[
				\Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
				\Symfony\Component\Serializer\Encoder\JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
			]
		);

		$storePayload['options'] = $json;

		if ($store instanceof Session) {
			$store->getFlashBag()->set('webauthn.passkey.authentication.options', $storePayload);
			// Nur zum debuggen!
			// $store->set('webauthn.passkey.authentication.options', $storePayload);
		} else {
			$store($storePayload);
		}

		return [$options, $json];
	}

	private function executeUsernamePasswortLogin(string $username, string $password, bool $force = false): bool
	{
		$aUser = $this->userQuery()
			->selectRaw('IF(`supk`.`id` > 0, 1, 0) AS `has_passkeys`')
			->leftJoin('system_user_passkeys as supk', function ($join) {
				$join->on('supk.user_id', '=', 'su.id')
					->where('supk.active', '=', 1);
			})
			->where(function ($query) use ($username) {
				$query->where('su.username', $username)
					->orWhere('su.email', $username);
			})
			->groupBy('su.id')
			->first();

		if (!empty($aUser)) {

			if (
				// Extern immer über Passkeys gehen
				$aUser['authentication'] === \User::AUTH_PASSKEYS_EXTERN ||
				// Sobald ein Passkey existiert ist der Login über Passwort gesperrt
				($aUser['authentication'] === \User::AUTH_PASSKEYS && $aUser['has_passkeys'] > 0)
			) {
				Log::add(self::LOG_PASSWORD_DISABLED_DUE_PASSKEYS, null, null, ['username' => $aUser['username']]);

				$this->_sLastErrorCode = 'Einloggen fehlgeschlagen! Die Anmeldung per Passwort wurde deaktiviert. Bitte nutzen Sie eine alternative Anmeldemethode.';

				self::getLogger()->info('Password login disabled', ['id' => $aUser['id'], 'ip' => $_SERVER['REMOTE_ADDR']]);

				return false;
			}

			// Altes MD5 Format
			if (strpos($aUser['password'], '$') !== 0) {

				$bPasswordVerify = ($aUser['password'] === md5($password));

				// Convert into new format
				if ($bPasswordVerify === true) {

					$aUpdateUser = [
						'password' => password_hash($password, PASSWORD_DEFAULT)
					];
					DB::updateData('system_user', $aUpdateUser, '`id` = ' . (int)$aUser['id']);

					self::getLogger()->info('Old md5 password updated', ['id' => $aUser['id'], 'ip' => $_SERVER['REMOTE_ADDR']]);

				}

			} else {
				$bPasswordVerify = password_verify($password, $aUser['password']);
			}

			if (
				$bPasswordVerify === true &&
				!$aUser['blocked']
			) {

				/* @var \User $user */
				$user = Factory::getInstance(\User::class, $aUser['id']);

				$loginSuccess = $this->login($user, $force);

				return $loginSuccess;

			} else {

				if (!empty($aUser)) {
					$this->handleUserLoginFailed($aUser, self::getLogger());
				}

				$aUser = NULL;

			}

		} else {

			Log::add(self::LOG_UNKNOWN_USER, null, null, ['username' => Util::convertHtmlEntities($username)]);
			$this->setInvalidLoginDataError();

			self::getLogger()->error('User not found', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);

		}

		return false;
	}

	public function executePasskeyLogin(Session|callable $store, string $passkeyJson, string $host, string $username = null, bool $force = false, $externLogin = false): array
	{
		$logger = self::getLogger('Passkey');

		try {
			$serializer = self::getWebauthnSerializer();

			/** @var \Webauthn\PublicKeyCredential $publicKeyCredential */
			$publicKeyCredential = $serializer->deserialize($passkeyJson, \Webauthn\PublicKeyCredential::class, 'json');

			if (!$publicKeyCredential->response instanceof \Webauthn\AuthenticatorAssertionResponse) {
				throw new \RuntimeException('Passkey: invalid public key credential response.');
			}

			$query = $this->userQuery();

			if (!$externLogin) {
				// TODO nach Update auf v3.0.0 immer einbinden (Fidelo-Benutzer haben noch keine Passkey-Einstellung)
				$query->whereIn('su.authentication', [\User::AUTH_PASSKEYS, \User::AUTH_PASSKEYS_EXTERN]);
			}

			if (!empty($username)) {
				$query->where(function ($query) use ($username) {
					$query->where('su.username', $username)
						->orWhere('su.email', $username);
				});
			} else if (
				!empty($userHandle = $publicKeyCredential->response->userHandle)
			) {
				[$prefix, $id] = explode('::', $userHandle, 2);

				if (empty($prefix) || empty($id)) {
					$logger->error('Invalid passkey user handle', ['user_handle' => $userHandle, 'ip' => $_SERVER['REMOTE_ADDR']]);
					$this->setInvalidLoginDataError();

					return [false, null];
				}

				if ($prefix === $this->getPasskeyPrefix()) {
					// Lokaler Login
					$query->where('su.id', $id);
				} else {
					// Support Login(?)
					$query->where('su.username', 'support');
				}
			} else {
				throw new \RuntimeException('Passkey: invalid public key credential response');
			}

			$userArray = $query->first();

			if (!$userArray) {

				$this->setInvalidLoginDataError();

				if (!empty($username)) {
					Log::add(self::LOG_UNKNOWN_USER, null, null, ['username' => Util::convertHtmlEntities($username)]);
					$logger->error('User not found', ['username' => $username, 'extern' => $externLogin, 'ip' => $_SERVER['REMOTE_ADDR']]);
				} else {
					Log::add(self::LOG_UNKNOWN_USER, null, null, ['passkey_user_handle' => Util::convertHtmlEntities($publicKeyCredential->response->userHandle)]);
					$logger->error('User not found', ['passkey_user_handle' => $publicKeyCredential->response->userHandle, 'extern' => $externLogin, 'ip' => $_SERVER['REMOTE_ADDR']]);
				}

				return [false, null];
			}

			if (!$userArray['blocked']) {

				if ($store instanceof Session) {
					$storePayload = $store->getFlashBag()->get('webauthn.passkey.authentication.options');
					// Nur zum debuggen!
					// $storePayload = $store->get('webauthn.passkey.authentication.options');
				} else {
					$storePayload = $store();
				}

				if (empty($storePayload)) {
					$logger->error('Missing store payload', ['user' => $userArray['id'], 'extern' => $externLogin, 'ip' => $_SERVER['REMOTE_ADDR']]);
					throw new \RuntimeException('Missing store payload');
				}

				$user = $localPasskey = $passkeyId = null;

				if (!$externLogin && $userArray['authentication'] === \User::AUTH_PASSKEYS_EXTERN) {

					// Externe Passkey-Authentifizierung

					$logger->info('Extern passkey login', ['user' => $userArray['id'], 'ip' => $_SERVER['REMOTE_ADDR']]);

					if ($storePayload['type'] !== 'extern') {
						throw new \RuntimeException('Invalid session payload');
					}

					// FIDELO
					$operation = new \Licence\Service\Office\Api\Object\Auth\PasskeyVerify($storePayload['key'], $host, $passkeyJson);
					$response = (new \Licence\Service\Office\Api)->request($operation);

					if (!$response->isSuccessful() || empty($externPasskeyId = $response->get('passkey_id'))) {

						if (!empty($error = $response->get('error')) && is_string($error)) {
							$this->_sLastErrorCode = $error;
						}

						return [false, null];
					}

					$user = \Factory::getInstance(\User::class, $userArray['id']);
					$passkeyId = sprintf('extern:%d', $externPasskeyId);

				} else {

					// Lokale Passkey-Authentifizierung

					$logger->info('Local passkey login', ['user' => $userArray['id'], 'extern' => $externLogin, 'ip' => $_SERVER['REMOTE_ADDR']]);

					if (empty($credentialId = bin2hex($publicKeyCredential->rawId))) {
						$this->setInvalidLoginDataError();
						return [false, null];
					}

					/* @var Passkey $passkey */
					$localPasskey = Passkey::query()
						->where('user_id', $userArray['id'])
						->where('credential_id', $credentialId)
						->first();

					if ($localPasskey) {
						$ceremonyStepManager = (new \Webauthn\CeremonyStep\CeremonyStepManagerFactory())->requestCeremony();

						$validator = \Webauthn\AuthenticatorAssertionResponseValidator::create($ceremonyStepManager);
						//$validator->setLogger($logger);

						$challenge = self::getWebauthnSerializer()->deserialize($storePayload['options'], \Webauthn\PublicKeyCredentialRequestOptions::class, 'json');

						$publicKeyCredentialSource = $validator->check(
							publicKeyCredentialSource: $serializer->deserialize($localPasskey->data, \Webauthn\PublicKeyCredentialSource::class, 'json'),
							authenticatorAssertionResponse: $publicKeyCredential->response,
							publicKeyCredentialRequestOptions: $challenge,
							host: $host,
							userHandle: sprintf('%s::%d', $this->getPasskeyPrefix(), $userArray['id']),
						);

						if ($publicKeyCredentialSource instanceof \Webauthn\PublicKeyCredentialSource) {
							$user = $localPasskey->getUser();
							$passkeyId = $localPasskey->id;
						}
					} else {
						$logger->error('Passkey not found', ['user' => $userArray['id'], 'extern' => $externLogin, 'ip' => $_SERVER['REMOTE_ADDR']]);
					}

				}

				if ($user && ($externLogin || $this->login($user, $force, $passkeyId))) {
					if ($localPasskey) {
						$localPasskey->last_login = date('Y-m-d H:i:s');
						$localPasskey->save();
					}

					return [true, $localPasskey];
				}
			}

		} catch (\Throwable $e) {

			if (str_contains(strtolower($e::class), 'webauthn')) {
				$logger->error('Passkey login failed', ['message' => $e->getMessage()]);
			} else {
				$logger->error('Passkey login failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace(), 'ip' => $_SERVER['REMOTE_ADDR']]);
				throw $e;
			}

		}

		$this->handleUserLoginFailed($userArray, $logger);

		return [false, null];
	}

	public static function getWebauthnSerializer(): SerializerInterface
	{
		// https://webauthn-doc.spomky-labs.com/v5.2/pure-php/input-loading#attestation-statement-support-manager
		$attestationStatementSupportManager = \Webauthn\AttestationStatement\AttestationStatementSupportManager::create();
		// Keine Attestation verlangen, d.h. allen Public-Keys vertrauen (Alternative: nur bestimmte zertifizierte Authenticator-Modelle akzeptieren)
		$attestationStatementSupportManager->add(\Webauthn\AttestationStatement\NoneAttestationStatementSupport::create());

		/** @var \Webauthn\PublicKeyCredential $publicKeyCredential */
		$serializer = (new \Webauthn\Denormalizer\WebauthnSerializerFactory($attestationStatementSupportManager))->create();

		return $serializer;
	}

	private function userQuery(): \Core\Database\Query\Builder
	{
		return \DB::table('system_user as su')
			->select('su.*')
			->selectRaw('IF(`su`.`blocked_until` > NOW(), 1, 0) AS `blocked`')
			->where('su.active', 1)
			->where('su.status', 1);
	}

	private function handleUserLoginFailed(array $user, \Psr\Log\LoggerInterface $logger)
	{
		$loginFailed = $user['login_failed'] + 1;

		if (!$user['blocked'] && $loginFailed > 3) {
			$loginFailed = 1;
		}

		$sql = "
			UPDATE
				`system_user`
			SET
				`login_failed` = :login_failed,
				`changed` = `changed`
			WHERE
				`id` = :id
			LIMIT
				1
		";

		$this->_oDb->preparedQuery($sql, ['id' => (int)$user['id'], 'login_failed' => $loginFailed]);

		if ($loginFailed >= 3) {

			$time = time() + 3600;

			$sql = "
				UPDATE
					`system_user`
				SET
					`blocked_until` = '" . date('Y-m-d H:i:s', $time) . "',
					`changed` = `changed`
				WHERE
					`id` = " . (int)$user['id'] . "
				LIMIT
					1
			";

			$this->_oDb->query($sql);

			Log::add(self::LOG_USER_LOCKED, null, null, ['id' => $user['id']]);

			$this->_sLastErrorCode = 'Einloggen fehlgeschlagen! Der Benutzer wurde nach drei fehlgeschlagenen Loginversuchen gesperrt.';

			$logger->error('User locked', ['id' => $user['id'], 'ip' => $_SERVER['REMOTE_ADDR']]);

		} else {

			Log::add(self::LOG_WRONG_PASSWORD, null, null, ['username' => $user['username']]);

			$this->setInvalidLoginDataError();

			$logger->error('Wrong password', ['id' => $user['id'], 'ip' => $_SERVER['REMOTE_ADDR']]);

		}

	}

	/**
	 * Speichert die Zugriffsinfos in der Access-Tabelle
	 * Aktualisiert, wenn schon vorhanden und legt neu an, wenn noch nicht vorhanden.
	 *
	 * @param int|null $iValid
	 * @param array $additionalData
	 * @param string $sAccessPass
	 */
	public function saveAccessData($iValid = null, array $additionalData = [])
	{
		parent::saveAccessData($iValid, ['passkey_id' => $this->_sAccessPasskey]);

		// Aktive User
		$aUsers = (array)WDCache::get('access_backend_users');
		$aUsers[$this->_sAccessUser] = time();
		WDCache::set('access_backend_users', 60 * 60, $aUsers);
	}

	protected function getAccessData() {
		$accessData = parent::getAccessData();

		if (!empty($accessData['passkey_id'])) {
			$this->_sAccessPasskey = $accessData['passkey_id'];
		}

		return $accessData;
	}

	public function deleteAccessData()
	{
		parent::deleteAccessData();

		$this->_sAccessPasskey = null;

		if (!empty($this->_sAccessUser)) {
			$aUsers = (array)WDCache::get('access_backend_users');
			unset($aUsers[$this->_sAccessUser]);
			WDCache::set('access_backend_users', 60 * 60, $aUsers);
		}
	}

	protected function _getCacheKey()
	{
		$sCacheKey = static::getCacheKey($this->_sAccessUser);

		if ($this->_bMultiLogin) {
			// $this->_sAccessPass sollte ein zufällig generierter String sein
			$sCacheKey .= '_' . $this->_sAccessPass;
		}

		return $sCacheKey;
	}

	/**
	 * @param string $sAccessUser
	 * @return string
	 */
	public static function getCacheKey(string $sAccessUser)
	{
		return parent::getCacheKey('backend_' . $sAccessUser);
	}

	static public function checkAccess($mRight, $bDie = true)
	{
		$oInstance = Access::getInstance();

		if (
			$oInstance instanceof self &&
			$oInstance->checkValidAccess()
		) {
			$bHasRight = $oInstance->hasRight($mRight);

			if ($bHasRight === true) {
				return true;
			}

		}

		if ($bDie === true) {
			die('No access');
		}

		return false;
	}

	public function hasRight($mRight)
	{
		if ($this->_bValidAccess !== true) {
			throw new Exception('No access');
		}

		$aRight = array();
		$aRight['right'] = $mRight;
		$aRight['has_right'] = false;
		$aRight['return'] = false;

		System::wd()->executeHook('has_right', $aRight);

		if (
			$aRight['has_right'] == true ||
			$aRight['return'] == true
		) {
			return (bool)$aRight['has_right'];
		}

		$mRight = (array)$mRight;

		foreach ($mRight as $sRight) {
			if (
				is_scalar($sRight) &&
				isset($this->_aUserData['rights'][$sRight]) &&
				$this->_aUserData['rights'][$sRight] == 1
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Aktuell eingeloggte User, die in den letzten 2 Minuten aktiv waren (30-Sekunden-Ping und jeder Request)
	 *
	 * Rückgabe: ["username" => TIMESTAMP]
	 *
	 * @return array
	 */
	public static function getCurrentUsers()
	{
		$aUsers = (array)WDCache::get('access_backend_users');

		$aUsers = array_filter($aUsers, function ($iTime, $sUsername) {

			// Nach zwei Stunden Inaktivität rausschmeißen
			if ($iTime < (time() - (60 * 60 * 2))) {
				$oLog = Log::getLogger('access');
				$oLog->info('Auto logout', [$sUsername]);
				\WDCache::delete(Access_Backend::getCacheKey($sUsername), true);
				return false;
			}

			return true;
		}, ARRAY_FILTER_USE_BOTH);

		// Bereinigte Daten abspeichern
		WDCache::set('access_backend_users', 60 * 60, $aUsers);

		return $aUsers;
	}

	/**
	 * Gibt ein Array mit den aktuell eingeloggten Usern zurück
	 *
	 * @return array
	 */
	public static function getActiveUser()
	{

		$users = array();

		$aUsers = self::getCurrentUsers();

		$aUsernames = array_keys($aUsers);

		$sSql = "
				SELECT 
					u.username as name,
					u.id as userid,
					r.name as role,
					CONCAT(
						u.`firstname`,
						' ',
						u.`lastname`
					) `user`
				FROM 
					system_user u JOIN
					system_roles r ON
						u.role = r.id
				WHERE 
					`username` IN (:users)
		";
		$aSql = array(
			'users' => $aUsernames
		);
		$aActiveUsers = DB::getQueryRows($sSql, $aSql);

		if (is_array($aActiveUsers)) {
			foreach ($aActiveUsers as $my) {
				$my['last_action'] = $aUsers[$my['name']];
				$my['ftime'] = strftime("%x %X", $aUsers[$my['name']]);
				$users[] = $my;
			}
		}

		// Sortieren absteigend
		usort($users, function ($aA, $aB) {
			return $aB['last_action'] - $aA['last_action'];
		});

		return $users;
	}

	public function generateSecret()
	{
		$oUser = $this->getUser();

		if (empty($oUser->secret)) {

			$oGoogleAuthenticator = new \Google\Authenticator\GoogleAuthenticator();
			$sSecret = $oGoogleAuthenticator->generateSecret();
			$oUser->secret = $sSecret;
			$oUser->save();

			$this->_aUserData['data']['secret'] = $sSecret;

		}

		return $oUser->secret;
	}

	/**
	 * @return User
	 */
	public function getUser()
	{
		$oUser = Factory::getInstance('User', $this->_aUserData['data']['id']);
		return $oUser;
	}

	static public function getUserRoles($iUserId)
	{
		global $session_data;

		if (!isset($session_data['user_roles'][$iUserId])) {

			$oUser = new \User($iUserId);

			$aRoles = array();
			$aRoles[] = $oUser->role;
			$sExtRoles = $oUser->ext_role;

			$aExtRoles = explode("|", $sExtRoles);

			$aRoles = array_merge((array)$aRoles, (array)$aExtRoles);

			$session_data['user_roles'][$iUserId] = $aRoles;

		}

		return $session_data['user_roles'][$iUserId];

	}

	protected function checkSystemUpdateStatus($aUser, $bForce)
	{
		if (
			!empty(\System::d('system_update_locked_by')) &&
			\System::d('system_update_locked_by') != $aUser['id']
		) {

			//Wenn force existiert, den Nutzer, der das Update ausführt, rausschmeißen und durch den aktuellen(Support) ersetzen
			if ($bForce === true) {

				$oUser = \User::getInstance((int)\System::d('system_update_locked_by'));

				\WDCache::delete(Access_Backend::getCacheKey($oUser->username), true);
				\System::s('system_update_locked_by', $aUser['id']);

			} else {

				$this->_sLastErrorCode = "Das System ist gesperrt bis das Update abgeschlossen ist!";
				return true;
			}
		}

		return false;
	}

	/**
	 * Der rausgeworfene User bekommt über einen Toastr diese Infos
	 *
	 * @param string $sUsername
	 * @param string|null $sReason
	 * @param array $aInfo
	 */
	static public function setLogoutInfo(string $sUsername, string $sReason, array $aInfo = [])
	{

		$aInfo['reason'] = $sReason;
		\WDCache::set('access_logout_' . $sUsername, 60 * 15, $aInfo);

	}

	public function login(User $user, bool $force = false, string $passkeyId = null)
	{
		$userData = $user->getData();

		$bValidLicense = System::checkValidLicense($userData);

		if ($bValidLicense === true) {

			$bUpdateRunning = $this->checkSystemUpdateStatus($userData, $force);

			if ($bUpdateRunning) {
				return false;
			}

			// Blocked ist kein DB Feld und muss daher weg.
			$this->_prepareUserData($userData, $passkeyId);

			$this->_bValidAccess = true;

			$sSql = "
				UPDATE
					`system_user`
				SET
					`blocked_until` = 0,
					`login_failed` = 0,
					`changed` = `changed`,
					`lastlogin` = NOW()
				WHERE
					`id` = " . (int)$user->id . "
			";
			DB::executeQuery($sSql);

			// Login Hook, nach erfolgreiches Login
			\System::wd()->executeHook('login_ok', $this);

			Log::add(self::LOG_LOGIN_SUCCESSFUL);

			self::getLogger()->info('Login succeeded', ['id' => $user->id, 'ip' => $_SERVER['REMOTE_ADDR'], 'passkey' => $passkeyId]);

			return true;

		} else {

			Log::add(self::LOG_LOGIN_FAILED);
			$this->_sLastErrorCode = "Sie haben keinen Zugang! Ihr Lizenzschl&uuml;ssel ist ung&uuml;ltig!";

			self::getLogger()->error('License not valid', ['id' => $user->id, 'ip' => $_SERVER['REMOTE_ADDR']]);

		}

		return false;
	}

	public function setInvalidLoginDataError()
	{
		// TODO nicht so schön
		$this->_sLastErrorCode = 'Einloggen fehlgeschlagen! Es wurden falsche Daten eingegeben.';
	}

}
