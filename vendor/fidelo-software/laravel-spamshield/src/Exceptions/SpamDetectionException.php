<?php

namespace FideloSoftware\Spam\Exceptions;

use FideloSoftware\Spam\Strategies\AbstractStrategy;

class SpamDetectionException extends \RuntimeException {

	private $strategy;

	public function strategy(AbstractStrategy $strategy) {
		$this->message = 'Spam detected by strategy "'.get_class($strategy).'"';
		$this->strategy = $strategy;
		return $this;
	}

	public function getStrategy(): ?AbstractStrategy {
		return $this->strategy;
	}

}
