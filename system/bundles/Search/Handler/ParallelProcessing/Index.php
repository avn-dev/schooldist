<?php

namespace Search\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class Index extends TypeHandler {

    /**
	 *  
     * @param  array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {
		
		$oSite = \Cms\Entity\Site::getInstance($aData['site_id']);

		$oLog = \Log::getLogger('search');
		
		$oLog->addInfo('ParallelProcessing: Start site', [$oSite->name]);

		$oSearch = new \Ext_Search($oSite);
		$oSearch->index();

		$oLog->addInfo('ParallelProcessing: End site', [$oSite->name, \Ext_Search::$aDebug]);

		return true;
	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Website - Indizierung', 'Framework');
	}

}