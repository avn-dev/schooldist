<?php

namespace Core\Facade;

use \Core\Helper\Bundle;
use \Core\Collection\SequentialProcessing as Collection;

/**
 * SequentialProcessing – Aktionen unmittelbar am Ende des Requests ausführen
 */
class SequentialProcessing {

	/** @var \Core\Collection\SequentialProcessing[] */
	private static $aStack = [];

	/**
	 * Eintrag ins SequentialProcessing hinzufügen
	 *
	 * @param $sTypeHandler
	 * @param $oObject
	 */
	public static function add($sTypeHandler, $oObject) {

		if(!isset(self::$aStack[$sTypeHandler])) {
			$oTypeHandler = self::createTypeHandler($sTypeHandler);
			self::$aStack[$sTypeHandler] = new Collection($oTypeHandler);
		}

		self::$aStack[$sTypeHandler]->attach($oObject);

	}

	/**
	 * Eintrag aus dem SequentialProcessing entfernen
	 *
	 * @param $sTypeHandler
	 * @param $oObject
	 */
	public static function remove($sTypeHandler, $oObject) {

		if(!isset(self::$aStack[$sTypeHandler])) {
			$oTypeHandler = self::createTypeHandler($sTypeHandler);
			self::$aStack[$sTypeHandler] = new Collection($oTypeHandler);
		}

		self::$aStack[$sTypeHandler]->detach($oObject);

	}

	/**
	 * SequentialProcessing ausführen
	 */
	public static function execute() {

		foreach(self::$aStack as $sTypeHandler => $oCollection) {

			$oTypeHandler = self::createTypeHandler($sTypeHandler);

			$oCollection->rewind();
			
			while ($oCollection->valid()) {
				$oObject = $oCollection->current();
				$oCollection->next();
				$oTypeHandler->execute($oObject);
			}

			// Nicht detach in der Schleife aufrufen, da attach/detach den Pointer verändern
			// https://bugs.php.net/bug.php?id=65629
			$oCollection->removeAll($oCollection);
		}

	}

	/**
	 * Instanz des entsprechenden TypeHandlers erzeugen
	 *
	 * @param string $sHandler
	 * @return \Core\Handler\SequentialProcessing\TypeHandler
	 */
	public static function createTypeHandler($sHandler) {

		$aParts = explode('/', $sHandler, 2);
		$oBundleHelper = new Bundle();
		$sBundleNamespace = $oBundleHelper->convertBundleName($aParts[0]);
		$sHandlerClass = \Util::convertHyphenLowerCaseToPascalCase($aParts[1]);

		$sHandlerClass = $sBundleNamespace.'\Handler\SequentialProcessing\\'.$sHandlerClass;

		if(!class_exists($sHandlerClass)) {
			throw new \RuntimeException('Class '.$sHandlerClass.' doesn\'t exist!');
		}

		return new $sHandlerClass();

	}

}