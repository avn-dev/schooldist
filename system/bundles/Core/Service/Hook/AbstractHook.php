<?php

namespace Core\Service\Hook;

/**
 * @method run
 */
abstract class AbstractHook {
	
	const BACKEND = 'backend';
	
	const FRONTEND = 'frontend';
	
	protected $sInterface;
	
	protected $sHook;
	
	public function __construct(string $sInterface, string $sHook) {
		$this->sInterface = $sInterface;
		$this->sHook = $sHook;
	}

}

