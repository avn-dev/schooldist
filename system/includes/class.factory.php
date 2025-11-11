<?php

class Factory {
	
	protected static $_aAllocations = array();
	
	protected static $_aInstances = array();

	/**
	 * Klassenzuordnungen setzen
	 * @param array $aAllocations 
	 */
	public static function setAllocations(array $aAllocations) {
		self::$_aAllocations += $aAllocations;
	}
	
	/**
	 * Einzelne Klassenzuordnung setzen/überschreiben
	 * @param string $from
	 * @param string $to
	 * @return void
	 */
	public static function setAllocation(string $from, string $to): void {
		
		self::$_aAllocations[$from] = $to;

		if(
			substr($from, 0, 1) !== '\\' &&
			isset(self::$_aAllocations['\\'.$from])
		) {
			$from = '\\'.$from;
			self::$_aAllocations[$from] = $to;
		}
		
	}
	
	/**
	 * Klasse in Zuordnungstabelle übersetzen
	 * @param string $sClassName
	 * @return string|null
	 */
	protected static function _getClassName($sClassName) {

		if($bFound = isset(self::$_aAllocations[$sClassName])) {
			$sClassName = self::$_aAllocations[$sClassName];
		}

		// Backslash am Anfang entfernen und nochmal suchen. Passiert z.b. wenn man die 
		// Factory Allocations mit ::class definiert und die Factory dann mit Backslash abfragt
		if(!$bFound && substr($sClassName, 0, 1) === '\\') {
			$sClassName = self::_getClassName(ltrim($sClassName, '\\'));
		}
		
		return $sClassName;
		
	}
	
	/**
	 * Gibt den übersetzten Klassennamen aus
	 * @param string $sClassName
	 * @return string
	 */
	public static function getClassName($sClassName) {
		
		$sClassName = self::_getClassName($sClassName);
		
		return $sClassName;
		
	}
	
	/**
	 * @TODO Wird $aReferences noch benötigt oder kann das einfach entfernt werden?
	 * Wenn man diesen unglaublich speziellen Sonderfall benötigt, kann man auch mit getClassName() arbeiten.
	 *
	 * Objekt der Klasse zurückgeben
	 * @param string $sClassName
	 * @param array $aParameters Beliebige Anzahl an Parametern 
	 *	(Werden als richtige Parameter im Konstruktor aufgerufen)
	 * @return mixed
	 */
	public static function getObject($sClassName, $aParameters = array(), $aReferences=null) {

		$aParameters = (array)$aParameters;
		$sClassName = self::_getClassName($sClassName);
		
		$oReflection = new ReflectionClass($sClassName);

		if(empty($aParameters)) {
			$oObject = $oReflection->newInstance();
		} else {

			if($aReferences !== null) {
				foreach($aReferences as $iParameter) {
					$aParameters[$iParameter] = &$aParameters[$iParameter];
				}
			}

			$oObject = $oReflection->newInstanceArgs($aParameters);
		}

		return $oObject;

	}

	/**
	 * Instanz der Klasse zurückgeben
	 * @param string $sClassName
	 * @param int $iObjectId
	 * @return WDBasic
	 */
	public static function getInstance($sClassName, $iObjectId = null) {
		
		$oObject = self::executeStatic($sClassName, 'getInstance', array($iObjectId));

		return $oObject;
		
	}

	/**
	 * Statische Methode aufrufen, optional mit Parametern
	 * @param string $sClassName
	 * @param string $sMethod
	 * @param mixed $aParameter
	 * @return mixed
	 */
	public static function executeStatic($sClassName, $sMethod, $aParameter=null) {
		
		$sClassName = self::_getClassName($sClassName);

		$aCallable = array($sClassName, $sMethod);
		
		if($aParameter === null) {
			$mReturn = call_user_func($aCallable);
		} else {

			$aParameter = (array)$aParameter;

			$mReturn = call_user_func_array($aCallable, $aParameter);

		}
		
		return $mReturn;
		
	}

	/**
	 * Objekteingeschaft zurückliefern
	 *
	 * @static
	 * @param $sClassName
	 * @param $sProperty
	 * @return mixed
	 */
	public static function getProperty($sClassName, $sProperty)
	{

		$sClassName = self::_getClassName($sClassName);
		$oReflectionProperty = new ReflectionProperty($sClassName, $sProperty);
		$oReflectionProperty->setAccessible(true);
		$mReturn = $oReflectionProperty->getValue();

		return $mReturn;

	}
	
}
