<?php

namespace TsApi\Exceptions;

use \Illuminate\Validation\Validator;

class ApiError extends \Exception {

	/**
	 * @var Validator
	 */
	private $oValidator;

	/**
	 * ApiError constructor.
	 * @param string $oMessage
	 * @param null $oValidator
	 */
	public function __construct($oMessage = '', $oValidator = null) {
		$this->oValidator = $oValidator;
		parent::__construct($oMessage);
	}

	/**
	 * @return Validator
	 */
	public function getValidator() {
		return $this->oValidator;
	}

}
