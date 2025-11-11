<?php

namespace Ts\Controller;

/**
 * Class SchoolController
 * @package Ts\Controller
 */
class SchoolController extends \MVC_Abstract_Controller {

	/**
	 * Methode darf nicht set() heißen
	 *
	 * @param $iSchoolId
	 */
	public function setSchool($iSchoolId) {

		$oSchoolIdHandler = new \Ts\Handler\SchoolId();

		$oSchoolIdHandler->setSchool($iSchoolId);

		$this->redirect('Admin.admin', [], false);

	}
	
}
