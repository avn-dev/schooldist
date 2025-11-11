<?php

namespace TsTeacherLogin\Handler\Message;

abstract class AbstractMessage {

	/**
	 * @var \MVC_Request
	 */
	protected $oRequest;
	protected $oTeacher;

	protected $aErrors = [];

	public function setRequest(\MVC_Request $oRequest) {

		$this->oRequest = $oRequest;

	}

	public function setTeacher(\Ext_Thebing_Teacher $oTeacher) {

		$this->oTeacher = $oTeacher;

	}

	abstract public function send();

	public function getErrorMessages() {
		return $this->aErrors;
	}

}