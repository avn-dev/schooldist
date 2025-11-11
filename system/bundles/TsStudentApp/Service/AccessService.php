<?php

namespace TsStudentApp\Service;

use Core\Helper\BundleConfig;
use TcFrontend\Service\Auth\Token;
use TsStudentApp\AppInterface;

class AccessService {

	/**
	 * @var \Access_Frontend
	 */
	private $access;
	/**
	 * @var BundleConfig
	 */
	private $bundleConfig;

	/**
	 * AccessService constructor.
	 *
	 * @param \Access_Frontend $access
	 * @param BundleConfig $bundleConfig
	 */
	public function __construct(\Access_Frontend $access, BundleConfig $bundleConfig) {
		$this->access = $access;
		$this->bundleConfig = $bundleConfig;
	}

	/**
	 * @return bool
	 */
	public function check(): bool {
		if (!\TcExternalApps\Service\AppService::hasApp(\TsStudentApp\Handler\ExternalApp::APP_NAME)) {
			return false;
		}
		return $this->access->checkValidAccess();
	}

	/**
	 * @return \Ext_TS_Inquiry_Contact_Login|null
	 */
	public function getUser(): ?\Ext_TS_Inquiry_Contact_Login {
		return \Ext_TS_Inquiry_Contact_Login::getRepository()
			->findOneBy(['id' => $this->access->id]);
	}

	/**
	 * Login
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool|string
	 */
	public function login(string $username, string $password)  {

		$this->access->checkManualLogin([
			'customer_login_1' => $username,
			'customer_login_3' => $password,
			'table_number' => $this->bundleConfig->get('auth.customer_db'),
			'loginmodul' => 1
		]);

		// Prüfen, ob die Eingabedaten richtig sind
		$success = $this->access->executeLogin();

		if($success) {
			return $this->generateAccessToken();
		}

		return $success;
	}

	/**
	 * Login über Zugangscode
	 *
	 * @param string $accessCode
	 * @return bool|string
	 */
	public function loginViaAccessCode(string $accessCode) {

		$this->access->checkDirectLogin($this->bundleConfig->get('auth.customer_db'), $accessCode);

		if(
			$this->access->checkExecuteLogin() === true &&
			$this->access->executeLogin() === true
		) {
			return $this->generateAccessToken();
		}

		return false;
	}

	/**
	 * Logout
	 */
	public function logout(): void {
		$this->access->destroyAccess();
	}

	/**
	 * @param string $email
	 * @return array|null
	 */
	public function generateAccessCode(string $email): ?array {

		if(\Util::checkEmailMx($email) !== false) {

			$user = $this->searchUserByEmail($email);

			if($user instanceof \Ext_TS_Inquiry_Contact_Login) {

				$accessCode = \Util::generateRandomString(6);
				$user->access_code = $accessCode;
				$user->save();

				return [
					'code' => $accessCode,
					'user' => $user
				];
			}
		}

		return null;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword(string $password): bool {

		$user = $this->getUser();

		if(
			$user && (
				!empty($user->password) &&
				// TODO in Methode in der Access_Frontend auslagern
				password_verify($password, $user->password)
			) || (
				!empty($user->access_code) &&
				$user->access_code === $password
			)
		) {
			return true;
		}

		return false;

	}

	/**
	 * @param string $newPassword
	 * @return bool
	 */
	public function changePassword(string $newPassword): bool {
		$user = $this->getUser();

		if (
			!$user ||
			$user->credentials_locked
		) {
			return false;
		}

		$user->password = $this->access->generatePasswordHash($newPassword);
		$user->access_code = '';
		$user->save();

		return true;
	}

	/**
	 * @return string
	 */
	private function generateAccessToken(): string {

		//$lifeTime = (int)$this->bundleConfig->get('auth.token_lifetime');
		$lifeTime = 0; // Eingeloggt bleiben

		$this->access->saveAccessData($lifeTime);

		return $this->getAccessToken();
	}

	/**
	 * @return string
	 */
	public function getAccessToken(): string {
		return Token::generate($this->access);
	}

	/**
	 * @param string $email
	 * @return \Ext_TS_Inquiry_Contact_Login|null
	 */
	public function searchUserByEmail(string $email): ?\Ext_TS_Inquiry_Contact_Login {

		$sql = "
			SELECT
				`ts_icl`.`id`
			FROM
				`tc_contacts` `tc_c` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`contact_id` = `tc_c`.`id` INNER JOIN
				`ts_inquiries_contacts_logins` `ts_icl` ON
					`ts_icl`.`contact_id` = `tc_c`.`id` AND
					`ts_icl`.`active` = 1 INNER JOIN
				`tc_contacts_to_emailaddresses` `tc_cte` ON
					`tc_cte`.`contact_id` = `tc_c`.`id` INNER JOIN
				`tc_emailaddresses` `tc_e` ON
					`tc_e`.`id` = `tc_cte`.`emailaddress_id` AND
					`tc_e`.`active` = 1 AND
					`tc_e`.`email` = :email
			WHERE
				`tc_c`.`active` = 1
			LIMIT 
				1
		";

		$id = (int) \DB::getQueryOne($sql, [ 'email' => $email ]);

		if($id > 0) {
			return \Ext_TS_Inquiry_Contact_Login::getInstance($id);
		}

		return null;
	}

	/**
	 * @param string $accessCode
	 * @return \Ext_TS_Inquiry_Contact_Login|null
	 */
	private function searchUserByAccessCode(string $accessCode): ?\Ext_TS_Inquiry_Contact_Login {

		$userLogin = \Ext_TS_Inquiry_Contact_Login::getRepository()
			->findOneBy(['access_code' => md5($accessCode)]);

		/** @var $userLogin \Ext_TS_Inquiry_Contact_Login */

		if($userLogin) {
			// Wenn das Passwort nie verschickt wurde, muss das jetzt generiert werden, da das ansonsten alles nicht funktioniert
			if(empty($userLogin->password)) {
				$userLogin->generatePassword();
			}

			return $userLogin;
		}

		return null;
	}
}
