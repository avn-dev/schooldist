<?php

namespace TsTeacherLogin\Controller;

use Core\Handler\CookieHandler;
use Core\Handler\SessionHandler as Session;

class AbstractController extends \MVC_Abstract_Controller {

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;
	
	/**
	 * @var \Log
	 */
	protected $log;

	/**
	 * @var Session
	 */
	protected $oSession;

	function __construct($sExtension, $sController, $sAction, $oAccess = null) {

		parent::__construct($sExtension, $sController, $sAction, $oAccess);

		$this->oSession = Session::getInstance();
		$this->set('oSession', $this->oSession);

		$this->log = \Log::getLogger('frontend', 'teacherportal');
		
	}

	public function accessDenied() {

		$oDesign = new \Admin\Helper\Design;
		$aImages = $oDesign->getLogos();

		$this->oSession->getFlashBag()->add('error', \L10N::t('This page is not available for your school!'));

		return response()->view('layout/login', ['oSession' => $this->oSession, 'aLogos' => $aImages]);
	}

	/**
	 * Setzt die Backendsprache
	 *
	 * @param string $sLanguage
	 */
	public function changeLanguage(string $sLanguage) {

		CookieHandler::set("frontendlanguage", $sLanguage);

		$this->redirect('TsTeacherLogin.teacher_login', [], false);

	}

	/**
	 * Prüft, ob bereits eine Sprache gesetzt wurde und verwendet die Browsersprache falls nicht
	 */
	private function checkLanguage(array $aLanguages) {

		$sLanguage = null;

		if(CookieHandler::is('frontendlanguage')) {
			$sLanguage = CookieHandler::get('frontendlanguage');
		} else {
			$sLanguage = \System::getDefaultInterfaceLanguage($aLanguages);
		}

		if(!empty($sLanguage)) {
			\System::setInterfaceLanguage($sLanguage);
		}

		return $sLanguage;
	}

	public function beforeAction($sAction = null) {

		$aLanguages = \Ext_Thebing_Client::getLanguages();

		$bAccess = \Ext_Thebing_Access_Client::check('thebing_tuition_resource_teachers_login');

		if($bAccess === false) {
			$oResponse = $this->accessDenied();
			$oResponse->send();
			die();
		}

		$sLanguage = $this->checkLanguage(array_keys($aLanguages));

		$this->merge('aTranslations', [
			'today' => \L10N::t('Today'),
			'month' => \L10N::t('Month'),
			'week' => \L10N::t('Week'),
			'day' => \L10N::t('Day'),
			'list' => \L10N::t('List'),
			'empty_list' => \L10N::t('List empty'),
			'showing_all' => \L10N::t('All {0}'),
			'filter' => \L10N::t('Filter'),
			'move_all' => \L10N::t('Move all'),
			'remove_all' => \L10N::t('Remove all'),
			'show_all' => \L10N::t('Show all'),
		]);

		if(
			$sAction !== 'login' &&
			$sAction !== 'getForgotPasswordView' &&
			$sAction !== 'postResetPassword' &&
			$sAction !== 'getResetPasswordView' &&
			$sAction !== 'postResetPasswordSave' &&
			$sAction !== 'changeLanguage'
		) {
			$this->auth();

			if($this->_oAccess->getAccessUser() !== null) {

				$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
				
				$oSchool = $oTeacher->getSchool();
				$this->set('oSchool', $oSchool);

				$this->oSession->set('sid', $oSchool->id);

				$this->set('oTeacher', $oTeacher);

			}

		}

		$oDesign = new \Admin\Helper\Design;

		$aImages = $oDesign->getLogos();

		$this->set('sSystemLogo', $aImages['system_logo']);
		$this->set('aLogos', $aImages);
		$this->set('aLanguages', $aLanguages);
		$this->set('sInterfaceLanguage', $sLanguage);

	}
	
	protected function auth(bool $iRedirect = true) {

		$this->set('oAccess', $this->_oAccess);

		if($this->_oRequest->exists('remember_password')) {

			$this->_oAccess->saveAccessData();
		}

		if($this->_oAccess->checkValidAccess()) {

			$aUserData = $this->_oAccess->getUserData();

			$dValidUntil = new \DateTime($aUserData['data']['valid_until']);
			$dNow = new \DateTime();

			if(
				$dValidUntil < $dNow &&
				$aUserData['data']['valid_until'] !== '0000-00-00'
			) {

				$this->_oAccess->deleteAccessData();

				$this->oSession->getFlashBag()->add('error', \L10N::t('This user is not active anymore.'));

				$this->redirect('TsTeacherLogin.teacher_login', [], false);

			}

			$iTableId = $aUserData['idTable'];

			if($iTableId === 32) {
				return true;
			}

		}

		if($iRedirect === true) {

			// Eventuelle Fehlermeldungen in Flashbag setzen
			$sLastErrorCode = $this->_oAccess->getLastErrorCode();

			if(!empty($sLastErrorCode)) {
				
				$this->log->info('Login failed');
				
				$this->oSession->getFlashBag()->clear();
				$this->oSession->getFlashBag()->add('error', $sLastErrorCode);
			}

			$this->redirect('TsTeacherLogin.teacher_login', [], false);
		}

	}

}
