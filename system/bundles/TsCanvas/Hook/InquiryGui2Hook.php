<?php

namespace TsCanvas\Hook;

class InquiryGui2Hook extends \Core\Service\Hook\AbstractHook {
	
	public function run(array &$mixInput) {

		if(\TcExternalApps\Service\AppService::hasApp(\TsCanvas\Handler\ExternalApp::APP_NAME)) {
					
			$aGuiData = &$mixInput['config'];

			$aGuiData['bars'][0]['elements'][] = array(
				'element' => 'labelgroup',
				'label' => 'Canvas'
			);
			$aGuiData['bars'][0]['elements'][] = array(
				'element' => 'icon',
				'label' => 'Schüler übertragen',
				'task' => 'request',
				'action' => 'canvasTransfer',
				'active' => 0,
				'img' => 'fa-paper-plane'
			);

		}

	}
	
}

