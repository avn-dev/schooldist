<?php

class WDBasic_Persister extends SplObjectStorage {
	
	protected static $_oInstance;
	
	/**
	 * Konstruktor nicht von außen aufrufen
	 */
	private function __construct() {
		
	}

	/**
	 * Singleton Implementierung
	 * @return WDBasic_Persister
	 */
	public static function getInstance() {
		
		if(self::$_oInstance === null) {
			self::$_oInstance = new self;
		}
		
		return self::$_oInstance;
		
	}

	/**
	 * Alle Entitäten speichern
	 */
	public function save() {

		 while (count($this) > 0) {
			$this->rewind();
			$oEntity = $this->current();

			$oEntity->save();
			$this->detach($oEntity);

		}
 		
	}
	
}