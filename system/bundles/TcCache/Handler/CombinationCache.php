<?php

namespace TcCache\Handler;

use TcCache\Handler\AbstractCacheHandler;

class CombinationCache extends AbstractCacheHandler {

    protected $sSessionSubDir = "combinations";

	/**
	 * @var \Ext_TC_Frontend_Combination
	 */
	protected $oCombination;

	public function setCombination(\Ext_TC_Frontend_Combination $oCombination) {
		$this->oCombination = $oCombination;
	}
	
    /**
     * Generiert den Dateinamen für eine Session-Datei
     *
     * @param string $sRegistrationKey
     * @return string
     */
    public function buildFilename($sCacheKey) {

		if(!$this->oCombination instanceof \Ext_TC_Frontend_Combination) {
			throw new \RuntimeException('Combination object missing');
		}
		
        $sFileName = $this->getSessionDirectory() . '/';

		$sFileName .= $this->oCombination->id.'_';

        $sFileName .= $sCacheKey.'.txt';

		return $sFileName;
    }

}