<?php

namespace Core\Middleware\Auth;

use Admin\Entity\Device;
use Admin\Service\Auth\Authentication;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

/**
 * TODO wird aktuell noch nicht global benutzt, nur in vereinzelten Bundles
 */
readonly class BackendAuth extends AbstractAuth
{
	public function __construct(
		private Container $app,
		private \Access_Backend $access
	) {}

	public function handle(Request $request, \Closure $next, $right = null)
	{
		// Login über Cookie-Daten
		$hasAccess = $this->checkCookieSession($this->access);

		if(
			$hasAccess &&
			$this->access->checkValidAccess() === true
		) {
			$this->access->saveAccessData();

			$userData = array();
			$this->access->reworkUserData($userData);

			$hasRight = true;
			// Prüfen ob ein bestimmtes Recht abgefragt werden soll (z.b. control)
			if ($right !== null) {
				$hasRight = $this->access->hasRight($right);
			}

			if($hasRight && $this->isTrustedDevice($this->access->getUser())) {
				return $next($request);
			}
		}

		return $this->unauthenticated($request, $this->access);
	}

	protected function redirectTo(): string
	{
		return \Core\Helper\Routing::generateUrl('Admin.login');
	}

	private function isTrustedDevice(\User $user): bool
	{
		if (!empty(\System::d('system_update_locked_by'))) {
			// Während des Updates nicht prüfen
			return true;
		}

		if (!empty($user->getDevices())) {
			if (empty([$device, $payload] = Authentication::readDeviceFromCookie())) {
				return false;
			};

			$this->app->instance(Device::class, $device);

			return $device->isTrustedBy($user);
		}

		// TODO für das 3.0.0 Update mal sicherheitshalber auf true lassen
		return true;
	}

}
