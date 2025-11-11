<?php

use Core\Traits\WdBasic\Notifiable;
use Core\Traits\WdBasic\MetableTrait;
use Illuminate\Support\Collection;

class User extends WDBasic
{

	use Notifiable, MetableTrait;

	const AUTH_SIMPLE = 'simple';
	const AUTH_AUTHENTICATOR_APP = 'googletwofactor';
	const AUTH_PASSKEYS = 'passkeys';
	const AUTH_PASSKEYS_EXTERN = 'passkeys_extern';

	protected $_sTable = 'system_user';
	protected $_sTableAlias = 'su';

	protected $_sTempOrginalPW = null;

	protected $_aFormat = array(
		'password' => array(
			'format' => 'PASSWORD',
		),
		'email' => array(
			'unique' => 1,
			'required' => true,
			'validate' => 'MAIL'
		),
		'username' => array(
			'unique' => 1
		),
		'birthday' => array(
			'validate' => 'DATE_PAST'
		),
	);

	protected $_aJoinedObjects = [
		'passkeys' => [
			'class' => \Admin\Entity\User\Passkey::class,
			'key' => 'user_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	];

	protected $_aJoinTables = [
		'devices' => [
			'table' => 'system_user_devices',
			'foreign_key_field' => ['device_id', 'last_login', 'login_count', 'created'],
			'primary_key_field' => 'user_id',
			'autoload' => false
		]
	];

	protected $_aRights = null;

	protected function _loadData($iDataID)
	{

		parent::_loadData($iDataID);

		// Username muss IMMER gefüllt sein, das wird beim Login zur eindeutigen Identifizierung verwendet.
		if (empty($this->_aData['username'])) {
			$this->_aData['username'] = Util::generateRandomString(32);
		}

	}

	public function __wakeup()
	{
		$this->_sTempOrginalPW = null;
	}

	/**
	 * If the Password was set with an empty value, dont change it!
	 */
	public function __set($sName, $sValue)
	{

		// Ein leeres Passwort darf nicht gespeichert werden
		if (
			$sName === 'password' &&
			empty($sValue)
		) {

		} else {

			if ($sName === 'password') {
				$this->_sTempOrginalPW = $sValue;
			}

			parent::__set($sName, $sValue);
		}

	}

	public function __get($sName)
	{

		if ($sName == 'password') {
			$sValue = '';
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}

	public static function getList()
	{
		$sSQL = "
			SELECT * 
			FROM `system_user` 
			WHERE `active` = 1
		";
		$aUsers = DB::getQueryData($sSQL);

		return $aUsers;
	}

	public function unblockLogin()
	{

		$sSql = "
			UPDATE
				`system_user`
			SET
				`changed` = `changed`,
				`blocked_until` = 0,
				`login_failed` = 0
			WHERE
				`id` = :id
			LIMIT
				1
		";
		$aSql = array(
			'id' => (int)$this->id
		);

		DB::executePreparedQuery($sSql, $aSql);

	}

	public function getRights()
	{

		$aUser = $this->aData;

		if ($this->_aRights === null) {

			$aRights = array();

			// Diese Funktion schreibt alle Rechte des Users mit der ID 'id'
			// in das Multi-Array $user_data
			//  aus der Role
			$temp_role = $this->role;
			if (is_numeric($temp_role)) {

				$aRoleRights = (array)DB::getQueryRows("SELECT * FROM `system_rights` r1, system_roles2rights r2, system_roles r3 WHERE r1.id = r2.right_id AND r2.role_id = r3.id AND r3.id = " . (int)$temp_role . "");

				foreach ($aRoleRights as $aRoleRight) {
					$aRights[$aRoleRight['right']] = 1;
				}

			}

			//  aus der ext_role
			$temp_ext_role = $this->ext_role;
			$ext_role_array = explode("|", $temp_ext_role);

			for ($i = 0; $i < count($ext_role_array); $i++) {
				if ($ext_role_array[$i] != "|") {
					if (is_numeric($ext_role_array[$i])) {

						$aRoleRights = (array)DB::getQueryRows("SELECT * FROM  `system_rights` r1, system_roles2rights r2, system_roles r3 WHERE r1.id = r2.right_id AND r2.role_id = r3.id AND r3.id = " . (int)$ext_role_array[$i] . "");
						foreach ($aRoleRights as $aRoleRight) {
							$aRights[$aRoleRight['right']] = 1;
						}
					}
				}
			}

			//  aus access
			$access = $this->access;
			$access_array = explode("|", $access);
			for ($i = 0; $i < count($access_array); $i++) {
				if ($access_array[$i] != "") {
					$aRights[$access_array[$i]] = 1;
				}
			}

			//  aus access_denied
			$access_denied = $this->access_denied;
			$access_denied_array = explode("|", $access_denied);
			for ($i = 0; $i < count($access_denied_array); $i++) {
				if ($access_denied_array[$i] != "") {
					$aRights[$access_denied_array[$i]] = 0;
				}
			}

			$this->_aRights = $aRights;

		}

		return $this->_aRights;

	}

	protected function _prepareArrayListByOptions($sCacheKey, $bForSelect = false, $sNameField = 'name', $bIgnorePosition = false)
	{

		$aArrayList = (array)self::$_aArrayListCache[$sCacheKey];
		$aBack = array();

		if (
			$bForSelect
		) {
			foreach ($aArrayList as $aData) {
				$aBack[$aData['id']] = $aData['firstname'] . ' ' . $aData['lastname'];
			}
		} else {
			$aBack = $aArrayList;
		}

		if (
			$bForSelect &&
			(
				!array_key_exists('position', $this->_aData) ||
				$bIgnorePosition
			)
		) {
			asort($aBack);
		}

		return $aBack;
	}

	public function getName()
	{

		$sName = $this->firstname . ' ' . $this->lastname;

		return $sName;
	}

	public function getInitials(): string
	{

		if (!empty($this->firstname) && !empty($this->lastname)) {
			$initials = substr($this->firstname, 0, 1) . substr($this->lastname, 0, 1);
		} else if (!empty($this->firstname)) {
			$initials = substr($this->firstname, 0, 2);
		} else {
			$initials = substr($this->lastname, 0, 2);
		}

		return strtoupper($initials);
	}

	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * Gibt die Benutzerrechte zurück
	 *
	 * @return array
	 */
	public function getUserRights()
	{
		return [];
	}

	/**
	 * Setzt ein neues Passwort an __set und validate vorbei
	 *
	 * @param string $sPassword
	 */
	public function setPassword(string $sPassword)
	{

		$this->_aData['password'] = password_hash($sPassword, PASSWORD_DEFAULT);

	}

	public function getPasswordHash()
	{
		return $this->_aData['password'];
	}

	public function getPasskeys(): array
	{
		return $this->getJoinedObjectChilds('passkeys', true);
	}

	public function getDevices(): Collection
	{
		$deviceIds = array_column($this->devices, 'device_id');

		$devices = \Admin\Entity\Device::query()->whereIn('id', $deviceIds)->get()
			->map(function ($device) {
				$joinData = \Illuminate\Support\Arr::first($this->devices, fn ($data) => $data['device_id'] == $device->id);
				return [$device, \Illuminate\Support\Arr::except($joinData, ['user_id', 'device_id'])];
			});

		return $devices;
	}

	public function getStandardDevice(): ?\Admin\Entity\Device
	{
		$devices = \Illuminate\Support\Arr::sort($this->devices, fn ($joinData) => $joinData['login_count']);

		if (!empty($devices)) {
			return \Admin\Entity\Device::getInstance($devices[0]['device_id']);
		}

		return null;
	}

	public function getAdditional(string $sKey)
	{

		$aAdditional = Util::decodeSerializeOrJson($this->additional);

		if (isset($aAdditional[$sKey])) {
			return $aAdditional[$sKey];
		}
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function setAdditional(string $sKey, $mValue)
	{

		$aAdditional = Util::decodeSerializeOrJson($this->additional);

		if (!is_array($aAdditional)) {
			$aAdditional = [];
		}

		$aAdditional[$sKey] = $mValue;

		$this->additional = Util::encodeJson($aAdditional);

		$this->save();

	}

	static public function getAuthenticationMethods()
	{

		$aMethods = [
			'simple' => L10N::t('Einfache Authentifizierung'),
			'googletwofactor' => L10N::t('Zwei-Faktor-Authentifizierung'),
			'passkeys' => L10N::t('Passkeys'),
		];

		return $aMethods;
	}

	public function save()
	{

		if ($this->_aOriginalData['authentication'] !== $this->aData['authentication']) {
			$this->secret = '';
		}

		return parent::save();
	}

	public function validate($bThrowExceptions = false)
	{

		$mValidate = parent::validate($bThrowExceptions);

		// Wenn sonst alles gut und ein neues PW per __set gesetzt wurde
		if (
			$mValidate === true &&
			$this->_sTempOrginalPW !== null
		) {

			// Passwortstärke checken
			$aUserData = [
				$this->username,
				$this->firstname,
				$this->lastname,
				$this->email
			];

			$oZxcvbn = new \ZxcvbnPhp\Zxcvbn();
			$aStrength = $oZxcvbn->passwordStrength($this->_sTempOrginalPW, $aUserData);

			if ($aStrength['score'] < \System::getMinPasswordStrength()) {
				$mValidate = [
					$this->_sTableAlias . '.password' => [
						'WEAK_PASSWORD'
					]
				];
			}

		}

		return $mValidate;
	}

	public function getInterfaceColorScheme(): ?\Admin\Enums\ColorScheme
	{
		if (!empty($setting = $this->getMeta('admin.color_scheme'))) {
			return \Admin\Enums\ColorScheme::from($setting);
		}

		return null;
	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'database' => \Core\Entity\System\UserNotification::query(),
			'mail' => [$this->email, $this->getName()],
			default => null,
		};
	}

}
