<?php

namespace TsTeacherLogin\Controller;

class PasswordController extends InterfaceController {

	public function getForgotPasswordView() {

		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/forgot_password.tpl';
		$this->_oView->setTemplate($sTemplate);
	}

	public function postResetPassword() {

		$oTeacherRepository = \Ext_Thebing_Teacher::getRepository();

		$sEmail = $this->_oRequest->get('email');
		if($sEmail === '') {

			$this->oSession->getFlashBag()->add('error', \L10N::t('Please enter your e-mail address.'));
			$this->redirect('TsTeacherLogin.teacher_forgot_password', [], false);

		}

		$oTeacher = $oTeacherRepository->findOneBy(['email' => $sEmail]);

		if($oTeacher !== null) {

			$oSchool = $oTeacher->getSchool();
			$sLanguage = $oSchool->language;

			$oTemplate = $oSchool->getJoinedObject('teacherlogin_template');

			if($oTemplate->id === 0) {

				$this->oSession->getFlashBag()->add('error', \L10N::t('The e-mail could not be sent! Please get in touch with your contact person at the school!'));

				$this->redirect('TsTeacherLogin.teacher_login', [], false);

			}

			$oPlaceholder = new \Ext_Thebing_Teacher_Placeholder($oTeacher->id);

			// E-Mail zusammenstellen und senden.
			$oEmail = new \Ts\Service\Email($oTemplate, $sLanguage);

			$oEmail->setSchool($oSchool);
			$oEmail->setEntity($oTeacher);
			$oEmail->setPlaceholder($oPlaceholder);

			$aTo = [$oTeacher->email];

			$bSuccess = $oEmail->send($aTo);

		}

		if($bSuccess) {
			
			$this->log->info('Password link requested', [$oTeacher->id]);		
			
			$this->oSession->getFlashBag()->add('success', \L10N::t('The confirmation link has been sent, please check your e-mail!'));
		}

		$this->redirect('TsTeacherLogin.teacher_login', [], false);

	}

	private function truncateInvalidTokens() {

		$hours = \System::d('ts_teacher_portal_activation_key_lifetime', 8);
		
		// überprüfen ob die Passwortanfrage älter als 8 Stunden ist, wenn ja, löschen
		$dDateCheck = new \DateTime('-'.(int)$hours.' hour');

		$aSql = [
			'id_table' => 32,
			'time' => $dDateCheck->format('Y-m-d H:i:s')
		];

		$sSql = "
			DELETE FROM 
				`customer_db_activation` 
			WHERE 
			    `id_table` = :id_table AND  
		  		`date` < :time";
		\DB::executePreparedQuery($sSql, $aSql);

	}

	private function getToken(string $sToken) {

		$this->truncateInvalidTokens();

		$aSql = [
			'id_table' => 32,
			'token' => $sToken
		];

		$sSql = "
			SELECT 
				* 
			FROM 
				`customer_db_activation` 
			WHERE 
				`id_table` = :id_table AND
				`activation_key` = :token
		";

		$aToken = \DB::getQueryRow($sSql, $aSql);

		return $aToken;
	}

	public function getResetPasswordView(string $sToken) {

		$aToken = $this->getToken($sToken);

		if(empty($aToken)) {

			$this->oSession->getFlashBag()->add('error', \L10N::t('The link has expired, please request a new password again!'));
			$this->redirect('TsTeacherLogin.teacher_login', [], false);

		} else {

			$oRouting = new \Core\Helper\Routing;
			$sFormAction = $oRouting->generateUrl('TsTeacherLogin.teacher_reset_password_save', ['sToken'=>$sToken]);

			$this->set('oAccess', $this->_oAccess);
			$this->set('sFormAction', $sFormAction);
			$this->_oView->setTemplate('system\bundles\TsTeacherLogin\Resources\views\pages\forgot_password_save.tpl');

		}
	}

	public function postResetPasswordSave(string $sToken) {

		$aToken = $this->getToken($sToken);
		
		if(empty($aToken)) {
			$this->oSession->getFlashBag()->add('error', \L10N::t('The link has expired!'));
			$this->redirect('TsTeacherLogin.teacher_login', [], false);
		}

		$iTeacherId = $aToken['id_user'];

		$oTeacher = \Ext_Thebing_Teacher::getInstance($iTeacherId);

		$sPassword = $this->_oRequest->get('password_new');
		$sPasswordCheck = $this->_oRequest->get('password_check');

		if(
			empty($sPassword) ||
			empty($sPasswordCheck)
		) {
			$this->oSession->getFlashBag()->add('error', \L10N::t('Please fill in both fields!'));
			$this->redirect('TsTeacherLogin.teacher_reset_password_link', ['sToken'=>$sToken], false);
		}

		if($sPassword !== $sPasswordCheck) {
			$this->oSession->getFlashBag()->add('error', \L10N::t('The entered passwords do not match!'));
			$this->redirect('TsTeacherLogin.teacher_reset_password_link', ['sToken'=>$sToken], false);
		}

		$aUserData = [
			$oTeacher->username,
			$oTeacher->firstname,
			$oTeacher->lastname,
			$oTeacher->email
		];

		$oZxcvbn = new \ZxcvbnPhp\Zxcvbn();
		$aStrength = $oZxcvbn->passwordStrength($sPassword, $aUserData);

		if($aStrength['score'] < \System::getMinPasswordStrength()) {
			$this->oSession->getFlashBag()->add('error', \L10N::t('The password is not secure enough!'));
			$this->redirect('TsTeacherLogin.teacher_reset_password_link', ['sToken'=>$sToken], false);
		}

		$oCustomerDb = new \Ext_CustomerDB_DB(32);
		$oCustomerDb->updateCustomerField($oTeacher->id, 'password', $sPassword);

		$this->oSession->getFlashBag()->add('success', \L10N::t('Your password has been updated successfully!'));

		$this->deleteToken($sToken);

		$this->log->info('Password changed', [$oTeacher->id]);
		
		$this->redirect('TsTeacherLogin.teacher_login', [], false);
	}

	private function deleteToken($sToken) {

		$aSql = [
			'token' => $sToken
		];

		$sSql = "
				DELETE 
				FROM 
					`customer_db_activation` 
				WHERE 
					`activation_key` = :token
			";
		\DB::executePreparedQuery($sSql, $aSql);

	}

}