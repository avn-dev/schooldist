<?php

namespace TcExternalApps\Api\Operations;

use Licence\Service\Office\Api\AbstractObject;
use Licence\Service\Office\Api\Request;
use TcExternalApps\Interfaces\ExternalApp;

class UninstallApp extends AbstractObject {

	private $app;

	private $user;

	public function getRequestMethod() {
		return 'POST';
	}

	public function getUrl() {
		return '/customer/api/apps/uninstall';
	}

	public function __construct(ExternalApp $app, \User $user) {
		$this->app = $app;
		$this->user = $user;
	}

	public function prepareRequest(Request $request) {

		$request->add('app', $this->app->toArray());
		$request->add('user', $this->user->id);

	}

}
