<?php

namespace Sso\Controller;

use Core\Handler\SessionHandler as Session;

class AdminController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = null;
	
	/**
	 * @var Session
	 */
	protected $session;
	
	/**
	 * @var \Log
	 */
	protected $log;
			
	protected $auth;
			
	function __construct($sExtension, $sController, $sAction, $oAccess = null) {

		parent::__construct($sExtension, $sController, $sAction, $oAccess);
		
		$this->session = Session::getInstance();
		
		$this->log = \Log::getLogger('api', 'sso');
			
		$settingsHelper = new \Sso\Helper\Settings;
		$this->auth = new \OneLogin\Saml2\Auth($settingsHelper->get());

	}

	public function login() {
		
		$this->auth->login();
		
	}
	
	public function metadata() {
		
		$settingsHelper = new \Sso\Helper\Settings;
		$settingsInfo = $settingsHelper->get();
		
		$settings = new \OneLogin\Saml2\Settings($settingsInfo, true);
		$metadata = $settings->getSPMetadata();
		
		return response($metadata, 200, [
			'Content-Type' => 'application/xml'
		]);		
	}
	
	public function acs(\MVC_Request $request) {
		   
		try {
			   
			if ($this->session->has('AuthNRequestID')) {
				$requestID = $this->session->get('AuthNRequestID');
			} else {
				$requestID = null;
			}

			$this->auth->processResponse($requestID);

			$errors = $this->auth->getErrors();

			if (!empty($errors)) {
				$this->log->error('acs', ['errors'=>$errors, 'reason'=>$this->auth->getLastErrorReason(), 'response'=>$request->getAll()]);
			}

			$this->log->info('acs', ['authenticated' => $this->auth->isAuthenticated()]);

			if (!$this->auth->isAuthenticated()) {
				$this->log->error('acs', ['errors'=>'Not authenticated', 'response'=>$request->getAll()]);
				$this->session->getFlashBag()->add('error', \L10N::t('Not authenticated!', 'Framework'));
				return redirect('/admin/login');
			}

			$this->session->set('samlUserdata', $this->auth->getAttributes());
			$this->session->set('samlNameId', $this->auth->getNameId());
			$this->session->set('samlNameIdFormat', $this->auth->getNameIdFormat());
			$this->session->set('samlNameIdNameQualifier', $this->auth->getNameIdNameQualifier());
			$this->session->set('samlNameIdSPNameQualifier', $this->auth->getNameIdSPNameQualifier());
			$this->session->set('samlSessionIndex', $this->auth->getSessionIndex());
			$this->session->remove('AuthNRequestID');

			$user = $this->getUser($this->auth->getNameId(), $this->auth->getAttributes());
			
			if(!$user instanceof \User) {
				
				$this->log->error('acs', ['errors'=>'User not valid', 'response'=>$request->getAll()]);
				
				$this->session->getFlashBag()->add('error', \L10N::t('User not valid!', 'Framework'));
				return redirect('/admin/login');
				
			}
			
			$db = \DB::getDefaultConnection();
			
			$accessBackend = new \Access_Backend($db);
			$login = $accessBackend->login($user);

			if(!$login) {
				
				$this->log->error('acs', ['errors'=>'Login not successful', 'response'=>$request->getAll()]);
				
				$this->session->getFlashBag()->add('error', \L10N::t('Login not successfull!', 'Framework'));
				return redirect('/admin/login');
				
			} else {
				
				$accessBackend->saveAccessData();

				$aUserData = array();
				$accessBackend->reworkUserData($aUserData);
				
				$this->log->info('acs', ['success'=>true, 'data'=>$aUserData]);
				
				$this->auth->redirectTo(\Core\Helper\Routing::generateUrl('admin'));
			}
			
		} catch(\Throwable $e) {
			
			$this->log->error('acs', ['exception'=>$e->getMessage()]);
			
			__pout($e);
			
			$this->session->getFlashBag()->add('error', \L10N::t('An error occured! Please contact support.', 'Framework'));
			return redirect('/admin/login');
			
		}
		
	}
	
	protected function getUser(string $email, array $data) {
		
		$this->log->info('Get user', ['email'=>$email, 'data'=>$data]);
		
		$userClass = \Factory::getClassName('User');
		
		$user = $userClass::query()->where('email', $email)->first();

		if($user instanceof \User) {
			
			if(
				$user->active != 1 ||
				$user->status != 1
			) {
				return null;
			}
			
			$user->email = $email;
			$user->firstname = implode(' ', $data['firstname']??[]);
			$user->lastname = implode(' ', $data['lastname']??[]);
			
			$this->log->info('User updated', ['data'=>$user->aData]);
			
		} else {
			
			$user = new $userClass();
			$user->active = 1;
			$user->status = 1;
			$user->role = \System::d(\Sso\Service\SsoApp::KEY_USERGROUP);
			$user->username = \Util::generateRandomString(32);
			$user->password = password_hash(\Util::generateRandomString(32), PASSWORD_DEFAULT);
			$user->email = $email;
			$user->firstname = implode(' ', $data['firstname']??[]);
			$user->lastname = implode(' ', $data['lastname']??[]);
			
			$this->log->info('User created', ['data'=>$user->aData]);
			
		}

		// Update roles
		if(
			method_exists($user, 'updateRoles') &&
			isset($data['roles'])
		) {
			
			$roles = explode(';', implode(';', $data['roles']));
			$result = $user->updateRoles($roles);
			
			$this->log->info('User roles updated', ['result'=>$result]);
			
		}
		
		// Zuerst die Rollen setzen, da der User sonst automatisch deaktiviert wird
		$user->save();
		
		return $user;
	}

	public function redirectToLogin() {
		return redirect('/admin/sso/login');
	}

	public function logout() {
		  
		$returnTo = null;
		$parameters = array();
		$nameId = null;
		$sessionIndex = null;
		$nameIdFormat = null;
		$samlNameIdNameQualifier = null;
		$samlNameIdSPNameQualifier = null;

		if ($this->session->has('samlNameId')) {
			$nameId = $this->session->get('samlNameId');
		}
		if ($this->session->has('samlNameIdFormat')) {
			$nameIdFormat = $this->session->get('samlNameIdFormat');
		}
		if ($this->session->has('samlNameIdNameQualifier')) {
			$samlNameIdNameQualifier = $this->session->get('samlNameIdNameQualifier');
		}
		if ($this->session->has('samlNameIdSPNameQualifier')) {
			$samlNameIdSPNameQualifier = $this->session->get('samlNameIdSPNameQualifier');
		}
		if ($this->session->has('samlSessionIndex')) {
			$sessionIndex = $this->session->get('samlSessionIndex');
		}

		$access = \Access_Backend::getInstance();
		
		if(
			$access instanceof \Access_Backend &&
			$access->checkValidAccess()
		) {
			$access->deleteAccessData();
			$access->destroyAccess();
		}
		
		if(!empty($nameId)) {
			$this->auth->logout($returnTo, $parameters, $nameId, $sessionIndex, false, $nameIdFormat, $samlNameIdNameQualifier, $samlNameIdSPNameQualifier);
		}

	}
	
}
