<?php

namespace TsTeacherLogin\Handler;

use TsTeacherLogin\Handler\Message\Email;
use TsTeacherLogin\Handler\Message\Sms;
use TsTeacherLogin\Handler\Message\App;

class Message {

	protected $oMessage;

	public function __construct(string $sType) {

		switch($sType) {
			case 'email':

				$this->oMessage = new Email();

				break;
			case 'sms':

				$this->oMessage = new Sms();

				break;
			case 'app':

				$this->oMessage = new App();

				break;
		}

	}

	public function setRequest(\MVC_Request $oRequest) {

		$this->oMessage->setRequest($oRequest);

	}

	public function setTeacher(\Ext_Thebing_Teacher $oTeacher) {

		$this->oMessage->setTeacher($oTeacher);

	}

	public function send() {

		$dReturn = $this->oMessage->send();

		return $dReturn;
	}
	
	public function getErrorMessages() {
		return $this->oMessage->getErrorMessages();
	}

}