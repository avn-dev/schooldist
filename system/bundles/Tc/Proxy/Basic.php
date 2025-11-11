<?php

namespace Tc\Proxy;

use Core\Proxy\WDBasicAbstract;
use FileManager\Proxy\File;
use FileManager\Traits\FileManagerTrait;

/**
 * @property \Ext_TC_Basic $oEntity
 */
class Basic extends WDBasicAbstract {

	// Da dieser Proxy schon fleißig in TA verwendet wird, aber keine Entity den Trait einbindet, macht das einfach der Proxy.
	use FileManagerTrait {
		getFiles as private;
		getFirstFile as private;
		getRandomFile as private;
	}

	public function getFlexibleFieldValue($iField, $sLanguageIso = null, bool $format=true) {
		
		$oFlexField = \Ext_TC_Flexibility::getInstance($iField);

		// Nicht über getFormattedValue, da dort jedes Mal ein Query abgefeuert wird…
		$sValue = $this->oEntity->getFlexValue($oFlexField->id, $sLanguageIso);
		if(
			$format === true &&
			!empty($sValue)
		) {
			$sValue = $oFlexField->formatValue($sValue, $sLanguageIso, false);
		}
		
		return $sValue;
	}

	public function getId() {
		return $this->oEntity->getId();
	}

	public function getName($sLanguageIso = null) {
		return $this->oEntity->getName($sLanguageIso);
	}

	/**
	 * @param string $sTag
	 * @return File[]
	 */
	public function getFilemanagerEntries($sTag = null) {
		return collect($this->getFiles($sTag))->mapInto(File::class)->toArray();
	}

	/**
	 * @param string $sTag
	 * @return File|null
	 */
	public function getFirstFilemanagerEntry($sTag = null) {

		$oFile = $this->getFirstFile($sTag);
		if ($oFile !== null) {
			return new File($oFile);
		}

		return null;

	}

	/**
	 * @param string $sTag
	 * @return File[]
	 */
	public function getRandomFilemanagerEntries($sTag = null) {
		return collect($this->getFilemanagerEntries($sTag))->shuffle()->toArray();
	}

	/**
	 * @param string $sTag
	 * @return File|null
	 */
	public function getRandomFilemanagerEntry($sTag = null) {

		$oFile = $this->getRandomFile($sTag);
		if ($oFile !== null) {
			return new File($oFile);
		}

		return null;

	}

}