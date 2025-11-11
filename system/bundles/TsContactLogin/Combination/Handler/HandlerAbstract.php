<?php

namespace TsContactLogin\Combination\Handler;

use TsContactLogin\Combination\Contact as ContactLogin;

/**
 * Base class for the tasks handlers of the contact login portal
 */

abstract class HandlerAbstract {

	/**
	 * @var ContactLogin
	 */
	protected ContactLogin $login;

	/**
	 * Constructor
	 * @param ContactLogin $login
	 */
	public function __construct(ContactLogin $login) {
		$this->login = $login;
		$this->handle();
	}

	/**
	 * Assigns smarty variables
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function assign(string $name, mixed $value): void {
		$this->login->assign($name, $value);
	}
	
	/**
	 * Initiates the handling
	 * @return void
	 */
	abstract protected function handle(): void;
}