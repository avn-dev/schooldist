<?php

namespace TsRdstation\Hook;

use Core\Enums\AlertLevel;

class PaymentCreateHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(\Ext_Thebing_Inquiry_Payment $item) {

		if(\TcExternalApps\Service\AppService::hasApp(\TsRdstation\Handler\ExternalApp::APP_NAME)) {

			$oAccessToken = \TsRdstation\Service\RDStation::getAccessToken();

			if($oAccessToken) {

				$this->bCreate = true;
				
				$stack = 'ts-rdstation/sync-payment';

				// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
				if(\System::d('debugmode')) {

					$oService = new \TsRdstation\Service\RDStation($oAccessToken);
					
					$oService->syncPayment($item, $this->bCreate);	

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
