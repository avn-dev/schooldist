<?php

namespace TsRdstation\Hook;

use Core\Enums\AlertLevel;

class AbstractItemHook extends \Core\Service\Hook\AbstractHook {
	
	protected $bCreate = false;

	public function run(\Ext_TS_Inquiry_Abstract $item) {

		if(\TcExternalApps\Service\AppService::hasApp(\TsRdstation\Handler\ExternalApp::APP_NAME)) {

			$oAccessToken = \TsRdstation\Service\RDStation::getAccessToken();

			if($oAccessToken) {

				if($item instanceof \Ext_TS_Enquiry) {
					$stack = 'ts-rdstation/sync-enquiry';
				} else {
					$stack = 'ts-rdstation/sync-inquiry';
				}
				
				// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
				if(\System::d('debugmode')) {

					$oService = new \TsRdstation\Service\RDStation($oAccessToken);
					
					if($item instanceof \Ext_TS_Enquiry) {
						$oService->syncEnquiry($item, $this->bCreate);
					} else {
						$oService->syncInquiry($item, $this->bCreate);	
					}

				} else {

					$oUser = \Access::getInstance();
					
					$userId = null;
					if($oUser !== null) {
						$userId = $oUser->id;
					}

					$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
					$oStackRepository->writeToStack($stack, ['item_id' => $item->id, 'create'=>$this->bCreate, 'user_id' => $userId], 2);

				}
				
			} else {
				
				$access = \Access_Backend::getInstance();
				if($access instanceof \Access_Backend) {
					$user = $access->getUser();
					\Core\Service\NotificationService::sendToUser($user, sprintf(\L10N::t('Item could not be synchronized with RD Station! Please check the app settings.', 'TS » Apps » RD Station')), AlertLevel::DANGER);
				}
				
			}

		}

	}
	
}
