<?php

/**
 * @property string $sEmail
 * @property string $sObject
 * @property integer $iObjectId
 * @property string $sLanguage
 * @property string $sName
 * @property array $aAdditional
 * @property array $aSelectedIds
 * @property array $aDecodedData
 * @property integer $iSelectedId
 */
class Ext_Thebing_Communication_RecipientDTO {

	private $sEmail;
	private $sObject;
	private $iObjectId;
	private $sLanguage;
	private $sName;
	private $aAdditional;
	private $aSelectedIds;
	private $iSelectedId;
	private $aDecodedData;
	
	public function __set($sName, $mValue) {
		
		if(property_exists($this, $sName)) {
			$this->$sName = $mValue;
			
			if($sName === 'aSelectedIds') {
				$this->iSelectedId = reset($mValue);
			}

			return;
		}
		
		throw new InvalidArgumentException('Invalid name "'.$sName.'"');

	}
	
	public function __get($sName) {
		
		if(property_exists($this, $sName)) {
			return $this->$sName;
		}
		
		throw new InvalidArgumentException('Invalid name "'.$sName.'"');

	}
	
	public function __toString() {
		return $this->sEmail;
	}
	
	public function hasValue($sName) {
		
		if(property_exists($this, $sName)) {
			return !empty($this->$sName);
		}

		throw new InvalidArgumentException('Invalid name "'.$sName.'"');

	}

	/**
	 * Der Name enthält manchmal zusätzliche Angaben, die entfernt werden müssen
	 * z.B. Lektionskurs, Test 1 (lektionskurs1@p32.de)
	 * @return string
	 */
	public function getCleanName() {

		$sName = preg_replace('/\s*\(.*?\)\s*/', '', $this->sName);

		return $sName;
	}
	
}
