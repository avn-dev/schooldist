<?php

namespace Ts\Handler\LoadingIndicator;

use \Core\Handler\LoadingIndicatorHandler;

class Document extends LoadingIndicatorHandler {

	public function getStatus(array $aIds) {

		$oRepository = \Ts\Entity\Document::getRepository();
		$aStatus = $oRepository->getStatusByIds($aIds);

		foreach($aStatus as &$aData) {
			if($aData['status'] === 'ready') {
				$aData['url'] = '/storage/download'.$aData['path'];
				$aData['style'] = array('cursor' => 'pointer');
			}

			unset($aData['path']);
		}

		return $aStatus;
	}

	public function getIcons() {
		$aIcons = parent::getIcons();
		$aIcons['ready'] = \Ext_Thebing_Util::getIcon('pdf');
		return $aIcons;
	}

}