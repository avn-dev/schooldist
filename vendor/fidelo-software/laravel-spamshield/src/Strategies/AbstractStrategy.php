<?php

namespace FideloSoftware\Spam\Strategies;

use Illuminate\Http\Request;
use FideloSoftware\Spam\Contracts\Form;

abstract class AbstractStrategy {

	/**
	 * @var array
	 */
	protected $log = [];

	/**
	 * Define strategy actions on form initialization
	 *
	 * @param Form $form
	 * @param Request $request
	 * @return void
	 */
	public function onload(Form $form, Request $request): void {}

	/**
	 * Define HTML parts of the strategy
	 *
	 * @param Form $form
	 * @return string
	 */
	public function html(Form $form): string { return ''; }

	/**
	 * Clean up after spam detection is no longer needed
	 *
	 * @param Form $form
	 * @return void
	 */
	public function cleanUp(Form $form): void {}

	/**
	 * Log information during spam detection
	 *
	 * @param array $info
	 * @return $this
	 */
	protected function info(array $info) {
		$this->log = array_merge($this->log, $info);
		return $this;
	}

	/**
	 * Get collected information of the spam detection
	 *
	 * @return array
	 */
	public function getInfo(): array {
		return $this->log;
	}

	/**
	 * Check given form and request object on spam
	 *
	 * @param Form $form
	 * @param Request $request
	 * @return bool
	 */
	abstract public function detect(Form $form, Request $request): bool;

}
