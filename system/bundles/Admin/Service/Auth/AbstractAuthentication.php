<?php

namespace Admin\Service\Auth;

use Admin\Notifications\NewDeviceNotification;
use Carbon\Carbon;
use Core\Handler\CookieHandler as Cookie;
use Core\Handler\SessionHandler as Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

abstract class AbstractAuthentication {

	const COOKIE_NAME = 'device';

	/**
	 * @var \Access_Backend 
	 */
	protected $oAccess;
	
	/**
	 * @var Session 
	 */
	protected $oSession;

	protected $aViewValues = [];
	
	/**
	 * @var \Log
	 */
	protected $oLog;


	/**
	 * @param \Access_Backend $oAccess
	 * @param Session $oSession
	 */
	public function __construct(\Access_Backend &$oAccess, Session $oSession) {
		
		$this->oAccess = &$oAccess;
		$this->oSession = $oSession;
		
		$this->oLog = \Log::getLogger('authenticator');
		
	}

	public function getViewValues(): array {
		return $this->aViewValues;
	}

	/**
	 * Zwischenschritt zwischen Prüfung der Login-Daten und tatsächlichem Login
	 *
	 * \Access::checkValidAccess() muss vorher aufgerufen werden!
	 *
	 * @param Request $oRequest
	 */
	protected function handleLogin(Request $oRequest) {

		$bExecuteLogin = true;
		$this->aViewValues['existing_session'] = false;

		$currentUsers = $this->oAccess->getCurrentUsers();

		// Prüfen, ob Session für diesen User bereits aktiv ist
		if (!$this->oAccess->isMultiLogin() && isset($currentUsers[$this->oAccess->getAccessUser()])) {
			// Temporären Login speichern, damit Formular nicht nochmal angezeigt wird
			$this->saveTmpAccess();

			if (!$oRequest->has('overwrite_session')) {

				$this->aViewValues['existing_session'] = true;
				$bExecuteLogin = false;

			} else {

				// Cancel-Button
				if($oRequest->has('cancel')) {
					$this->removeTmpAccess();
					// Nicht auf logout weiterleiten, da ansonsten mehr gelöscht wird als darf
					return redirect(
						\Core\Helper\Routing::generateUrl('Admin.login')
					);
				}

				\Access_Backend::setLogoutInfo($this->oAccess->getAccessUser(), 'overwrite', [
					'ip' => $oRequest->ip(),
					'browser' => \Core\Helper\Agent::getInfo()
				]);

			}
		}

		if($bExecuteLogin) {
			return $this->executeLogin($oRequest);
		}

	}
	
	/**
	 * Login ausführen: User-Session gültig und andere mögliche Session wird beendet
	 */
	protected function executeLogin(Request $request) {

		$this->removeTmpAccess();

		$this->oAccess->saveAccessData();

		$aUserData = array();
		$this->oAccess->reworkUserData($aUserData);

		// Lifetime zurücksetzen, falls vorhanden
		$this->oAccess->setLifetime();

		if($this->oAccess->hasRight('control')) {

			$this->checkTrustedDeviceAfterLogin($request);

			return redirect(
				\Core\Helper\Routing::generateUrl('Admin.index')
			);
		}

	}
	
	/**
	 * Erfolgreichen Login zwischenspeichern für Zwischenschritt (z.B. 2-Faktor-Auth)
	 */
	protected function saveTmpAccess() {

		// Access-Objekt in Session temporär speichern
		$this->oAccess->setTmpLifetime();
		$this->oSession->set('admin_access', $this->oAccess);

	}

	/**
	 * Temporären Login (saveTmpAccess) entfernen
	 */
	protected function removeTmpAccess() {

		$this->oSession->remove('admin_access');

	}

	/**
	 * @param \MVC_View_Smarty $oView
	 */
	protected function prepareView(\MVC_View_Smarty $oView) {

		$oView->set('bExistingSession', $this->aViewValues['existing_session'] ?? false);

	}

	private function checkTrustedDeviceAfterLogin(Request $request): void
	{
		if(!$this->oAccess->hasRight('control')) {
			return;
		}

		$logger = $this->oAccess::getLogger();

		$user = $this->oAccess->getUser();

		$issuedAt = Carbon::now();
		$expiresAt = $issuedAt->clone()->addDays(30);

		$deviceName = \Core\Helper\Agent::getDeviceName($request->userAgent());

		if (!empty([$device, $payload] = static::readDeviceFromCookie())) {

			$payload['exp'] = $expiresAt->getTimestamp();

			$logger->info('Known device', ['user' => $user->id, 'device' => $device->id, 'new_expires_at' => $expiresAt->toDateTimeString()]);

		} else {
			$payload = [
				'iat' => $issuedAt,
				'exp' => $expiresAt->getTimestamp(),
				'dev' => bin2hex(random_bytes(32))
			];

			$device = new \Admin\Entity\Device();
			$device->device_token = $payload['dev'];
			$device->ip = $request->ip();

			$logger->info('New device', ['user' => $user->id, 'device' => $device->id, 'expires_at' => $expiresAt->toDateTimeString()]);
		}

		// Cookie setzen / verlängern
		Cookie::set(self::COOKIE_NAME, Crypt::encryptString(json_encode($payload)), $expiresAt->getTimestamp());

		$device->name = $deviceName;
		$device->user_agent = $request->userAgent();
		$device->expires_at = $expiresAt->toDateTimeString();

		// Falls der Benutzer bereits andere Devices registriert hat, dieses aber noch nicht, soll eine Benachrichtigung
		// versendet werden
		$sendNotification = !empty($user->devices) && !$device->isTrustedBy($user);

		$device
			->registerUserLogin($user, $issuedAt)
			->save();

		if ($sendNotification) {
			$user->notifyNow((new NewDeviceNotification($device, $user))->queue(1), ['admin-mail']);
			$logger->warning('New device for user', ['user' => $user->id, 'device' => $device->id]);
		}
	}

	public static function readDeviceFromCookie(): ?array
	{
		if (empty($cookieValue = Cookie::get(self::COOKIE_NAME))) {
			return null;
		}

		try {
			$decrypted = Crypt::decryptString($cookieValue);
		} catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
			return null;
		}

		if (empty($payload = json_decode($decrypted, true))) {
			return null;
		}

		if ($payload['exp'] < time()) {
			return null;
		}

		$device = \Admin\Entity\Device::query()
			->where('device_token', $payload['dev'])
			->where('expires_at', '>', \Carbon\Carbon::now())
			->first();

		if (!$device) {
			return null;
		}

		return [$device, $payload];
	}

	/**
	 * @param Request $oRequest
	 */
	abstract public function handleRequest(Request $oRequest, bool $isViewRequest = true): ?RedirectResponse;
	
}
	