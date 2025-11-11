<?php

namespace Core\Exception\ParallelProcessing;

/**
 * Mit dieser Exception wird der Task erneut ins ParallelProcessing eintragen.
 *
 * TypeHandler::getRewriteAttempts() steuert dabei die maximale Anzahl an Versuchen.
 * @see \Core\Handler\ParallelProcessing\TypeHandler::getRewriteAttempts()
 */
class RewriteException extends \Exception {

	/**
	 * @var int
	 */
	private $iRewriteAttempts;

	/**
	 * @internal
	 */
	public function setRewriteAttempts($iAttempts) {
		$this->iRewriteAttempts = $iAttempts;
	}

	/**
	 * @internal
	 */
	public function getRewriteAttempts() {
		return $this->iRewriteAttempts;
	}

}
