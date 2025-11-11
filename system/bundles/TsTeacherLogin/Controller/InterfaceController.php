<?php

namespace TsTeacherLogin\Controller;

class InterfaceController extends AbstractController {

	protected $_sViewClass = '\MVC_View_Smarty';

	public function login() {

		if($this->auth(false) === true) {
			$this->redirect('TsTeacherLogin.teacher', [], false);
		}
		
		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/authentication.tpl';
		$this->_oView->setTemplate($sTemplate);
	}

	public function logout() {

		$this->log->info('Logout', [$this->_oAccess->id]);
			
		$this->_oAccess->deleteAccessData();

		$this->oSession->getFlashBag()->add('success', \L10N::t('You have been logged out successfully.'));

		$this->redirect('TsTeacherLogin.teacher_login', [], false);

	}

	public function teacher() {

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$oSchool = $oTeacher->getSchool();

		$sWelcomeText = $oSchool->teacherlogin_welcome_text;

		$this->set('sWelcomeText', $sWelcomeText);
		$this->set('oTeacher', $oTeacher);

		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/teacher.tpl';
		$this->_oView->setTemplate($sTemplate);
		
	}

	public function redirectToHttps(string $sPath) {
		
		$sUrl = 'https://'.\Util::getSystemHost().'/teacher/'.$sPath;

		$this->redirectUrl($sUrl);
		
	}

	protected function getTeacher(): \Ext_Thebing_Teacher
	{
		return \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
	}

	protected function getSchool(): \Ext_Thebing_School
	{
		return $this->getTeacher()->getSchool();
	}
}
