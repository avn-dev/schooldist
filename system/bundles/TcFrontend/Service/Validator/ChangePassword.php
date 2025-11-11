<?php

namespace TcFrontend\Service\Validator;

/**
 * Class ChangePassword
 *
 * Validierungsklasse des Prozess "Passwort ändern"
 *
 * @package TsAgencyLogin\Service
 */
class ChangePassword extends Validator {

	/**
	 * Neues Passwort
	 *
	 * @var string
	 */
	private $sNewPassword = '';

	/**
	 * Neues Passwort wiederholt
	 *
	 * @var string
	 */
	private $sNewPasswordRepeat = '';

	/**
	 * Sicherheitsstatus
	 *
	 * @var string
	 */
	private $sSecurityStatus = '';

	/**
	 * @inheritDoc
	 */
	protected function validate() {

		if(empty($this->sNewPassword)) {
			$this->appendErrorWithKey('password_repeat', 'Das Passwort muss eingegeben werden!');
		}

		if(empty($this->sNewPasswordRepeat)) {
			$this->appendErrorWithKey('new_password_repeat', 'Das Passwort muss wiederholt werden!');
		}

		if($this->sNewPassword !== $this->sNewPasswordRepeat) {
			$this->appendError('Die Passwörter sind nicht identisch!');
		}

		if(!empty($this->sNewPassword)) {
			$bCheck = \Ext_TC_Util::validPass($this->sNewPassword, $this->sSecurityStatus);
			if(!$bCheck) {
				$this->appendError(\Ext_TC_Frontend_Messages::ERROR_PASSWORDSECURE);
			}
		}

	}

	/**
	 * @param string $sNewPassword
	 * @return void
	 */
	public function setNewPassword($sNewPassword) {
		$this->sNewPassword = $sNewPassword;
	}

	/**
	 * @param string $sNewPasswordRepeat
	 * @return void
	 */
	public function setNewPasswordRepeat($sNewPasswordRepeat) {
		$this->sNewPasswordRepeat = $sNewPasswordRepeat;
	}

	/**
	 * @param string $sSecurityStatus
	 * @return void
	 */
	public function setSecurityStatus($sSecurityStatus) {
		$this->sSecurityStatus = $sSecurityStatus;
	}
}