<?php

namespace Admin\Entity\User;

class Passkey extends \WDBasic
{
	protected $_sTable = 'system_user_passkeys';

	protected $_sTableAlias = 'supk';

	protected $_aFormat = [
		'last_login' => ['format' => 'TIMESTAMP'],
	];

	public function getUser(): \User
	{
		return \Factory::getInstance(\User::class, $this->user_id);
	}
}