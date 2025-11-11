<?php

namespace TcExternalApps\Api\Operations;

use Licence\Service\Office\Api\AbstractObject;
use Licence\Service\Office\Api\Request;
use TcExternalApps\Interfaces\ExternalApp;

class InstallApp extends AbstractObject {

	private $app;

	private $user;

	public function getRequestMethod() {
		return 'POST';
	}

	public function getUrl() {
		return '/customer/api/apps/install';
	}

	public function __construct(ExternalApp $app, \User $user) {
		$this->app = $app;
		$this->user = $user;
	}

	public function prepareRequest(Request $request) {

		$appData = ['key', 'title', 'price'];

		$request->add('app', array_intersect_key($this->app->toArray(), array_flip($appData)));
		$request->add('user', $this->user->id);

	}

}
