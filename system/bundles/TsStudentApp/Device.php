<?php

namespace TsStudentApp;

class Device {

	const ANDROID = 'Android';

	const IOS = 'iOS';

	public $id;

	public $version;

	public $os;

	public $appId;

	public $additional;

	public function __construct(string $os, string $version, string $appId) {
		$this->os = $os;
		$this->version = $version;
		$this->appId = $appId;
	}

	public function isAndroid(): bool {
		return ($this->os === self::ANDROID);
	}

	public function isIOS(): bool {
		return ($this->os === self::IOS);
	}

	public function getLoginDevice(\Ext_TS_Inquiry_Contact_Login $login): ?\Ext_TS_Inquiry_Contact_Login_Device {
		return \Ext_TS_Inquiry_Contact_Login_Device::query()
			->where('login_id',  $login->getId())
			->where('app_id', $this->appId)
			->first();
	}
}
