<?php

namespace TsAccommodationLogin\Controller;

use TsAccommodationLogin\Handler\ExternalApp;

class InterfaceController extends AbstractController {

	protected $_sViewClass = '\MVC_View_Smarty';

	public function login() {

		if($this->auth(false) === true) {
			$this->redirect('TsAccommodationLogin.accommodation', [], false);
		}
		
		$sTemplate = 'system/bundles/TsAccommodationLogin/Resources/views/pages/authentication.tpl';
		$this->_oView->setTemplate($sTemplate);
	}

	public function logout() {

		$this->log->info('Logout', [$this->_oAccess->id]);
			
		$this->_oAccess->deleteAccessData();

		$this->oSession->getFlashBag()->add('success', \L10N::t('You have been logged out successfully.'));

		$this->redirect('TsAccommodationLogin.accommodation_login', [], false);

	}

	public function accommodation() {

		$accommodation = \Ext_Thebing_Accommodation::getInstance($this->_oAccess->id);
		$currentAllocations = $accommodation->getAllocations([['kaal.reservation', '=', null],['kaal.from', '<=', (new \DateTime())->format('Y-m-d')], ['kaal.until', '>=', (new \DateTime())->format('Y-m-d')]], 'kaal.from');
		$upcomingAllocations = $accommodation->getAllocations([['kaal.reservation', '=', null],['kaal.from', '>', (new \DateTime())->format('Y-m-d')]], 'kaal.from');
		$app = new ExternalApp();

		$config = new \Ext_TS_Config(0, null, true);

		$configKeyForColumns = $app::KEY_COLUMNS;

		$existingColumns = $config->$configKeyForColumns;

		if (empty($existingColumns)) {
			$existingColumns = $app->getDefaultColumnValues();
		}

		$this->set('currentAllocations', $currentAllocations);
		$this->set('upcomingAllocations', $upcomingAllocations);
		$this->set('existingColumns', $existingColumns);

		$sTemplate = 'system/bundles/TsAccommodationLogin/Resources/views/pages/accommodation.tpl';
		$this->_oView->setTemplate($sTemplate);
		
	}

	public function redirectToHttps(string $sPath = "") {
		
		$sUrl = 'https://'.\Util::getSystemHost().'/accommodation/'.$sPath;

		$this->redirectUrl($sUrl);
		
	}

}
