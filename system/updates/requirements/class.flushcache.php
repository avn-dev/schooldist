<?php

/**
 * 
 */
class Updates_Requirements_FlushCache extends Requirement {

	/**
	 * @return boolean
	 */
	public function checkSystemRequirements() {

		$oCacheHelper = new \Core\Helper\Cache();
		$oCacheHelper->clearAll();

		return true;
	}

}