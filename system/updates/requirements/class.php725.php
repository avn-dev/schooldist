<?php

class Updates_Requirements_PHP725 extends Requirement {

	public function checkSystemRequirements() {

		// Laravel 7
		if (version_compare(PHP_VERSION, '7.2.5', '<')) {
			$this->_aErrors[] = 'Please update your PHP version to 7.2.5 at least.';
			return false;
		}

		return true;

	}

}