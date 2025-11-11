<?php

namespace Gui2\Interfaces;

/**
 *
 *
 * Interface PostProcess
 * @package Gui2\Interfaces
 */
interface PostProcess {

	/**
	 * @param array $aResult
	 * @param array $aOptions
	 * @param \WDBasic $oEntity
	 * @return mixed
	 */
	public function execute(array $aResult, array $aOptions, \WDBasic $oEntity = null);

}