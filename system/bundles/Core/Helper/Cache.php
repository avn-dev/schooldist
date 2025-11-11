<?php

namespace Core\Helper;

class Cache {
	
	public function clearAll() {

		$this->clearFileCache();
		$this->clearSmartyCache();
		$this->clearWDCache();
		$this->clearDashboard();

		return true;
	}
	
	public function clearWDCache() {

		\WDCache::flush();
		\Core\Facade\Cache::flush();

	}
	
	public function clearFileCache() {

		$sTmpDir = \Util::getDocumentRoot().'storage/tmp/';
		\Util::recursiveDelete($sTmpDir);
		\Util::checkDir($sTmpDir);

		$sTmpDir = \Util::getDocumentRoot().'storage/public/tmp/';
		\Util::recursiveDelete($sTmpDir);
		\Util::checkDir($sTmpDir);

	}
	
	public function clearSmartyCache() {

		$oSmarty = new \SmartyWrapper;
		$oSmarty->clearAllCache();
		$oSmarty->clearCompiledTemplate();

		$oTemplating = new \Core\Service\Templating;
		$oTemplating->clearAllCache();
		$oTemplating->clearCompiledTemplate();

	}

	public function clearDashboard()
	{
		\Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('admin/dashboard', [], 100);
	}

}
