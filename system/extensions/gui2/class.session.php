<?php

/**
 * Speichert die GUI2 Instanzen
 * @author Mark Koopmann
 */
class Ext_Gui2_Session {
	
	public static $sFilePath = '/storage/gui2/sessions/';
	
	protected static $aInstance = array();
	
	/**
	 * Lädt eine GUI2 Instanz aus der Datei
	 * 
	 * @todo Hash und Instancehash filtern, da so theoretisch andere Dateien zugegriffen werden kann
	 * @author Mark Koopmann
	 * @param string $userKey
	 * @param string $sHash
	 * @param string $sInstanceHash
	 * @return Ext_Gui2|false
	 */
	public static function load(string $userKey, string $sHash, string $sInstanceHash): \Ext_Gui2|false
	{
		$sDir = Util::getDocumentRoot(false).self::$sFilePath;
		
		$sFile = $sDir.\Util::getCleanFilename($userKey).'_'.$sHash.'_'.$sInstanceHash.'.sess';

		// Wenn Instanz da ist, nicht neu auslesen
		if (isset(self::$aInstance[$sFile])) {
			return self::$aInstance[$sFile];
		}
		
		if (is_file($sFile)) {

			touch($sFile);

			$sContent = file_get_contents($sFile);
			$oInstance = unserialize($sContent);

			if($oInstance instanceof Ext_Gui2) {
				self::$aInstance[$sFile] = $oInstance;
				return $oInstance;
			} else {
				throw new RuntimeException('GUI object could not be unserialised.');
			}

		}
		
		return false;
		
	}
	
	/**
	 * Schreibt eine GUI2 Instanz in eine Datei
	 * 
	 * @author Mark Koopmann
	 * @param string $userKey
	 * @param \Ext_Gui2 $oInstance
	 */
	public static function write(string $userKey, \Ext_Gui2 $oInstance): void
	{
		$sDir = Util::getDocumentRoot(false).self::$sFilePath;

		$bSuccess = Util::checkDir($sDir);

		if ($bSuccess) {

			$sFile = $sDir.\Util::getCleanFilename($userKey).'_'.$oInstance->hash.'_'.$oInstance->instance_hash.'.sess';
			try {
				$sContent = serialize($oInstance);
			} catch (Exception $e) {
				\Log::getLogger()->error('Serialize Error in Gui2_Session: '.$e->getMessage(), self::findReferences($oInstance));
				throw $e;
			}

			$mWrite = file_put_contents($sFile, $sContent);
			if ($mWrite === false) {
				ob_end_clean(); // Ohne dies sieht man die Exception wegen der GUI nicht
				throw new RuntimeException('Failed to write GUI2 session to disk!');
			}

			unset(self::$aInstance[$sFile]);
			
		} else {
			throw new Exception('Gui2 session directory is not writable!');
		}
	}

	/**
	 * Löscht die Datei mit der Instanz
	 * 
	 * @param string $userKey
	 * @param string $sHash
	 * @param string $sInstanceHash
	 */
	public static function delete(string $userKey, string $sHash, string $sInstanceHash): void
	{
		$sDir = Util::getDocumentRoot(false).self::$sFilePath;
		
		$sFile = $sDir.\Util::getCleanFilename($userKey).'_'.$sHash.'_'.$sInstanceHash.'.sess';

		if (is_file($sFile)) {
			unset(self::$aInstance[$sFile]);
			unlink($sFile);
		}
	}
	
	/**
	 * Löscht alle Instanzdateien des aktuellen Users
	 * 
	 * @param string $userKey
	 */
	public static function reset(string $userKey): void
	{
		$sDir = Util::getDocumentRoot(false).self::$sFilePath;
		
		$aFiles = (array)glob($sDir.\Util::getCleanFilename($userKey).'_*.sess');

		foreach ($aFiles as $sFile) {
			if (is_file($sFile)) {
				unlink($sFile);
			}
		}
		
		self::$aInstance = [];
	}

	public static function findReferences(mixed $object, array $path = [], ?SplObjectStorage $processed = null)
	{
		// Use SplObjectStorage to track processed objects and avoid infinite loops
		if ($processed === null) {
			$processed = new SplObjectStorage();
		}

		$result = [];

		// Only proceed with objects
		if (is_object($object)) {
			// Skip already-processed objects
			if ($processed->contains($object)) {
				return [];
			}

			// Mark this object as processed
			$processed->attach($object);

			// Reflect the object’s class
			$reflection = new ReflectionClass($object);

			// Loop through all properties (private, protected, public, and inherited)
			foreach ($reflection->getProperties() as $property) {
				$isStatic = $property->isStatic();
				$propertyName = $property->getName();

				// Construct the path for reporting (with static marker)
				$propertyPath = implode('->', [...$path, $isStatic ? "::$propertyName" : $propertyName]);

				// Get the property's value
				if ($isStatic) {
					$value = $property->getValue(); // Directly get the static value
				} else {
					// Get instance property value—Reflection allows access regardless of visibility in PHP 8+
					$value = $property->getValue($object);
				}

				// Check if it's a PDO instance
				if ($value instanceof PDO) {
					$result[] = $propertyPath . " (PDO)";
				}

				// Recursively check nested objects or arrays
				if (is_object($value) || is_array($value)) {
					$result = array_merge($result, \Ext_Gui2_Session::findReferences($value, [...$path, $propertyName], $processed));
				}
			}
		} elseif (is_array($object)) {
			// Check each array element
			foreach ($object as $key => $value) {
				$propertyPath = implode('->', [...$path, $key]);

				// Check if it's a PDO instance
				if ($value instanceof PDO) {
					$result[] = $propertyPath . " (PDO)";
				}

				// Recursively check nested objects or arrays
				if (is_object($value) || is_array($value)) {
					$result = array_merge($result, \Ext_Gui2_Session::findReferences($value, [...$path, $key], $processed));
				}
			}
		}

		return $result;
	}


}