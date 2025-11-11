<?php

namespace Admin\Entity;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class Device extends \WDBasic
{
	protected $_sTable = 'system_trusted_devices';

	protected $_sTableAlias = 'std';

	protected $_aJoinTables = [
		'users' => [
			'table' => 'system_user_devices',
			'primary_key_field' => 'device_id',
			'foreign_key_field' => ['user_id', 'last_login'],
		]
	];

	public function isTrustedBy(\User $user): bool
	{
		$user = Arr::first($this->users, fn (array $data) => $data['user_id'] == $user->id);
		return $user !== null;
	}

	public function isStandardDeviceForUser(\User $user): bool
	{
		if ($this->isTrustedBy($user)) {
			return $user->getStandardDevice()->id == $this->id;
		}
		return false;
	}

	public function getLastLoginForUser(\User $user): ?\DateTimeInterface
	{
		$user = Arr::first($this->users, fn (array $data) => $data['user_id'] == $user->id);

		if ($user) {
			return Carbon::parse($user->last_login);
		}

		return null;
	}

	public function registerUserLogin(\User $user, \DateTimeInterface $loginAt): static
	{
		$lastLogin = Carbon::create($this->last_login);

		if ($loginAt > $lastLogin) {
			$this->last_login = $loginAt->format('Y-m-d H:i:s');
		}

		$allUsers = array_filter($this->users, fn (array $data) =>  $data['user_id'] != $user->id);

		$joinData = Arr::first($this->users, fn (array $data) => $data['user_id'] == $user->id);
		if (!$joinData) {
			$joinData = ['user_id' => $user->id, 'last_login' => null, 'created' => $loginAt->format('Y-m-d H:i:s')];
		}

		$joinData['login_count']++;

		if (!$joinData['last_login'] || $loginAt > Carbon::create($joinData['last_login'])) {
			$joinData['last_login'] = $loginAt->format('Y-m-d H:i:s');
		}

		$allUsers[] = $joinData;

		$this->users = $allUsers;

		return $this;
	}

}