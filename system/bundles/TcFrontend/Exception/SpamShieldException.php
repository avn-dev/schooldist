<?php

namespace TcFrontend\Exception;

use TcFrontend\Spam\Strategy\AbstractStrategy;

class SpamShieldException extends \RuntimeException {

	private $strategy;

	public function bindStrategy(AbstractStrategy $strategy) {
		$this->strategy = $strategy;
		return $this;
	}

}
