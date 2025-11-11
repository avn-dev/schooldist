<?php

use Symfony\Component\HttpFoundation\File\UploadedFile;

class MVC_Request extends \Illuminate\Http\Request implements Serializable {

	/**
	 * Ergänzt Request Parameter in das interne Array
	 * 
	 * @param array $aParameters 
	 */
	public function add($aParameters) {

		if(
			empty($aParameters) ||
			!is_array($aParameters)
		) {
			return $this;
		}

		// Je nach Request-Methode (GET, POST) setzt Laravel die Werte entweder in ->query oder in ->request
		// @see Illuminate\Http\Request::getInputSource()
		$this->getInputSource()
				->add($aParameters);

		return $this;
	}

	/**
	 * Gibt einen Wert zurück oder alternativ einen angegebenen Default-Wert
	 * 
	 * @param string $sKey
	 * @param mixed $mDefault
	 * @return mixed 
	 */
	public function get(string $sKey, mixed $mDefault = null): mixed {
		
		$aProperties = [
			'request',
			'query',
			'cookies'
		];
		
		foreach($aProperties as $sProperty) {
			
			if($this->$sProperty->has($sKey) === true) {
				return $this->$sProperty->get($sKey);
			}
			
		}
		
		return $mDefault;
	}

	/**
	 * Gibt die Post-Daten zurück
	 * @return string
	 */
	public function getPostData(){
		return $this->getContent();
	}

	/**
	 * Gibt die Daten in aFilesData ($_FILES) zurück
	 * @return array
	 */
	public function getFilesData() {
		
		$aFilesData = [];
		
		$aFiles = $this->files;
		
		if(!empty($aFiles)) {
			foreach($aFiles as $sKey=>$mFile) {

				if($mFile instanceof UploadedFile) {
					$aFilesData[$sKey] = [
						'name' => $mFile->getClientOriginalName(),
						'type' => $mFile->getClientMimeType(),
						'tmp_name' => $mFile->getRealPath(),
						'error' => $mFile->getError(),
						'size' => $mFile->getSize()
					];
				} else {
					$aFilesData[$sKey] = $this->getRecursiveFileData($mFile);
				}

			}
		}

		return $aFilesData;
	}

	private function getRecursiveFileData(array $aFileData, array $aReturn=null, array $aKeys=[]) {
		
		if($aReturn === null) {
			$aReturn = [
				'name' => [],
				'type' => [],
				'tmp_name' => [],
				'error' => [],
				'size' => []
			];
		}
		
		foreach($aFileData as $sKey=>$mFile) {
			
			$aNextKeys = $aKeys;
			$aNextKeys[] = $sKey;
			
			if($mFile instanceof UploadedFile) {
				
				Util::setRecursiveArrayValue($aReturn, array_merge(['name'], $aNextKeys), $mFile->getClientOriginalName());
				Util::setRecursiveArrayValue($aReturn, array_merge(['type'], $aNextKeys), $mFile->getClientMimeType());
				Util::setRecursiveArrayValue($aReturn, array_merge(['tmp_name'], $aNextKeys), $mFile->getRealPath());
				Util::setRecursiveArrayValue($aReturn, array_merge(['error'], $aNextKeys), $mFile->getError());
				Util::setRecursiveArrayValue($aReturn, array_merge(['size'], $aNextKeys), $mFile->getSize());

			} elseif($mFile !== null) {

				$aReturn = $this->getRecursiveFileData($mFile, $aReturn, $aNextKeys);

			}
			
		}
		
		return $aReturn;
	}
		
	/**
	 * Gibt die Post-Daten als JSON Decodiertes Array zurück.
	 * 
	 * @return array
	 */
	public function getJSONDecodedPostData() {
		// Alle Post-Daten
		$sPostData = $this->getPostData();
		// Dekodiere JSON zu nem Array
		$aPostData = json_decode($sPostData, true);
		return $aPostData;
	}

	/**
	 * Gibt alle Request-Parameter zurück
	 * @return array 
	 */
	public function getAll() {
		
		return $this->all();
		
	}
	
	/**
	 *
	 * @param string $sKey
	 * @return boolean 
	 */
	public function exists($sKey) {

		if (array_key_exists($sKey, $this->all())) {
			return true;
		}
		
		return false;
		
	}

    /**
     * Prüft ob der Eintrag existiert und auch einen Wert beinhaltet.
	 *
	 * @deprecated
     * @param string $sKey
     * @return bool
     */
	public function hasValue($sKey) {
		return $this->filled($sKey);
    }

	public function serialize():? string {
		$this->file = [];
		return null;
	}

	/**
	 * Dürfte nie aufgerufen werden, da Objekt nicht serialisiert werden darf.
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized): void {
	}

	public function __serialize(): array
	{
		return [];
	}

	public function __unserialize(array $data): void
	{

	}
}
